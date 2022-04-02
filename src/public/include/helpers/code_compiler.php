<?php

/**
 * @desc Extracting sources from ZIP-archive, compiling, and adding into archive
 * @param $outputDir string Output directory
 * @param $projectName string Project filename
 * @param $zipPath string Path to ZIP-archive with project files
 * @return false|string
 */
function CompileCode($outputDir, $projectName, $zipPath) {
    $projectDir = $outputDir . '/' . $projectName;

    if (!file_exists($projectDir)) {
        mkdir($projectDir, 0777, true);
    } else {
        recurseRmdir($projectDir);
    }

    $zip = new ZipArchive;
    if ($zip->open($zipPath) !== true) {
        return false;
    }
    if (!$zip->extractTo($projectDir)) {
        return false;
    }

    shell_exec('cd ' . $projectDir . ' && sjasmplus --inc=' . $projectDir . '/. --inc=' . $projectDir . '/sources/. ' . $projectDir . '/sources/zapil.asm');

    $compileResult = false;
    if (file_exists($projectDir . '/zapil.sna')) {
        $zip->addFile($projectDir . '/zapil.sna', 'zapil.sna');
        $compileResult = true;
    }

    if (file_exists($projectDir . '/zapil.trd')) {
        $zip->addFile($projectDir . '/zapil.trd', 'zapil.trd');
        $compileResult = true;
    }

    $zip->close();
//    recurseRmdir($projectDir);

    return $compileResult;
}

function recurseRmdir($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file") && !is_link("$dir/$file")) ? recurseRmdir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

