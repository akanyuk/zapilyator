<?php
/***********************************************************************
  Copyright (C) 2009-2013 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$  

 ************************************************************************/
class demo_studio extends active_record {
	private $projects_path = 'var/demo_studio/projects/';
	
	var $resources = array();			// System resources
	var $project_key = false;			// Current project key (folder)
	var $project_settings = array();	// Current project key (folder)
	
	var $attributes = array(
		'project_name' => array('desc' => 'Project label', 'type' => 'str', 'required' => true, 'maxlength' => 8),
		'project_desc' => array('desc' => 'Project name', 'type' => 'str', 'required' => true, 'maxlength' => 32),
		'project_author' => array('desc' => 'Project author', 'type' => 'str', 'required' => true, 'maxlength' => 32),
	);
	
	function __construct() {
		$this->projects_path = PROJECT_ROOT.$this->projects_path;
		
		// Load resources
		$fp = fopen(PROJECT_ROOT.'resources/demo_studio/resources/list.xml', 'r');
		$xml_content = fread($fp, filesize(PROJECT_ROOT.'resources/demo_studio/resources/list.xml'));
		fclose($fp);
		
		if (!$xml = simplexml_load_string($xml_content)) {
			$this->error('Wrong resources config.', __FILE__, __LINE__);
			return false;
		}
		
		foreach ($xml->resource as $x) {
			$r = array();
			foreach ($x as $varname=>$value) $r[$varname] = (string)$value;
			
			$r['params'] = array();
			foreach ($x->params->children() as $xp) {
				$params = array();
				foreach ($x as $varname=>$value) $params[$varname] = (string)$value;
				
				$r['params'][] = $params;
			}
			
			$this->resources[] = $r;
		}

		
		// Try to load curren project
		if (isset($_COOKIE['demo_studio_key'])) {
			$this->load($_COOKIE['demo_studio_key']);
		}
	}
	
	// Load project
	protected function load($key) {
		if (!$key || !file_exists($this->projects_path.$key)) return false;
		
		$fp = fopen($this->projects_path.$key.'/settings.xml', 'r');
		$config_content = fread($fp, filesize($this->projects_path.$key.'/settings.xml'));
		fclose($fp);
		
		if (!$xml = simplexml_load_string($config_content)) return false;
		if (!$xml->project_settings) return false;
		foreach ($xml->project_settings->children() as $varname=>$a) {
			$this->project_settings[$varname] = (string)$a;
		}
		
		$this->project_key = $key;
		return true;
	}
	
	// Save current project
	protected function save() {
		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		
		$xml->startElement('project');
		$xml->startElement('project_settings');
		foreach ($this->attributes as $varname=>$foo) {
			$xml->writeElement($varname, $this->project_settings[$varname]);
		}
		$xml->endElement(); // </project_settings>
		$xml->endElement(); // </project>
		
		$fp = fopen($this->projects_path.$this->project_key.'/settings.xml', 'w');
		fwrite($fp, $xml->outputMemory());
		fclose($fp);
		chmod($this->projects_path.$this->project_key.'/settings.xml', 0777);
	}
	
	function actionCreateProject($params) {
		$this->error_report_type = 'active_form';
		
		$this->project_settings = $this->formatAttributes($params['POST'], $this->attributes);
		$errors = $this->validate($this->project_settings, $this->attributes);
		if (!empty($errors)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}

		// Create project
		$chars = '0123456789';
		while(true) {
			$this->project_key = '';
			for ($i = 0; $i < 8; ++$i)
				$this->project_key .= substr($chars, (mt_rand() % strlen($chars)), 1);
				
			if (!file_exists($this->projects_path.$this->project_key)) break;
		}
		mkdir($this->projects_path.$this->project_key, 0777);
		
		$this->save();
		
		NFW::i()->setCookie('demo_studio_key', $this->project_key, time() + 60*60*24*30);
		NFW::i()->renderJSON(array('result' => 'success', 'message' => '<p>Your project key is: <strong>'.$this->project_key.'</strong></p><p>SAVE IT AT HARD STORAGE NOW!</p>'));
	}

	function actionOpenProject($params) {
		$this->error_report_type = 'active_form';
		
		if (!$this->load(isset($_POST['project_key']) ? $_POST['project_key'] : false)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => array('project_key' => 'Project not found')));
		}

		NFW::i()->setCookie('demo_studio_key', $this->project_key, time() + 60*60*24*30);
		NFW::i()->renderJSON(array('result' => 'success'));
	}
	
	function actionCloseProject($params) {
		NFW::i()->setCookie('demo_studio_key', null);
		return true;		
	}
	
	function actionSaveSettings($params) {
		$this->error_report_type = 'active_form';
	
		$this->project_settings = $this->formatAttributes($params['POST'], $this->attributes);
		$errors = $this->validate($this->project_settings, $this->attributes);
		if (!empty($errors)) {
			NFW::i()->renderJSON(array('result' => 'error', 'errors' => $errors));
		}
	
		$this->save();
		NFW::i()->renderJSON(array('result' => 'success', 'message' => 'Project settings saved.'));
	}
		
	function actionMain() {
		if (!$this->project_key) {
			return $this->renderAction(array(), '_open_project');
		}
		
		return $this->renderAction();		
	}
}