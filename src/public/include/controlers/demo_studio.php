<?php

NFW::i()->setUI('bootstrap');

$CPages = new pages();
if (!$page = $CPages->loadPage()) {
	NFW::i()->stop(404);
}
elseif (!$page['is_active']) {
	NFW::i()->stop('inactive');
}

// Do demo_studio

$action = (isset($_GET['action'])) ?  $_GET['action'] : 'main';
$CDS = new demo_studio();

NFW::i()->assign('Module', $CDS);

$page['content'] = $CDS->action($action, array('POST' => $_POST, 'FILES' => $_FILES));
if($CDS->error) {
	NFW::i()->stop($CDS->last_msg, $CDS->error_report_type);
}

NFW::i()->assign('page', $page);
NFW::i()->display('main.tpl');