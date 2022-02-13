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
		'path' => 'zapilyator_te',
		'title' => 'Zapilyator TE',
		'content' => NFW::i()->fetch(PROJECT_ROOT.'include/templates/zapilyator/zapilyator_te.tpl')
	));
	NFW::i()->display('main.tpl');
}

// -----------------
//  Ok. Lets do it!
// -----------------

ini_set('max_execution_time', 300);

require_once PROJECT_ROOT.'include/helpers/parse256x192.php';
require_once PROJECT_ROOT.'include/helpers/ZXAnimation.php';

$DemoMaker = new zapilyator_te();

// First stage - load data
if (!isset($_POST['stage'])) {
	$main_color = intval($_POST['main_ink'] + $_POST['main_paper'] * 8 + $_POST['main_bright'] * 64);
	$data = array(
		'splash' => array(
			'border' => intval($_POST['splash_border']) < 0 || intval($_POST['splash_border']) > 7 ? 0 : intval($_POST['splash_border']),
		),
		'main' => array(
			'border' => intval($_POST['main_border']) < 0 || intval($_POST['main_border']) > 7 ? 0 : intval($_POST['main_border']),
			'color' => $main_color < 0 || $main_color > 255 ? 0x47 : $main_color,
		),
		'1' => false,
		'2' => false,
		'3' => false,
	);
	
	// Load three animations
	for ($i = 1; $i <= 3; $i++) {
		if (!$animation = $DemoMaker->upload('animation'.$i)) continue;
		
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
		
		$speed = intval($_POST['speed'.$i]);
		
		$data[$i] = array(
			'source' => $animation,
			'source_type' => $source_type,
			'parsed' => array(),
			'speed' => $speed >= 0 && $speed < 256 ? $speed : 0,
			'totalFramesLen' => 0,
			'totalBytesAff' => 0,
			'is_done' => false			
		);		
		
	}
	

	if (isset($_FILES['music_file'])) {
		$music_file = $DemoMaker->upload('music_file');
		if (!$DemoMaker->error) {
			$data['music_file'] = $music_file;
		}
	}

	
	// Splash screen
	$splash_background = $DemoMaker->upload('splash_background');
	if (!$DemoMaker->error) {
		$data['splash']['background'] = $splash_background;
		$data['splash']['delay'] = intval($_POST['splash_delay']) < 1 || intval($_POST['splash_delay']) > 5 ? 1 : intval($_POST['splash_delay']);
	}
	
	
	// Main background
	$main_background = $DemoMaker->upload('main_background');
	if (!$DemoMaker->error) {
		$data['main']['background'] = $main_background;
	}
	

	// Analyzator
	$ach = isset($_POST['main_analyzator_chanel']) ? intval($_POST['main_analyzator_chanel']) : 0;
	if ($ach >= 8 && $ach <= 11) {
		$data['main']['analyzator']['chanel'] = $ach;
		$data['main']['analyzator']['sens'] = intval($_POST['main_analyzator_sens']) < 8 || intval($_POST['main_analyzator_sens']) > 15 ? 15 : intval($_POST['main_analyzator_sens']);
	}
	
	// Analyzator in splash
	$ach = isset($_POST['splash_analyzator_chanel']) ? intval($_POST['splash_analyzator_chanel']) : 0;
	if ($ach >= 8 && $ach <= 11) {
		$data['splash']['analyzator']['chanel'] = $ach;
		$data['splash']['analyzator']['sens'] = intval($_POST['splash_analyzator_sens']) < 8 || intval($_POST['splash_analyzator_sens']) > 15 ? 15 : intval($_POST['splash_analyzator_sens']);
	}
	
	
	// Scroll
	if ($_POST['scroll_text']) {
		$data['scroll']['text'] = $_POST['scroll_text'];
		$data['scroll']['font'] = intval($_POST['scroll_font']) < 1 || intval($_POST['scroll_font']) > 3 ? '16x16font1' : '16x16font'.intval($_POST['scroll_font']);
		list($data['scroll']['address'], $data['scroll']['attr']) = explode('|', $_POST['scroll_position']);
	
		$scroll_color = intval($_POST['scroll_ink'] + $_POST['scroll_paper'] * 8 + $_POST['scroll_bright'] * 64);
		$data['scroll']['color'] = $scroll_color < 0 || $scroll_color > 255 ? 0x47 : $scroll_color;
	}
	
	// Upload pone!
	
	$project_name = md5(NFW::i()->serializeArray($data));
	$DemoMaker->saveProject($project_name, $data);

	NFW::i()->renderJSON(array(
		'result' => 'success',
		'stage' => 'parse_animation',
		'project_name' => $project_name,
		'log' => array(
			'Done!',
			'---',
			'Parsing animation data...'
		)
	));
}

if ($_POST['stage'] == 'parse_animation') {
	$project_name = isset($_POST['project_name']) ? $_POST['project_name'] : false;
	if (!$data = $DemoMaker->loadProject($_POST['project_name'])) {
		NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => $DemoMaker->last_msg));
	}
	
	for ($i = 1; $i <= 3; $i++) {
		if (!$data[$i] || $data[$i]['is_done']) continue;

		$method = $i==3 ? ZXAnimation::METHOD_FAST : ZXAnimation::METHOD_MEMSAVE;
		$data[$i]['method'] = $i==3 ? 'fast' : 'memsave';
		$data[$i]['position'] = $i==1 ? 'main_flow' : 'timeline';
		
		list($data, $loading_result) = $DemoMaker->parseAnimation($data, $i, $method);
		if ($loading_result['is_done']) {
			// Next animation
			$data['from'] = 0;
			$data[$i]['is_done'] = true;
			$DemoMaker->saveProject($project_name, $data);
			
			$log = array(
				'Parsed: <strong>'.$loading_result['from'].' - '.$loading_result['to'].'</strong> ('.$loading_result['total'].' total).',
				'Done!',
				'Animation size: <strong>'.number_format($data[$i]['totalFramesLen'], 0, '.', ' ').'</strong> bytes',
				'Bytes affected: <strong>'.number_format($data[$i]['totalBytesAff'], 0, '.', ' ').'</strong> bytes',
				'Data ratio: <strong>'.number_format($data[$i]['totalFramesLen'] / $data[$i]['totalBytesAff'], 2, '.', '').'</strong> bytes',
				'---'
			);
			if ($i < 3) {
				$log[] = 'Parsing animation '.($i+1).'...';
			}
			
			NFW::i()->renderJSON(array(
				'result' => 'success',
				'stage' => 'parse_animation',
				'project_name' => $project_name,
				'log' => $log
			));
		}
		else {
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
	}
	
	// Parsed successfully - make sources
	
	$result_zip = $DemoMaker->generateDemo($data);
	
	NFW::i()->renderJSON(array(
		'result' => 'done',
		'download' => '?get_file='.$result_zip,
		'log' => array(
			'Generating demo...',
			$DemoMaker->is_overflow ? '' : 'Freespace: <strong>'.number_format($DemoMaker->getFreeSpace() / 1024, 2, '.', '').'</strong> kb (<strong>'.number_format($DemoMaker->getFreeSpace(), 0, '.', ' ').'</strong> bytes)',
			$DemoMaker->is_overflow ? '<div class="error">RAM limit exceeded!</div>' :  '<div class="success">Success!</div>'
		)
	));
}

NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('Unknown error.')));