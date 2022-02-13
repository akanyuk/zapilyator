<?php
$captcha_params = array(
	'length' => 6,
	'size' => 15,
	'width' => 100,
	'font' => '3.ttf',
);
require(NFW_ROOT.'helpers/captcha/Captcha.php');
$CCaptcha = new CaptchaNumbers($captcha_params);

// Enable sending of a P3P header
header('P3P: CP="CUR ADM"');
 
if (version_compare(PHP_VERSION, '5.2.0', '>='))
	setcookie(NFW::i()->cfg['cookie']['name'].'_captcha', md5($CCaptcha->getString()), 0, NFW::i()->cfg['cookie']['path'], NFW::i()->cfg['cookie']['domain'], NFW::i()->cfg['cookie']['secure'], true);
else
	setcookie(NFW::i()->cfg['cookie']['name'].'_captcha', md5($CCaptcha->getString()), 0, NFW::i()->cfg['cookie']['path'].'; HttpOnly', NFW::i()->cfg['cookie']['domain'], NFW::i()->cfg['cookie']['secure']);

$CCaptcha->display();