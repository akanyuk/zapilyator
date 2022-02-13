<?php
$CMedia = new media;

$path_parts = pathinfo(preg_replace("/^\//", "", $_SERVER['REQUEST_URI']));
$path_parts['dirname'] = str_replace(get_class($CMedia).'/_protected/', '', $path_parts['dirname']);

if (stristr($_SERVER['REQUEST_URI'], '_protected/')) {
	list($foo, $record_id) = explode('/', $path_parts['dirname']);
	
	if (!$CMedia->reload($record_id, array('load_data' => true))) {
		NFW::i()->stop($CMedia->last_msg);
	}
}
else {
	if (!$CMedia->reload(
		array(
			'owner_class' => preg_replace('/(^\/?'.get_class($CMedia).'\/)/', '', $path_parts['dirname']),
			'basename' => rawurldecode(preg_replace("/_tmb.*/", "", $path_parts['filename'])).(isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '')
		),
		array('load_data' => true))
	) {
        NFW::i()->stop($CMedia->last_msg);
	}
}

if ($CMedia->record['type'] != 'image') {
	// даем возможность открывать pdf в браузере
	if ($CMedia->record['mime_type'] != 'application/pdf') {
		header('Content-Disposition: attachment; filename="'.$CMedia->record['basename'].'"');
	}
	
	header('Content-type: '.$CMedia->record['mime_type']);
	header('Content-Length: '.$CMedia->record['filesize']);
	
	header('Content-Transfer-Encoding: binary');
	NFW::i()->stop($CMedia->record['data']);
}

if (!getimagesize($CMedia->record['fullpath'])) {
	// This is not image!
	header("Status: 404 Not Found");
	NFW::i()->stop('File not found.');
}

// Determine thumbnail size
$d = array();
preg_match("/_tmb(\d+)?(x(\d+))?/", $path_parts['filename'], $d);
$width = isset($d[1]) ? $d[1] : null;
$height = isset($d[3]) ? $d[3] : null;
$options = array(
	'crop' => strstr($path_parts['filename'], '-crp') ? true : false,
	'complementary' => strstr($path_parts['filename'], '-cmp') ? true : false,
	'format' => 'original'
);

NFW::i()->registerFunction('tmb');
$path = tmb($CMedia->record, $width, $height, $options);
$full_path = $CMedia->record['secure_storage'] ? $path : str_replace('//', '/', PROJECT_ROOT.parse_url($path, PHP_URL_PATH));

// do conditional get
$timestamp = filemtime($full_path);

$gmt_mtime = gmdate('r', $timestamp);
header('ETag: "'.md5($timestamp.$path).'"');
header('Last-Modified: '.$gmt_mtime);
header('Cache-Control: public');
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
	if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp.$path)) {
		header('HTTP/1.1 304 Not Modified');
		exit();
	}
}

header('Content-type: '.$CMedia->record['mime_type']);
header("Content-Length: ".filesize($full_path));

// Cache control
header("Last-Modified: ".gmdate("D, d M Y H:i:s",filemtime($full_path))." GMT");
header("Cache-Control: max-age=10000000, s-maxage=1000000, must-revalidate, proxy-revalidate");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + (60 * 60 * 24 * 100)) . " GMT");

readfile($full_path);