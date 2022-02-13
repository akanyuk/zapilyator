<?php
/***********************************************************************
  Copyright (C) 2011-2015 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$  

  Админский скрипт для управления своим профилем.
		
 ************************************************************************/

class profile extends users {
	
	function __construct($record_id = false) {
		$this->db_table = 'users';
		
		return parent::__construct($record_id);
	}
	
	function actionAdminAdmin() {
		if (empty($_POST)) return $this->renderAction();
		
		// Start updating
		$this->error_report_type = 'active_form';
		
		if (!$this->load(NFW::i()->user['id'])) return false;

		// Modify allowed fields
		$this->record['language'] = isset($_POST['language']) ? $_POST['language'] : $this->record['language'];
		$this->record['country'] = $_POST['country'];
		$this->record['city'] = $_POST['city'];
		$this->record['realname'] = $_POST['realname'];
		$errors = $this->validate('update');
		
		// Validate password
		if (isset($_POST['password']) && $_POST['password']) {
	    	$this->record['password'] = $_POST['password'];
	    	$this->record['password2'] = $_POST['password2'];
	    	
	    	$errors = array_merge($errors, $this->validate('update_password'));
		}
		
		if (!empty($errors)) {
	   		NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}
	        
		// Save profile
		$is_updated = $this->save();

		// Save password
		if (isset($_POST['password']) && $_POST['password']) {
	    	$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'password=\''.self::hash($this->record['password'], $this->record['salt']).'\'',
				'WHERE'		=> 'id='.$this->record['id']
			);
			if (!NFW::i()->db->query_build($query)) {
				$this->error('Unable to update users password',__FILE__, __LINE__,  NFW::i()->db->error());
				return false;
			}
			
			$is_updated = true;
    	}
    			
    	NFW::i()->renderJSON(array('result' => 'success', 'is_updated' => $is_updated));
	}
}