<?php
/**
 * @desc Модуль работы с мультимедией (фото, файлы).
 */
class media extends active_record {
	const LOGS_MEDIA_UPLOAD = 20;
	const LOGS_MEDIA_REMOVE = 21;
	
	const CACHE_PATH = 'var/images_cache/';	// Path to images cache
	const JPEG_QUALITY = 100;				// JPEG creation quality (100 - best)

	const MAX_FILE_SIZE = 2097152;			// Default MAX_FILE_SIZE # 2Mb
	const MAX_SESSION_SIZE = 10485760;		// Default MAX_SESSION_SIZE # 10Mb
	
	protected $storage_path 		 	= 'media';				// Path for 'filesystem' storage
	protected $media_controler 		 	= 'media';				// Path for controler (making url's)
	protected $secure_storage_path 		= 'var/protected_media';
	protected $session = array();			// Current opened session
	
	var $attributes = array(
		'basename' => array('type' => 'str', 'desc' => 'Filename', 'required' => true, 'maxlength' => 64),
		'comment' => array('type' => 'textarea', 'desc' => 'Comment', 'maxlength' => 256),
	);
	
	function __construct($record_id = false, $params = array()) {
		$this->storage_path = isset(NFW::i()->cfg['media']['storage_path']) ? NFW::i()->cfg['media']['storage_path'] : $this->storage_path;
		$this->media_controler = isset(NFW::i()->cfg['media']['media_controler']) ? NFW::i()->cfg['media']['media_controler'] : $this->media_controler;
		$this->secure_storage_path = isset(NFW::i()->cfg['media']['secure_storage_path']) ? NFW::i()->cfg['media']['secure_storage_path'] : $this->secure_storage_path;

		parent::__construct($record_id, $params);
	}
	
	private function loadData(&$record) {
		if (!file_exists($record['fullpath'])) {
			$this->error('File not found in storage', __FILE__, __LINE__);
			return false;
		}
	
		ob_start();
		readfile($record['fullpath']);
		$record['data'] = ob_get_clean();
	
		return true;
	}

	private function removeExpieredSessions() {
		if (!$result = NFW::i()->db->query_build(array(
			'SELECT' => 'session_id',
			'FROM' => 'media_sessions',
			'WHERE' => 'posted < '.(time() - 86400)
		))) {
			$this->error('Unable to fetch expiried sessions', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}

		while($session = NFW::i()->db->fetch_assoc($result)) {
			$this->removeTemporaryFiles($session['session_id']);
			
			NFW::i()->db->query_build(array('DELETE' => 'media_sessions', 'WHERE' => 'session_id=\''.$session['session_id'].'\''));
		}
		
		return true;
	}
	
	private function removeTemporaryFiles($session_id) {
		$query = array(
			'SELECT'	=> 'id, basename, owner_class, secure_storage',
			'FROM'		=> $this->db_table,
			'WHERE' 	=> 'owner_id=0 AND session_id=\''.$session_id.'\''
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch temporary files', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		while($record = NFW::i()->db->fetch_assoc($result)) {
			NFW::i()->db->query_build(array('DELETE' => $this->db_table, 'WHERE' => 'id='.$record['id']));
			
			$this->removeFile($record);
		}
		
		return true;
	}
			
	private function removeFile($record) {
		if (!$record['secure_storage']) {
			@unlink(PROJECT_ROOT.$this->storage_path.'/'.$record['owner_class'].'/'.$record['basename']);
		}
		
		@unlink(PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y', $record['posted']).'/'.$record['id']);			
		@unlink(PROJECT_ROOT.$this->secure_storage_path.'/'.$record['id']);
		
		// remove cached thumbnails
		$prefix = sprintf("%08s", $record['id']);
		if ($dh = opendir(PROJECT_ROOT.self::CACHE_PATH)) {
			while (($file = readdir($dh)) !== false) {
				if (substr($file,0,8) == $prefix) {
					@unlink(PROJECT_ROOT.self::CACHE_PATH.'/'.$file);
				}
			}
			
			closedir($dh);
		}
	}
	
	protected function formatRecord($record) {
		$lang_media = NFW::i()->getLang('media');
		
		// Filesize str
		if ($record['filesize'] >= 1048576) {
			$record['filesize_str'] = number_format($record['filesize']/1048576, 2, '.', ' ').$lang_media['mb'];
		}
		elseif ($record['filesize'] >= 1024) {
			$record['filesize_str'] = number_format($record['filesize']/1024, 2, '.', ' ').$lang_media['kb'];
		}
		else {
			$record['filesize_str'] = $record['filesize'].$lang_media['b'];
		}

		$path_parts = pathinfo($record['basename']);
		$record['filename'] = $path_parts['filename'];
		$record['extension'] = isset($path_parts['extension']) ? $path_parts['extension'] : '';
		
		// icons
		NFW::i()->registerResource('icons');
		$record['icons']['16x16'] = file_exists(PROJECT_ROOT.'assets/icons/16x16/mimetypes/'.$record['extension'].'.png') ? NFW::i()->absolute_path.'/assets/icons/16x16/mimetypes/'.$record['extension'].'.png' : NFW::i()->absolute_path.'/assets/icons/16x16/mimetypes/unknown.png';
		$record['icons']['32x32'] = file_exists(PROJECT_ROOT.'assets/icons/32x32/mimetypes/'.$record['extension'].'.png') ? NFW::i()->absolute_path.'/assets/icons/32x32/mimetypes/'.$record['extension'].'.png' : NFW::i()->absolute_path.'/assets/icons/32x32/mimetypes/unknown.png';
		$record['icons']['64x64'] = file_exists(PROJECT_ROOT.'assets/icons/64x64/mimetypes/'.$record['extension'].'.png') ? NFW::i()->absolute_path.'/assets/icons/64x64/mimetypes/'.$record['extension'].'.png' : NFW::i()->absolute_path.'/assets/icons/64x64/mimetypes/unknown.png';
		
		// mime_type
		$mimetypes = array(
			"pdf"=>"application/pdf",
			"exe"=>"application/octet-stream",
			"zip"=>"application/zip",
			"doc"=>"application/msword",
			"xls"=>"application/vnd.ms-excel",
			"ppt"=>"application/vnd.ms-powerpoint",
			"gif"=>"image/gif",
			"png"=>"image/png",
			"jpeg"=>"image/jpeg",
			"jpg"=>"image/jpeg",
			"mp3"=>"audio/mpeg",
			"wav"=>"audio/x-wav",
			"ogg"=>"audio/ogg",
			"mpeg"=>"video/mpeg",
			"mpg"=>"video/mpeg",
			"mpe"=>"video/mpeg",
			"mov"=>"video/quicktime",
			"avi"=>"video/x-msvideo",
			"css"=>"text/css",
			"php"=>"text/plain",
			"htm"=>"text/plain",
			"html"=>"text/plain",
			"tpl"=>"text/plain",
			"txt"=>"text/plain",
			"diz"=>"text/plain"
		);
		$lext = strtolower($record['extension']);
		$record['mime_type'] = isset($mimetypes[$lext]) ? $mimetypes[$lext] : 'application/force-download';
		list($record['type']) = explode('/',$record['mime_type']);
		
		$record['url'] = $record['secure_storage'] ? NFW::i()->absolute_path.'/'.$this->media_controler.'/_protected/'.$record['owner_class'].'/'.$record['id'].'/'.$record['basename'] : NFW::i()->absolute_path.'/'.$this->storage_path.'/'.$record['owner_class'].'/'.$record['basename'];

		if ($record['secure_storage']) {
			$record['fullpath'] =  file_exists(PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y', $record['posted']).'/'.$record['id']) ? PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y', $record['posted']).'/'.$record['id'] : PROJECT_ROOT.$this->secure_storage_path.'/'.$record['id'];
			$record['tmb_dir'] = PROJECT_ROOT.self::CACHE_PATH.'/';
		}
		else {
			$record['fullpath'] =  PROJECT_ROOT.$this->storage_path.'/'.$record['owner_class'].'/'.$record['basename'];
		}

		if ($record['type'] == 'image') {
			$record['tmb_prefix'] = $record['secure_storage'] ? NFW::i()->absolute_path.'/'.$this->media_controler.'/_protected/'.$record['owner_class'].'/'.$record['id'].'/_tmb' : NFW::i()->absolute_path.'/'.$this->storage_path.'/'.$record['owner_class'].'/'.$record['filename'].'_tmb';
		}
				
		return $record;
	}
	
	protected function upload($file, $params = array()) {
		$lang_media = NFW::i()->getLang('media');
	
		// Make sure the upload went smooth
		if ($file['error']) switch ($file['error']) {
			case 1: // UPLOAD_ERR_INI_SIZE
			case 2: // UPLOAD_ERR_FORM_SIZE
				$this->error($lang_media['Errors']['Ambigious_file'], __FILE__, __LINE__);
				return false;
			case 3: // UPLOAD_ERR_PARTIAL
				$this->error($lang_media['Errors']['Partial_Upload'], __FILE__, __LINE__);
				return false;
			case 4: // UPLOAD_ERR_NO_FILE
				$this->error($lang_media['Errors']['No_File'], __FILE__, __LINE__);
				return false;
			default:
				// No error occured, but was something actually uploaded?
				if ($file['size'] == 0) {
					$this->error($lang_media['Errors']['No_File'], __FILE__, __LINE__);
					return false;
				}
				break;
		}
	
		if (!is_uploaded_file($file['tmp_name'])) {
			$this->error($lang_media['Errors']['Unknown'], __FILE__, __LINE__);
			return false;
		}
	
		if ($file['size'] > $this->session['MAX_FILE_SIZE']) {
			$this->error($lang_media['Errors']['File_too_big1'].$this->session['MAX_FILE_SIZE'].$lang_media['Errors']['File_too_big2'], __FILE__, __LINE__);
			return false;
		}
	
		$size = getimagesize($file['tmp_name']);
		$is_image = in_array($size['mime'], array('image/gif','image/png','image/jpeg')) ? true : false;
		
		if (isset($this->session['images_only']) && $this->session['images_only'] && !$is_image) {
			$this->error($lang_media['Errors']['Wrong_image_type'], __FILE__, __LINE__);
			return false;
		}
				
		if ($is_image && isset($this->session['image_max_x']) && $size[0] > $this->session['image_max_x']) {
			$this->error($lang_media['Errors']['Wrong_image_size'], __FILE__, __LINE__);
			return false;
		}
	
		if ($is_image && isset($this->session['image_max_y']) && $size[1] > $this->session['image_max_y']) {
			$this->error($lang_media['Errors']['Wrong_image_size'], __FILE__, __LINE__);
			return false;
		}
	
		if (isset($this->session['allowed_types']) && !empty($this->session['allowed_types'])) {
			if (!in_array($file['type'], $this->session['allowed_types'])) {
				$this->error($lang_media['Errors']['Wrong_file_type'], __FILE__, __LINE__);
				return false;
			}
		}
			
		if (isset($this->session['single_upload']) && $this->session['single_upload']) {
			// Only one file for each owner allowed
			if ($this->session['owner_id']) {
				foreach ($this->getFiles($this->session['owner_class'], $this->session['owner_id']) as $f) {
					if (!$this->load($f['id'])) continue;
					$this->delete();
				}
			}
			else {
				$this->removeTemporaryFiles($this->session['session_id']);
			}
		}
	
		// Safely filename
		if (isset($this->session['safe_filenames']) && $this->session['safe_filenames']) {
			$this->record['basename'] =  str_replace(
				array(' ', 'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'),
				array('_', 'a','b','v','g','d','e','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sch','','y','','e','yu','ya'),
				mb_convert_case($file['name'], MB_CASE_LOWER, 'UTF-8'));
			
			$this->record['basename'] = preg_replace('/[^a-zA-Z0-9.]/', '_', $this->record['basename']);
		}
		else {
			$this->record['basename'] = $file['name'];
		}
		
		if (!$this->session['secure_storage'] && file_exists(PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename'])) {
			if (isset($this->session['force_overwrite']) && $this->session['force_overwrite']) {
				@unlink(PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename']);
			}
			elseif(isset($this->session['force_rename']) && $this->session['force_rename']) {
				$target_dir = PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/';
		
				$path_parts = pathinfo($this->record['basename']);
				$filename = $path_parts['filename'];
				$extension = isset($path_parts['extension']) ? '.'.$path_parts['extension'] : '';
				$name_postfix = 0;
				while (file_exists($target_dir.$filename.'_'.$name_postfix.$extension)) {
					$name_postfix++;
				}
				
				$this->record['basename'] = $filename.'_'.$name_postfix.$extension;
			}
			else {
				$this->error($lang_media['Errors']['File_Exists'], __FILE__, __LINE__);
				return false;
			}
		}
		
		if (!$this->record['id']) {
			$this->record['filesize'] = $file['size'];
			$this->record['comment'] = isset($params['comment']) ? $params['comment'] : '';
			if (!$this->save()) return false;
		}

		if ($this->session['secure_storage']) {
			if (!file_exists(PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y'))) {
				mkdir(PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y'), 0777);
			}
				
			$target_file = PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y').'/'.$this->record['id'];
		}
		else {
			$target_file = PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename'];
		}
						
		if (!move_uploaded_file(urldecode($file['tmp_name']), $target_file)) {
			$this->error($lang_media['Errors']['Move_Error'], __FILE__, __LINE__);
			return false;
		}
				
		$this->reload();
		
		logs::write($this->record['basename'], self::LOGS_MEDIA_UPLOAD, $this->session['owner_id'].':'.$this->session['owner_class']);
		
		return true;
	}

	protected function loadSession($session_id) {
		if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => 'media_sessions', 'WHERE'  => 'session_id=\''.NFW::i()->db->escape($session_id).'\''))) {
			$this->error('Unable to fetch session', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		if (!NFW::i()->db->num_rows($result)) {
			$this->error('Media session not found.', __FILE__, __LINE__);
			return false;
		}
		$record = NFW::i()->db->fetch_assoc($result);
		
		$this->session = NFW::i()->unserializeArray($record['data']);
		
		return true;
	}
	
	protected function load($id, $options = array()) {
		if (is_array($id) && isset($id['owner_class']) && isset($id['basename'])) {
			// Load by `owner_class` && `basename`
			if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table, 'WHERE' => 'owner_class=\''.NFW::i()->db->escape($id['owner_class']).'\' AND basename=\''.NFW::i()->db->escape($id['basename']).'\''))) {
				$this->error('Unable to fetch record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
			if (!NFW::i()->db->num_rows($result)) {
				$this->error('Record not found.', __FILE__, __LINE__);
				return false;
			}
			$this->db_record = $this->record = NFW::i()->db->fetch_assoc($result);
		}
		elseif (!parent::load($id)) {
			return false;
		}

		// Check permissions (Temporary file)
		if (!$this->record['owner_id'] && $this->record['posted_by'] != NFW::i()->user['id']) {
			$this->error('Permissions denied', __FILE__, __LINE__);
			return false;
		}
		
		//  Check permissions (permanent file)
		if ($this->record['secure_storage'] && $this->record['owner_id']) {
			if (!NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_get', $this->record['owner_id'])) {
				$this->error('Permissions denied', __FILE__, __LINE__);
				return false;
			}
		}
	
		$this->record = $this->formatRecord($this->record);
		
		if (file_exists($this->record['fullpath'])) {
			if (isset($options['load_data']) && $options['load_data']) {
				if (!$this->loadData($this->record)) {
					return false;
				}
			}
		}
		else {
			$this->record['url'] = $this->record['fullpath'] = false;
		}
		
		return $this->record;
	}

	protected function save($attributes = array()) {
		if ($this->record['id']) {
			return parent::save($attributes);
		}
			
		if (isset($this->session['owner_id']) && $this->session['owner_id']) {
			$query = array(
				'INSERT'	=> 'owner_class, owner_id, secure_storage, basename, filesize, comment, posted_by, posted_username, poster_ip, posted',
				'INTO'		=> $this->db_table,
				'VALUES'	=> '\''.NFW::i()->db->escape($this->session['owner_class']).'\', '.$this->session['owner_id'].', '.intval($this->session['secure_storage']).', \''.NFW::i()->db->escape($this->record['basename']).'\', '.$this->record['filesize'].', \''.NFW::i()->db->escape($this->record['comment']).'\', '.NFW::i()->user['id'].', \''.NFW::i()->db->escape(NFW::i()->user['username']).'\', \''.logs::get_remote_address().'\','.time()
			);
		}
		else {
			$query = array(
				'INSERT'	=> 'session_id, owner_class, secure_storage, basename, filesize, comment, posted_by, posted_username, poster_ip, posted',
				'INTO'		=> $this->db_table,
				'VALUES'	=> '\''.$this->session['session_id'].'\', \''.NFW::i()->db->escape($this->session['owner_class']).'\', '.intval($this->session['secure_storage']).', \''.NFW::i()->db->escape($this->record['basename']).'\', '.$this->record['filesize'].', \''.NFW::i()->db->escape($this->record['comment']).'\', '.NFW::i()->user['id'].', \''.NFW::i()->db->escape(NFW::i()->user['username']).'\', \''.logs::get_remote_address().'\','.time()
			);
		}
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to insert record.', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
		$this->record['id'] = NFW::i()->db->insert_id();

		return true;
	}
	
	protected function calculateSessionId($owner_class, $owner_id = 0) {
		$salt = NFW::i()->user['is_guest'] ? $_SERVER['REMOTE_ADDR'] : NFW::i()->user['id'];
		return 'm'.substr(md5($salt.$owner_class.$owner_id),5,15);
	}
	
	public function validate($record = false, $attributes = false) {
		$record = $record ? $record : $this->record;
		$attributes = $attributes ? $attributes : $this->attributes;
		
		$errors = parent::validate($record, $attributes);
		
		// Validate 'basename'
		if (!isset($errors['basename']) && isset($record['basename'])) {
			$lang_media = NFW::i()->getLang('media');
			
			$matches = array();
			preg_match('/([^ a-zA-Z0-9-_&\(\)\[\].])/', $record['basename'], $matches);
			if (!empty($matches) || $record['basename'] == '.' || $record['basename'] == '..') {
				$errors['basename'] = $lang_media['Errors']['Wrong_filename'];
			}
			
			if (file_exists(dirname($record['fullpath']).'/'.$record['basename'])) {
				$errors['basename'] = $lang_media['Errors']['File_Exists'];
			}
		}
		
		return $errors;
	}
	
	public function insertFromString($data, $params) {
		if (!isset($params['owner_class'])) {
			$this->error('Missing `owner_class` during insertFromString', __FILE__, __LINE__);
			return false;
		}
		
		if (!isset($params['owner_id'])) {
			$this->error('Missing `owner_id` during insertFromString', __FILE__, __LINE__);
			return false;
		}

		if (!isset($params['basename'])) {
			$this->error('Missing `basename` during insertFromString', __FILE__, __LINE__);
			return false;
		}
		
		$this->session = array(
			'owner_class' => $params['owner_class'],
			'owner_id' => $params['owner_id'],
			'secure_storage' => isset($params['secure_storage']) && $params['secure_storage'] ? true : false);

		// Фикс неправильной работы strlen (возвращает количество символов вместо количества байт, расхождение при UTF-8)
		mb_internal_encoding("iso-8859-1");
		
		$this->record = array(
			'id' => false,
			'basename' => $params['basename'],
			'comment' => isset($params['comment']) ? $params['comment'] : '',
			'filesize' => mb_strlen($data)
		);
		
		if (!$this->save()) return false;
		
		if ($this->session['secure_storage']) {
			if (!file_exists(PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y'))) {
				mkdir(PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y'), 0777);
			}
		
			$target_file = PROJECT_ROOT.$this->secure_storage_path.'/'.date('Y').'/'.$this->record['id'];
		}
		else {
			$target_file = PROJECT_ROOT.$this->storage_path.'/'.$this->session['owner_class'].'/'.$this->record['basename'];
		}
						
		if (file_exists($target_file) && !isset($params['force_overwrite'])) {
			$lang_media = NFW::i()->getLang('media');
			$this->error($lang_media['Errors']['File_Exists'], __FILE__, __LINE__);
			return false;
		}
		
		$fp = fopen($target_file, 'w');
		fwrite($fp, $data);
		fclose($fp);		
		
		$this->reload();
		
		// Add file position
		NFW::i()->db->query('UPDATE '.NFW::i()->db->prefix.$this->db_table.' SET position=(SELECT next_pos FROM(SELECT MAX(position) + 1 AS next_pos FROM '.NFW::i()->db->prefix.$this->db_table.' AS tmp WHERE owner_class="'.$this->record['owner_class'].'" AND owner_id='.$this->record['owner_id'].') AS tmp) WHERE id='.$this->record['id']);
		
		return true;
	}

	public function delete() {
		$record = $this->record;
		if (!parent::delete()) return false;
		
		$this->removeFile($record);
		return true;
	}
		
	/* 
	 * $options - session data
	 * $form_data - direct bypass to form without session saving
	 *   
	 * Available options:
	 * owner_class		string 	required!
	 * owner_id			int 	if not set, required `closeSession` triggering for save temporary files
	 * secure_storage	bool	store files secure or not
	 * allow_reload		bool	allow reload file or not
	 * preload_media	bool	load media list or not
	 * single_upload	bool	one owner - one file
	 * safe_filenames	bool	rename russian filenames
	 * force_overwrite	bool	overwrite exists files
	 * force_rename		bool	rename new file if exists 
	 * images_only		bool 	only images (png, jpg, gif)
	 * image_max_x		int
	 * image_max_y		int
	 * MAX_FILE_SIZE	int
	 * MAX_SESSION_SIZE	int 
	 */
	public function openSession($options, $form_data = array()) {
		if (!$this->removeExpieredSessions()) return false;
		
		$_data = array();
		
		// Load defaults
		foreach (isset(NFW::i()->cfg['media']['defaults']) ? NFW::i()->cfg['media']['defaults'] : array() as $varname=>$value) {
			$_data[$varname] = $value;
		}

		$_data = array_merge($_data, $options);
		
		// Try open session
		
		if (!isset($_data['owner_class'])) {
			$this->error('`owner_class` - required parameter', __FILE__, __LINE__);
			return false;
		}
		
		$_data['owner_id'] = isset($_data['owner_id']) ? $_data['owner_id'] : 0;
		$_data['secure_storage'] = isset($_data['secure_storage']) && $_data['secure_storage'] ? true : false;
		
		if (!$_data['secure_storage'] && !file_exists(PROJECT_ROOT.$this->storage_path.'/'.$_data['owner_class'])) {
			$this->error('Storage path not found: '.PROJECT_ROOT.$this->storage_path.'/'.$_data['owner_class'], __FILE__, __LINE__);
			return false;
		}
		
		if ($_data['secure_storage'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $_data['owner_class']), 'media_upload', $_data['owner_id'])) {
			$lang_media = NFW::i()->getLang('media');
			$this->error($lang_media['Errors']['No_Permissions'], __FILE__, __LINE__);
			return false;
		}
		
		if (!isset($_data['MAX_FILE_SIZE'])) {
			$_data['MAX_FILE_SIZE'] = isset(NFW::i()->cfg['media']['MAX_FILE_SIZE']) ? NFW::i()->cfg['media']['MAX_FILE_SIZE'] : self::MAX_FILE_SIZE;
		}

		if (!isset($_data['MAX_SESSION_SIZE'])) {
			$_data['MAX_SESSION_SIZE'] = isset(NFW::i()->cfg['media']['MAX_SESSION_SIZE']) ? NFW::i()->cfg['media']['MAX_SESSION_SIZE'] : self::MAX_SESSION_SIZE;
		}

		$_data['session_id'] = $this->calculateSessionId($_data['owner_class'], $_data['owner_id']);

		// remove previously uploaded, but unconfirmed files
		$this->removeTemporaryFiles($_data['session_id']);
		
		// Remove sessions
		NFW::i()->db->query_build(array('DELETE' => 'media_sessions', 'WHERE' => 'session_id=\''.$_data['session_id'].'\''));
		
		$query = array(
			'INSERT'	=> 'session_id, data, posted, posted_by',
			'INTO'		=> 'media_sessions',
			'VALUES'	=> '\''.$_data['session_id'].'\', \''.NFW::i()->serializeArray($_data).'\', '.time().', '.NFW::i()->user['id']
		);
		if (!NFW::i()->db->query_build($query)) {
			$this->error('Unable to create session.', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
		// Render form
		
		$template_vars = array_merge($_data, $form_data);
		$template_vars['files'] = $_data['owner_id'] ? $this->getFiles($_data['owner_class'], $_data['owner_id']) : array();
		
		return $this->renderAction($template_vars, isset($_data['template']) ? $_data['template'] : 'form');
	}
	
	// Close given session
	public function closeSession($owner_class, $owner_id) {
		if (!$this->loadSession($this->calculateSessionId($owner_class))) return false;
		
		if ($num_files = $this->countSessionFiles($owner_class)) {
            // Move all files to last position
            $queryStr = 'UPDATE '.NFW::i()->db->prefix.$this->db_table.' SET position=(SELECT next_pos FROM(SELECT MAX(position) + 1 AS next_pos FROM '.NFW::i()->db->prefix.$this->db_table.' AS tmp WHERE owner_class="'.$owner_class.'" AND owner_id='.$owner_id.') AS tmp) WHERE session_id=\''.$this->session['session_id'].'\'';
            if (!NFW::i()->db->query($queryStr)) {
                $this->error('Unable to move session files at end position', __FILE__, __LINE__, NFW::i()->db->error());
            }

			$query = array('UPDATE' => $this->db_table, 'SET' => 'session_id=NULL, owner_id='.$owner_id, 'WHERE' => 'session_id=\''.$this->session['session_id'].'\'');
			if (!NFW::i()->db->query_build($query)) {
				$this->error('Unable to close session', __FILE__, __LINE__, NFW::i()->db->error());
			}
		}

		// Remove session
		NFW::i()->db->query_build(array('DELETE' => 'media_sessions', 'WHERE' => 'session_id=\''.$this->session['session_id'].'\''));
		
		return $num_files;
	}

	// Return count of session files for given $owner_class
	// If $owner_class == false - try to get files from $this->session
	public function countSessionFiles($owner_class = false) {
		// Load session for given $owner_class 
		if ($owner_class !== false) {
			if (!$this->loadSession($this->calculateSessionId($owner_class))) return false;
			$where = 'session_id=\''.$this->session['session_id'].'\'';
		}
		elseif (isset($this->session['owner_id']) && $this->session['owner_id'] && isset($this->session['owner_class']) && $this->session['owner_class']) {
			$where = 'owner_class=\''.$this->session['owner_class'].'\' AND owner_id='.$this->session['owner_id'];
		}
		elseif (isset($this->session['session_id']) && $this->session['session_id']) {
			$where = 'session_id=\''.$this->session['session_id'].'\'';
		}
		else {
			$this->error('Wrong request', __FILE__, __LINE__);
			return false;
		}
		
		if (!$result = NFW::i()->db->query_build(array('SELECT'	=> 'COUNT(*)', 'FROM' => $this->db_table, 'WHERE' => $where))) {
			$this->error('Unable to count uploaded files', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		list ($num_files) = NFW::i()->db->fetch_row($result);
		
		return $num_files;		
	}
	
	// Return full files list for given $owner_class
	// If $owner_class == false - try to get files from $this->session
	function getSessionFiles($owner_class = false) {
		// Load session for given $owner_class 
		if ($owner_class !== false) {
			if (!$this->loadSession($this->calculateSessionId($owner_class))) return false;
			$where = 'session_id=\''.$this->session['session_id'].'\'';
		}
		elseif (isset($this->session['owner_id']) && $this->session['owner_id'] && isset($this->session['owner_class']) && $this->session['owner_class']) {
			$where = 'owner_class=\''.$this->session['owner_class'].'\' AND owner_id='.$this->session['owner_id'];
		}
		elseif (isset($this->session['session_id']) && $this->session['session_id']) {
			$where = 'session_id=\''.$this->session['session_id'].'\'';
		}
		else {
			$this->error('Wrong request', __FILE__, __LINE__);
			return false;
		}
		
		if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table, 'WHERE' => $where, 'ORDER BY' => 'position, posted'))) {
			$this->error('Unable to fetch media list', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		$records = array();
		while($cur_file = NFW::i()->db->fetch_assoc($result)) {
			$records[] = $this->formatRecord($cur_file);
		}
			
		return $records;
	}
	
	// Return full files list for given `owner_class`
	// Only for unsecure storage
	function getOwnerFiles($owner_class = '') {
		if (!file_exists(PROJECT_ROOT.$this->storage_path.'/'.$owner_class)) {
			$this->error('Unknown `owner_class`', __FILE__, __LINE__);
			return false;
		}
	
		if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table,	'WHERE' => 'owner_class=\''.$owner_class.'\''))) {
			$this->error('Unable to fetch media list', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		$records = array();
		while($cur_file = NFW::i()->db->fetch_assoc($result)) {
			$records[] = $this->formatRecord($cur_file);
		}
			
		return $records;
	}
		
	// Return files by `owner_class` & `owner_id` 
	public function getFiles($owner_class = false, $owner_id = 0, $options = array()) {
		if (!$owner_id) {
			$this->error('`owner_id` required!', __FILE__, __LINE__);
			return false;
		}
		
		$where = array();
		if (strstr($owner_class, '%')) {
			// Неточное соответствие класса
			$where[]= 'owner_class LIKE \''.NFW::i()->db->escape($owner_class).'\'';
		}
		else {
			$where[] = 'owner_class=\''.NFW::i()->db->escape($owner_class).'\'';
		}
			
		if (is_array($owner_id)) {
			$where[] = 'owner_id IN('.implode(',',$owner_id).')';
		}
		else {
			$where[] = ' owner_id='.intval($owner_id);
		}

		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $this->db_table,
			'WHERE' 	=> implode (' AND ', $where), 
			'ORDER BY'  => isset($options['order_by']) ? $options['order_by'] : 'posted'
		);
		
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch media list', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
		$files = array();
		$load_data = isset($options['load_data']) && $options['load_data'] ? true : false;
		
		while($cur_file = NFW::i()->db->fetch_assoc($result)) {
			$cur_file = $this->formatRecord($cur_file);
			
			if ($load_data) {
				$this->loadData($cur_file);
			}
			
			$files[] = $cur_file;
		}
		 
		return $files;
	}
	
	/** 
	 * Special actions for CKEditor
	 **/
	
	function actionCKEList() {
		$this->error_report_type = 'plain';
		
		if (isset($_GET['owner_class'])) {
			$session_id = $this->calculateSessionId($_GET['owner_class'], isset($_GET['owner_id']) ? $_GET['owner_id'] : 0);
		}
		else {
			$this->error('Wrong request', __FILE__, __LINE__);
			return false;
		}
		
		if (!$this->loadSession($session_id)) {
			$this->error('Unable to load session', __FILE__, __LINE__);
			return false;
		}
		
		if ($this->session['owner_id'] && $this->session['secure_storage'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->session['owner_class']), 'media_get', $this->session['owner_id'])) {
			$this->error('Недостаточно прав для просмотра списка вложений.', __FILE__, __LINE__);
			return false;
		}
		
		NFW::i()->display($this->renderAction(array(
			'records' => $this->getSessionFiles(),
		), isset($this->session['records-template']) ? $this->session['records-template'] : 'CKE_list'), true);
	}
	
	function actionCKEUpload() {
		$lang_media = NFW::i()->getLang('media');
		
		if (isset($_GET['owner_class'])) {
			$session_id = $this->calculateSessionId($_GET['owner_class'], isset($_GET['owner_id']) ? $_GET['owner_id'] : 0);
		}
		else {
			NFW::i()->stop(json_encode(array('uploaded' => '0', 'error' => array('message' => 'Wrong request'))));
		}
		
		if (!$this->loadSession($session_id)) {
			NFW::i()->stop(json_encode(array('uploaded' => '0', 'error' => array('message' => 'Unable to load session'))));
		}
		
		if (!NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->session['owner_class']), 'media_upload', $this->session['owner_id'])) {
			NFW::i()->stop(json_encode(array('uploaded' => '0', 'error' => array('message' => $lang_media['Errors']['No_Permissions']))));
		}
		
		// Check MAX_SESSION_SIZE overflow
		$session_size = $_FILES['upload']['size'];
		foreach ($this->getSessionFiles() as $a) {
			$session_size += $a['filesize'];
		}
		if ($session_size > $this->session['MAX_SESSION_SIZE']) {
			NFW::i()->stop(json_encode(array('uploaded' => '0', 'error' => array('message' => $lang_media['Errors']['Session_Overflow1'].number_format($this->session['MAX_SESSION_SIZE']/(1024*1024), 2, '.', ' ').$lang_media['Errors']['Session_Overflow2']))));
		}
		
		if (!$this->upload($_FILES['upload'], $_POST)) {
			NFW::i()->stop(json_encode(array('uploaded' => '0', 'error' => array('message' => $this->last_msg))));
		}
		
		logs::write($this->record['basename'], self::LOGS_MEDIA_UPLOAD, $this->session['owner_id'].':'.$this->session['owner_class']);
		
		if (isset($this->session['after_upload']) && $this->session['after_upload']) {
			NFW::i()->registerFunction($this->session['after_upload']);
			if (function_exists($this->session['after_upload'])) {
				call_user_func($this->session['after_upload'], $this, $this->db_table);
			}
		}
		
		NFW::i()->stop(json_encode(array(
			'uploaded' => '1',
			'fileName' => $this->record['basename'],
			'url' => $this->record['url'],
		)));
	}
	
	/**
	 * Regular actions
	 **/
	
	function actionList() {
		$this->error_report_type = 'alert';
	
		if (isset($_GET['session_id'])) {
			$session_id = $_GET['session_id']; 			
		}
		elseif (isset($_GET['owner_class'])) {
			$session_id = $this->calculateSessionId($_GET['owner_class'], isset($_GET['owner_id']) ? $_GET['owner_id'] : 0);
		}
		else {
			$this->error('Wrong request', __FILE__, __LINE__);
			return false;
		}
				
		if (!$this->loadSession($session_id)) {
			$this->error('Unable to load session', __FILE__, __LINE__);
			return false;
		}
		
		if ($this->session['owner_id'] && $this->session['secure_storage'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->session['owner_class']), 'media_get', $this->session['owner_id'])) {
			$this->error('Недостаточно прав для просмотра списка вложений.', __FILE__, __LINE__);
			return false;
		}
	
		if (isset($this->session['records-template'])) {
			$tpl = $this->session['records-template'];
		}
		elseif (isset($_GET['tpl'])) {
			$tpl = $_GET['tpl'];
		}
		else {
			$tpl = 'records.js';
		}
		
		$content = $this->renderAction(array('session' => $this->session, 'records' => $this->getSessionFiles()), $tpl);
		NFW::i()->stop($content);
	}
	
	function actionSort() {
		$this->error_report_type = 'plain';
		$lang_media = NFW::i()->getLang('media');
		
		if (!isset($_GET['session_id']) || !$this->loadSession($_GET['session_id'])) {
			$this->error($lang_media['Errors']['Session_Load'], __FILE__, __LINE__);
			return false;
		}
		
		if (!NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->session['owner_class']), 'media_upload', $this->session['owner_id'])) {
			$this->error($lang_media['Errors']['No_Permissions'], __FILE__, __LINE__);
			return false;
		}
		
		foreach ($this->getSessionFiles() as $record) {
			foreach ($_POST['positions'] as $r) {
				if ($r['id'] == $record['id']) {
					NFW::i()->db->query_build(array('UPDATE' => 'media', 'SET'	=> 'position='.intval($r['position']), 'WHERE' => 'id='.intval($r['id'])));
					break;
				}
			}
		}
		
		NFW::i()->stop('success');
	}
	
	function actionUpload() {
		$this->error_report_type = 'active_form';
		$lang_media = NFW::i()->getLang('media');
		
		if (!isset($_GET['session_id']) || !$this->loadSession($_GET['session_id'])) {
			$this->error($lang_media['Errors']['Session_Load'], __FILE__, __LINE__);
			return false;
		}
		
		if (!NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->session['owner_class']), 'media_upload', $this->session['owner_id'])) {
			$this->error($lang_media['Errors']['No_Permissions'], __FILE__, __LINE__);
			return false;
		}
		
		// Check MAX_SESSION_SIZE overflow and determine next position
		$session_size = $_FILES['local_file']['size'];
		$position = 1;
		foreach ($this->getSessionFiles() as $a) {
			$session_size += $a['filesize'];
			$position = $a['position'] >= $position ? $a['position'] + 1 : $position;
		}
		if ($session_size > $this->session['MAX_SESSION_SIZE']) {
			$this->error($lang_media['Errors']['Session_Overflow1'].(number_format($this->session['MAX_SESSION_SIZE']/(1024*1024), 2, '.', ' ')).$lang_media['Errors']['Session_Overflow2'], __FILE__, __LINE__);
			return false;
		}
		
		if (!$this->upload($_FILES['local_file'], $_POST)) {
			NFW::i()->renderJSON(array('result' => 'error', 'last_message' => $this->last_msg, 'errors' => array('local_file' => $this->last_msg)));
		}
		
		NFW::i()->db->query_build(array('UPDATE' => $this->db_table, 'SET' => 'position='.$position, 'WHERE' => 'id='.$this->record['id']));
		
		if (isset($this->session['after_upload']) && $this->session['after_upload']) {
			NFW::i()->registerFunction($this->session['after_upload']);
			if (function_exists($this->session['after_upload'])) {
				call_user_func($this->session['after_upload'], $this, $this->db_table);
			}
		}
		
		NFW::i()->renderJSON(array(
			'result' => 'success',
			'iSessionSize' => $session_size,
			
			'id' => $this->record['id'],
			'type' => $this->record['type'],
			'filesize_str' => $this->record['filesize_str'],
			'posted' => $this->record['posted'],
			'posted_username' => $this->record['posted_username'],
			'url' => $this->record['url'],
			'basename' => $this->record['basename'],
			'extension' => $this->record['extension'],
			'tmb_prefix' => isset($this->record['tmb_prefix']) ? $this->record['tmb_prefix'] : null,
			'comment' => $this->record['comment'],
			'icons' => $this->record['icons']
		));
	}

	function actionMakeTmb() {
		$this->error_report_type = 'plain';
		
		if (!$this->load($_POST['file_id'])) return false;
	
		// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его владельцем.
		// Для перманентных файлов производим проверку.
		if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify', $this->record['owner_id'])) {
			$this->error('Permissions denied', __FILE__, __LINE__);
			return false;
		}
	
		$width = isset($_POST['width']) ? intval($_POST['width']) : false;
		$height = isset($_POST['height']) ? intval($_POST['height']) : false;
		$options = isset($_POST['options']) && is_array($_POST['options']) ? $_POST['options'] : array();
		$options['filename'] = sprintf("%08s", $this->record['id']);
		
		NFW::i()->registerFunction('tmb');
		NFW::i()->stop(tmb($this->record, $width, $height, $options));
	}
		
	function actionUpdate(){
		$this->error_report_type = 'active_form';
		$lang_media = NFW::i()->getLang('media');
		
		if (!$this->load($_POST['record_id'])) return false;
		
		// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его автором и имеем право редактировать.
		// Для перманентных файлов производим проверку.
		if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify')) {
			$this->error(NFW::i()->lang['Errors']['No_Permissions'], __FILE__, __LINE__);
			return false;
		}
		
		// Save
		$old_basename = $this->record['basename'];
		
		$this->formatAttributes($_POST);
		$errors = $this->validate();
		if ($old_basename == $this->record['basename']) {
			unset($errors['basename']);
		}
		elseif (!isset($errors['basename']) && !$this->record['secure_storage']) {
			// Already validated. Try to rename file
			if (!rename($this->record['fullpath'], dirname($this->record['fullpath']).'/'.$this->record['basename'])) {
				$this->error($lang_media['Errors']['Rename_error'], __FILE__, __LINE__);
				return false;
			}
		}
		
		if (!empty($errors)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

		$this->save();
		if ($this->error) return false;
		
		NFW::i()->renderJSON(array(
			'result' => 'success',
			'url' => $this->record['url'],
			'basename' => $this->record['basename'],
			'comment' => $this->record['comment'],
		));
	}
	
	function actionRemove() {
		$this->error_report_type = 'plain';
		
		if (!$this->load($_POST['file_id'])) return false;
	
		// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его автором и имеем право удалить.
		// Для перманентных файлов производим проверку.
		if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify', $this->record['owner_id'])) {
			$this->error('Permissions denied', __FILE__, __LINE__);
			return false;
		}
	
		// Store variables before `delete`
		$basename = $this->record['basename'];
		$owner_id = $this->record['owner_id'];
		$owner_class = $this->record['owner_class'];
		
		$this->delete();
		logs::write($basename, self::LOGS_MEDIA_REMOVE, $owner_id.':'.$owner_class);
		NFW::i()->stop('success');
	}
	
	/**
	 * OBSOLETE! Use `actionUpdate` instead!
	 **/
	function actionUpdateComment(){
		$this->error_report_type = 'plain';
	
		// Generate updating list
		if (!isset($_POST['comments']) && !is_array($_POST['comments'])) {
			NFW::i()->stop('success');
		}
		
		foreach($_POST['comments'] as $r) {
			if (!isset($r['file_id']) || !$this->load($r['file_id'])) continue;
	
			// Для временных файлов проверка прав не нужна, т.к. если смогли его получить, значит являемся его автором и имеем право удалить.
			// Для перманентных файлов производим проверку.
			if ($this->record['owner_id'] && !NFW::i()->checkPermissions(preg_replace('/\|.*/', '', $this->record['owner_class']), 'media_modify')) return false;
				
			if (!NFW::i()->db->query_build(array(
				'UPDATE'	=> $this->db_table,
				'SET'		=> '`comment`='.(isset($r['comment']) ? '\''.NFW::i()->db->escape(trim($r['comment'])).'\'' : 'NULL'),
				'WHERE'		=> '`id`='.$this->record['id']
			))) {
				$this->error('Unable to update record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
		}
	
		NFW::i()->stop('success');
	}
}