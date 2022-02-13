<?php
/**
 * @var string $foo
 **/
// Custom error handler
set_error_handler('_errorHandler');
set_exception_handler('_exceptionHandler');

// For PHP above 5.3.0
date_default_timezone_set('Etc/GMT-3');

// Strip out "bad" UTF-8 characters
function _remove_bad_utf8_characters($array) {
	$bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");
	return is_array($array) ? array_map('_remove_bad_utf8_characters', $array) : str_replace($bad_utf8_chars, '', $array);
}
$_GET = _remove_bad_utf8_characters($_GET);
$_POST = _remove_bad_utf8_characters($_POST);
$_COOKIE = _remove_bad_utf8_characters($_COOKIE);
$_REQUEST = _remove_bad_utf8_characters($_REQUEST);

class NFW {
	// Site config
	var $cfg;
	
	// paths
	var $base_path = '';
	var $absolute_path = '';

	// DB class instance
	var $db = false;
	
	var $lang = array();
	
	var $include_paths = array();
	
	// breadcrumb related
	var $breadcrumb = array();
	var $breadcrumb_status = '';
	
	// Current user's profile
	var $user = array();
	
	var $current_controler = false;
	
	protected $default_user = array(
		'id' => 1,
		'username' => 'Guest',
		'group_id' => 0,
		'is_guest' => true,
		'is_blocked' => false,
	);
	
	protected $current_language = false;
			
	protected $permissions = null;		//Permissions list for current user
	
	protected $resources_depends = array(
		'bootstrap' => array(
			'resources' => array('jquery'),
		),
		'bootstrap.sidebar' => array(
			'resources' => array('bootstrap'),
		),
		'jquery.activeForm' => array(
			'copy' => array('jquery.activeForm'),
			'resources' => array(
				'jquery', 
				'base',
				'jquery.activeForm/jquery.form.js',
				'jquery.activeForm/jquery.activeForm.js',
				'jquery.activeForm/jquery.activeForm.css'
			),
			'functions' => array('active_field')
		),
		'jquery.file-upload' => array(
			'resources' => array(
				'jquery', 
				'base',
				'jquery.ui.widget',	# internal depend of 'jquery.file-upload'
				'jquery.ui.interactions', 
				'jquery.jgrowl',
				'font-awesome',						
			),
			'functions' => array('tmb')
		),
		'jquery.jgrowl' => array(
			'resources' => array('jquery'),
		),
		'jquery.ui.widget' => array(
			'resources' => array('jquery'),
		),
		'jquery.ui.interactions' => array(
			'resources' => array('jquery'),
		),
		'dataTables' => array(
			'copy' => array('dataTables'),
			'resources' => array(
				'bootstrap',
				'dataTables/jquery.dataTables.min.js',
				'dataTables/dataTables.bootstrap.min.js',
				'dataTables/jquery.dataTables.custom.js',
					
				'dataTables/jquery.dataTables.min.css',
				'dataTables/dataTables.bootstrap.min.css',
			),
		),
		'ckeditor' => array(
			'copy' => array('ckeditor'),
			'resources' => array('jquery', 'ckeditor/ckedit.js', 'ckeditor/ckeditor.js', 'ckeditor/adapters/jquery.js'),  
		),
		'admin' => array(
			'resources' => array('jquery', 'font-awesome'),
		),
	);
	
	// Rendering vars
	protected $_template_var = array();
	protected $_head_assets = array(); 	// Needfull assets
	
	private $_start_execution;
	
	private static $_instance;

	function __construct($cfg = null) {
		// Record the start time (will be used to calculate the generation time for the page)
		$this->_start_execution = $this->microtime();
				
		self::$_instance = $this;
		
		$this->cfg = $cfg;

		// default timezone
		if (isset($this->cfg['default_timezone'])) {
			date_default_timezone_set($this->cfg['default_timezone']);
		}
		
		// include paths with order important (modules, templates, controlers, resources)
		$this->include_paths = isset($this->cfg['include_paths']) && !empty($this->cfg['include_paths']) ? $this->cfg['include_paths'] :
		array(
			PROJECT_ROOT.'include/',
			NFW_ROOT.'/'
		);
							
		
		if (isset($this->cfg['db']['type'])) {
			// Load DB abstraction layer and connect
			require NFW_ROOT.'dblayer/'.$this->cfg['db']['type'].'.php';
			$this->db = new DBLayer($this->cfg['db']['host'], $this->cfg['db']['username'], $this->cfg['db']['password'], $this->cfg['db']['name'], $this->cfg['db']['prefix'], $this->cfg['db']['p_connect']);
		}
		else {
			$this->cfg['db']['type'] = 'dummy';
			require NFW_ROOT.'dblayer/dummy.php';
			$this->db = new DBLayer();
		}
		
		// base_path, absolute _path
		if (!$this->base_path) {
			$page = preg_replace('/(^\/)|(\/$)|(\?.*)|(\/\?.*)/', '', $_SERVER['REQUEST_URI']);
			if ($page) {
				$chapters = explode('/', $page);
				$this->base_path = str_repeat('../', count($chapters));		
			}
		}
				
		if (!$this->absolute_path) {
			$this->absolute_path = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'];
		}
		
		// We need a assets main folder
		if (!file_exists(PROJECT_ROOT.'assets')) {
			mkdir(PROJECT_ROOT.'assets', 0777);
		}
		
		// Set default language
		$this->setLanguage();
		
		// Authentificate user if possible
		$this->login();

		// Reload correct lang pack after login attempt
		if ($this->user['language'] != $this->current_language) {
			// Set user's profile language from GET-string
			if ($this->user['id'] && isset($this->cfg['update_profile_language']) && $this->cfg['update_profile_language'] && isset($_GET['lang']) && in_array($_GET['lang'], $this->cfg['available_languages'])) {
				NFW::i()->db->query_build(array('UPDATE' => 'users', 'SET' => 'language=\''.$_GET['lang'].'\'', 'WHERE' => 'id='.$this->user['id']));
				$this->user['language'] = $_GET['lang'];
			}
			elseif (isset($this->cfg['available_languages']) && in_array($this->user['language'], $this->cfg['available_languages'])) {
				// Reload language pack
				$this->current_language = $this->user['language'];
				$this->lang = $this->getLang('nfw_main');
			}
			else {
				// Fix incorrect user's language
				$this->user['language'] = $this->current_language;
			}
		}
		
		if (isset($_GET['lang'])) unset($_GET['lang']);
	}
	
	/**
	 * @return self instance
	 */
	public static function i() {
		return self::$_instance;
	}

	public static function run($cfg = null) {
		$classname = defined('NFW_CLASSNAME') ? NFW_CLASSNAME : 'NFW';
		spl_autoload_register(array($classname, 'autoload'));
		
		new $classname($cfg);
		
		@list($foo, NFW::i()->current_controler) = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
		if (NFW::i()->current_controler) {
			foreach (NFW::i()->include_paths as $path) {
				if (file_exists($path.'controlers/'.NFW::i()->current_controler.'.php')) {
					require $path.'controlers/'.NFW::i()->current_controler.'.php';
					NFW::i()->stop();
				}
			}
		}
		
		// Try to find default controler
		NFW::i()->current_controler = isset($cfg['default_controler']) ? $cfg['default_controler'] : 'main';
		
		foreach (NFW::i()->include_paths as $path) {
			if (file_exists($path.'controlers/'.NFW::i()->current_controler.'.php')) {
				require $path.'controlers/'.NFW::i()->current_controler.'.php';
				NFW::i()->stop();
			}
		}
		
		NFW::i()->stop('Controler not found.');	
	}
	
	public static function autoload($class_name) {
		foreach (NFW::i()->include_paths as $path) {
		 	if (file_exists($path.'modules/'.$class_name.'.php')) {
				require_once($path.'modules/'.$class_name.'.php');
	    	}
		}
	}

	function checkPermissions($module, $action = '', $additional = false) {
		if ($this->permissions === null) {
			$C = new permissions();
			$this->permissions = $C->getPermissions($this->user);
		}		
		
		// Search permission
		foreach ($this->permissions as $p) {
			if ($p['module'] == $module && $p['action'] == $action) return true;
		}

		return false;
	}
	
	function getClass($module, $reverse_search = false) {
	 	$classname = is_object($module) ? get_class($module) : $module; 

	 	if ($reverse_search) {
	 		foreach (isset($this->cfg['module_map']) ? $this->cfg['module_map'] : array() as $new => $old) {
	 			if ($old == $classname) return $new; 
	 		}
	 		return $classname;	 		
	 	} 
	 	
	 	return isset($this->cfg['module_map'][$classname]) ? $this->cfg['module_map'][$classname] : $classname;
	}
	
	// Return array with language
	function getLang($lang_name, $field_name = false) {
	    $lang = 'lang_'.$lang_name;
	    $langname = 'langname_'.$lang_name;
	
	    global $$lang, $$langname;
	    if (!empty($$lang) && $$langname == NFW::i()->current_language) {
	    	$result = $$lang;
	        return $field_name ? $result[$field_name] : $result;
	    }

	    $$langname = NFW::i()->current_language;
	    
		$result = array();
	    
		foreach ($this->include_paths as $i) {
		   	if (file_exists($i.'lang/'.$this->current_language.'/'.$lang_name.'.php')) {
		       	include $i.'lang/'.$this->current_language.'/'.$lang_name.'.php';
		       	$result = array_replace_recursive($$lang, $result);
		   	}
		}

        if (empty($result)) {
        	return false;
        }
        	
        $$lang = $result;
        
        return $field_name ? $result[$field_name] : $result;
	}
	
	// Set $this->user from defaults with reloaded language by various methods
	function setLanguage($language = false) {
		$lang_cookie = NFW::i()->cfg['cookie']['name'].'_lang';
		
		// Try to load language from GET, COOKIE, or geo IP
		if (isset($this->cfg['set_language_by_get']) && $this->cfg['set_language_by_get'] && isset($_GET['lang']) && in_array($_GET['lang'], $this->cfg['available_languages'])) {
			$this->current_language = $_GET['lang'];
			$this->setCookie($lang_cookie, $_GET['lang'], time() + 60*60*24*30);
			$_SERVER['REQUEST_URI'] = preg_replace('/(&?lang='.$_GET['lang'].')/', '', $_SERVER['REQUEST_URI']);
			$_SERVER['REQUEST_URI'] = preg_replace('/(\?$)/', '', $_SERVER['REQUEST_URI']);
		}
		elseif (isset($this->cfg['set_language_by_cookie']) && $this->cfg['set_language_by_cookie'] && isset($_COOKIE[$lang_cookie]) && in_array($_COOKIE[$lang_cookie], $this->cfg['available_languages'])) {
			$this->current_language = $_COOKIE[$lang_cookie];
		}
		elseif (isset($this->cfg['set_language_by_geoip']) && $this->cfg['set_language_by_geoip'] && file_exists(PROJECT_ROOT.'var/SxGeo.dat')) {
			require_once(NFW_ROOT.'helpers/SxGeo/SxGeo.php');
			$SxGeo = new SxGeo(PROJECT_ROOT.'var/SxGeo.dat');
			$country = $SxGeo->get($_SERVER['REMOTE_ADDR']);
			if (in_array($country, array('RU', 'UA', 'BY', 'KZ'))) {
				$this->current_language = 'Russian';
			}
			else {
				$this->current_language = 'English';
			}
		}
		elseif (isset($this->cfg['default_language'])) {
			$this->current_language = $this->cfg['default_language'];
		}
		else {
			$this->current_language = 'English';
		}
		
		$this->lang = $this->getLang('nfw_main');
	} 
	
	// Authenificate user if possible via activeForm
	function login($action = '', $login_options = array()) {
		$this->user = $this->default_user;
		$this->user['language'] = $this->current_language;
		
		$classname = isset(NFW::i()->cfg['auth_class']) && NFW::i()->cfg['auth_class'] ? NFW::i()->cfg['auth_class'] : 'users';
		$CUsers = new $classname ();
	
		// Logout action
		if ($action == 'logout' || isset($_GET['action']) && $_GET['action'] == 'logout') {
			$CUsers->cookie_logout();
	
			// Делаем редирект, чтобы куки прижились
			// Send no-cache headers
			header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header('Cache-Control: post-check=0, pre-check=0', false);
			header('Pragma: no-cache');		// For HTTP/1.0 compability
			header('Content-type: text/html; charset=utf-8');
			NFW::i()->stop('<html><head><meta http-equiv="refresh" content="0;URL='.$this->absolute_path.'" /></head><body></body></html>');
		}
			
		// Login form action
		if ($action == 'form') {
			$this->assign('login_options', $login_options);
			$this->display('login.tpl');
		}
			
		// Authentificate send
		if (isset($_POST['login']) && isset($_POST['username']) && isset($_POST['password'])) {
			$form_username = trim($_POST['username']);
			$form_password = trim($_POST['password']);
			unset($_POST['login'], $_POST['username'], $_POST['password']);
	
			if (!$account = $CUsers->authentificate($form_username, $form_password)) {
				$lang_cookie = NFW::i()->cfg['cookie']['name'].'_lang';
				if (isset($_COOKIE[$lang_cookie]) && in_array($_COOKIE[$lang_cookie], array('Russian', 'English')) && $_COOKIE[$lang_cookie] != $this->user['language']) {
					$this->user['language'] = $_COOKIE[$lang_cookie];
					// Reload lang file
					$this->current_language = $this->user['language'];
					$this->lang = $this->getLang('nfw_main');
				}
	
				$this->renderJSON(array('result' => 'error', 'errors' => array(
					'username' => $this->lang['Errors']['Wrong_auth'],
					'password' => $this->lang['Errors']['Wrong_auth']
				)));
			}
	
			$this->user = $account;
			$this->user['is_guest'] = false;
	
			$CUsers->cookie_update($this->user);
			logs::write(logs::KIND_LOGIN);
				
			$this->loginSuccess();
		}
	
		// Cookie login
		if ($account = $CUsers->cookie_login()) {
			$this->user = $account;
			$this->user['is_guest'] = false;
		}
	
		return;
	}
	
	// After succesfully logined
	function loginSuccess() {
		$this->renderJSON(array('result' => 'succes'));		
	}
	
	// Return microtime for execution counting
	function microtime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
	
	//PHP функция для обратимого шифрования
	//-------------------------------------
	function encodeStr($str, $seq = '') {
		$salt = isset($this->cfg['encode_str_salt']) ? $this->cfg['encode_str_salt'] : 'yFR84oF5EWqPEDfD';
		$len = strlen($str);
		$gamma = '';
		$n = $len>100 ? 8 : 2;
		while(strlen($gamma) < $len) {
			$gamma .= substr(pack('H*', sha1($seq.$gamma.$salt)), 0, $n);
		}
		
		return $str^$gamma;
	}
		
	function serializeArray($array) {
		return base64_encode(serialize($array));
	}
	
	function unserializeArray($string) {
		$result = unserialize(base64_decode($string));
		if (!$result) $result = array();
		 
		return $result;
	}
	
	// Set COOKIE
	function setCookie($name, $value, $expire = 0) {
		// Enable sending of a P3P header
		header('P3P: CP="CUR ADM"');
	
		if (version_compare(PHP_VERSION, '5.2.0', '>='))
			setcookie($name, $value, $expire, $this->cfg['cookie']['path'], $this->cfg['cookie']['domain'], $this->cfg['cookie']['secure'], true);
		else
			setcookie($name, $value, $expire, $this->cfg['cookie']['path'].'; HttpOnly', $this->cfg['cookie']['domain'], $this->cfg['cookie']['secure']);
	}
	
	function registerFunction($functionPath = '') {
	    $parts = explode("/", $functionPath);
	    $functionName = array_pop($parts);
		if (function_exists($functionName)) return true;
	
		foreach ($this->include_paths as $i) {
			if (file_exists($i.'functions/'.$functionPath.'.php')) {
				include($i.'functions/'.$functionPath.'.php');
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Copy resource from protected storage to `assets`:
	 */
	private function copyResource($path, $_srcRes = null) {
		if (file_exists(PROJECT_ROOT.'assets/'.$path)) return;
		
		if ($_srcRes !== null) {
			if (is_dir($_srcRes)) {
				if (!file_exists(PROJECT_ROOT.'assets/'.$path)) {
					mkdir(PROJECT_ROOT.'assets/'.$path, 0777);
				}
					
				$files  = scandir($_srcRes);
				foreach ($files as $f) {
					if ($f != '.' && $f != '..') {
						$this->copyResource($path.'/'.$f, $_srcRes.'/'.$f);
					}
				}
				return;
			}
				
			if (!file_exists(PROJECT_ROOT.'assets/'.$path)) {
				@unlink(PROJECT_ROOT.'assets/'.$path);
				@copy($_srcRes, PROJECT_ROOT.'assets/'.$path);
				@touch(PROJECT_ROOT.'assets/'.$path, filemtime($_srcRes));
				clearstatcache();
			}
				
			return;
		}
	
		foreach ($this->include_paths as $i) {
			if (file_exists($i.'resources/'.$path)) {
				$this->copyResource($path, $i.'resources/'.$path);
			}
		}
	}
		
	/**
	 * Available options:
	 *  'atStart' 		- register resource maximum top of head
	 *  'skipDepends' 	- do not register depended resources 
	 */
	function registerResource($path, $options = array(), $_srcRes = null) {
		$atStart = isset($options['atStart']) && $options['atStart'] ? true : false;
		$skipDepends = isset($options['skipDepends']) && $options['skipDepends'] ? true : false;
		
		if (!$skipDepends) {
			if (isset($this->resources_depends[$path]['resources'])) {
				foreach ($this->resources_depends[$path]['resources'] as $r) $this->registerResource($r, array('atStart' => $atStart, 'skipDepends' => $skipDepends));
			}
				
			if (isset($this->resources_depends[$path]['functions'])) {
				foreach ($this->resources_depends[$path]['functions'] as $f) $this->registerFunction($f, array('atStart' => $atStart, 'skipDepends' => $skipDepends));
			}
			
			if (isset($this->resources_depends[$path]['copy'])) {
				foreach ($this->resources_depends[$path]['copy'] as $f) $this->copyResource($f);
				if (in_array($path, $this->resources_depends[$path]['copy'])) return;
			}
		}
		
		if ($_srcRes !== null) {
			if (in_array($path, $this->_head_assets)) return;
			
			if (is_dir($_srcRes)) {
				if (!file_exists(PROJECT_ROOT.'assets/'.$path)) {
					mkdir(PROJECT_ROOT.'assets/'.$path, 0777);
				}
			
				$files  = scandir($_srcRes);
				foreach ($files as $f) {
					if ($f != '.' && $f != '..') {
						$this->registerResource($path.'/'.$f, array('atStart' => $atStart, 'skipDepends' => $skipDepends), $_srcRes.'/'.$f);
					}
				}
				return;
			}
			
			if (!file_exists(PROJECT_ROOT.'assets/'.$path) || abs(filemtime($_srcRes) - filemtime(PROJECT_ROOT.'assets/'.$path)) > 3600) {
				@unlink(PROJECT_ROOT.'assets/'.$path);
				@copy($_srcRes, PROJECT_ROOT.'assets/'.$path);
				@touch(PROJECT_ROOT.'assets/'.$path, filemtime($_srcRes));
				clearstatcache();
			}
			
			if ($atStart) {
				array_unshift($this->_head_assets, $path);
			}
			else {
				$this->_head_assets[] = $path;
			}
			
			return;			
		}
		
		foreach ($this->include_paths as $i) {
			if (file_exists($i.'resources/'.$path)) {
				$this->registerResource($path, array('atStart' => $atStart, 'skipDepends' => $skipDepends), $i.'resources/'.$path);
			}
		}
	}
	
	/**
	 * Create assets resource (css, js, img) if not exists
	 * and return path to it
	 * 
	 * @param string 	Requested filename path
	 * @param boolean 	Echo result if false. Otherwise result returned as string to paste in <head>
	 * @return string	Real path
	 */
	function assets($path = '', $post_process = false) {
		if(!file_exists(PROJECT_ROOT.'assets/'.$path)) {
			if (strstr($path, '/')) {
				$first_dir = reset(explode('/', $path));
				$this->registerResource($first_dir);
			}
			else {			
				$this->registerResource($path);
			}
		}
		
		$result = $this->absolute_path.'/assets/'.$path;
		
		if (!$post_process) return $result;
		
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		
		if ($ext == 'js') {
			return '<script src="'.$result.'" type="text/javascript"></script>';
		}
		elseif ($ext == 'css') {
			return '<link href="'.$result.'" type="text/css" rel="stylesheet" media="screen" />';
		}
		elseif (strstr($path, 'favicon.ico') || strstr($path, 'favicon.png')) {
			return '<link href="'.$result.'" rel="shortcut icon" />';
		}
		else
			return false;
	}
		
	function renderJSON($data, $_reqursive = null, $wrap_textarea = true) {
		if ($_reqursive !== null) {
			$result = '{'."\n";
			foreach ($data as $key=>$value) {
				if (is_array($value)) {
					$result .= '"'.$key.'": '.self::renderJSON($value, true, $wrap_textarea).',';
				}
				else {
					$result .= '"'.$key.'": '.json_encode($value).',';
				}
			}
			return substr($result, 0, -1)."\n".'}';
				
		}
		
		$result = $this->renderJSON($data, true, $wrap_textarea);
		// wrap json in a textarea if the request did not come from xhr 
		if (!$wrap_textarea || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
			$this->stop($result); 
		} 
		else {
			$this->stop('<textarea>'.$result.'</textarea>');
		}		
	}
		
	public function assign($name, $value) {
		$this->_template_var[$name] = $value;
	}

	public function unassign($name) {
		unset($this->_template_var[$name]);
	}
	
	public function fetch($template_file, $local_vars = array()) {
		if (!file_exists($template_file)) return false;

		extract($this->_template_var);
		extract($local_vars);
		ob_start();
		include($template_file);
		return ob_get_clean();
	}
	
	function display($tpl, $is_prerendered_content = false) {
	    $content = $is_prerendered_content ? strval($tpl) : $this->fetch($this->findTemplatePath($tpl));

	    // Check if jQuery required by template (only for normal output)
	    if (!$is_prerendered_content && (strstr($content,'$(document).ready') || strstr($content,'$(function()'))) {
	    	$this->registerResource('jquery', array('atStart' => true));
	    }
	     
	    if (defined('NFW_SEPARATED_RESOURCES')) {
		    foreach(array_unique(array_reverse($this->_head_assets)) as $filename) {
		    	if ($cur_assets = $this->assets($filename, true)) {
	    			$content = str_ireplace('<head>', '<head>'."\n".$cur_assets, $content);
		    	}
		    }
	    }
		else {
			include_once(NFW_ROOT.'helpers/minify/src/Minify.php');
			include_once(NFW_ROOT.'helpers/minify/src/CSS.php');
			include_once(NFW_ROOT.'helpers/minify/src/JS.php');
			include_once(NFW_ROOT.'helpers/minify/src/Converter.php');
			$minifier_js = new MatthiasMullie\Minify\JS();
			$minifier_css = new MatthiasMullie\Minify\CSS();
			
			$js_filename = $css_filename = false;
			
		    foreach(array_unique($this->_head_assets) as $filename) {
		    	switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
		    		case 'js':
		    			$minifier_js->add(PROJECT_ROOT.'assets/'.$filename);
		    			$js_filename = md5($js_filename.file_get_contents(PROJECT_ROOT.'assets/'.$filename)).'.js';
		    			break;
		    		case 'css':
		    			// FIX paths in css
		    			$foo = pathinfo($filename);
		    			$dirname = $foo['dirname'];
		    			
		    			$css_content = file_get_contents(PROJECT_ROOT.'assets/'.$filename);
		    			$css_content = str_replace('url("', 'url("'.$dirname.'/', $css_content);
		    			$css_content = str_replace('url(\'', 'url(\''.$dirname.'/', $css_content);
		    			
		    			// Return back inline images and direct url's
		    			$css_content = str_replace($dirname.'/data:', 'data:', $css_content);
		    			$css_content = str_replace($dirname.'/http://', 'http://', $css_content);
		    			$css_content = str_replace($dirname.'/https://', 'https://', $css_content);
		    			$minifier_css->add($css_content);
		    			$css_filename = md5($css_filename.file_get_contents(PROJECT_ROOT.'assets/'.$filename)).'.css';
		    			break;
		    		default:
		    			if ($cur_assets = $this->assets($filename, true)) {
		    				$content = str_ireplace('<head>', '<head>'."\n".$cur_assets, $content);
		    			}
		    	}
		    }
			
		    if ($js_filename !== false) {
		    	if (!file_exists(PROJECT_ROOT.'assets/'.$js_filename)) {
		    		file_put_contents(PROJECT_ROOT.'assets/'.$js_filename, $minifier_js->minify());
		    	}
	    		$content = str_ireplace('<head>', '<head>'."\n".'<script src="'.$this->absolute_path.'/assets/'.$js_filename.'" type="text/javascript"></script>', $content);
		    }
		    
		    if ($css_filename !== false) {
				if(!file_exists(PROJECT_ROOT.'assets/'.$css_filename)) {
			    	file_put_contents(PROJECT_ROOT.'assets/'.$css_filename, $minifier_css->minify());
			    }
			    $content = str_ireplace('<head>', '<head>'."\n".'<link href="'.$this->absolute_path.'/assets/'.$css_filename.'" type="text/css" rel="stylesheet" media="screen" />', $content);
		    }
		}
	     
		// Calculate script generation time
		if (defined('NFW_LOG_GENERATED_TIME')) {
			$str = 'Generated in '.sprintf('%.3f', $this->microtime() - $this->_start_execution).' seconds, '.$this->db->get_num_queries().' queries executed';
			if (class_exists('ChromePhp')) {
				ChromePhp::info($str);
			}
			else {
				$content = str_ireplace('</html>', '</html>'."\n\n".'<!--'.$str.'-->', $content);
			}
		}
		 
		if (defined('NFW_LOG_QUERIES') && class_exists('ChromePhp')) {
            ChromePhp::info('Executed queries:');
            foreach ($this->db->saved_queries as $q) {
                ChromePhp::info($q[0], $q[1].' sec');
            }
		}
		
	    // If a database connection was established (before this error) we close it
	    if ($this->db) $this->db->close();		
	    exit (trim($content));
	}

    function findTemplatePath($filename, $class = '') {
    	$class = $this->getClass($class);
    	 
    	$path = str_replace('//', '/', $class.'/'.NFW::i()->current_controler.'/'.$filename);
    	foreach ($this->include_paths as $i) {
    		if (file_exists($i.'templates/'.$path)) {
    			return $i.'templates/'.$path;
    		}
    	}

    	// Try to find template without `controler` subfolder
        $path = str_replace('//', '/', $class.'/'.$filename);
    	foreach ($this->include_paths as $i) {
    		if (file_exists($i.'templates/'.$path)) {
    			return $i.'templates/'.$path;
    		}
    	}
    	    	
    	// Try to find template of parent class
    	if ($parent_class = get_parent_class($class)) {
    		return $this->findTemplatePath($filename, $parent_class);
    	}
    
    	return false;
    }
	
	
	function stop($message = '', $output = null) {
		if ($output == 'default') {
			$output = isset(NFW::i()->cfg['default_error_message_mode']) ? NFW::i()->cfg['default_error_message_mode'] : 'error-page';
		}
		
		$tpl = 'main.tpl';
		
		if ($message === 404) {
			header("HTTP/1.0 404 Not Found");
			$this->assign('page', array('path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'is_error' => true, 'title' => $this->lang['Errors']['Bad_request'], 'content' => '<div class="alert alert-danger">'.$this->lang['Errors']['Page_not_found'].'</div>'));
			$this->display($tpl);
		}
		elseif ($message === 'inactive') {
			$this->assign('page', array('path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'is_error' => true, 'title' => $this->lang['Errors']['Page_inactive'], 'content' => '<div class="alert alert-danger">'.$this->lang['Errors']['Page_inactive'].'</div>'));
			$this->display($tpl);
		}
		
		switch ($output) {
			case 'silent':
				$message = '';
				break;
			case 'xml':
				header ("Content-Type:text/xml");
				break;
			case 'login':
				$this->assign('error', $message);
				$this->login('form');
				return;
			case 'alert':
				$message = '<html><script type="text/javascript">alert("'.$message.'");</script></html>';
				break;
			case 'active_form':
				$this->renderJSON(array('result' => 'error', 'errors' => array('general' => $message), 'last_message' => $message));
				break;
			case 'error-page':
				$this->assign('page', array(
					'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
					'is_error' => true,
					'title' => $this->lang['error_page_title'],
					'content' => '<div class="alert alert-danger">'.$message.'</div>',
				));
				$this->display($tpl);
				break;
			case 'standalone':
				$this->display($message, true);
				break;
			default:
				break;
		}

		if ($this->db) {
			$queries_postfix = ', '.$this->db->get_num_queries().' queries executed';
			$queries_saved = $this->db->saved_queries;
			$this->db->close();
		}
		else {
			$queries_postfix = '';
			$queries_saved = array();
		}
		
			// Calculate script generation time
		if (defined('NFW_LOG_GENERATED_TIME') && class_exists('ChromePhp')) {
			ChromePhp::info('Generated in '.sprintf('%.3f', $this->microtime() - $this->_start_execution).' seconds'.$queries_postfix);
		}
		 
		if (defined('NFW_LOG_QUERIES') && class_exists('ChromePhp') && !empty($queries_saved)) {
			ChromePhp::info('Executed queries:');
			foreach ($queries_saved as $q) {
				ChromePhp::info($q[0], $q[1].' sec');
			}
		}
		
	    exit ($message);
	}
	
	function errorHandler($error_number, $message, $file, $line, $db_error = false) {
		if (class_exists('ChromePhp')) {
			ChromePhp::error('Error: '.$message);
			ChromePhp::error('File: '.$file.':'.$line);
			if (isset($db_error['error_msg']) && $db_error['error_msg']) {
				ChromePhp::error('Database reported: '.$db_error['error_msg']);
				if (isset($db_error['error_sql']) && $db_error['error_sql']) {
					ChromePhp::error('Failed query: '.$db_error['error_sql']);
				}
			}
		}
				
		return true;
	}	
}

function _exceptionHandler($exception) {
        if (class_exists('ChromePhp')) {
                ChromePhp::error('Error: '.$exception->getMessage());
                ChromePhp::error('File: '.$exception->getFile().':'.$exception->getLine());
        } else {
                echo $exception->getMessage().' (File: '.$exception->getFile().' , Line: '.$exception->getLine();
        }

	return true;
}

function _errorHandler($error_number, $message, $file, $line, $db_error = false) {
	if ($error_number && (!(error_reporting() & $error_number))) {
		// This error code is not included in error_reporting
		return true;
	}
        
        if (class_exists('ChromePhp')) {
                ChromePhp::error('Error: '.$message);
                ChromePhp::error('File: '.$file.':'.$line);
                if (isset($db_error['error_msg']) && $db_error['error_msg']) {
                        ChromePhp::error('Database reported: '.$db_error['error_msg']);
                        if (isset($db_error['error_sql']) && $db_error['error_sql']) {
                                ChromePhp::error('Failed query: '.$db_error['error_sql']);
                        }
                }
        }

	return true;
}