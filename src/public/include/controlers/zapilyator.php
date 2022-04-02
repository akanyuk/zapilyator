<?php

const CACHE_DIR = PROJECT_ROOT . '../../cache/';

if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0777, true);
}

if (isset($_GET['get_file'])) {
    $filename = str_replace(array('\\', '/'), array('', ''), $_GET['get_file']);
    if (!file_exists(CACHE_DIR . $filename) || is_dir(CACHE_DIR . $filename)) {
        NFW::i()->stop('File not found');
        return false;
    }

    header('Content-type: application/force-download');
    header('Content-Disposition: attachment; filename="test-demo.zip"');
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize(CACHE_DIR . $filename));
    readfile(CACHE_DIR . $filename);
    exit;
} elseif (empty($_POST)) {
    // Main page
    NFW::i()->assign('page', array(
        'path' => 'zapilyator',
        'title' => 'Zapilyator',
        'content' => NFW::i()->fetch(PROJECT_ROOT . 'include/templates/zapilyator.tpl')
    ));
    NFW::i()->display('main.tpl');
}

// -----------------
//  Ok. Lets do it!
// -----------------

ini_set('max_execution_time', 300);

require_once PROJECT_ROOT . 'include/helpers/parse256x192.php';
require_once PROJECT_ROOT . 'include/helpers/ZXAnimation.php';

$Zapilyator = new zapilyator(array(
    'cacheDir' => CACHE_DIR,
));

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
        '4' => false,
    );

    // Load animations
    for ($i = 1; $i <= 4; $i++) {
        $result = $Zapilyator->upload('animation' . $i);
        if ($result == false) {
            continue;
        }

        switch ($result['fileExtension']) {
            case 'gif':
                $source_type = 'gif';
                break;
            case 'zip':
                $source_type = 'scr_zip';
                break;
            default:
                NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => 'Unknown animation type.'));
                exit();
        }

        $speed = intval($_POST['speed' . $i]);

        $data[$i] = array(
            'source' => $result['targetFile'],
            'source_type' => $source_type,
            'parsed' => array(),
            'speed' => $speed >= 0 && $speed < 256 ? $speed : 0,
            'totalFramesLen' => 0,
            'totalBytesAff' => 0,
            'is_done' => false
        );
    }

    if (isset($_FILES['music_file'])) {
        $result = $Zapilyator->upload('music_file');
        if ($result !== false) {
            $data['music_file'] = $result['targetFile'];
        }
    }

    // Splash screen
    $result = $Zapilyator->upload('splash_background');
    if ($result !== false) {
        $data['splash']['background'] = $result['targetFile'];
        $data['splash']['delay'] = intval($_POST['splash_delay']) < 1 || intval($_POST['splash_delay']) > 5 ? 1 : intval($_POST['splash_delay']);
    }

    // Main background
    $result = $Zapilyator->upload('main_background');
    if ($result !== false) {
        $data['main']['background'] = $result['targetFile'];
    }

    // Analyzer
    $ach = isset($_POST['main_analyzer_channel']) ? intval($_POST['main_analyzer_channel']) : 0;
    if ($ach >= 8 && $ach <= 11) {
        $data['main']['analyzer']['channel'] = $ach;
        $data['main']['analyzer']['sens'] = intval($_POST['main_analyzer_sens']) < 8 || intval($_POST['main_analyzer_sens']) > 15 ? 15 : intval($_POST['main_analyzer_sens']);
    }

    // Analyzer in splash
    $ach = isset($_POST['splash_analyzer_channel']) ? intval($_POST['splash_analyzer_channel']) : 0;
    if ($ach >= 8 && $ach <= 11) {
        $data['splash']['analyzer']['channel'] = $ach;
        $data['splash']['analyzer']['sens'] = intval($_POST['splash_analyzer_sens']) < 8 || intval($_POST['splash_analyzer_sens']) > 15 ? 15 : intval($_POST['splash_analyzer_sens']);
    }

    // Scroll
    if ($_POST['scroll_text']) {
        $data['scroll']['text'] = $_POST['scroll_text'];
        $data['scroll']['font'] = intval($_POST['scroll_font']) < 1 || intval($_POST['scroll_font']) > 3 ? '16x16font1' : '16x16font' . intval($_POST['scroll_font']);
        list($data['scroll']['address'], $data['scroll']['attr']) = explode('|', $_POST['scroll_position']);

        $scroll_color = intval($_POST['scroll_ink'] + $_POST['scroll_paper'] * 8 + $_POST['scroll_bright'] * 64);
        $data['scroll']['color'] = $scroll_color < 0 || $scroll_color > 255 ? 0x47 : $scroll_color;
    }

    // Upload done!
    $projectName = md5(NFW::i()->serializeArray($data));
    $Zapilyator->saveProject($projectName, $data);

    NFW::i()->renderJSON(array(
        'result' => 'success',
        'stage' => 'parse_animation',
        'project_name' => $projectName,
        'log' => array(
            'Done!',
            '---',
            'Parsing animation data...'
        )
    ));
}

if ($_POST['stage'] == 'parse_animation') {
    $projectName = isset($_POST['project_name']) ? $_POST['project_name'] : false;
    if (!$data = $Zapilyator->loadProject($_POST['project_name'])) {
        NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => $Zapilyator->last_msg));
    }

    for ($i = 1; $i <= 4; $i++) {
        if (!$data[$i] || $data[$i]['is_done']) {
            continue;
        }

        $method = $i > 2 ? ZXAnimation::METHOD_FAST : ZXAnimation::METHOD_MEMSAVE;
        $data[$i]['method'] = $i > 2 ? 'fast' : 'memsave';
        $data[$i]['position'] = $i == 1 ? 'main_flow' : 'timeline';

        list($data, $loading_result) = $Zapilyator->parseAnimation($data, $i, $method);
        if ($loading_result['is_done']) {
            // Next animation
            $data['from'] = 0;
            $data[$i]['is_done'] = true;
            $Zapilyator->saveProject($projectName, $data);

            $log = array(
                'Parsed: <strong>' . $loading_result['from'] . ' - ' . $loading_result['to'] . '</strong> (' . $loading_result['total'] . ' total).',
                'Done!',
                'Animation size: <strong>' . number_format($data[$i]['totalFramesLen'], 0, '.', ' ') . '</strong> bytes',
                'Bytes affected: <strong>' . number_format($data[$i]['totalBytesAff'], 0, '.', ' ') . '</strong> bytes',
                'Data ratio: <strong>' . number_format($data[$i]['totalFramesLen'] / $data[$i]['totalBytesAff'], 2, '.', '') . '</strong> bytes',
                '---'
            );
            if ($i < 4) {
                $log[] = 'Parsing animation ' . ($i + 1) . '...';
            }

            NFW::i()->renderJSON(array(
                'result' => 'success',
                'stage' => 'parse_animation',
                'project_name' => $projectName,
                'log' => $log
            ));
        } else {
            $data['from'] = $loading_result['to'] + 1;
            $Zapilyator->saveProject($projectName, $data);
            NFW::i()->renderJSON(array(
                'result' => 'success',
                'stage' => 'parse_animation',
                'project_name' => $projectName,
                'log' => array(
                    'Parsed: <strong>' . $loading_result['from'] . ' - ' . $loading_result['to'] . '</strong> (' . $loading_result['total'] . ' total).',
                )
            ));
        }
    }

    // Parsed successfully - make sources

    $resultZip = $Zapilyator->generateSource($data);

    require_once PROJECT_ROOT . 'include/helpers/code_compiler.php';

    $compileLog = array(
        'Compiling sources...',
    );

    $result = CompileCode(CACHE_DIR, $projectName.'-out', CACHE_DIR . $resultZip);
    if ($result === false) {
        $compileLog[] = '<div class="text-danger">Code compilation failed</div>';
    } else {
        $compileLog[] = '<div class="text-success">Code compilation success</div>';
    }

    NFW::i()->renderJSON(array(
        'result' => 'done',
        'download' => '?get_file=' . $resultZip,
        'log' => array_merge(array(
            'Generating sources...',
            $Zapilyator->isOverflow ? '' : 'Free space: <strong>' . number_format($Zapilyator->getFreeSpace() / 1024, 2, '.', '') . '</strong> kb (<strong>' . number_format($Zapilyator->getFreeSpace(), 0, '.', ' ') . '</strong> bytes)',
            $Zapilyator->isOverflow ? '<div class="text-danger">RAM limit exceeded!</div>' : '<div class="text-success">Success!</div>',
            '---',
        ), $compileLog),
    ));
}

NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('Unknown error.')));