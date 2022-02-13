<?php
// Demo maker special edition: nodeus animations

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
	NFW::i()->setUI('bootstrap');
	
	NFW::i()->assign('page', array(
		'path' => 'demo_maker_nd',
		'title' => 'Zapilyator ND',
		'content' => NFW::i()->fetch(PROJECT_ROOT.'include/templates/zapilyator/zapilyator_nd.tpl')
	));
	NFW::i()->display('main.tpl');
}

// -----------------
//  Ok. Lets do it!
// -----------------

require_once PROJECT_ROOT.'include/helpers/parse256x192.php';
require_once PROJECT_ROOT.'include/helpers/ZXAnimation.php';

$DemoMaker = new demo_maker_nd();

// First stage - load data
if (!isset($_POST['stage'])) {
	
	if (!$animation1 = $DemoMaker->upload('animation_file1')) {
		NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => $DemoMaker->last_msg));
	}
	
	switch($DemoMaker->last_uploaded['extension']) {
		case 'gif': 
			$source_type = 'gif';
			break;
		case 'zip':
			$source_type = 'scr_zip';
			break;
		default:
			NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => 'Unknown animation type.'));
	}
	
	$main_color = intval($_POST['ink'] + $_POST['paper'] * 8 + $_POST['bright'] * 64);
	
	$speed1 = intval($_POST['speed1']);
	$data = array(
		'border' => intval($_POST['border']) < 0 || intval($_POST['border']) > 7 ? 0 : intval($_POST['border']),
		'main_color' => $main_color < 0 || $main_color > 255 ? 0x47 : $main_color,

		// parser related
		'a1' => array(
			'source' => $animation1,
			'source_type' => $source_type,
			'parsed' => array(),
			'speed' => $speed1 >= 0 && $speed1 < 256 ? $speed1 : 0,
			'totalFramesLen' => 0,
			'totalBytesAff' => 0
		)
	);
	
	if (isset($_FILES['music_file'])) {
		$music_file = $DemoMaker->upload('music_file');
		if (!$DemoMaker->error) {
			$data['music_file'] = $music_file;
		}	
	}
		
	$project_name = md5(NFW::i()->serializeArray($data));
	$DemoMaker->saveProject($project_name, $data);
	
	NFW::i()->renderJSON(array(
		'result' => 'success', 
		'stage' => 'parse_animation', 
		'project_name' => $project_name,
		'log' => array(
			'Done.', 
			'Parsing GIF-animation...'
		)
	));
}

if ($_POST['stage'] == 'parse_animation') {
	$project_name = isset($_POST['project_name']) ? $_POST['project_name'] : false;
	if (!$data = $DemoMaker->loadProject($_POST['project_name'])) {
		NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => $DemoMaker->last_msg));
	}

	// Parse GIF portion	
	$parser = new parse256x192(array('initialColor' => $data['main_color'], 'sourceType' => $data['a1']['source_type'], 'defaultDuration' => $data['a1']['speed']));
	$from = isset($data['from']) ? $data['from'] : 0;
	$count = 1;  
	if (!$loading_result = $parser->load($data['a1']['source'], array(
		'from' => isset($data['from']) ? $data['from'] : 0,
		'count' => 100,
		'is_continuous' => true
	))) {
		NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => $parser->last_msg));
	}
	
	$frames = $parser->parseSource();

	// Generate data	
	$generator = new ZXAnimation();
	//$data['a1']['parsed'] = array_merge($data['a1']['parsed'], $generator->generateCode($frames, ZXAnimation::METHOD_FAST));
	$result = $generator->generateCode($frames, ZXAnimation::METHOD_MEMSAVE);
	$data['a1']['parsed'] = array_merge($data['a1']['parsed'], $result);
	
	$data['a1']['totalFramesLen'] += $generator->totalFramesLen;
	$data['a1']['totalBytesAff'] += $generator->totalBytesAff;
	
	if (!$loading_result['is_done']) {
		$data['from'] = $loading_result['to'] + 1;
		
		$DemoMaker->saveProject($project_name, $data);
		NFW::i()->renderJSON(array(
		'result' => 'success',
			'stage' => 'parse_animation',
			'project_name' => $project_name,
			'log' => array(
				'Parsed: <strong>'.$loading_result['from'].' - '.$loading_result['to'].'</strong> ('.$loading_result['total'].' total).',
			)
		));
	}
	
	// Parsed successfully - make sources
	
	$result_zip = $DemoMaker->generateDemo($data);
	
	NFW::i()->renderJSON(array(
		'result' => 'done',
		'download' => '?get_file='.$result_zip,
		'log' => array(
			'Parsed: <strong>'.$loading_result['from'].' - '.$loading_result['to'].'</strong> ('.$loading_result['total'].' total).',
			'Done.',
			'Animation size: <strong>'.number_format($data['a1']['totalFramesLen'], 0, '.', ' ').'</strong> bytes',
			'Bytes affected: <strong>'.number_format($data['a1']['totalBytesAff'], 0, '.', ' ').'</strong> bytes',
			'Data ratio: <strong>'.number_format($data['a1']['totalFramesLen'] / $data['a1']['totalBytesAff'], 2, '.', '').'</strong> bytes',
			$DemoMaker->is_overflow ? '' : 'Freespace: <strong>'.number_format($DemoMaker->getFreeSpace() / 1024, 2, '.', '').'</strong> kb (<strong>'.number_format($DemoMaker->getFreeSpace(), 0, '.', ' ').'</strong> bytes)',
			$DemoMaker->is_overflow ? '<div class="error">RAM limit exceeded!</div>' :  '<div class="success">Success!</div>'
		)
	));
}


NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('Unknown error.')));


class demo_maker_nd extends base_module {
	private $space = array(
		0 => 40860,
		1 => 16384,
		3 => 16384,
		4 => 16384,
		6 => 16384,
		7 => 16384
	); 
	
	private $sizes = array(
		// Player length (114) + play code (9)
		'Anima player' => 123,
			
		// Player length (2840) + init code (3) + play code (9) + safety align #100 (255)
		'PT3 player' => 3107,
	);
	
	// Overflowed size
	var $is_overflow = false;
	
	// Uploading related
	private $uploads_timelive = 864000;
	private $cache_dir = 'var/cache/';
	var $last_uploaded = false;
	
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

	private function allocSpace($size) {
		foreach ($this->space as $page=>$free) {
			if ($free > $size) {
				$this->space[$page] -= $size;
				return $page; 
			}
		}
		
		$this->is_overflow = true;
		return false;
	}
	
	function loadProject($project_name) {
		if (!file_exists($this->cache_dir.$project_name)) {
			$this->error('System error: wrong project temporary ID.', __FILE__, __LINE__);
			return false;
		}
		
		if (!$data = NFW::i()->unserializeArray(file_get_contents($this->cache_dir.$project_name))) {
			$this->error('System error: unable to reload project.', __FILE__, __LINE__);
			return false;
		}
		
		return $data;
	}
	
	function saveProject($project_name, $data) {
		file_put_contents($this->cache_dir.$project_name, NFW::i()->serializeArray($data));
	}
	
	function getFreeSpace() {
		$free_space = 0;
		foreach ($this->space as $val) {
			$free_space += $val;
		}			
		
		return $free_space;
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

		$targetFile =  $this->cache_dir.md5($file['name'].$file['size']);
			
		if (file_exists($targetFile) && !is_writable($targetFile)) {
			$this->error('File exists and can not be overwritten');
			return false;
		}
			
		move_uploaded_file(urldecode($file['tmp_name']), $targetFile);
		chmod($targetFile, 0777);

		$foo = pathinfo($file['name']);
		$this->last_uploaded = array(
			'basename' => strtolower($foo['basename']),
			'filename' => strtolower($foo['filename']),
			'extension' => strtolower($foo['extension']),
			'type' => $file['type'],
			'size' => $file['size']
		);
		
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
		$fp = fopen(PROJECT_ROOT.'resources/zapilyator/sources/zapilyator_nd.asm.tpl', 'r');
		$source_tpl = fread($fp, filesize(PROJECT_ROOT.'resources/zapilyator/sources/zapilyator_nd.asm.tpl'));
		fclose($fp);
		
		$source_tpl = str_replace('%border_color%', $params['border'], $source_tpl);
		$source_tpl = str_replace('%main_color%', $params['main_color'], $source_tpl);
		
		// -- music
		if (isset($params['music_file'])) {
			// Alloc memory for music:
			$this->allocSpace($this->sizes['PT3 player'] + filesize($params['music_file']));
			$zip->addFile(PROJECT_ROOT.'resources/zapilyator/sources/PTxPlay.asm', 'sources/PTxPlay.asm');
			$zip->addFile($params['music_file'], 'res/music');
		}
		$source_tpl = str_replace('%if_music%', isset($params['music_file']) ? '' : ';', $source_tpl);
		
		//-- frames 1
		if (!empty($params['a1']['parsed'])) {
			// Alloc memory for animation:
			$this->allocSpace($this->sizes['Anima player'] + count($params['a1']['parsed']) * 4);
			
			$source_tpl = str_replace('%if_anima%', '', $source_tpl);

			// Generate page-related array of frames,
			// create diff's directory
			// and create DB array
			$frames_by_page = $anima_frames = array(); 
			foreach ($params['a1']['parsed'] as $key=>$frame) {
				$page = $this->allocSpace($frame['frame_len']);
				if ($page === false) $page = 8;	// Fake page for overflowed frames
				
				$frame['sub_name'] = 'A1_'.$page.'_'.sprintf("%04x", $key);
				$anima_frames[] = ($page == 8 ? ';' : '')."\tdb ".$frame['duration'].', '.$page.' : dw '.$frame['sub_name'];
				
				if (!isset($frames_by_page[$page])) $frames_by_page[$page] = array();
				$frames_by_page[$page][] = $frame;

				// Add diff's
				if (empty($frame['diff'])) continue;
				$diff = '';
				foreach ($frame['diff'] as $address=>$byte) {
					$diff .= sprintf("%04x", $address).' '.sprintf("%02x", $byte)."\n";
				}
				$zip->addFromString('diff\\'.sprintf("%04d", $key).'.txt', $diff);
			}
			
			// Add DB list in sources
			$source_tpl = str_replace('%anima_frames%', implode("\n", $anima_frames), $source_tpl);
			
			// Create includes list in sources			
			foreach ($frames_by_page as $page => $frames) {
				switch ($page) {
					case 0:
						$includes = array();
						break;
					case 8:
						$includes = array(
							'',
							';Overflowed frames starts here',
							'/*'
						);
						break;
					default:
						$includes = array(
								"\t".'define _page'.$page,
								"\t".'page '.$page,
								"\torg #c000",
								'page'.$page.'s'
						);
				}
				
				foreach($frames as $frame) {
					// Add data
					$includes[] = $frame['sub_name']."\tinclude \"res/".$frame['sub_name'].".asm\"";
					$zip->addFromString('res\\'.$frame['sub_name'].'.asm', $frame['source']);
				} 	

				$includes[] = $page == 8 ? '*/' : 'page'.$page.'e';
				$source_tpl = str_replace('%anima_includes%', implode("\n", $includes)."\n".'%anima_includes%', $source_tpl);
			}
			$source_tpl = str_replace('%anima_includes%', '', $source_tpl);
		}
		else {
			$source_tpl = str_replace('%if_anima%', ';', $source_tpl);
		}
		
		$zip->addFromString('sources/test.asm', $source_tpl);
		$zip->close();
		
		return $dest_filename;
	}
}

function sort_by_page($a, $b) {
	if ($a['page'] == $b['page']) {
		return 0;
	}
	return ($a['page'] < $b['page']) ? -1 : 1;
}