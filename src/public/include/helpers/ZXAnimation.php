<?php
// Generate animation source from frames data

class ZXAnimation extends base_module {
	const METHOD_FAST = 1;
	const METHOD_MEMSAVE = 2;
	
	var $totalFramesLen = 0;
	var $totalBytesAff = 0;
	
	// Settings
	private $isDebug = false;
	private $skipAttr = 0x80;
	private $procPrefix = 'x';
	
	function __construct($config = array()) {
        parent::__construct();

		$this->isDebug = isset($config['isDebug']) ? $config['isDebug'] : $this->isDebug;
		$this->skipAttr = isset($config['skipAttr']) ? $config['skipAttr'] : $this->skipAttr;
		$this->procPrefix = isset($config['procPrefix']) ? $config['procPrefix'] : $this->procPrefix;
	}
	
	private function log($message) {
		if (!$this->isDebug || !class_exists('ChromePhp')) {
		    return;
        }

        ChromePhp::log($message);
	}
	
	// Генерация быстрого кода (1-я версия запилятора)
	private function generateCodeFast($input_data) {
		$generated = array();

		foreach ($input_data as $key=>$frame) {
			$generated[$key] = array(
				'frame_len' => 0,
				'bytes_aff' => 0,
				'duration' => $frame['duration'],
				'source' => '',
				'diff' => array()
			);
						
			//$output = $this->procPrefix.$key;
			$output = '';
				
			$curA = -1;
			foreach($frame['data'] as $byte=>$addresses) {
				// add diff 
				foreach ($addresses as $address) {
					$generated[$key]['diff'][$address] = $byte;
				}
				
				if ($byte == 0) {
					$output .= "\t\txor a\n";
					$generated[$key]['frame_len']++;
				}
				elseif ($byte - $curA == 1) {
					$output .= "\t\tinc a\n";
					$generated[$key]['frame_len']++;
				}
				else {
					$output .= "\t\tld a, ".$byte."\n";
					$generated[$key]['frame_len'] += 2;
				}
				$curA = $byte;
		
				$addresses = array_reverse($addresses, true);
				
				// !!!
				$output .= $this->generateSeriaV($addresses, $generated[$key]['frame_len']);
				$output .= $this->generateSeriaH($addresses, $generated[$key]['frame_len']);
		
				// Формируем оставшиеся фрагменты:
				// ld (addr1), a
				foreach ($addresses as $address) {
					$output .= "\t\tld (#".dechex(0x4000 + $address)."), a\n";
					$generated[$key]['frame_len'] += 3;
				}
			}
				
			$output .= "\t\tret\n";
			$generated[$key]['frame_len']++;
				
			// Finalize with current frame
			$generated[$key]['source'] = $output;
			ksort($generated[$key]['diff']);
			$generated[$key]['bytes_aff'] = count($generated[$key]['diff']);
			
			$this->totalFramesLen += $generated[$key]['frame_len'];
			$this->totalBytesAff += $generated[$key]['bytes_aff'];
		}
		
		return $generated;		
	}
	
	
	/* Формирует из массива $source фрагменты типа:
	 * ld hl, addr : ld (hl), a : inc h : ld (hl), a
	*/
	private function generateSeriaV(&$source, &$counter) {
		sort($source);
	
		$output = '';
	
		while(true) {
			$is_seria_found = false;
				
			foreach ($source as $address) {
				$seria = array();
	
				while(true) {
					if (!in_array($address, $source)) break;
						
					$seria[] = $address;
					$address += 0x100;
				}
	
				// Seria end
				if (count($seria) > 2) {
					// Remove seria addresses from source
					foreach ($source as $k=>$a) {
						if (in_array($a, $seria)) unset($source[$k]);
					}
	
					$output .= "\t\tld hl, #".dechex(0x4000 + array_shift($seria))."\n";
					$output .= "\t\tld (hl), a\n";
					$counter += 4;
	
					foreach($seria as $foo) {
						$output .= "\t\tinc h\n";
						$output .= "\t\tld (hl), a\n";
						$counter += 2;
					}
						
					$is_seria_found = true;
				}
			}
				
			if (!$is_seria_found) break;
		}
	
		return $output;
	}
	
	/* Формирует из массива $source фрагменты типа:
	 * ld hl, addr : ld (hl), a : inc hl : ld (hl), a
	*/
	private function generateSeriaH(&$source, &$counter) {
		sort($source);
	
		$output = '';
		$processedDE = false;
	
		while(true) {
			$is_seria_found = false;
				
			foreach ($source as $address) {
				$seria = array();
					
				while(true) {
					if (!in_array($address, $source)) break;
	
					$seria[] = $address;
					$address++;
				}
	
				// Seria end
				if (count($seria) >= 2) {
					// Remove seria addresses from source
					foreach ($source as $k=>$a) {
						if (in_array($a, $seria)) unset($source[$k]);
					}
	
					if (count($seria) == 2) {
						if (!$processedDE) {
							$output .= "\t\tld d,a\n";
							$output .= "\t\tld e,a\n";
							$counter += 2;
							$processedDE = true;
						}
							
						$output .= "\t\tld (#".dechex(0x4000 + $seria[0])."), de\n";
						$counter += 4;
					}
					else {
						$output .= "\t\tld hl, #".dechex(0x4000 + array_shift($seria))."\n";
						$output .= "\t\tld (hl), a\n";
						$counter += 4;
							
							
						foreach($seria as $foo) {
							$output .= "\t\tinc hl\n";
							$output .= "\t\tld (hl), a\n";
							$counter += 2;
						}
					}
						
					$is_seria_found = true;
				}
			}
				
			if (!$is_seria_found) break;
		}
	
		return $output;
	}
	
	
	
	/*
	 * Генерация исходника анимации.
		поток комманд:
		%00xxxxxх - вывести следующие xxxxx + 1 байт (1-64) из потока данных на экран со сдвигом адреса +1
		%01xxxxxx - сдвинуть указатель адреса на xxxxxx + 1 (1-64)
		%101yxxxx - сдвинуть указатель адреса #100 байт xxxxx раз (0-15). Если установлен бит Y, то сдвигаемся еще на 128 байт
		%11111111 - конец фрейма
		далее - поток данных, где первые два байта - стартовый адрес в экране
	 */
	private function generateCodeMemsave($input_data) {
		$generated = array();
		
		foreach ($input_data as $key=>$frame) {
			$generated[$key] = array(
				'frame_len' => 0,
				'bytes_aff' => 0,
				'duration' => $frame['duration'],
				'source' => '',
				'diff' => array()
			);
				
			if (empty($frame['data'])) {
				$generated[$key]['source'] = "\t".'db %11100000'."\n";
				$generated[$key]['frame_len'] = 1;
				$this->totalFramesLen ++;
				continue;
			}
			
			// Repack data
			$addresses = array();
			foreach($frame['data'] as $byte=>$addr_array) foreach ($addr_array as $address) {
				$addresses[$address] = $byte;
				$generated[$key]['diff'][$address] = $byte;
			}
			ksort($addresses);
			ksort($generated[$key]['diff']);
			$generated[$key]['bytes_aff'] = count($addresses);
			$this->totalBytesAff += $generated[$key]['bytes_aff'];
			
			$data_flow = array();		// Поток данных сначала сохраняем в массив, потом разворачиваем в строку 
			$commands_flow = array();	// Поток комманд сначала сохраняем в массив, потом разворачиваем в строку
			$data_buf = array();		// Накопительный буфер для выводимых байтов
			$cur_address = 0;			// Последний обработанный экранный адрес
			foreach ($addresses as $address=>$byte) {
				$this->log('Processing address: '.$address);
				
				// Initial address
				if ($cur_address == 0) {
					$data_flow[] = "\t".'dw #'.sprintf("%04x", 0x4000 + $address);
					$data_buf[] = $byte;
					$cur_address = $address + 1;
					
					$this->log('Initialized. Next address: '.$cur_address);
					continue;
				}
				
				// Simple add $data_buf value
				if ($address == $cur_address && count($data_buf) < 32) {
					$data_buf[] = $byte;
					$cur_address = $address + 1;
					
					$this->log('Bufferr added. Next address: '.$cur_address);
					continue;		
				}
				
				if (($address != $cur_address && !empty($data_buf)) || count($data_buf) == 64) {
					$commands_flow[] = "\t".'db %00'.sprintf("%06s", decbin(count($data_buf) - 1));
						
					foreach($data_buf as $b) {
						$data_flow[] = "\t".'db #'.sprintf("%02x", $b);
					}
					$data_buf = array();
					
					$this->log('Buffer cleaned.');
				} 
				
				while ($address > $cur_address + 128) {
					$delta = floor(($address - $cur_address) / 256);
					if ($delta > 15) $delta = 15;
					$cur_address += $delta * 256;
					$delta2 = $address >= $cur_address + 128 ? 0x10 : 0;
					$cur_address += $delta2 ? 128 : 0;

					$commands_flow[] = "\t".'db %101'.sprintf("%05s", decbin($delta + $delta2));
					
					$this->log('Long jump '.($delta * 256).' '.($delta2 ? ' +128: ' : ': ').$cur_address);
				}

				while ($cur_address != $address) {
					$delta = $address - $cur_address > 64 ? 64 : $address - $cur_address;
					$commands_flow[] = "\t".'db %01'.sprintf("%06s", decbin($delta - 1));
					$cur_address += $delta;
					
					$this->log('Jump +'.$delta.': '.$cur_address);
				}
				
				$data_buf[] = $byte;
				$cur_address++;
				$this->log('Address increased: '.$cur_address);
			}
			
			// Extract last buffer
			$commands_flow[] = "\t".'db %00'.sprintf("%06s", decbin(count($data_buf) - 1));
			foreach($data_buf as $b) {
				$data_flow[] = "\t".'db #'.sprintf("%02x", $b);
			}

			// End of frame
			$commands_flow[] = "\t".'db %11111111';

			$generated[$key]['frame_len'] = count($commands_flow) + count($data_flow) + 1;
			$this->totalFramesLen += $generated[$key]['frame_len'];
			$generated[$key]['source'] = implode("\n", $commands_flow)."\n".implode("\n", $data_flow);
		}
		
		$this->log($generated);
		return $generated; 
	}
	
	function generateCode($input_data, $method = self::METHOD_FAST) {
		// Remove skipping attributes from changes
		foreach ($input_data as $frame_key=>$frame) {
			if (!isset($frame['data'][$this->skipAttr])) continue;
					
			foreach($frame['data'][$this->skipAttr] as $key=>$addresses) {
				if ($addresses > 0x17ff) {
					unset($input_data[$frame_key]['data'][$this->skipAttr][$key]);
				}
			}
		}
		
		switch ($method) {
			case self::METHOD_FAST:
				return $this->generateCodeFast($input_data);
			case self::METHOD_MEMSAVE:
				return $this->generateCodeMemsave($input_data);
			default:
				$this->error('Unknown source type.', __FILE__, __LINE__);
				return false;
		}
	}
}
