<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Notify
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class NotifyManager extends Notification {
	
	/**
	 * 
	 * @var NotifyModule
	 */
	public $module = null;
	
	public $core = null;

	/**
	 * 
	 * @var CMSDatabase
	 */
	public $db = null;
	
	public $user = null;
	public $userid = 0;
	
	public function NotifyManager(NotifyModule $module){
		
		$this->module = $module;
		$core = $module->registry;
		$this->core = $core;
		$this->db = $core->db;
		
		$this->user = $core->user->info;
		$this->userid = $this->user['userid'];
	}
	
	public function SendMail($email, $subject, $message, $from='', $fromName=''){
		/*
		// настройки конфига
		$config['module']['notify']['type'] = "abricos";
		/**/
		switch(CMSRegistry::$instance->config['module']['notify']['type']){
			case 'abricos':
				return $this->SendByAbricos($email, $subject, $message, $from, $fromName);
			default: 
				return $this->SendByMailer($email, $subject, $message, $from, $fromName);
		}
	}
	
	public function SendByMailer($email, $subject, $message, $from='', $fromName=''){
		$mailer = new NotifyMailer();
		if (!$mailer->ValidateAddress($email)){
			return false;
		}
		$mailer->Subject = $subject;
		$mailer->MsgHTML($message);
		$mailer->AddAddress($email);
		if (!empty($from)){
			$mailer->From = $from;
		}
		if (!empty($fromName)){
			$mailer->FromName = $fromName;
		}
		
		$result = $mailer->Send();
		return $result;
	}
	
	public function SendByAbricos($email, $subject, $message, $from='', $fromName=''){
		$mailer = new NotifyAbricos();
		$mailer->email = $email;
		$mailer->subject = $subject;
		$mailer->message = $message;
		if (!empty($from)){
			$mailer->from = $from;
		}
		if (!empty($fromName)){
			$mailer->fromName = $fromName;
		}
		$mailer->Send();
	}
}

class NotifyAbricos {
	
	public $fromName = "";
	public $from = "";
	public $email = "";
	public $subject = "";
	public $message = "";
	
	private $host = "";
	private $password = "";
	
	public function NotifyAbricos(){
		
		$this->from = Brick::$builder->phrase->Get('sys', 'admin_mail'); 
		$this->fromName = Brick::$builder->phrase->Get('sys', 'site_name');
		
		$cfg = &CMSRegistry::$instance->config['module']['notify'];
		$this->host = $cfg['host'];
		$this->password = $cfg['password'];
	}

	private function EncodeParam($arr){
		$data = array();
		foreach($arr as $name => $value){
			array_push($data, $name."=".urlencode($value));
		}
		return implode("&", $data);
	}
	public function Send(){
		$data = $this->EncodeParam(array(
			"from"=>$this->from,
			"fromname"=>$this->fromName,
			"body"=>$this->message,
			"subject"=>$this->subject,
			"to"=>$this->email,
			"password" => $this->password
		));
		
		$fp = fsockopen($this->host, 80, $errno, $errstr, 10);
		if ($fp) {
		
			$out = "POST /mailer/send/ HTTP/1.1\r\n";
			$out .= "Host: ".$this->host."\r\n";
			$out .= "User-Agent: Opera/8.50 (Windows NT 5.1; U; ru)\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "Content-Length: ".strlen($data)."\r\n\r\n";
			$out .= $data."\r\n\r\n";
		
			fputs($fp, $out); // отправляем данные
			
			/*
			while($gets=fgets($fp,2048)){
				// print $gets;
			}
			/**/
			fclose($fp);
		}
		return true;
	}
}

class NotifyMailer extends PHPMailer {
	
	/**
	 * 
	 * @var CMSRegistry
	 */
	public $core = null;
	
	public function NotifyMailer(){
		$this->core = CMSRegistry::$instance;
		
		$this->FromName = Brick::$builder->phrase->Get('sys', 'site_name');
		$this->From = Brick::$builder->phrase->Get('sys', 'admin_mail'); 
		$this->AltBody = "To view the message, please use an HTML compatible email viewer!";
		$this->Priority = 3; 
		$this->CharSet = "utf-8";
	} 
	
	public function MsgHTML($message, $basedir=''){
		$message = "<html><body>".$message."</body></html>";
		parent::MsgHTML($message, $basedir);
	}
	
	public function Send(){
		if ($this->core->db->readonly){
			return true;
		}
		return parent::Send();
	}
}



?>