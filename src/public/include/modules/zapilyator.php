<?php

class zapilyator extends base_module {
    private $space = array(
        0 => 40873,
        1 => 16384,
        3 => 16384,
        4 => 16384,
        6 => 16384,
        7 => 16384
    );

    private $sizes = array(
        'Int flow' => 346,

        /* Player length:
         * 2840 - player
         * 0003 - init
         * 0017 - play
         * 0002 - CUR_PATTERN / _curPattern
         * 0255 - safety align #100
         */
        'PT3 player' => 3117,
    );

    var $isOverflow = false; // Size overflowing control

    private $cacheDir = 'var/cache/';

    function __construct($config) {
        parent::__construct();

        if (isset($config['cacheDir'])) {
            $this->cacheDir = $config['cacheDir'];
        } else {
            $this->cacheDir = PROJECT_ROOT . $this->cacheDir;

            if (!file_exists($this->cacheDir)) {
                mkdir($this->cacheDir, 0777, true);
            }
        }
    }

    private function emptyFolder($dir, $liveTime = 0) {
        if (substr($dir, -1, 1) != '/') {
            $dir = $dir . '/';
        }

        // Remove old files
        $files = scandir($dir);
        foreach ($files as $f) {
            if (is_dir($dir . $f)) {
                continue;
            }

            if ((time() - filemtime($dir . $f) > $liveTime) && is_writable($dir . $f)) {
                @unlink($dir . $f);
            }
        }
    }

    private function allocSpace($size, $page = null) {
        // Alloc in given page
        if ($page !== null && isset($this->space[$page])) {
            if ($this->space[$page] < $size) {
                $this->isOverflow = true;
                return false;
            }

            $this->space[$page] -= $size;
            return $page;
        }

        // Alloc in page with minimal free memory
        $found_size = 65535;
        $found_page = false;
        foreach ($this->space as $page => $free) {
            if ($free >= $size && $free < $found_size) {
                $found_size = $free;
                $found_page = $page;
            }
        }

        if ($found_page === false) {
            $this->isOverflow = true;
            return false;
        }

        $this->space[$found_page] -= $size;
        return $found_page;
    }

    private function getSnippet($snippet_name, $options = array()) {
        if (!$xml = simplexml_load_file(PROJECT_ROOT . 'resources/output/snippets/' . $snippet_name . '.xml')) {
            $this->error('Wrong XML file.', __FILE__, __LINE__);
            return false;
        }

        $snippet = array(
            'template' => (string)$xml->template,
            'length' => (int)$xml->length
        );

        foreach ($xml->params->param as $param) {
            $varName = (string)$param->varname;
            $value = isset($options['params'][$varName]) ? $options['params'][$varName] : (string)$param->default;
            $snippet['template'] = str_replace('%' . $varName . '%', $value, $snippet['template']);
        }

        // Set module name
        if (isset($options['module']) && $options['module']) {
            $snippet['template'] = "\t" . 'module ' . $options['module'] . "\n" . $snippet['template'] . "\n\t" . 'endmodule';
        }

        // Switch page
        if (isset($options['page'])) {
            $snippet['template'] = "\t" . 'ld a, #' . sprintf("%02x", $options['page'] + 0x10) . ' : call setPage' . "\n" . $snippet['template'];
            $snippet['length'] += 5;
        }

        // Set function name
        if (isset($options['function_name'])) {
            $snippet['template'] = $options['function_name'] . "\n" . $snippet['template'];
        }

        return $snippet;
    }

    private function generateAnalyzerData($scr_filename) {
        $scr = file_get_contents($scr_filename);
        $result = array();

        for ($address = 6144; $address < 6912; $address++) {
            if (!isset($scr[$address]) || ord($scr[$address]) < 128) continue;

            $scr[$address] = chr(ord($scr[$address]) - 128);
            $result[] = "\t" . 'dw #' . sprintf("%04x", $address + 0x4000);
        }

        if (empty($result)) return false;

        // Save modified (without FLASH) screen
        file_put_contents($scr_filename, $scr);

        return $result;
    }

    private function generateTimeline($timeline) {
        $timeline_flow = $functions = '';

        foreach ($timeline as $key => $t) {
            $function_name = isset($t['function_name']) ? $t['function_name'] : 'INTFLOW' . $key;
            $next_run = $t['next_run'] === 'next' ? $key + 1 : $t['next_run'];

            $timeline_flow .= "\t" . 'db #' . sprintf("%02x", $t['star_pattern']) . '		; start pattern' . "\n";
            $timeline_flow .= "\t" . 'db #' . sprintf("%02x", $t['page'] + 0x10) . '		; proc page' . "\n";
            $timeline_flow .= "\t" . 'dw ' . $function_name . '		; proc address' . "\n";
            $timeline_flow .= "\t" . 'dw #' . sprintf("%04x", $t['ints_counter']) . '		; ints counter' . "\n";
            $timeline_flow .= "\t" . 'db #' . sprintf("%02x", $t['stop_pattern']) . '		; stop pattern' . "\n";
            $timeline_flow .= "\t" . 'db #' . sprintf("%02x", $next_run) . '		; next run' . "\n";
            $timeline_flow .= "\n";

            if (isset($t['function']) && $t['function']) {
                $functions .= $function_name . "\n";
                $functions .= $t['function'];
                $functions .= "\n";
            }
        }

        return array($timeline_flow, $functions);
    }

    private function generateMainFlow($main_flow) {
        $result = array();

        foreach ($main_flow as $i) {
            if (!is_array($i)) {
                $result[] = $i;
                continue;
            }

            // Call from different page
            $result[] = "\t" . 'ld a, #' . sprintf("%02x", $i['page'] + 0x10) . ' : call setPage';
            $result[] = $i['code'];
            $result[] = "\t" . 'ld a, #10 : call setPage';
        }

        return implode("\n", $result);
    }

    private function generateDataFlow($data_flow) {
        $result = array();
        foreach ($data_flow as $page => $data) {
            // Фейковая 8-я страница
            if ($page == 8 && !empty($data)) {
                $result[] = '/* overflow data';
                $result = array_merge($result, $data);
                $result[] = '*/';
                continue;
            }

            // Заголовок для всех непустых страниц, кроме 0-й
            if ($page != 0 && !empty($data)) {
                $result[] = "\t" . 'define _page' . $page . ' : page ' . $page . ' : org #c000';
                $result[] = 'page' . $page . 's';
            }

            // Завершение для всех непустых страниц, для 0-й - всегда
            if ($page == 0 || !empty($data)) {
                $result = array_merge($result, $data);
                $result[] = 'page' . $page . 'e' . "\t" . 'display /d, \'Page ' . $page . ' free: \', #ffff - $';
            }
        }

        return implode("\n", $result);
    }

    function loadProject($projectName) {
        if (!file_exists($this->cacheDir . $projectName)) {
            $this->error('System error: file "'.$projectName.'" not found in '.$this->cacheDir, __FILE__, __LINE__);
            return false;
        }

        if (!$data = NFW::i()->unserializeArray(file_get_contents($this->cacheDir . $projectName))) {
            $this->error('System error: unable to reload project', __FILE__, __LINE__);
            return false;
        }

        return $data;
    }

    function saveProject($projectName, $data) {
        if (file_put_contents($this->cacheDir . $projectName, NFW::i()->serializeArray($data)) === false) {
            $this->error('System error: save file "'.$projectName.'" into '.$this->cacheDir.' failed', __FILE__, __LINE__);
            return false;
        }

        return true;
    }

    function parseAnimation($data, $i, $method = ZXAnimation::METHOD_FAST) {
        // Parse GIF portion
        $parser = new parse256x192(array('initialColor' => $data['main']['color'], 'sourceType' => $data[$i]['source_type'], 'defaultDuration' => $data[$i]['speed']));
        if (!$loading_result = $parser->load($data[$i]['source'], array(
            'from' => isset($data['from']) ? $data['from'] : 0,
            'count' => 100,
            'is_continuous' => true
        ))) {
            NFW::i()->renderJSON(array('result' => 'error', 'last_msg' => $parser->last_msg));
        }

        $frames = $parser->parseSource();

        // Generate data
        $generator = new ZXAnimation();
        $result = $generator->generateCode($frames, $method);
        $data[$i]['parsed'] = array_merge($data[$i]['parsed'], $result);
        $data[$i]['totalFramesLen'] += $generator->totalFramesLen;
        $data[$i]['totalBytesAff'] += $generator->totalBytesAff;

        return array($data, $loading_result);
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
        $this->emptyFolder($this->cacheDir, 3600);

        if (!isset($_FILES[$field_name])) {
            $this->error('No file selected: ' . $field_name, __FILE__, __LINE__);
            return false;
        }

        $file = $_FILES[$field_name];

        if (!empty($file['error'])) {
            switch ($file['error']) {
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
                    $this->error('No error code available');
                    return false;
            }
        }

        $targetFile = $this->cacheDir . md5($file['name'] . $file['size']);

        if (file_exists($targetFile) && !is_writable($targetFile)) {
            $this->error('File exists and can not be overwritten');
            return false;
        }

        move_uploaded_file(urldecode($file['tmp_name']), $targetFile);
        chmod($targetFile, 0777);

        return array(
            'targetFile' => $targetFile,
            'fileExtension' => pathinfo($file['name'], PATHINFO_EXTENSION)
        );
    }

    function generateSource($params) {
        // Generate ZIP
        $dest_filename = md5(serialize($params));
        $zip = new ZipArchive();
        $zip->open($this->cacheDir . $dest_filename, ZIPARCHIVE::OVERWRITE | ZIPARCHIVE::CREATE);
        $zip->addFile(PROJECT_ROOT . 'resources/output/make.cmd', 'make.cmd');
        $zip->addFile(PROJECT_ROOT . 'resources/output/sources/builder.asm', 'sources/builder.asm');

        // -----------------
        //  Generate source
        // -----------------

        $fp = fopen(PROJECT_ROOT . 'resources/output/sources/test.asm.tpl', 'r');
        $source_tpl = fread($fp, filesize(PROJECT_ROOT . 'resources/output/sources/test.asm.tpl'));
        fclose($fp);

        $data_flow = $main_flow = $timeline = array();
        $is_music = isset($params['music_file']);

        // Generate empty $data_flow array
        foreach ($this->space as $page => $foo) $data_flow[$page] = array();

        // -- music
        if ($is_music) {
            $source_tpl = str_replace('%if_music%', '', $source_tpl);

            $this->allocSpace($this->sizes['PT3 player'] + filesize($params['music_file']), 0);
            $zip->addFile(PROJECT_ROOT . 'resources/output/sources/PTxPlay.asm', 'sources/PTxPlay.asm');
            $zip->addFile($params['music_file'], 'res/music');
        } else
            $source_tpl = str_replace('%if_music%', ';', $source_tpl);

        // Set border
        $snippet = $this->getSnippet('set_border', array(
            'params' => array('VALUE' => $params['splash']['border'])
        ));
        $main_flow[] = $snippet['template'];
        $this->allocSpace($snippet['length'], 0);

        // -- splash background
        if (isset($params['splash']['background'])) {
            $snippet = $this->getSnippet('copy_to_scr', array(
                'params' => array('SOURCE' => 'SPLASH_BG')
            ));
            $page = $this->allocSpace(filesize($params['splash']['background']) + $snippet['length']);

            $main_flow[] = array(
                'page' => $page,
                'code' => $snippet['template']
            );

            $data_flow[$page][] = 'SPLASH_BG' . "\t" . 'incbin "res/splash_bg"';

            $zip->addFile($params['splash']['background'], 'res/splash_bg');

            // Generate pause
            if ($is_music) {
                $snippet = $this->getSnippet('wait_pattern', array(
                    'module' => 'splash_delay',
                    'params' => array('DELAY' => $params['splash']['delay'])
                ));
            } else {
                $snippet = $this->getSnippet('pause_short', array(
                    'params' => array('DELAY' => '#FF')
                ));
            }
            $main_flow[] = $snippet['template'];
            $this->allocSpace($snippet['length'], 0);
        }

        // Analyzer in splash screen
        if (isset($params['splash']['analyzer']['channel']) && isset($params['splash']['analyzer']) && $data = $this->generateAnalyzerData($params['splash']['background'])) {
            $timeline[] = array(
                'star_pattern' => 0,
                'page' => 0,
                'function' => $snippet['template'],
                'ints_counter' => 0xffff,
                'stop_pattern' => $params['splash']['delay'],
                'next_run' => 0xff
            );
            $snippet = $this->getSnippet('analyzer_bright', array(
                'module' => 'splash_analyzer',
                'params' => array(
                    'CHANNEL' => $params['splash']['analyzer']['channel'],
                    'SENS' => $params['splash']['analyzer']['sens'],
                    'DATA' => implode("\n", $data)
                )
            ));
            $this->allocSpace($snippet['length'] + count($data) * 2, 0);
        }

        // Change border if different
        if ($params['main']['border'] != $params['splash']['border']) {
            $snippet = $this->getSnippet('set_border', array(
                'params' => array('VALUE' => $params['main']['border'])
            ));
            $main_flow[] = $snippet['template'];
            $this->allocSpace($snippet['length']);
        }

        // Change background after splash
        if (isset($params['main']['background'])) {
            $snippet = $this->getSnippet('copy_to_scr', array(
                'params' => array('SOURCE' => 'MAIN_BG')
            ));
            $page = $this->allocSpace(filesize($params['main']['background']) + $snippet['length']);
            $data_flow[$page][] = 'MAIN_BG' . "\t" . 'incbin "res/main_bg"';

            $main_flow[] = "\t" . 'ld a, ' . ($page + 0x10) . ' : call setPage' . "\n" . $snippet['template'];
            $this->allocSpace(5, 0);

            $zip->addFile($params['main']['background'], 'res/main_bg');
        } else {
            // Simple clear screen
            $snippet = $this->getSnippet('clear_scr', array(
                'params' => array('ATTR' => $params['main']['color'])
            ));
            $main_flow[] = $snippet['template'];
            $this->allocSpace($snippet['length'], 0);
        }

        // Analyzer in main screen
        if (isset($params['main']['analyzer']['channel']) && isset($params['main']['background']) && $data = $this->generateAnalyzerData($params['main']['background'])) {
            $snippet = $this->getSnippet('analyzer_bright', array(
                'module' => 'main_analyzer',
                'params' => array(
                    'CHANNEL' => $params['main']['analyzer']['channel'],
                    'SENS' => $params['main']['analyzer']['sens'],
                    'DATA' => implode("\n", $data)
                )
            ));

            // Generate pause before start
            $timeline[] = pauseBlock($params['splash']['delay']);
            $this->allocSpace(1, 0);

            $timeline[] = array(
                'star_pattern' => 0xfe,
                'page' => 0,
                'function' => $snippet['template'],
                'ints_counter' => 0xffff,
                'stop_pattern' => 0xff,
                'next_run' => 0xff
            );
            $this->allocSpace($snippet['length'] + count($data) * 2, 0);
        }


        // -- scroll
        if (isset($params['scroll']['text'])) {
            // Prepare screen background for scroll
            $snippet = $this->getSnippet('fill_block', array(
                'params' => array(
                    'FROM' => $params['scroll']['attr'],
                    'LENGTH' => 0x40,
                    'FILL' => $params['scroll']['color']
                )
            ));
            $main_flow[] = $snippet['template'];
            $this->allocSpace($snippet['length'], 0);

            // Generate scroll function
            $snippet = $this->getSnippet('scroll_text16', array(
                'module' => 'scroll_text',
                'function_name' => 'SCROLL_FUNC',
                'params' => array(
                    'ADDRESS' => $params['scroll']['address']
                )
            ));

            $page = $this->allocSpace($snippet['length'] + 512 + strlen($params['scroll']['text']) + 1 + 3072);
            $data_flow[$page][] = $snippet['template'];
            $data_flow[$page][] = 'SCROLL_BUFF' . "\t" . 'block 512';
            $data_flow[$page][] = 'SCROLL_TEXT' . "\t" . 'incbin "res/scroll"';
            $data_flow[$page][] = "\t" . 'db #00';
            $data_flow[$page][] = 'FONT16X16' . "\t" . 'incbin "res/16x16font"';

            // Generate pause before scroll
            $timeline[] = array(
                'star_pattern' => $params['splash']['delay'],
                'page' => 0,
                'function' => "\t" . 'ret' . "\n",
                'ints_counter' => 0x0004,
                'stop_pattern' => 0xff,
                'next_run' => 'next'
            );
            $this->allocSpace(1, 0);

            $timeline[] = array(
                'star_pattern' => 0xfe,
                'page' => $page,
                'function_name' => 'SCROLL_FUNC',
                'ints_counter' => 0xffff,
                'stop_pattern' => 0xff,
                'next_run' => 0xff
            );

            $zip->addFile(PROJECT_ROOT . 'resources/output/res/' . $params['scroll']['font'], 'res/16x16font');
            $zip->addFromString('res/scroll', iconv("UTF-8", 'cp1251', mb_strtoupper($params['scroll']['text'], "UTF-8")));
        }

        // Generate animations
        for ($i = 1; $i <= 4; $i++) {
            if (!$params[$i]) continue;

            // Alloc memory for animation
            $tmp_snippet = $this->getSnippet('animation_' . $params[$i]['method']);
            $this->allocSpace($tmp_snippet['length'] + count($params[$i]['parsed']) * 4, 0);

            // Generate page-related array of frames,
            // create `diff` directory
            // and create DB array
            $anima_frames = array();
            foreach ($params[$i]['parsed'] as $key => $frame) {
                $page = $this->allocSpace($frame['frame_len']);
                if ($page === false) {
                    $page = 8;    // Fake page for overflowed frames
                }

                $proc_name = 'A' . $i . '_' . $page . '_' . sprintf("%04x", $key);

                $data_flow[$page][] = $proc_name . "\t" . 'include "res/' . $proc_name . '.asm"';
                $zip->addFromString('res\\' . $proc_name . '.asm', $frame['source']);

                $anima_frames[] = ($page == 8 ? ';' : '') . "\tdb " . $frame['duration'] . ', ' . $page . ' : dw ' . $proc_name;

                // Adding `diff`
                if (empty($frame['diff'])) continue;
                $diff = '';
                foreach ($frame['diff'] as $address => $byte) {
                    $diff .= sprintf("%04x", $address) . ' ' . sprintf("%02x", $byte) . "\n";
                }
                $zip->addFromString('diff\\' . $i . '-' . sprintf("%04d", $key) . '.txt', $diff);
            }

            // Generate animation function
            $snippet = $this->getSnippet('animation_' . $params[$i]['method'], array(
                'module' => 'animation' . $i,
                'function_name' => 'ANIMATION' . $i,
                'params' => array(
                    'ANIMATION_FRAMES' => implode("\n", $anima_frames)
                )
            ));

            if ($params[$i]['position'] == 'main_flow') {
                // Generate main flow function (at first of flow!)
                array_unshift($data_flow[0], $snippet['template']);
                $main_flow[] = "\t" . 'call ANIMATION' . $i . ' : halt : jr $-4';
                $this->allocSpace(6, 0);
            } else {
                // Generate pause before start
                $timeline[] = pauseBlock($params['splash']['delay']);
                $this->allocSpace(1, 0);

                // Generate timeline function
                $timeline[] = array(
                    'star_pattern' => 0xfe,
                    'page' => 0,
                    'function' => $snippet['template'],
                    'ints_counter' => 0xffff,
                    'stop_pattern' => 0xff,
                    'next_run' => 0xff
                );
            }
        }

        // Almost done. Finalize.
        if (empty($timeline)) {
            $source_tpl = str_replace('%timeline%', '', $source_tpl);
            $source_tpl = str_replace('%functions%', '', $source_tpl);
            $source_tpl = str_replace('%if_int_flow%', ';', $source_tpl);
        } else {
            $this->allocSpace($this->sizes['Int flow'] + count($timeline) * 8, 0);

            list($timeline, $functions) = $this->generateTimeline($timeline);
            $source_tpl = str_replace('%timeline%', $timeline, $source_tpl);
            $source_tpl = str_replace('%functions%', $functions, $source_tpl);
            $source_tpl = str_replace('%if_int_flow%', '', $source_tpl);
        }

        $source_tpl = str_replace('%main_flow%', $this->generateMainFlow($main_flow), $source_tpl);
        $source_tpl = str_replace('%data_flow%', $this->generateDataFlow($data_flow), $source_tpl);
        $zip->addFromString('sources/test.asm', $source_tpl);
        $zip->close();

        return $dest_filename;
    }
}

/**
 * @param int $delay
 * @return array
 */
function pauseBlock($delay = 0) {
    return array(
        'star_pattern' => $delay,
        'page' => 0,
        'function' => "\t" . 'ret' . "\n",
        'ints_counter' => 0x0004,
        'stop_pattern' => 0xff,
        'next_run' => 'next'
    );
}
