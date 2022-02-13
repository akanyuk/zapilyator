<?php
/**
 * @desc Управление правами пользователей.
 */
class permissions extends base_module {
	public $roles = array();		// Полный список существующих ролей
	
	function __construct() {
		// Load all available roles
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> 'permissions',
			'ORDER BY'	=> 'role, module, action'
		);
	    if (!$result = NFW::i()->db->query_build($query)) {
	    	$this->error('Unable to fetch permissions', __FILE__, __LINE__, NFW::i()->db->error());
	    	return false;
	    }
    	while($record = NFW::i()->db->fetch_assoc($result)) {
    		$this->roles[$record['role']][] = $record;
    	}
		    	
    	return true;
    }
    
    private function getRoles($user_id) {
    	if (!$result = NFW::i()->db->query_build(array('SELECT'	=> 'role', 'FROM' => 'users_role', 'WHERE' => 'user_id='.intval($user_id), 'ORDER BY' => 'role'))) {
    		$this->error('Unable to fetch user roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	$roles = array();
    	while($record = NFW::i()->db->fetch_assoc($result)) {
    		$roles[] = $record['role'];
    	}
    	
    	return $roles;
    }
    
    public function getPermissions($user) {
    	$permissions = array();
    	
    	$query = array(
    		'SELECT'	=> 'DISTINCT role',
    		'FROM'		=> 'users_role',
    		'WHERE'		=> 'user_id IN ('.$user['id'].','.$user['group_id'].')'
    	);
    	if (!$result = NFW::i()->db->query_build($query)) {
    		$this->error('Unable to fetch users roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	while($role = NFW::i()->db->fetch_assoc($result)) {
    		foreach ($this->roles[$role['role']] as $r) {
    			$permissions[] = $r;
    		}
    	}
    	
    	// Expand permissions by aliases
    	foreach ($permissions as $p) {
    		if (class_exists($p['module']) && isset($p['module']::$action_aliases[$p['action']])) {
	    		foreach ($p['module']::$action_aliases[$p['action']] as $alias) {
	    			$permissions[] = $alias;
	    		}
    		}
    		
    		$mapped_module = NFW::i()->getClass($p['module'], true);
    		if ($mapped_module != $p['module'] && class_exists($mapped_module) && isset($mapped_module::$action_aliases[$p['action']])) {
    			foreach ($mapped_module::$action_aliases[$p['action']] as $alias) {
    				$permissions[] = $alias;
    			}
    		}
    	}
    		
    	return $permissions;
    }
    
    public function emptyUserRoles($user_id) {
		$query = array(
			'DELETE'	=> 'users_role',
    		'WHERE'		=> 'user_id='.intval($user_id)
		);
    	if (!NFW::i()->db->query_build($query)) {
    		$this->error('Unable to delete user roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}
    	
    	return true;
    }

    function actionAdminUpdate() {
    	if (!$CUsers = new users($_GET['user_id'])) return false;
    	 
    	if (empty($_POST)) {
    		NFW::i()->stop($this->renderAction(array(
    			'user_roles' => $this->getRoles($CUsers->record['id']),
    			'group_roles' => $this->getRoles($CUsers->record['group_id']),
    			'user' => $CUsers->record    		
    		)));
    	}

    	// Empty user's roles
    	$sql = 'DELETE FROM '.NFW::i()->db->prefix.'users_role WHERE user_id='.$CUsers->record['id'];
    	if (!NFW::i()->db->query($sql)) {
    		$this->error('Unable to empty user roles', __FILE__, __LINE__, NFW::i()->db->error());
    		return false;
    	}

    	if (!empty($_POST['roles'])) foreach (array_keys($_POST['roles']) as $rolename) {
    		if (!isset($this->roles[$rolename])) continue;
    		    		
    		$sql = 'INSERT INTO '.NFW::i()->db->prefix.'users_role (user_id, role) VALUES ('.$CUsers->record['id'].', \''.NFW::i()->db->escape($rolename).'\')';
    		if (!NFW::i()->db->query($sql)) {
    			$this->error('Unable to insert user role', __FILE__, __LINE__, NFW::i()->db->error());
    			return false;
    		}
    	}
    	
    	logs::write('UID='.$CUsers->record['id'], logs::KIND_PERMISSIONS_UPDATE_ROLES);
    	NFW::i()->renderJSON(array('result' => 'success'));
    }
}