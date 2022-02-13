<?php
// $Id$

/**
 * Абстрактный класс active_record.
 *
 * @copyright 2009-2018 Andrey nyuk Marinov
 * @author Andrey nyuk Marinov (aka.nyuk@gmail.com)
 */

abstract class active_record extends base_module {
	var $attributes = array();
	var $record = false;				//	Current record
	protected $db_record = false;		// Last loaded DB-record
	protected $db_table = false;		// DB Tablename with stored records			
		
	function __construct($record_id = false, $params = array()) {
		if ($this->db_table === false) {
			$this->db_table = get_class($this);
		}
		
		// Fill undefuned attributes
		foreach ($this->attributes as &$attr) {
			if (!isset($attr['required'])) $attr['required'] = false;
			if (!isset($attr['unique'])) $attr['unique'] = false;
		}

		// Load record
		if ($record_id !== false) {
		    return $this->load($record_id, $params);
		}
		
		// Fill new record default values
		$this->record['id'] = 0;
		foreach ($this->attributes as $varname=>$attributes) {
			$this->record[$varname] = isset($attributes['default']) ? $attributes['default'] : null;
		}
		
		return parent::__construct($record_id);
   	}

   	/**
   	 * Merge main attributes with service attributes
   	 */
   	protected function loadServicettributes() {
   		$this->attributes = array_merge($this->attributes, $this->service_attributes);
   		
   		if ($this->record['id'] === false) {
   			foreach ($this->service_attributes as $varname=>$attributes) {
	   			$this->record[$varname] = isset($attributes['default']) ? $attributes['default'] : null;
	   		}
   		}   		
   	}
   	
   	/**
   	 * Default record loader
   	 * 
   	 * @param $id		int 	Record ID
   	 * @return 			Array 	Return loaded $this->record 
   	 */
	protected function load($id) {
		if (!$result = NFW::i()->db->query_build(array('SELECT' => '*', 'FROM' => $this->db_table, 'WHERE' => 'id='.intval($id)))) {
	    	$this->error('Unable to fetch record', __FILE__, __LINE__, NFW::i()->db->error());
	    	return false;
		}
	    if (!NFW::i()->db->num_rows($result)) {
	    	$this->error('Record not found ('.$id.')', __FILE__, __LINE__);
	    	return false;
	    }
	    $this->db_record = $this->record = NFW::i()->db->fetch_assoc($result);

		return $this->record;
	}

	protected function unload() {
		$this->record = array('id' => false);
		$this->db_record = null;
		
		foreach ($this->attributes as $varname=>$attributes) {
			$this->record[$varname] = isset($attributes['default']) ? $attributes['default'] : null;
		}
		
		return true;
	}
	
	protected function save($attributes = array()) {
		$attributes = empty($attributes) ? $this->attributes : $attributes;
		$is_updating = $this->record['id'] ? true : false;
		
		if ($is_updating) {
			// Check if record need to update
			$update = array();
			foreach (array_keys($attributes) as $varname) {
				$is_modified = false;
				$type = isset($attributes[$varname]['type']) ? $attributes[$varname]['type'] : 'str';
				switch ($type) {
					case 'str':
					case 'textarea':
						if (strcmp($this->record[$varname], $this->db_record[$varname]) != 0) $is_modified = true;
						break;
					default:
						if ($this->record[$varname] != $this->db_record[$varname]) $is_modified = true;
						break;
				}
			
				if (!$is_modified) continue;
			
				$update[] = '`'.$varname.'` = \''.NFW::i()->db->escape($this->record[$varname]).'\'';
			}
			if (empty($update)) return false;
			
			if (!NFW::i()->db->query_build(array('UPDATE' => $this->db_table, 'SET' => implode(', ', $update), 'WHERE' => 'id='.$this->record['id']))) {
				$this->error('Unable to update record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
		}
		else {
		    $insert = $values = array();
			foreach (array_keys($attributes) as $varname) {
				$insert[] = '`'.$varname.'`';
				$values[] = '\''.NFW::i()->db->escape($this->record[$varname]).'\'';
			}
				
			if (!NFW::i()->db->query_build(array('INSERT' => implode(', ', $insert), 'INTO' => $this->db_table, 'VALUES' => implode(', ', $values)))) {
				$this->error('Unable to insert record', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
			$this->record['id'] = NFW::i()->db->insert_id();
		}
		
		// Add service information.
		// Only if presented all needfull fields
		$sql = 'SHOW COLUMNS FROM '.NFW::i()->db->prefix.$this->db_table;
		if (!$result = NFW::i()->db->query($sql)) {
			$this->error('Unable to fetch table information.', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		$available_table_rows = array();
		while($row = NFW::i()->db->fetch_assoc($result)) {
			$available_table_rows[] = $row['Field'];
		}
		
		if ($is_updating) {
			$diff = array_diff(array('edited_by', 'edited_username', 'edited_ip', 'edited'), $available_table_rows);
			$set = 'edited_by='.NFW::i()->user['id'].', edited_username=\''.NFW::i()->db->escape(NFW::i()->user['username']).'\', edited_ip=\''.logs::get_remote_address().'\', edited='.time();
		}
		else {
			$diff = array_diff(array('posted_by', 'posted_username', 'poster_ip', 'posted'), $available_table_rows);
			$set = 'posted_by='.NFW::i()->user['id'].', posted_username=\''.NFW::i()->db->escape(NFW::i()->user['username']).'\', poster_ip=\''.logs::get_remote_address().'\', posted='.time();
		}

		if (empty($diff)) {
			if (!NFW::i()->db->query_build(array('UPDATE' => $this->db_table, 'SET' => $set, 'WHERE' => 'id='.$this->record['id']))) {
				$this->error('Unable to add service information', __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
		}
		
		$this->reload();
		
		return true;		
	}
	
	public function reload($id = false, $params = null) {
		return $this->load($id ? $id : $this->record['id'], $params); 
	}
	
	/* Remove current record
	 * 
	 */
	public function delete() {
		if (!$this->record['id']) {
			$this->error('Record not loaded', __FILE__, __LINE__);
			return false;
		}
		
		if (!NFW::i()->db->query_build(array('DELETE' => $this->db_table, 'WHERE' => 'id='.$this->record['id']))) { 
			$this->error('Unable to delete record', __FILE__, __LINE__, NFW::i()->db->error());
			return false;
		}
		
		$this->unload();
		return true;
	}
	
	public function validate($record = false, $attributes = false) {
		$record = $record ? $record : $this->record;
		$attributes = $attributes ? $attributes : $this->attributes;
		
		$errors = parent::validate($record, $attributes);
    	
		// Validate 'unique' values
		foreach($attributes as $varname=>$attribute) {
			if (isset($errors[$varname])) continue;	// Already wrong
			if (!isset($attribute['unique']) || !$attribute['unique'] || !isset($record[$varname]) || !$record[$varname]) continue;
			
    		$error_varname = isset($attribute['desc']) ? $attribute['desc'] : $varname;
			$query = array(
				'SELECT' 	=> '*',
				'FROM'		=> $this->db_table,
				'WHERE'		=> $varname.'=\''.NFW::i()->db->escape($record[$varname]).'\''
			);
			if ($record['id']) {
				$query['WHERE'] .= ' AND id<>'.$record['id'];
			}
			if (!$result = NFW::i()->db->query_build($query)) {
				$this->error('Unable to validate '.$varname, __FILE__, __LINE__, NFW::i()->db->error());
				return false;
			}
    	
			if (NFW::i()->db->num_rows($result)) {
				$errors[$varname] = NFW::i()->lang['Errors']['Dupe1'].$error_varname.NFW::i()->lang['Errors']['Dupe2'];
			}
		}
		
		return $errors;
	}

    /**
     * Format $data by $attributes rules and store in $this->record
     *
     * @param array $data to format
     * @param array $attributes array with rules
     * @param bool $skip_store
     * @return array with affected fields
     */
    public function formatAttributes($data, $attributes = array(), $skip_store = false) {
    	$result = array();
    	
    	foreach (empty($attributes) ? $this->attributes : $attributes as $varname => $a) {
    		if (!isset($data[$varname])) continue;
    		$result[$varname] = $this->formatAttribute($data[$varname], $a, $varname);
    	}
    	
    	// Store in $this->record
    	if (!$skip_store) {
	    	foreach ($result as $varname=>$value) {
	    		$this->record[$varname] = $value;
	    	}
    	}
    	 
    	return $result;
    }
    
    public function formatAttribute($value, $rules) {
    	switch ($rules['type']) {
    		case 'custom':
    			break;
    		case 'date':
    			$value = intval($value);
    			if (isset($rules['is_end']) && $rules['is_end'] && $value) { 
    				$value = mktime(23,59,59,date("n", $value), date('j', $value), date("Y", $value));
    			}
    			break;
    			 
    		case 'int':
    			$value = intval(trim($value));
    			break;
    		case 'float':
    			$value = floatval(str_replace(',', '.', trim($value)));
    			break;
    		case 'bool':
    		case 'checkbox':
    			$value = $value ? 1 : 0;
    			break;
    		default:
    			$value = trim($value);
    	}
    	
    	return $value;
    }
}