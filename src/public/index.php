<?php
// Debugging stuffs
#define ('NFW_DEBUG', '1');
#define ('NFW_LOG_GENERATED_TIME', '1');
#define ('NFW_LOG_QUERIES', '1');
#define ('NFW_SEPARATED_RESOURCES', '1');

// NFW initialization and run
define('PROJECT_ROOT', dirname(__FILE__).'/');
define('NFW_ROOT', dirname(__FILE__).'/../nfw/');

// Run project
$config = include(dirname(__FILE__).'/config.php');
require NFW_ROOT.'nfw.php';
NFW::run($config);
