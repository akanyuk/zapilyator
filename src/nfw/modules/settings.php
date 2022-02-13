<?php
/***********************************************************************
  Copyright (C) 2009-2015 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$  

	Редактируемые из админки настройки
  
 ************************************************************************/

class settings extends active_record {
	static $action_aliases = array(
		'update' => array(
			array('module' => 'settings', 'action' => 'admin'),
		)
	);
		
	var $attributes = array(
		'name' => array('desc'=>'Наименование', 'type'=>'str', 'required'=>true, 'unique'=>true, 'minlength'=>4, 'maxlength'=>255),
		'values' => array('desc'=>'Параметры', 'type'=>'custom'),
	);
	
	function __construct($record_id = false) {
		$this->lang = NFW::i()->getLang('settings');
	
		return parent::__construct($record_id);
	}
	
	
	private function parseConfig($msg) {
		 
		$unparsed_params = preg_split("/[\n]/", $msg);
		$new_params = array();
		$prev_parameter = '';	//	Previous parameter (for fetching comment).
	
		foreach ($unparsed_params as $p) {
			$p = trim($p);
	
			if (substr($p, 0, 1) == '#' || substr($p, 0, 1) == ';' || substr($p, 0, 2) == '//') {
				// This is comment
				$prev_parameter = $p;
				continue;
			}
	
			if ($delimiter = strpos($p, '=')) {
				$key = trim(substr($p, 0, $delimiter));
				$value = trim(substr($p, $delimiter + 1));
				 
				if (substr($value, 0, 8) == '#base64#') {
					$value = base64_decode(substr($value, 8));
				}
	
				// Finding parameter description
				$desc = (substr($prev_parameter, 0, 3) == '###') ?  trim(substr($prev_parameter, 3)) : false;
				 
				// an array in key name
				if (strpos($key, '.')) {
					$k = explode('.', $key);
					 
					switch (count($k)) {
						case 2:
							$new_params[$k[0]][$k[1]] = $value;
							if ($desc)
								$new_params[$k[0]][$k[1].'_desc'] = $desc;
							break;
						case 3:
							$new_params[$k[0]][$k[1]][$k[2]] = $value;
							if ($desc)
								$new_params[$k[0]][$k[1]][$k[2].'_desc'] = $desc;
							break;
						case 4:
							$new_params[$k[0]][$k[1]][$k[2]][$k[3]] = $value;
							if ($desc)
								$new_params[$k[0]][$k[1]][$k[2]][$k[3].'_desc'] = $desc;
							break;
						case 5:
							$new_params[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] = $value;
							if ($desc)
								$new_params[$k[0]][$k[1]][$k[2]][$k[3]][$k[4].'_desc'] = $desc;
							break;
					}
				}
				else {
					$new_params[$key] = $value;
					if ($desc)
						$new_params[$key.'_desc'] = $desc;
				}
			}
			 
			$prev_parameter = $p;
		}
		 
		return $new_params;
	}
		
	protected function load($varname) {
		$query = array(
			'SELECT'	=> '*',
			'FROM'		=> $this->db_table,
			'WHERE'		=> 'varname=\''.NFW::i()->db->escape($varname).'\'',
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch record', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		if (!NFW::i()->db->num_rows($result)) {
			$this->error('Record not found.', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		$this->db_record = $this->record = NFW::i()->db->fetch_assoc($result);
		
		$this->record['attributes'] = $this->parseConfig($this->record['attributes']);
		$this->record['values'] = NFW::i()->unserializeArray($this->record['values']);
	
		return $this->record;
	}
		
	public function reload($varname = false, $foo = null) {
		return $this->load($varname ? $varname : $this->record['varname']);
	}
	
	function getConfigs($varnames = array()) {
		$configs = array();
		
		$query = array('SELECT' => '`varname`, `values`', 'FROM' => $this->db_table);
		
		if (!empty($varnames)) {
			foreach ($varnames as &$varname) {
				$configs[$varname] = array();	// defaults
				$varname = '\''.$varname.'\'';
			}
			$query['WHERE'] = '`varname` IN ('.implode(',', $varnames).')'; 
		}
		
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
	    while($cur_record = NFW::i()->db->fetch_assoc($result)) {
	    	$configs[$cur_record['varname']] = NFW::i()->unserializeArray($cur_record['values']);
	    }
	    
	    return $configs;
	}

	function getConfig($varname) {
		$query = array(
			'SELECT' => '`values`',
			'FROM'	 => $this->db_table,
			'WHERE'	 => '`varname`=\''.NFW::i()->db->escape($varname).'\''
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
	
		list($value) = NFW::i()->db->fetch_row($result);
		return NFW::i()->unserializeArray($value);
	}
		
	function validate($record = false, $attributes = false) {
		$errors = parent::validate($record, $attributes);
		
		if (!isset($errors['name'])) {
			// Check `desc` unique
			$query = array(
				'SELECT' 	=> '*',
				'FROM'		=> $this->db_table,
				'WHERE'		=>  '`name`=\''.NFW::i()->db->escape($this->record['name']).'\' AND id<>'.$this->record['id']
			);
			if (!$result = NFW::i()->db->query_build($query)) {
				$this->error('Unable to validate record name', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
			 
			if (NFW::i()->db->num_rows($result)) {
				$errors['name'] = 'Запись с таким же наименованием уже зарегистрирована в системе';
			}
		}
		
		return $errors;
	}
	
	function actionAdminAdmin() {
		$query = array(
			'SELECT' => 'id, name, varname', 
			'FROM' => $this->db_table,
			'WHERE' => 'is_admin=1',
			'ORDER BY' => 'id'
		);
		if (!$result = NFW::i()->db->query_build($query)) {
			$this->error('Unable to fetch records', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		$records = array();
		while($cur_record = NFW::i()->db->fetch_assoc($result)) {
			$records[] = $cur_record;
		}
				
        return $this->renderAction(array(
			'records' => $records
        ));        
	}

	function actionAdminUpdate() {
		$this->error_report_type = (empty($_POST)) ? 'default' : 'active_form';
		
    	if (!$this->load($_GET['varname'])) return false;
		
    	foreach ($this->record['attributes'] as $key=>$values) {
    		if (!isset($values['type'])) {
    			$values['type'] = 'str';
    		}
    		
    		$this->record['attributes'][$key] = $values;
    	}
    	 
	    if (empty($_POST)) {
	    	if (NFW::i()->findTemplatePath('update_'.$this->record['varname'].'.tpl', get_class($this))) {
	    		$result = $this->renderAction(array(), 'update_'.$this->record['varname']);
	    	}
	    	else {
	    		$result = $this->renderAction();
	    	}
	    	
    		NFW::i()->stop($result);
    	}

	   	// Save
	   	$this->formatAttributes($_POST);

	   	// Format `values`
	   	$values = array();
	   	foreach($this->record['attributes'] as $varname=>$a) {
		   	foreach ($_POST['values'][$varname] as $index=>$cur_val) {
		   		$values[$index][$varname] = $this->formatAttribute($cur_val, $a);
		   	}
	   	}
	   	$this->record['values'] = NFW::i()->serializeArray($values);
	   	 
		$errors = $this->validate();
		if (!empty($errors)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}
		
	   	$is_updated = $this->save();
    	if ($this->error) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('general' => $this->last_msg)));
		}
		
		NFW::i()->renderJSON(array('result' => 'success', 'is_updated' => $is_updated));
	}
}