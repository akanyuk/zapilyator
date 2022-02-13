<?php
/**
 * English language
 *
 * @var array $lang_nfw_main
 **/

$lang_nfw_main = array(
	'lang' => 'en',
	'lang_desc' => 'english',
	'months' => array( 1=>'January',2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'),
	'monthr' => array( 1=>'January',2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'),
	'today' => 'Today',
	'yesterday' => 'Yesterday',
		
	'error_page_title' => 'Error',
	'admin_menu_title' => 'Control Panel',
				
	// Globals
	'Go_Back' 		=> 'Go_Back',
	'Actions'		=> 'Actions',
	'LoggedAs'		=> 'Logged as',
	'Logout'		=> 'Logout',
	'Send'			=> 'Send',
	'Save changes'	=> 'Save changes',
	'Remove'		=> 'Remove',
	'Close'			=> 'Close',
	'Message'		=> 'Message',
		
	'Posted' 		=> 'Posted',
	'Updated' 		=> 'Updated',
		
	// Captcha
	'Captcha'			=> 'Protective code',
	'Captcha info'		=> 'Enter the protective code from image nearby.',
		
	// Login / logout
	'Authorization' => 'Authorization',
	'Authorization_desc' => 'Please complete authorization.',
	'Login'			=> 'Login', 	
	'Password'		=> 'Password',
	'GoIn'			=> 'Login',
		
	// Validation messages
	'Validation' => array(
		'Required'		=> '«%FIELD_DESC%» is required field.',
		'length'		=> '«%FIELD_DESC%» must have length %LENGTH% symbols.',
		'minlength'		=> '«%FIELD_DESC%» must be greater than %LENGTH% symbols.',
		'maxlength'		=> '«%FIELD_DESC%» must be less than %LENGTH% symbols.',
		'Wrong_captcha' => 'Wrong CAPTCHA code',
		'Invalid_email' => 'The email address you entered is invalid.',			
	),
	
	// Error messages
	'Errors' => array(
		'Bad_request'		=> 'Bad request. The link you followed is incorrect or outdated.',
		'No_Permissions'	=> 'No permissions',
		'Wrong_auth'		=> 'Wrong login or password.',
		'Account_disabled'	=> 'User account is disabled.',
		'Page_not_found' 	=> 'Requested page not found.',
		'Page_inactive' 	=> 'The page is temporarily unavailable.',
		'Dupe1'	=> 'Record with same «',
		'Dupe2'	=> '» already exists.',
	)
);
