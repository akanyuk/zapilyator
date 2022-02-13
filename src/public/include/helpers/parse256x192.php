<?php
// Только парсер, без генерации кода в отличии от предыдущей версии

class parse256x192 extends base_module {
	private $source = array();			// Массив, в который будет загружена исходная анимация для парсинга
	private $sourceType = 'gif';		// Идентификатор типа исходной анимации
	private $curScreen = array();		// Массив с данными текущего состояния экрана
	private $duration = array();		// Массив с данными текущего состояния экрана
	
	// Настройки
	private $cycleAnimation = true;
	private $separateFrames = false;	// Каждый фрейм - заново (без учета предыдущего) 
	private $initialColor = 0;			// Начальный цвет для цветной анимации (256x212 по методу Перцовский-Какос)
	private $defaultDuration = 1;		// Задержка между кадрами по умолчанию
	
	//private $startFrom
	
	function __construct($config) {
		$this->cycleAnimation = isset($config['cycleAnimation']) ? $config['cycleAnimation'] : $this->cycleAnimation;
		$this->separateFrames = isset($config['separateFrames']) ? $config['separateFrames'] : $this->separateFrames;
		$this->initialColor = isset($config['initialColor']) ? $config['initialColor'] : $this->initialColor;
		$this->sourceType = isset($config['sourceType']) ? $config['sourceType'] : $this->sourceType;
		$this->defaultDuration = isset($config['defaultDuration']) && intval($config['defaultDuration']) >= 0 && intval($config['defaultDuration']) <= 255 ? intval($config['defaultDuration']) : $this->defaultDuration;  
		
		// Задержка 0 допустима только для GIF (брать из файла)
		if ($this->sourceType != 'gif' && !$this->defaultDuration) {
			$this->defaultDuration = 1;
		} 
		
		// initial curScreen array
		for ($i=0; $i < 6144; $i++) $this->curScreen[$i] = 0;
		for ($i=6144; $i < 6912; $i++) $this->curScreen[$i] = $this->initialColor;
	}	
	
	private function proceedFrameSCR($scr) {
		$result = array();
		for ($address = 0; $address < 6912; $address++) {
			if (!isset($scr[$address])) continue;
			
			$byte = ord($scr[$address]);
			if ($this->curScreen[$address] == $byte) continue;
			
			$this->curScreen[$address] = $byte;
			if (!isset($result[$byte])) $result[$byte] = array();
			$result[$byte][] = $address;
		}
	
		return $result;
	}
	
	private function proceedFrameGIF($frame) {
		$frameWidth = imagesx($frame);
		$frameHeight = imagesy($frame);
		
		$maxX = intval($frameWidth / 8) > 32 ? 32 : intval($frameWidth / 8); 
		$maxY = $frameHeight > 216 ? 216 : $frameHeight;
		
		$result = array();
		
		for ($y = 0; $y < $maxY; $y++) {
			for ($x = 0; $x < $maxX; $x++) {
				list($byte, $address) = $this->proceedByteGIF($frame, $x, $y);
				if ($byte === false) continue;
				
				if (!isset($result[$byte])) $result[$byte] = array();
				$result[$byte][] = $address;
			}
		}

		return $result;
	}

	private function proceedByteGIF($frame, $x, $y) {
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

	/* Load frames from ZIP archive with SCR files to $this->source
	 *	Available options:
	*	from - from frame
	*	count - number of loaded frames
	*  is_continuous - continuous (partial) loading/parsing
	*
	*/
	private function loadSCRZIP($filename, $options = array()) {
		$zip = new ZipArchive;
		if (!$zip->open($filename)) {
			$this->error('Unable to open ZIP archive', __FILE__, __LINE__);
			return false;
		}
		
		// Try to load durations
		$durations = array();
		if ($result = $zip->getFromName('durations.txt')) {
			foreach (preg_split("/\\r\\n|\\r|\\n/", $result) as $line) {
				list($filename, $value) = explode(' ', $line);
				if (intval($value) > 0 && intval($value) <= 255) { 
					$durations[trim($filename)] = intval($value);
				} 
			}
		}
		
		$total = $key = 0;
		for ($i=0; $i < $zip->numFiles; $i++)	{
			$entry = $zip->getNameIndex($i);
			if (substr($entry, -1) == '/' ) continue; 	// skip directories
			if ($entry == 'durations.txt') continue;	// skip durations config
			
			$fp = $zip->getStream($entry);
			if (!$fp) {
				$this->error('Unable to extract the file: '.$entry, __FILE__, __LINE__);
				return false;
			}
			$frame = '';
			while (!feof($fp)) $frame .= fread($fp, 2);

			$this->source[] = array(
				'frame' => $frame,
				'duration' => isset($durations[pathinfo($entry , PATHINFO_BASENAME)]) ? $durations[pathinfo($entry , PATHINFO_BASENAME)] : $this->defaultDuration,
			);
			
			$total++;
			$key++;
		}
			
		$zip->close();

		if ($this->cycleAnimation) {
			$this->source[] = array(
				'frame' => $this->source[0]['frame'],
				'duration' => $this->source[0]['duration'],
			);
		}

		return array('total' => $total, 'from' => 0, 'to' => $key - 1, 'is_done' => true);
	}
	
	/* Load frames from GIF file to $this->source
	 *	Available options:
	 *	from - from frame
	 *	count - number of loaded frames
	 *  is_continuous - continuous (partial) loading/parsing
	 *
	 */
	private function loadGIF($filename, $options = array()) {
		require_once(dirname(__FILE__).'/GifFrameExtractor.php');
		
		if (!GifFrameExtractor::isAnimatedGif($filename)) {
			$this->error('Wrong GIF file', __FILE__, __LINE__);
			return false;
		}
		
		$this->source = array();
		
		$gfe = new GifFrameExtractor();
		$gfe->extract($filename);
		$frames = $gfe->getFrameImages();
		$durations = ($this->defaultDuration) ? false : $durations = $gfe->getFrameDurations();

		$total = count($frames);
		
		// Partial loading setup
		$is_continuous = isset($options['is_continuous']) && $options['is_continuous'] ? true : false;
		$from = isset($options['from']) ? intval($options['from']) : 0;
		if ($from && $is_continuous && !$this->separateFrames) {
			$this->source[] = array(
				'frame' => $frames[$from - 1],
				'duration' => $this->convertGIFFrameDuration($durations[$from - 1]),
				'initial_frame' => true
			);
		}
		
		$to = isset($options['count']) ? $from + intval($options['count']) - 1 : $total - 1;
		
		foreach ($frames as $key=>$frame) {
			if ($key < $from) continue;
			 	
			$this->source[] = array(
				'frame' => $frame,
				'duration' => $this->convertGIFFrameDuration($durations[$key]),
				'initial_frame' => false
			);
			
			if ($key >= $to) break;
		}
		
		if ($key == $total - 1 && $this->cycleAnimation) {
			$this->source[] = array(
				'frame' => $frames[0],
				'duration' => $this->convertGIFFrameDuration($durations[0]),
				'initial_frame' => false
			);
		}
		
		$is_done = $key == $total - 1 ? true : false;
		
		return array('total' => $total, 'from' => $from, 'to' => $key, 'is_done' => $is_done);
	}
	
	private function convertGIFFrameDuration($duration) {
		if ($this->defaultDuration) return $this->defaultDuration;
		
		$duration = ceil($duration / 2);
		if ($duration > 255) return 255;
		if ($duration < 1) return 1;
		return $duration;
	}
	
	function load($filename, $options = array()) {
		switch ($this->sourceType) {
			case 'gif':
				return $this->loadGIF($filename, $options);
			case 'scr_zip':
				return $this->loadSCRZIP($filename, $options);
			default:
				$this->error('Unknown source type.', __FILE__, __LINE__);
			return false;
		}
	}
	
	function parseSource($options = array()) {
		$result = array();
		
		foreach ($this->source as $key=>$f) {
			switch ($this->sourceType) {
				case 'gif':
					$parsed = $this->proceedFrameGIF($f['frame']);
					break;
				case 'scr_zip':
					$parsed = $this->proceedFrameSCR($f['frame']);
					break;
			}
				
			if (!isset($f['initial_frame']) || !$f['initial_frame']) {
				$result[$key] = array(
					'data' => $parsed,
					'duration' => $f['duration']
				);
			}
						
			if ($this->separateFrames) {
				for ($i=0; $i < 6144; $i++) $this->curScreen[$i] = 0;
				for ($i=6144; $i < 6912; $i++) $this->curScreen[$i] = $this->initialColor;
			}
		}

		return $result;
	}
}