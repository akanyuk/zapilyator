<?php
if (NFW::i()->user['is_blocked']) {
	NFW::i()->stop(NFW::i()->lang['Errors']['Account_disabled'], 'error-page');
}

// Set global 'admin' status
NFW::i()->current_controler = 'admin';

// Prepare settings from `admin_menu.php`
$top_menu = array();
if (file_exists(PROJECT_ROOT.'include/configs/admin_menu.php')) {
	include(PROJECT_ROOT.'include/configs/admin_menu.php');
	
	foreach ($top_menu as $key=>$i) {
		// Check permissions
		if (isset($i['perm'])) {
			list($module, $action) = explode(',',$i['perm']);
			if (!NFW::i()->checkPermissions($module, $action)) {
				unset($top_menu[$key]);
				continue;
			}
		}
	}
}
NFW::i()->assign('top_menu', $top_menu);
NFW::i()->assign('admin_help', isset($admin_help) ? $admin_help : array());

NFW::i()->registerResource('admin');
NFW::i()->registerResource('base');

$page = array(
	'title' => NFW::i()->cfg['admin']['title'], 
	'content' => '',
	'is_welcome' => false
);

// Do action

// Determine module and action
@list($foo, $foo, $module) = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$classname = NFW::i()->getClass($module, true);

$action = isset($_GET['action']) ?  $_GET['action'] : 'admin';

if (!$module) {
	// Welcome page
	if (!NFW::i()->checkPermissions('admin')) {
		NFW::i()->login('form');
	}
	
	$page['is_welcome'] = true;
	
	NFW::i()->assign('page', $page);
	NFW::i()->display('admin.tpl');
}
elseif (!class_exists($classname)) {
	NFW::i()->stop(NFW::i()->lang['Errors']['Bad_request'], 'error-page');
}

$CModule = new $classname ();
// Check module_name->action permissions 
if (!NFW::i()->checkPermissions($module, $action, $CModule)) {
	NFW::i()->login('form', array('redirect' => $_SERVER['REQUEST_URI']));
}
	    
NFW::i()->assign('Module', $CModule);
    
$page['content'] = $CModule->action($action);
if ($CModule->error) {
	NFW::i()->stop($CModule->last_msg, $CModule->error_report_type);
}

NFW::i()->assign('page', $page);
NFW::i()->display('admin.tpl');