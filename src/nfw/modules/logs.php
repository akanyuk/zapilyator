<?php
/**
 * @desc Модуль записи логов.
 */
class logs extends base_module {
	const KIND_LOGIN 					= 10;
	const KIND_PERMISSIONS_UPDATE_ROLES	= 14;
	
	public static function get_remote_address() {
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public static function get_browser($user_agent = false) {
		$user_agent = $user_agent ? $user_agent : $_SERVER['HTTP_USER_AGENT']; 
		// Try to determine browser
		if (isset(NFW::i()->cfg['use_browscap']) && NFW::i()->cfg['use_browscap']) {
			static $CBrowscap = false;
			
			if ($CBrowscap === false) {
				require_once NFW_ROOT.'helpers/SplClassLoader.php';
				$classLoader = new SplClassLoader('Crossjoin', NFW_ROOT.'helpers');
				$classLoader->register();
				
				$CBrowscap = new \Crossjoin\Browscap\Browscap(false);
			}
				
				
			$b = $CBrowscap->getBrowser($user_agent)->getData();
			$str =  isset($b->browser) ? $b->browser : '';
				 
			if (isset($b->version) && $b->version) $str .=  ' '.$b->version;
			if (isset($b->platform) && $b->platform) $str .= ' / '.$b->platform;
			 
			if ($str) return $str;
		}
		elseif (isset(NFW::i()->cfg['use_get_browser']) && NFW::i()->cfg['use_get_browser']) {
			if ($b = get_browser($user_agent)) {
				if (isset($b->parent)) {
					$browser =  $b->parent;
		
					if (isset($b->platform) && $b->platform)  {
						$browser .= ' / '.$b->platform;
					}
		
					return $browser;
				}
			}
		}
		 
		return '';
	}
		
	/**
	 * Write logs record
	 * @param $message	string	Logged message
	 * @param $kind		integer	Message kind
	 * 
	 * Usage:
	 * logs::write('Message');
	 * 
	 * or only `kind`:
	 * logs::write($kind);
	 * 
	 * or both `kind` and `message`:
	 * logs::write('Message', $kind);
	 *  
	 * or `kind`, `message` and `additional`:
	 * logs::write('Message', $kind, $additional);
	 *  
	 * @return true
	 */
    public static function write($message, $kind = 0, $additional = false) {
    	if (!isset(NFW::i()->cfg['write_logs']) || !NFW::i()->cfg['write_logs']) return true;
    	
    	if (!is_string($message) && !$kind) {
    		$kind = intval($message);
    		$message = '';    		
    	}
    	
    	$insert = array(
    		'posted' => time(),
    		'poster' => NFW::i()->user['id'],
    		'ip' => self::get_remote_address(),
    		'url' => $_SERVER['HTTP_HOST'] ? NFW::i()->db->escape(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) : '',
    		'message' => NFW::i()->db->escape($message),
    		'kind' => intval($kind)
    	);

    	if (isset($_SERVER['HTTP_USER_AGENT'])) {
    		$insert['user_agent'] = NFW::i()->db->escape($_SERVER['HTTP_USER_AGENT']);
    	}
    	
    	// API-пользователи, авторизованные локально (через класс SSL) не имеют поля `username`
    	if (isset(NFW::i()->user['username']) && NFW::i()->user['username']) {
    		$insert['poster_username'] = NFW::i()->user['username'];
    	}
    	
    	if ($additional) {
    		$insert['additional'] = NFW::i()->db->escape($additional);
    	}
    	
    	// Generate query
    	$varnames = $values = array();
    	foreach($insert as $varname=>$value) {
    		$varnames[] = $varname;
    		$values[] = '\''.$value.'\'';
    	}
    	if (!NFW::i()->db->query_build(array('INSERT' => implode(', ', $varnames), 'INTO' => 'logs', 'VALUES' => implode(', ', $values)))) {
    		self::error('Unable to insert log', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	
    	}

        return true;       
    }
    
    /**
     * Get array with logs
     *
     * @param array	  $options 		Options array:
     * 								'filter'		// Filter array
     * 								'kind'			// Logs with one kind or kind's array
     * 								'posted_from'	// From timestamp
     * 								'posted_to'		// To timestamp
     * 								'poster'		// User ID or array with ID's
     * 								'message'		// Logs message
     * 								'kind'			// Logs kind
     * 								'IP'			// Poster IP
     * 								'free_filter'	// Неполное совпадение с фильтром прои поиске
     * 								'IP'			// Poster IP
     * 								'limit'			// SQL LIMIT
     * 								'offset'		// SQL OFFSET
     * 								'sort_reverse'	// Reverse sorting
     *
     * @return array(
     * 			logs,				// Array with items
     * 		   )
     */
    public static function fetch($options = array()) {
    	$filter = (isset($options['filter'])) ? $options['filter'] : array();
    
    	// Setup WHERE from filter
    	$where = array();
    		
    	if (isset($filter['posted_from'])) {
    		$where[] = 'l.posted > '.intval($filter['posted_from']);
    	}
    
    	if (isset($filter['posted_to'])) {
    		$where[] = 'l.posted < '.intval($filter['posted_to']);
    	}
    
    	if (isset($filter['poster']) && $filter['poster']) {
    		$where[] = is_array($filter['poster']) ? 'l.poster IN ('.implode(',',$filter['poster']).')' : 'l.poster = '.intval($filter['poster']);
    	}
    
    	if (isset($filter['message'])) {
    		$where[] = 'l.message = \''.$filter['message'].'\'';
    	}
    
    	if (isset($filter['additional'])) {
    		$where[] = is_array($filter['additional']) ? 'l.additional IN ('.implode(',',$filter['additional']).')' : 'l.additional = \''.$filter['additional'].'\'';
    	}
    
    	if (isset($filter['kind']) && $filter['kind']) {
    		$where[] = is_array($filter['kind']) ? 'l.kind IN ('.implode(',',$filter['kind']).')' : 'l.kind= '.intval($filter['kind']);
    	}
    
    	if (isset($filter['ip'])) {
    		$where[] = 'l.ip = \''.$filter['ip'].'\'';
    	}
    
    	$where_str = count($where) ? ' WHERE '.implode(' AND ', $where) : '';
    
    	// Generate not strong "WHERE"
    	if (isset($options['free_filter']) && is_array($options['free_filter'])) {
    		$filter = $options['free_filter'];
    		$foo = array();
    		if (isset($options['free_filter']['ip'])) {
    			$foo[] = 'l.ip LIKE \'%'.NFW::i()->db->escape($filter['ip']).'%\'';
    		}
    
    		if (!empty($foo)) {
    			if ($where_str)
    				$where_str .= ' AND ('.implode(' OR ', $foo).')';
    				else
    					$where_str = ' WHERE '.implode(' OR ', $foo);
    		}
    	}
    
    	// Count filtered values
    	$sql = 'SELECT COUNT(*) FROM '.NFW::i()->db->prefix.'logs AS l'.$where_str;
    	if (!$result = NFW::i()->db->query($sql)) {
    		self::error('Unable to count logs', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	list($num_filtered) = NFW::i()->db->fetch_row($result);
    	if (!$num_filtered) {
    		return array(array(), 0);
    	}
    
    	// ----------------
    	// Fetching records
    	// ----------------
    
    	$sql_limit = (isset($options['limit']) && $options['limit']) ? ' LIMIT '.intval($options['limit']) : '';
    	$sql_offset = (isset($options['offset']) && $options['offset']) ? ' OFFSET '.intval($options['offset']) : '';
    
    	$sql_order_by = ' ORDER BY l.posted';
    	if (isset($options['sort_reverse']) && $options['sort_reverse']) {
    		$sql_order_by .= ' DESC';
    	}
    
    	$sql = 'SELECT l.* FROM '.NFW::i()->db->prefix.'logs AS l'.$where_str.$sql_order_by.$sql_limit.$sql_offset;
    	if (!$result = NFW::i()->db->query($sql)) {
    		self::error('Unable to fetch logs', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	if (!NFW::i()->db->num_rows($result)) return false;
    
    	$logs = array();
    	while ($l = NFW::i()->db->fetch_assoc($result)) {
    		$l['browser'] = self::get_browser($l['user_agent']);
    		$logs[] = $l;
    	}
    
    	return array($logs, $num_filtered);
    }    
}