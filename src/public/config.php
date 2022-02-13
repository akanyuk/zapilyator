<?php
return array (
	'default_controler' => 'demo_maker',
	'cookie' => array(
		'name' => 'zapilyator',
		'domain' => '.'.$_SERVER['HTTP_HOST'],
		'path' => '/',
		'secure' => 0,
		'expire' => 1209600	// The cookie expires after 14 days
	),
);
