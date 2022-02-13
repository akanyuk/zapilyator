<?php
if (isset($_GET['get_file'])) {
	$filename = str_replace(array('\\', '/'), array('',''), $_GET['get_file']);
	if (!file_exists(PROJECT_ROOT.'var/cache/'.$filename) || is_dir(PROJECT_ROOT.'var/cache/'.$filename)) {
		NFW::i()->stop('File not found');
		return false;
	}

	header('Content-type: application/force-download');
	header('Content-Disposition: attachment; filename="test-demo.zip"');
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize(PROJECT_ROOT.'var/cache/'.$filename));
	readfile(PROJECT_ROOT.'var/cache/'.$filename);
	exit;
}
elseif (empty($_POST)) {
	// Main page
	NFW::i()->assign('page', array(
		'path' => 'zapilaytor',
		'title' => 'Zapilaytor OLD',
		'content' => NFW::i()->fetch(PROJECT_ROOT.'include/templates/zapilyator/zapilyator_old.tpl')
	));
	NFW::i()->display('main.tpl');
}

// MAKE DEMO!

$DemoMaker = new ZXDemoMaker();

$parser_log = '';

$main_color = intval($_POST['gif_ink'] + $_POST['gif_paper'] * 8 + $_POST['gif_bright'] * 64);

// Load / validate / parse GIF
$errors = array();
$uploaded_gif = $DemoMaker->upload('gif_file1');
if (!$DemoMaker->error) {
	$parser = new parseGif256x192(array('procPrefix' => 'anma1', 'initialColor' => $main_color));
	$gif1 = $parser->parseGIF($uploaded_gif);
	
	// Custom animation speed
	if ($_POST['speed1']) {
		$speed = intval($_POST['speed1']) < 1 || intval($_POST['speed1']) > 8 ? 1 : intval($_POST['speed1']);
		foreach ($gif1['frames'] as $key=>$foo) {
			$gif1['frames'][$key]['duration'] = $speed; 
		}
	}
	
	$parser_log .=  '<div class="'.($parser->totalstat > 40000 ? 'error' : '').'">Animation 1 size: <strong>'.$parser->totalstat.'</strong> bytes</div>';
}
else 
	$gif1 = false;

// Load / validate / parse GIF2
$errors = array();
$uploaded_gif = $DemoMaker->upload('gif_file2');
if (!$DemoMaker->error) {
	$parser = new parseGif256x192(array('procPrefix' => 'anma2', 'initialColor' => $main_color));
	$gif2 = $parser->parseGIF($uploaded_gif);

	// Custom animation speed
	if ($_POST['speed2']) {
		$speed = intval($_POST['speed2']) < 1 || intval($_POST['speed2']) > 8 ? 1 : intval($_POST['speed2']);
		foreach ($gif2['frames'] as $key=>$foo) {
			$gif2['frames'][$key]['duration'] = $speed;
		}
	}
	
	$parser_log .=  '<div class="'.($parser->totalstat > 16000 ? 'error' : '').'">Animation 2 size: <strong>'.$parser->totalstat.'</strong> bytes</div>';
}
else 
	$gif2 = false;

// Load / validate / parse GIF3
$errors = array();
$uploaded_gif = $DemoMaker->upload('gif_file3');
if (!$DemoMaker->error) {
	$parser = new parseGif256x192(array('procPrefix' => 'anma3', 'initialColor' => $main_color));
	$gif3 = $parser->parseGIF($uploaded_gif);

	// Custom animation speed
	if ($_POST['speed3']) {
		$speed = intval($_POST['speed3']) < 1 || intval($_POST['speed3']) > 8 ? 1 : intval($_POST['speed3']);
		foreach ($gif3['frames'] as $key=>$foo) {
			$gif3['frames'][$key]['duration'] = $speed;
		}
	}
	
	$parser_log .=  '<div class="'.($parser->totalstat > 16000 ? 'error' : '').'">Animation 3 size: <strong>'.$parser->totalstat.'</strong> bytes</div>';	
}
else
	$gif3 = false;

// Make sources params
$params = array(
	'border' => intval($_POST['border']) < 0 || intval($_POST['border']) > 7 ? 0 : intval($_POST['border']),
	'animation_border' => intval($_POST['animation_border']) < 0 || intval($_POST['animation_border']) > 7 ? 0 : intval($_POST['animation_border']),
	'splash_delay' => intval($_POST['splash_delay']) < 0 || intval($_POST['splash_delay']) > 5 ? 0 : intval($_POST['splash_delay']),
	'main_color' => $main_color < 0 || $main_color > 255 ? 0x47 : $main_color,
		
	'gif1' => $gif1,
	'gif2' => $gif2,
	'gif3' => $gif3,
);

// PT3 / PT2 music
$music_file = $DemoMaker->upload('music_file');
if (!$DemoMaker->error) {
	$params['music_file'] = $music_file; 
}


// Splash screen
$splash_file = $DemoMaker->upload('splash_file');
if (!$DemoMaker->error) {
	$params['splash_file'] = $splash_file; 
	$params['splash_delay'] = intval($_POST['splash_delay']) < 1 || intval($_POST['splash_delay']) > 5 ? 1 : intval($_POST['splash_delay']);
}


// Main background
$gif_background = $DemoMaker->upload('gif_background');
if (!$DemoMaker->error) {
	$params['gif_background'] = $gif_background;
}


// Scroll
if ($_POST['scroll_text']) {
	$params['scroll_text'] = $_POST['scroll_text'];
	$params['scroll_font'] = intval($_POST['scroll_font']) < 1 || intval($_POST['scroll_font']) > 3 ? '16x16font1' : '16x16font'.intval($_POST['scroll_font']);
	list($params['scroll_address'], $params['scroll_attr']) = explode('|', $_POST['scroll_position']);
	
	$scroll_color = intval($_POST['scroll_ink'] + $_POST['scroll_paper'] * 8 + $_POST['scroll_bright'] * 64);
	$params['scroll_color'] = $scroll_color < 0 || $scroll_color > 255 ? 0x47 : $scroll_color;
}

// Analyzator
$ach = isset($_POST['analyzator_chanel']) ? intval($_POST['analyzator_chanel']) : 0; 
if ($ach >= 8 && $ach <= 11) {
	$params['analyzator_chanel'] = $ach;
	$params['analyzator_sens'] = intval($_POST['analyzator_sens']) < 8 || intval($_POST['analyzator_sens']) > 15 ? 15 : intval($_POST['analyzator_sens']);
}

// Analyzator in splash
$ach = isset($_POST['analyzator_splash_chanel']) ? intval($_POST['analyzator_splash_chanel']) : 0;
if ($ach >= 8 && $ach <= 11) {
	$params['analyzator_splash_chanel'] = $ach;
	$params['analyzator_splash_sens'] = intval($_POST['analyzator_splash_sens']) < 8 || intval($_POST['analyzator_splash_sens']) > 15 ? 15 : intval($_POST['analyzator_splash_sens']);
}

$result_zip = $DemoMaker->generateDemo($params);

// Response
NFW::i()->renderJSON(array('result' => 'success', 'log' => 'Done!<br /><br />'.$parser_log, 'download' => '?get_file='.$result_zip));

// -------

class ZXDemoMaker extends base_module {
	private $uploads_timelive = 864000;
	private $cache_dir = 'var/cache/';

	function __construct() {
		$this->cache_dir = PROJECT_ROOT.$this->cache_dir;
	}

	private function emptyFolder($dir, $timelive = 0) {
		if (substr($dir, -1, 1) != '/') $dir = $dir.'/';
		// Remove old files
		$files  = scandir($dir);
		foreach ($files as $f) {
			if (is_dir($dir.$f)) continue;

			//(fileperms($dir.$f) & 0x0080)
			if ((time() - filemtime($dir.$f) > $timelive) && is_writable($dir.$f)) {
				@unlink($dir.$f);
			}
		}
	}

	function upload($field_name) {
		$this->error_report_type = 'active_form';
		$this->error = false;
		//$this->emptyFolder($this->cache_dir, 86400);
		$this->emptyFolder($this->cache_dir, 3600);
		
		if (!isset($_FILES[$field_name])) {
			$this->error('No file selected: '.$field_name, __FILE__, __LINE__);
			return false;
		}

		$file = $_FILES[$field_name];

		if (!empty($file['error'])) {
			switch($file['error']) {
				case '1':
					$this->error('The uploaded file exceeds the upload_max_filesize directive in php.ini');
					return false;
				case '2':
					$this->error('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
					return false;
				case '3':
					$this->error('The uploaded file was only partially uploaded');
					return false;
				case '4':
					$this->error('No file was uploaded.');
					return false;
				case '6':
					$this->error('Missing a temporary folder');
					return false;
				case '7':
					$this->error('Failed to write file to disk');
					return false;
				case '8':
					$this->error('File upload stopped by extension');
					return false;
				case '999':
				default:
					$this->error('No error code avaiable');
					return false;
			}
		}


		$targetFile =  $this->cache_dir.md5(time().$file['name']);
			
		if (file_exists($targetFile) && !is_writable($targetFile)) {
			$this->error('File exists and can not be overwritten');
			return false;
		}
			
		move_uploaded_file(urldecode($file['tmp_name']), $targetFile);
		chmod($targetFile, 0777);

		return $targetFile;
	}
	
	function generateDemo($params) {
		// Generate ZIP
		$dest_filename = md5(serialize($params));
		$zip = new ZipArchive();
		$result = $zip->open($this->cache_dir.$dest_filename, ZIPARCHIVE::OVERWRITE | ZIPARCHIVE::CREATE);
		
		$zip->addFile(PROJECT_ROOT.'resources/zapilyator/bin/sjasmplus.exe', 'bin/sjasmplus.exe');
		$zip->addFile(PROJECT_ROOT.'resources/zapilyator/bin/unreal/unreal.exe', 'bin/unreal/unreal.exe');
		$zip->addFile(PROJECT_ROOT.'resources/zapilyator/bin/unreal/slipka.rom', 'bin/unreal/slipka.rom');
		$zip->addFile(PROJECT_ROOT.'resources/zapilyator/bin/unreal/unreal.ini', 'bin/unreal/unreal.ini');
		$zip->addFile(PROJECT_ROOT.'resources/zapilyator/make.cmd', 'make.cmd');
		$zip->addFile(PROJECT_ROOT.'resources/zapilyator/sources/builder.asm', 'sources/builder.asm');
		
		
		// Generate source
		$fp = fopen(PROJECT_ROOT.'resources/zapilyator/sources/zapilyator_old.asm.tpl', 'r');
		$source_tpl = fread($fp, filesize(PROJECT_ROOT.'resources/zapilyator/sources/zapilyator_old.asm.tpl'));
		fclose($fp);
		
		$source_tpl = str_replace('%border_color%', $params['border'], $source_tpl);
		$source_tpl = str_replace('%animation_border%', $params['animation_border'], $source_tpl);
		$source_tpl = str_replace('%main_color%', $params['main_color'], $source_tpl);
		
		//-- frames 1
		if ($params['gif1']) {
			$source_tpl = str_replace('%if_anima%', '', $source_tpl);
			
			$main_frames = '';
			foreach ($params['gif1']['frames'] as $f) {
				$main_frames .= "\t\tdb ".$f['duration']." : dw ".$f['function_name']."\n";
			}
			$source_tpl = str_replace('%main_frames%', $main_frames, $source_tpl);

			//-- include parsed GIF
			$includes = '';
			if ($params['gif1']['functions']['content']) {
				$includes = "\t\tinclude \"res/".$params['gif1']['functions']['filename']."\"\n";
				$zip->addFromString('res\\'.$params['gif1']['functions']['filename'], $params['gif1']['functions']['content']);
			}
			foreach ($params['gif1']['frames'] as $f) {
				$includes .= "\t\tinclude \"res/".$f['filename']."\"\n";
				$zip->addFromString('res\\'.$f['filename'], $f['content']);
			}
			$source_tpl = str_replace('%includes%', $includes, $source_tpl);
		}
		else {
			$source_tpl = str_replace('%if_anima%', ';', $source_tpl);
		}
		
		//-- frames 2
		if ($params['gif2']) {
			$source_tpl = str_replace('%if_anima2%', '', $source_tpl);
				
			$main_frames = '';
			foreach ($params['gif2']['frames'] as $f) {
				$main_frames .= "\t\tdb ".$f['duration']." : dw ".$f['function_name']."\n";
			}
			$source_tpl = str_replace('%main_frames2%', $main_frames, $source_tpl);
		
			//-- include parsed GIF
				
			$includes = '';
			if ($params['gif2']['functions']['content']) {
				$includes = "\t\tinclude \"res/".$params['gif2']['functions']['filename']."\"\n";
				$zip->addFromString('res\\'.$params['gif2']['functions']['filename'], $params['gif1']['functions']['content']);
			}
			foreach ($params['gif2']['frames'] as $f) {
				$includes .= "\t\tinclude \"res/".$f['filename']."\"\n";
				$zip->addFromString('res\\'.$f['filename'], $f['content']);
			}
			$source_tpl = str_replace('%includes2%', $includes, $source_tpl);
		}
		else {
			$source_tpl = str_replace('%if_anima2%', ';', $source_tpl);
		}		
		
		//-- frames 3
		if ($params['gif3']) {
			$source_tpl = str_replace('%if_anima3%', '', $source_tpl);
				
			$main_frames = '';
			foreach ($params['gif3']['frames'] as $f) {
				$main_frames .= "\t\tdb ".$f['duration']." : dw ".$f['function_name']."\n";
			}
			$source_tpl = str_replace('%main_frames3%', $main_frames, $source_tpl);
		
			//-- include parsed GIF
				
			$includes = '';
			if ($params['gif3']['functions']['content']) {
				$includes = "\t\tinclude \"res/".$params['gif3']['functions']['filename']."\"\n";
				$zip->addFromString('res\\'.$params['gif3']['functions']['filename'], $params['gif1']['functions']['content']);
			}
			foreach ($params['gif3']['frames'] as $f) {
				$includes .= "\t\tinclude \"res/".$f['filename']."\"\n";
				$zip->addFromString('res\\'.$f['filename'], $f['content']);
			}
			$source_tpl = str_replace('%includes3%', $includes, $source_tpl);
		}
		else {
			$source_tpl = str_replace('%if_anima3%', ';', $source_tpl);
		}
				
		// -- music
		$source_tpl = str_replace('%if_music%', isset($params['music_file']) ? '' : ';', $source_tpl);
		
		
		// -- splash screen
		$source_tpl = str_replace('%if_splash%', isset($params['splash_file']) ? '' : ';', $source_tpl);
		$source_tpl = str_replace('%splash_delay%', isset($params['splash_delay']) ? $params['splash_delay'] : '0', $source_tpl);
		
		// Main bg
		$source_tpl = str_replace('%if_anima_bg%', isset($params['gif_background']) ? '' : ';', $source_tpl);
		
		// -- scroll
		if (isset($params['scroll_text'])) {
			$source_tpl = str_replace('%if_scroll%', '', $source_tpl);
			$source_tpl = str_replace('%scroll_address%', $params['scroll_address'], $source_tpl);
			$source_tpl = str_replace('%scroll_attr%', $params['scroll_attr'], $source_tpl);
			$source_tpl = str_replace('%scroll_color%', $params['scroll_color'], $source_tpl);
			
			$zip->addFile(PROJECT_ROOT.'resources/zapilyator/sources/scroll.asm', 'sources/scroll.asm');
			$zip->addFile(PROJECT_ROOT.'resources/zapilyator/res/'.$params['scroll_font'], 'res/16x16font');
			$zip->addFromString('res/scroll', iconv("UTF-8", 'cp1251', mb_strtoupper($params['scroll_text'], "UTF-8")));
		}
		else {
			$source_tpl = str_replace('%if_scroll%', ';', $source_tpl);
			$source_tpl = str_replace('%if_scroll_up%', ';', $source_tpl);
			$source_tpl = str_replace('%scroll_color%', '#47', $source_tpl);
		}
		
		// Analyzator main 
		if (isset($params['analyzator_chanel'])) {
			$source_tpl = str_replace('%if_anal%', '', $source_tpl);
			$source_tpl = str_replace('%analyzator_chanel%', $params['analyzator_chanel'], $source_tpl);
			$source_tpl = str_replace('%analyzator_sens%', $params['analyzator_sens'], $source_tpl);
			
			$zip->addFile(PROJECT_ROOT.'resources/zapilyator/sources/analyzator.asm', 'sources/analyzator.asm');
		}
		else {
			$source_tpl = str_replace('%if_anal%', ';', $source_tpl);
		}
		
		// Analyzator splash
		if (isset($params['analyzator_splash_chanel'])) {
			$source_tpl = str_replace('%if_anal_splash%', '', $source_tpl);
			$source_tpl = str_replace('%analyzator_splash_chanel%', $params['analyzator_splash_chanel'], $source_tpl);
			$source_tpl = str_replace('%analyzator_splash_sens%', $params['analyzator_splash_sens'], $source_tpl);
			
			$zip->addFile(PROJECT_ROOT.'resources/zapilyator/sources/analyzator_splash.asm', 'sources/analyzator_splash.asm');
		}
		else {
			$source_tpl = str_replace('%if_anal_splash%', ';', $source_tpl);
		}
		
		if (isset($params['music_file'])) {
			$zip->addFile(PROJECT_ROOT.'resources/zapilyator/sources/PTxPlay.asm', 'sources/PTxPlay.asm');
			$zip->addFile($params['music_file'], 'res/music');
		}
		
		if (isset($params['splash_file'])) {
			$zip->addFile($params['splash_file'], 'res/splash');
		}

		if (isset($params['gif_background'])) {
			$zip->addFile($params['gif_background'], 'res/bg');
		}

		
		$zip->addFromString('sources/test.asm', $source_tpl);
		$zip->close();
		
		return $dest_filename;
	}
}




// ----------------
// parse256x192.php
// ----------------



class parseGif256x192 {
	private $curScreen = array();	// Массив с данными текущего состояния экрана
	private $filler = array();		// Массив данных для "точечной" отправки на экран
	private $sequences = array();	// Последовательности для объединения в процедуры

	// Настройки
	var $procPrefix = 'x';
	var $outputDir = false;
	var $cycleAnimation = true;
	var $separateFrames = false;		// Каждый фрейм - заново (без учета предыдущего)
	var $processSequences = array();	// Вычислять следующие последовательности
	var $initialColor = 0;				// Начальный цвет для цветной анимации (256x212 по методу Перцовский-Какос)

	// Результат
	var $totalstat = 0;
	var $framestats = array();		// Пофреймовая статистика обработки
	var $funcstats = 0;				// Статистика функций

	function __construct($config) {
		$this->procPrefix = isset($config['procPrefix']) ? $config['procPrefix'] : $this->procPrefix;
		$this->cycleAnimation = isset($config['cycleAnimation']) ? $config['cycleAnimation'] : $this->cycleAnimation;
		$this->outputDir = isset($config['outputDir']) ? $config['outputDir'] : $this->outputDir;
		$this->processSequences = isset($config['processSequences']) ? $config['processSequences'] : $this->processSequences;
		$this->separateFrames = isset($config['separateFrames']) ? $config['separateFrames'] : $this->separateFrames;
		$this->initialColor = isset($config['initialColor']) ? $config['initialColor'] : $this->initialColor;

		// initial curScreen array
		for ($i=0; $i < 6144; $i++) $this->curScreen[$i] = 0;
		for ($i=6144; $i < 6912; $i++) $this->curScreen[$i] = $this->initialColor;
	}

	private function proceedFrame($frame) {
		$frameWidth = imagesx($frame);
		$frameHeight = imagesy($frame);

		$maxX = intval($frameWidth / 8) > 32 ? 32 : intval($frameWidth / 8);
		$maxY = $frameHeight > 216 ? 216 : $frameHeight;

		$result = array();

		for ($y = 0; $y < $maxY; $y++) {
			for ($x = 0; $x < $maxX; $x++) {
				list($byte, $address) = $this->proceedByte($frame, $x, $y);
				if ($byte === false) continue;

				if (!isset($result[$byte])) $result[$byte] = array();
				$result[$byte][] = $address;
			}
		}

		ksort($result);

		return $result;
	}

	private function proceedByte($frame, $x, $y) {
		$byte = 0;
		for ($i = 0; $i < 8; $i ++) {
			$c = imagecolorsforindex($frame, imagecolorat($frame, $x*8 + $i, $y));
			if ($c['red'] + $c['green'] + $c['blue'] > 391 || $c['alpha'] != 0) continue;

			$byte += pow(2, 7-$i);
		}

		if ($y < 192) {
			// Адрес в экране
			$d = ($y & 0xc0)*0x20 + ($y%8)*256 + ($y & 0x38) * 4 + $x;
		}
		else {
			// Адрес в атрибутах
			$d = 0x1800 + ($y - 192) * 32 + $x;
		}

		if ($this->curScreen[$d] == $byte) return array(false, false);

		$this->curScreen[$d] = $byte;

		return array($byte, $d);
	}

	private function searchSequences($len = 8) {
		foreach ($this->filler as $key=>$data) {
			foreach($data as $byte=>$addresses) {
				$offset = 0;
				while ($offset++ < count($addresses) - $len) {
					$curSequence = array_slice($addresses, $offset, $len);
					if (in_array(-1, $curSequence)) continue; // Already has sequence
						
					if (!$result = $this->testSequence($curSequence)) continue;

					// Make sequence
					$sequenceName = $this->procPrefix.'s_'.substr(md5(serialize($curSequence)), 16);
					if (!isset($this->sequences[$sequenceName])) $this->sequences[$sequenceName] = array();
						
					foreach ($result as $r) {
						$this->sequences[$sequenceName][] = $r;
							
						for ($i = 0; $i < $len; $i++) {
							$this->filler[$r['key']][$r['byte']][$r['offset'] + $i] = -1;
						}
					}
					break;
				}
			}
		}
	}

	private function testSequence($sequence = array()) {
		$len = count($sequence);
		$result = array();
		foreach ($this->filler as $key=>$data) {
			foreach($data as $byte=>$addresses) {
				$offset = 0;
				while ($offset++ < count($addresses) - $len) {
						
					$is_sequence = true;
					for ($i = 0; $i < $len; $i++) {
						if ($sequence[$i] != $addresses[$offset + $i]) {
							$is_sequence = false;
							break;
						}
					}

					if ($is_sequence) {
						$result[] = array('key' => $key, 'byte' => $byte, 'offset' => $offset, 'sequence' => $sequence);
						break;
					}
				}
			}
		}

		return count($result) > 1 ? $result : false;
	}

	private function generateCode($durations) {
		$generated = array('frames' => array(), 'functions' => false);

		foreach ($this->processSequences as $i) {
			$this->searchSequences($i);
		}

		// Remove processed in sequences bytes from filler
		foreach ($this->filler as $key=>$data) {
			foreach($data as $byte=>$addresses) {
				foreach($addresses as $key2=>$address) {
					if ($address == -1) {
						unset($this->filler[$key][$byte][$key2]);
					}
				}
			}
		}

		// Generate functions
		$output = '';
		foreach ($this->sequences as $fnmame=>$s) {
			$output .= $fnmame."\n";
				
			$s = reset($s);
			$output .= $this->generateSequenceCode($s['sequence']);
			$output .= "\t\tret\n";
			$this->funcstats ++;
		}
		// Save functions
		if ($this->outputDir) {
			$fp = fopen($this->outputDir.'/'.$this->procPrefix.'func.asm', 'w');
			fwrite($fp, $output);
			fclose($fp);
		}
		else {
			$generated['functions'] = array('filename' => $this->procPrefix.'func.asm', 'content' => $output);
		}

		foreach ($this->filler as $key=>$data) {
			$output = $this->procPrefix.$key;
			$this->framestats[$key]['bytes'] = 0;
				
			$curA = -1;
			foreach($data as $byte=>$addresses) {
				if ($byte == 0) {
					$output .= "\t\txor a\n";
					$this->framestats[$key]['bytes']++;
				}
				elseif ($byte - $curA == 1) {
					$output .= "\t\tinc a\n";
					$this->framestats[$key]['bytes']++;
				}
				else {
					$output .= "\t\tld a, ".$byte."\n";
					$this->framestats[$key]['bytes'] += 2;
				}
				$curA = $byte;

				// Call sequences
				foreach ($this->sequences as $fnmame=>$i) foreach ($i as $s) {
					if ($s['byte'] == $byte && $s['key'] == $key) {
						$output .= "\t\tcall ".$fnmame."\n";
						$this->framestats[$key]['bytes'] += 3;
					}
				}

				$output .= $this->generateSeriaV($addresses, $this->framestats[$key]['bytes']);
				$output .= $this->generateSeriaH($addresses, $this->framestats[$key]['bytes']);

				// Формируем оставшиеся фрагменты:
				// ld (addr1), a
				foreach ($addresses as $address) {
					$output .= "\t\tld (#".dechex(0x4000 + $address)."), a\n";
					$this->framestats[$key]['bytes'] += 3;
				}
			}
				
			$output .= "\t\tret\n";
			$this->framestats[$key]['bytes']++;
				
			// Save result
			if ($this->outputDir) {
				$fp = fopen($this->outputDir.'/'.$this->procPrefix.$key.'.asm', 'w');
				fwrite($fp, $output);
				fclose($fp);
			}
			else {
				$duration = intval($durations[$key] / 2);

				$generated['frames'][] = array(
						'duration' => $duration < 1 ? 1 : ($duration > 255 ? 255 : $duration),
						'filename' => $this->procPrefix.$key.'.asm',
						'function_name' => $this->procPrefix.$key,
						'content' => $output
				);
			}
		}

		return $this->outputDir ? true : $generated;
	}

	private function generateSequenceCode($sequence) {
		$output = $this->generateSeriaV($sequence, $this->funcstats);
		$output .= $this->generateSeriaH($sequence, $this->funcstats);

		// Формируем оставшиеся фрагменты:
		// ld (addr1), a
		foreach ($sequence as $address) {
			$output .= "\t\tld (#".dechex(0x4000 + $address)."), a\n";
			$this->funcstats += 3;
		}

		return $output;
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

	function parseGIF($filename) {
		require_once(PROJECT_ROOT.'include/helpers/GifFrameExtractor.php');
		if (!GifFrameExtractor::isAnimatedGif($filename)) return false;

		$gfe = new GifFrameExtractor();
		$gfe->extract($filename);
		$frames = $gfe->getFrameImages();
		$durations = $gfe->getFrameDurations();

		foreach ($frames as $key=>$frame) {
			$this->filler[$key] = $this->proceedFrame($frame);
				
			if ($this->separateFrames) {
				for ($i=0; $i < 6144; $i++) $this->curScreen[$i] = 0;
				for ($i=6144; $i < 6912; $i++) $this->curScreen[$i] = $this->initialColor;
			}
		}

		if ($this->cycleAnimation) {
			$this->filler[$key + 1] = $this->proceedFrame($frames[0]);
			$durations[] = $durations[0];
		}

		$result = $this->generateCode($durations);

		// Calculate total bytes
		$this->totalstat = $this->funcstats;
		foreach ($this->framestats as $frame=>$stat) $this->totalstat += $stat['bytes'];

		return $result;
	}
}