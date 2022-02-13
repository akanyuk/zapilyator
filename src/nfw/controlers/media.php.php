<?php
NFW::i()->current_controler = false;	// in `media` shared actions for all controlers

$CMedia = new media;

if (!isset($_GET['action'])) {
	NFW::i()->stop(NFW::i()->lang['Errors']['Bad_request'], 'error-page');
}

NFW::i()->assign('Module', $CMedia);


$CMedia->action($_GET['action']);
if ($CMedia->error) {
	NFW::i()->stop($CMedia->last_msg, $CMedia->error_report_type);
}
