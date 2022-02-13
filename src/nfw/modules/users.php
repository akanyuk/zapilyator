<?php
/**
 * @desc Управление пользователями.
 */
class users extends active_record {
	static $action_aliases = array(
		'read' => array(
			array('module' => 'users', 'action' => 'admin'),
			array('module' => 'users', 'action' => 'ip2geo'),
		),
		'update' => array(
			array('module' => 'users', 'action' => 'admin'),
			array('module' => 'users', 'action' => 'insert'),
			array('module' => 'users', 'action' => 'update_password'),
			array('module' => 'users', 'action' => 'delete'),
		)
	);

	var $attributes = array(
		'realname' => array('type' => 'str', 'minlength' => 2, 'maxlength' => 255, 'required' => true),
		'language'	=> array('type' => 'select', 'options' => array()),
		'city' => array('type' => 'str', 'minlength' => 2, 'maxlength' => 100),
		'country' => array('type' => 'select', 'options' => array()),
	);
	
	protected $service_attributes = array(
		'username' => array('type' => 'str', 'required' => true, 'unique' => true, 'minlength' => 2, 'maxlength' => 32),
		'email' => array('type' => 'email', 'required' => true, 'unique' => true),
		'is_blocked' => array('type' => 'bool'),
		'group_id' => array('type' => 'select'),
	);

	function __construct($record_id = false) {
    	$result = parent::__construct($record_id);

    	$this->lang = NFW::i()->getLang('users');
    	
    	if (isset(NFW::i()->cfg['available_languages']) && !empty(NFW::i()->cfg['available_languages'])) {
    		$this->attributes['language']['options'] = NFW::i()->cfg['available_languages'];
    	}
    	 
    	foreach ($this->lang['Attributes'] as $varname => $desc) {
    		if (isset($this->attributes[$varname])) {
    			$this->attributes[$varname]['desc'] = $desc;
    		}
    		
    		if (isset($this->service_attributes[$varname])) {
    			$this->service_attributes[$varname]['desc'] = $desc;
    		}
    	}

    	$this->attributes['country']['options'] = $this->lang['CountryList'];
    	
    	return $result;
    }


    // Generates a salted, SHA-1 hash of $str
    public static function hash($str, $salt) {
    	return sha1($salt.sha1($str));
    }

    public static function random_key($len, $readable = false, $hash = false) {
    	$key = '';

    	if ($hash) {
    		$key = substr(sha1(uniqid(rand(), true)), 0, $len);
    	}
    	else if ($readable)	{
    		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    		for ($i = 0; $i < $len; ++$i)
    			$key .= substr($chars, (mt_rand() % strlen($chars)), 1);
    	}
    	else {
    		for ($i = 0; $i < $len; ++$i) {
    			$key .= chr(mt_rand(33, 126));
    		}
    	}

    	return $key;
    }

    /**
     * Try authentificate user with cookies
     * @return void|boolean
     */
    public function cookie_login() {
    	if (!isset(NFW::i()->cfg['cookie'])) return false;

    	// We assume it's a guest
    	$cookie = array('user_id' => 1, 'password_hash' => 'Guest', 'expiration_time' => 0, 'expire_hash' => 'Guest');

   		$cookie_data = @explode('|', base64_decode($_COOKIE[NFW::i()->cfg['cookie']['name']]));
   		if (!empty($cookie_data) && count($cookie_data) == 4) {
   			list($cookie['user_id'], $cookie['password_hash'], $cookie['expiration_time'], $cookie['expire_hash']) = $cookie_data;
    	}

    	// If this a cookie for a logged in user and it shouldn't have already expired
    	if (intval($cookie['user_id']) <= 1 || ($cookie['expiration_time'] && intval($cookie['expiration_time']) <= time())) return false;

   		if (!$user = $this->authentificate(intval($cookie['user_id']), $cookie['password_hash'], true)) return false;


   		// We now validate the cookie hash
   		if ($cookie['expire_hash'] !== sha1($user['salt'].$user['password'].self::hash(intval($cookie['expiration_time']), $user['salt']))) return false;

   		// Send a new, updated cookie with a new expiration timestamp
		$this->cookie_update($user);

   		return $user;
    }

    public function cookie_update($cookie) {
    	if (!isset(NFW::i()->cfg['cookie'])) return false;

    	// Send a new, updated cookie with a new expiration timestamp
    	$expire = NFW::i()->cfg['cookie']['expire'] ? time() + NFW::i()->cfg['cookie']['expire'] : 0;

    	// Enable sending of a P3P header
    	header('P3P: CP="CUR ADM"');

    	if (version_compare(PHP_VERSION, '5.2.0', '>='))
    		setcookie(NFW::i()->cfg['cookie']['name'], base64_encode($cookie['id'].'|'.$cookie['password'].'|'.$expire.'|'.sha1($cookie['salt'].$cookie['password'].self::hash($expire, $cookie['salt']))), $expire, NFW::i()->cfg['cookie']['path'], NFW::i()->cfg['cookie']['domain'], NFW::i()->cfg['cookie']['secure'], true);
    	else
    		setcookie(NFW::i()->cfg['cookie']['name'], base64_encode($cookie['id'].'|'.$cookie['password'].'|'.$expire.'|'.sha1($cookie['salt'].$cookie['password'].self::hash($expire, $cookie['salt']))), $expire, NFW::i()->cfg['cookie']['path'].'; HttpOnly', NFW::i()->cfg['cookie']['domain'], NFW::i()->cfg['cookie']['secure']);

    	return true;
    }

    public function cookie_logout() {
    	$cookie = array('id' => 1, 'password' => 'Guest', 'salt' => 'Guest');
    	// Send a new, updated cookie with a new expiration timestamp
   		return $this->cookie_update($cookie);
    }

    /**
     * Check if given username and password is correct
     * @param int or string	User's ID or username
     * @param string		User's password
     * @param boolean		Is password hash or readable
     * @return Array 		Users's profile
     */
    public function authentificate($user, $password, $password_is_hash = false) {
		// Get user info matching login attempt
		$where = array('`is_group`=0');
		$where[] = is_int($user) ? 'id='.intval($user) : 'username=\''.NFW::i()->db->escape($user).'\'';
		if (!$result = NFW::i()->db->query_build(array('SELECT'	=> '*', 'FROM' => 'users', 'WHERE' => implode(' AND ', $where)))) {
			$this->error('Search user error', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		if (!NFW::i()->db->num_rows($result)) return false;
		
		$db_user = NFW::i()->db->fetch_assoc($result);

		if (($password_is_hash && $password != $db_user['password']) || (!$password_is_hash && self::hash($password, $db_user['salt']) != $db_user['password'])) return false;

		return $db_user;
    }

    protected function loadServicettributes() {
    	$this->service_attributes['group_id']['options'] = $this->getRecords(array('SELECT' => array('u.id', 'u.username AS `desc`'), 'filter' => array('is_group' => '1'), 'ORDER BY' => 'u.username'));
    	array_unshift($this->service_attributes['group_id']['options'], array('id' => 0, 'desc' => $this->lang['No group']));
    	
    	return parent::loadServicettributes();
    }
    
	protected function save($foo = array()) {    	if ($this->record['id']) {
    		return parent::save();
    	}

   		$salt = self::random_key(12, true);
		$password_hash = self::hash($this->record['password'], $salt);
		
		$insert = array('password', 'salt', 'registered', 'registration_ip');
		$values = array('\''.$password_hash.'\'', '\''.$salt.'\'', time(), '\''.logs::get_remote_address().'\'');
		
		foreach ($this->attributes as $varname=>$foo) {
			$insert[] = '`'.$varname.'`';
			$values[] = '\''.NFW::i()->db->escape($this->record[$varname]).'\'';
		}
			
		if (!NFW::i()->db->query_build(array('INSERT' => implode(', ', $insert), 'INTO' => $this->db_table, 'VALUES' => implode(', ', $values)))) {
			$this->error('Unable to insert record', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		
		}
		$this->record['id'] = NFW::i()->db->insert_id();
		$this->reload();
		return true;
    }

   function delete() {
   		$CPermissions = new permissions();
   		if (!$CPermissions->emptyUserRoles($this->record['id'])) {
    		$this->error('Unable to delete user roles', __FILE__, __LINE__);
    		return false;
   		}

   		return parent::delete();
    }

    /**
     * Get array with users
     */
    public function getRecords($options = array()) {
    	$filter = isset($options['filter']) ? $options['filter'] : array();
    	
    	$where = array('u.is_group='.(isset($filter['is_group']) ? intval($filter['is_group']) : '0'));
    	
    	if (isset($filter['group_id'])) {
    		$where[] = 'u.group_id='.intval($filter['group_id']);
    	}

    	$query = array(
   			'SELECT' => isset($options['SELECT']) && !empty($options['SELECT']) ? implode(', ', $options['SELECT']) : 'u.*',
    		'FROM' => $this->db_table.' AS u',
    		'WHERE' => implode (' AND ', $where),
    		'ORDER BY' => isset($options['ORDER BY']) ? $options['ORDER BY'] : 'u.id'
    	);
    	if (!$result = NFW::i()->db->query_build($query)) {
    		$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	if (!NFW::i()->db->num_rows($result)) {
    		return array();
    	}

    	$records = array();
    	while($cur_record = NFW::i()->db->fetch_assoc($result)) {
    		$records[] = $cur_record;
    	}

    	return $records;
    }

    /**
     * Validate user attributes
     *
     * @return array with errors
     */
	function validate($role = 'update', $foo = false) {
    	// Validate password (only for 'update_password')
    	if ($role == 'update_password') {
    		$errors = array();

    		if (strlen($this->record['password']) < 4) {
            	$errors['password'] = $this->lang['Errors_password_too_short'];
    		}

	    	if ($this->record['password'] != $this->record['password2']) {
	    		$errors['password'] = $errors['password2'] = $this->lang['Error_passwords_missmatch'];
	    	}

    		return $errors;
    	}

    	$errors = parent::validate($this->record, $this->attributes);

    	// Validate password (only on 'insert')
    	if (!$this->record['id'] && (!isset($this->record['password']) || strlen($this->record['password']) < 4)) {
            $errors['password'] = $this->lang['Errors_password_too_short'];
    	}

        return $errors;
    }

	function actionAdminAdmin() {
		$this->loadServicettributes();
		
		return $this->renderAction(array('records' => $this->getRecords()));
	}

	function actionAdminIp2geo() {
		require_once(NFW_ROOT.'helpers/SxGeo/SxGeo.php');
		$SxGeo = new SxGeo(PROJECT_ROOT.'var/SxGeoCity.dat');
		if (!$result = $SxGeo->getCityFull($_GET['ip'])) {
			NFW::i()->renderJSON(array('result' => 'failed'));
		}
		
		$pfix = NFW::i()->lang['lang'] == 'ru' ? '_ru' : '_en';
		
		NFW::i()->renderJSON(array(
			'result' => 'success',
			'city' => $result['city']['name'.$pfix],
			'region' => $result['region']['name'.$pfix],
			'country' => $result['country']['name'.$pfix]
		));
	}
	
    function actionAdminInsert() {
    	if (empty($_POST)) return false;

    	$this->loadServicettributes();
    	
		$this->error_report_type = 'active_form';

    	$this->formatAttributes($_POST);
    	$this->record['password'] = $_POST['password'];
    	$this->record['is_blocked'] = 0;

    	$errors = $this->validate();
    	if (strlen($this->record['password']) < 4) {
    		$errors['password'] = $this->lang['Errors_password_too_short'];
    	}
		if (!empty($errors)) {
   			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}
		
    	$this->save();
    	if ($this->error) return false;
    	
   		NFW::i()->renderJSON(array('result' => 'success', 'record_id' => $this->record['id']));
    }

    function actionAdminUpdate() {
    	$this->error_report_type = empty($_POST) ? 'default' : 'active_form';
    	
        if (!$this->load($_GET['record_id'])) return false;

        $this->loadServicettributes();
        
    	if (empty($_POST)) {
    		return $this->renderAction();
    	}

    	// Start POST'ing
    	$this->error_report_type = 'active_form';

    	$this->formatAttributes($_POST);
    	$errors = $this->validate();
		if (!empty($errors)) {
   			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

    	$is_updated = $this->save();
    	if ($this->error) return false;
    	
   		NFW::i()->renderJSON(array('result' => 'success', 'is_updated' => $is_updated));
    }

    function actionAdminUpdatePassword() {
    	$this->error_report_type = 'active_form';
        if (!$this->load($_POST['record_id'])) return false;

    	$this->record['password'] = $this->record['password2'] = $_POST['password'];
    	$errors = $this->validate('update_password');
		if (!empty($errors)) {
   			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

    	$query = array(
			'UPDATE'	=> $this->db_table,
			'SET'		=> 'password=\''.self::hash($this->record['password'], $this->record['salt']).'\'',
			'WHERE'		=> 'id='.$this->record['id']
		);
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to update users password',__FILE__, __LINE__,  NFW::i()->db->error());
			return false;
		}

		NFW::i()->renderJSON(array('result' => 'success'));
	}

    function actionAdminDelete() {
    	$this->error_report_type = 'plain';
        if (!$this->load($_POST['record_id'])) return false;

		$this->delete();
        NFW::i()->stop();
    }
}