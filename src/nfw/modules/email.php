<?php
/***********************************************************************
  Copyright (C) 2007-2015 Andrew nyuk Marinov (aka.nyuk@gmail.com)
  $Id$
  
 ************************************************************************/

class email extends base_module {
	const LOGS_EMAIL_FAILED_SEND = 30;
	
	// Default language
	private $email_lang = 'en';
	// Default charset
	private $charset = 'utf-8';
	// Default mailer
	private $from = '';
	// Default mailer name
	private $from_name = 'e-mail system';
	// Attachments list
	private $attachments = array();
	
	private function addAttachment($path, $name = "", $encoding = "base64", $type = "application/octet-stream") {
		$this->attachments[] = array($path, $name, $encoding, $type);
	}
	
	private function send($recipients, $subject, $message) {
		// Load the phpmailer class
		require_once NFW_ROOT.'helpers/phpmailer/PHPMailerAutoload.php';
		$mail = new PHPMailer();
		$mail->From     = $this->from;
		$mail->FromName = $this->from_name;
		
		$mail->CharSet = $this->charset;
		$mail->Subject = $subject;
		
		// Load/override custom config
		if (isset(NFW::i()->cfg['PHPMailer']) && !empty(NFW::i()->cfg['PHPMailer'])) {
			foreach (NFW::i()->cfg['PHPMailer'] as $varname=>$value) {
				$mail->$varname = $value;
			}
		}
		
		if (substr($message, 0, 6) == '<html>') {
			// This is HTML message
			$mail->AltBody = strip_tags($message);
			$mail->MsgHTML($message);
		}
		else {
			// This is plain text message
			$mail->Body = $message;
		}
		
		if (!empty($this->attachments)) {
			foreach ($this->attachments as $a) {
				if (!$mail->AddAttachment($a[0],$a[1],$a[2],$a[3])) {
					email::error('Unable to add attachment');
					return false;
				}
			}
			$this->attachments = array();
		}
		
		if (is_string($recipients)) {
			$to = array($recipients);
		}
		else
			$to = $recipients;
			
		foreach($to as $r) {
			if (is_string($r)) {
				$mail->AddAddress($r);
			}
			else if (is_array($r)) {
				$mail->AddAddress($r["email"], '"'.$r["fullname"].'"');
			}
			else {
				email::error('Incorrect recipient address');
				return false;
			}
			
			if (!$mail->Send()) {
				self::error($mail->ErrorInfo, __FILE__, __LINE__);
				return false;
			}
			
			// Clear all addresses and attachments for next loop
			$mail->ClearAddresses();
			$mail->ClearAttachments();
		}
		
		return true;
	}
    
    /**
     * Create email from template and send to recipient.
     * If message start from <html>, automaticaly created HTML-email
     *  
     * @param $address			string	Recipient email address.
     * @param $tpl_filename		string	Template filename in folder 'email_templates' without extension.
     * @param $tpl_params		array	Variables to render in template.
     * @param $attachments		array 	Attachments filenames array.
     * 
     * @return boolean			Execution result.
     */
	public static function sendFromTemplate ($address, $tpl_filename, $tpl_params = array(), $attachments = array()) {
	    $email = new email();
	
	    if (!empty($attachments)) {
	    	foreach($attachments as $a) {
	    		if (is_array($a)) {
	    			$email->addAttachment($a['fullpath'], $a['basename']);
	    		}
	    		else {
	    			$email->addAttachment($a);
	    		}
	    	}
	    }
	    
	    $tpl_params['get_variable'] = 'message';
	    $message = trim($email->renderAction($tpl_params, 'email_templates/'.$tpl_filename, 'email'));
	    
	    $tpl_params['get_variable'] = 'subject';
	    $subject = trim($email->renderAction($tpl_params, 'email_templates/'.$tpl_filename, 'email'));

	    if (!$subject || !$message) return false;
	    
	    $tpl_params['get_variable'] = 'from';
		if ($from = trim($email->renderAction($tpl_params, 'email_templates/'.$tpl_filename, 'email'))) {
			$email->from = $from; 
		}

	    $tpl_params['get_variable'] = 'from_name';
		if ($from_name = trim($email->renderAction($tpl_params, 'email_templates/'.$tpl_filename, 'email'))) {
			$email->from_name = $from_name; 
		}
		
	    if (!$email->send($address, $subject, $message)) {
	    	logs::write($address, email::LOGS_EMAIL_FAILED_SEND); 
	    	return false;
	    }
	    else
	    	return true;
	    	
	}   

	/**
	 * Create email from string and send to recipient.
	 * If message start from <html>, automaticaly created HTML-email
	 *
	 * @param $address			string	Recipient email address.
	 * @param $subject			string	Message subject
	 * @param $message			string	Message body
	 * @param $params			array	Various unrequired params:
	 * 									'from' 		from e-mail
	 * 									'from_name' from name
	 * @param $attachments		array 	Attachments filenames array.
	 *
	 * @return boolean			Execution result.
	 */
	public static function sendFromString ($address, $subject, $message, $params = array(), $attachments = array()) {
		if (!$subject || !$message) return false;
		
		$email = new email();
	
		if (!empty($attachments)) {
			foreach($attachments as $a) {
				if (is_array($a)) {
					$email->addAttachment($a['path'], $a['name']);
				}
				else {
					$email->addAttachment($a);
				}
			}
		}
		 
		if (isset($params['from'])) {
			$email->from = $params['from'];
		}

		if (isset($params['from_name'])) {
			$email->from_name = $params['from_name'];
		}
		
		if (!$email->send($address, $subject, $message)) {
			logs::write($address, email::LOGS_EMAIL_FAILED_SEND);
			return false;
		}
		else
			return true;
	
	}	
}
