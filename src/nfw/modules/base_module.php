<?php
// $Id$

/**
 * Базовый класс для всех модулей.
 *
 * @copyright 2004-2018 Andrey nyuk Marinov
 * @author Andrey nyuk Marinov (aka.nyuk@gmail.com)
 */


abstract class base_module {
    /**
     * The post ID with module config.
     *
     * @var integer
     */ 
    var $lang = array();		// Language array;
    
    var $error 				= false;			// Error flag
    var $errors				= array();			// Array with all generated errors
    var $error_codes  		= array();			// List of module's error-codes
    var $last_msg 			= '';				// Stored error message
    var $last_error_code 	= 0;				// Stored error code
    var $error_report_type  = 'error-page';		// Type of error reporting (i.e. message, active_record, plain, silent...)
    
    var $cfg 		= array();
    var $action		= '';						// Current execution action;
    
    static $action_aliases = array();			// Actions aliases for permissions checking
    
    function __construct() {
    	// Define error_codes for use in error messages
    	foreach($this->error_codes as $code=>$v) {
    		if (!defined($v['define'])) {
    			define($v['define'], $code);
    		}
    	}
    	
    	return true;
    }
    
    public function searchArrayAssoc($in, $value = false, $keyname = 'id') {
    	foreach ($in as $a) {
    		if ($a[$keyname] == $value) return $a;
    	}
    	
    	return false;
    }
    
    /**
     * Validate fields array by given rules
     * 
     * @param array	Fields for validate in format $key=>$value
     * @param array	Validation rules in format $key=>$value
     * 
     * @return array Errors array
     */
	public function validate($record, $rules) {
    	if (is_array($record)) {
    		$multiple = true;	
    	}
    	else {
    		$record = array('foo' => $record);
    		$rules = array('foo' => $rules);
    		$multiple = false;
    	}

    	$lang = NFW::i()->lang['Validation'];
    	$errors = array();
    	foreach($rules as $key=>$params) {
    		$error_varname = (isset($params['desc'])) ? $params['desc'] : $key;
    		
    		// Validate CAPTCHA code. Always required!
    		if (isset($params['type']) && $params['type'] == 'captcha') {
		        if ($_COOKIE[NFW::i()->cfg['cookie']['name'].'_captcha'] != md5(isset($record[$key]) ? $record[$key] : '')) {
		            $errors[$key] = $lang['Wrong_captcha'];
		            continue;
		        }
    		}
    		
    		// Validate is required. Always required!
    		if (isset($params['required']) && $params['required']) {
    			if (!isset($record[$key]) || !$record[$key]) {
	    			$errors[$key] = str_replace('%FIELD_DESC%', $error_varname, $lang['Required']);
	    			continue;
    			}
    		}
    		
			// Next validate only given fields    		
    		if (!isset($record[$key]) || !$record[$key]) continue;
    		
    		// Validate length
    		if (isset($params['minlength']) && isset($params['maxlength']) && $params['minlength'] == $params['maxlength'] && strlen(utf8_decode($record[$key])) != $params['minlength']) {
    			$errors[$key] = str_replace(array('%FIELD_DESC%', '%LENGTH%'), array($error_varname, $params['minlength']), $lang['length']);
    		}
    		elseif (isset($params['minlength']) && strlen(utf8_decode($record[$key])) < $params['minlength']) {
    			$errors[$key] = str_replace(array('%FIELD_DESC%', '%LENGTH%'), array($error_varname, $params['minlength']), $lang['minlength']);
    		}
    		elseif (isset($params['maxlength']) && strlen(utf8_decode($record[$key])) > $params['maxlength']) {
    			$errors[$key] = str_replace(array('%FIELD_DESC%', '%LENGTH%'), array($error_varname, $params['maxlength']), $lang['maxlength']);
    		}
    		
    		// --------------------
    		// Validating by 'type'
    		// --------------------
    		
    		// Validate the email-address
    		if (isset($params['type']) && $params['type'] == 'email' && !$this->is_valid_email($record[$key])) {
				$errors[$key] = $lang['Invalid_email'];
    		}
    	}
    	
        return $multiple ? $errors : (isset($errors['foo']) ? $errors['foo'] : false);
    }
    
   /**
     * Set error variables and return false 
     *
     * @return void
     */ 
    function error($message, $file = false, $line = false, $db_error = false) {
    	if (!isset($this)) {
    		NFW::i()->errorHandler(null, $message, $file, $line, $db_error);
    		return;
    	}
    	
    	if (is_array($message) && isset($message['error_code']) && isset($message['desc'])) {
    		$this->last_error_code = $message['error_code'];
    		$this->last_msg = $message['desc'];
    	}
    	elseif (isset($this->error_codes[$message])) {
    		$this->last_error_code = $message;
    		$this->last_msg = $this->error_codes[$this->last_error_code]['desc'];
    	}
    	else {
    		$this->last_msg = $message;
    		$this->last_error_code = 0;
    	}

    	$this->errors[] = array(
    		'error_code' => $this->last_error_code,
    		'message' => $this->last_msg,
    	);
    	
    	$this->error = 1;
    	
    	NFW::i()->errorHandler($this->last_error_code, $this->last_msg, $file, $line, $db_error);
    }

    function resetErrors() {
    	$this->error = false;
    	$this->errors = array();
    	$this->last_msg = '';
    	$this->last_error_code = 0;    	   
    }
    
    function action($action, $params = array()) {
    	// Convert action name to function name
    	// i.e. 'activate_email' converted as 'actionActivateEmail'
    	$action_func = str_replace('_', ' ', $action);
    	$action_func = ucwords($action_func);
    	$action_func = str_replace(' ', '', $action_func);
    	$action_func = 'action'.ucwords(NFW::i()->current_controler).$action_func;
    	 
    	if (!method_exists(get_class($this), $action_func)) {
    		$this->error('Action not exists!', __FILE__, __LINE__);
    		return false;
    	}
    	 
    	$this->action = $action;
    	return call_user_func(array($this, $action_func), $params);
    }
    
    function formatURL($action = '', $additional_get = false, $is_relative = false) {
    	if (is_array($action)) {
    		list($action, $controler) = $action;
    	}
    	else {
    		$controler = NFW::i()->current_controler;
    	}
    	
    	$path = array();
    	
    	if ($controler !== false) {
    		$path[] = $controler;
    	}
    	$path[] = NFW::i()->getClass($this);
    	$path = implode('/',$path);
    	
    	$get = array();
    	if ($action) $get[] = 'action='.$action;
    	if ($additional_get) $get[] = $additional_get;
    	$get = empty($get) ? '' : '?'.join('&',$get);
    		
    	return $is_relative ? $path.$get : NFW::i()->absolute_path.'/'.$path.$get;
    }
    
    //
    // Validate an e-mail address
    //
    function is_valid_email($email) {
    	/* nyuk: функция filter_var не на всех хостингах работает правильно
    	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    	*/
    	if (strlen($email) > 80) return false;
    	
    	return preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email);
    }
    
    function renderAction($template_vars = array(), $action = '', $class = '') {
    	// Short call: renderAction('action') 
    	if (!is_array($template_vars)) {
    		$action = $template_vars; 
    		$template_vars = array();    		
    	}
    	
    	$filename = $action ? $action.'.tpl' : $this->action.'.tpl';
    	$class = $class ? $class : get_class($this);
    	if (!$full_path = NFW::i()->findTemplatePath($filename, $class)) {
    		return false;
    	}
		
		// Assign template vars
		if (is_array($template_vars)) {
			foreach($template_vars as $varname=>$value) {
				NFW::i()->assign($varname, $value);
			}
		}
		
		$local_vars = array('Module' => $this);
		$content = NFW::i()->fetch($full_path, $local_vars);

		// Unasssign template vars for future rendering
		if (is_array($template_vars)) {
			foreach(array_keys($template_vars) as $varname) {
				NFW::i()->unassign($varname);
			}
		}
		
		return $content;
    }
}

