<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Notify
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class NotifyManager extends Ab_Notification {
	
	/**
	 * 
	 * @var NotifyModule
	 */
	public $module = null;
	
	public $core = null;

	/**
	 * 
	 * @var Ab_Database
	 */
	public $db = null;
	
	public $user = null;
	public $userid = 0;
	private $emlcounter = 1;
	
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
		$cfg = &CMSRegistry::$instance->config['module']['notify'];
		
		if ($cfg['totestfile']){
			
			$filepath = CWD."/cache/eml";
			@mkdir($filepath);
			$filename = $filepath."/".date("YmdHis", time())."-".($this->emlcounter++).".htm";
			
			$fh = fopen($filename, 'a');

			if (!$fh){ return false; }
			
			$str = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" dir="ltr">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
<body class="yui-skin-sam">
	<div style="width: 600px; margin: 0 auto;">';
			
			$str .= "<p>E-mail: <b>".$email."</b><br />";
			$str .= "From: ".$from."<br />";
			$str .= "FromName: ".$fromName."<br />";
			$str .= "Subject: <b>".$subject."</b><br />";
			$str .= "Message (html code):</p>";
			$str .= '<textarea style="width: 100%; height: 300px;">';
			$str .= htmlspecialchars($message);
			$str .= '</textarea>';
			
			$str .= "<p>Message (preview):</p>";
			$str .= "<div style='background-color: #F0F0F0;'>".$message."</div>";
			
			$str .= '</div></body></html>';
			
			fwrite($fh, $str);
			fflush($fh);
			fclose($fh);
			
			return true;
		}else{
			switch($cfg['type']){
				case 'abricos':
					return $this->SendByAbricos($email, $subject, $message, $from, $fromName);
				default: 
					return $this->SendByMailer($email, $subject, $message, $from, $fromName);
			}
		}
	}
	
	
	/**
	 * Отправка почты через PHPMailer
	 * 
	 * Настройка SMTP в файле config.php
	 * $config["module"]["notify"] = array(
	 * 	"SMTP" => true,							// Использовать SMTP
	 * 	"SMTPHost" => "mail.yourdomain.com",	// SMTP сервер
	 * 	"SMTPPort" => 26,						// SMTP порт
	 * 	"SMTPAuth" => true,						// использовать авторизацию SMTP
	 * 	"SMTPUsername" => "yourname@youdomain",	// имя пользователя SMTP
	 * 	"SMTPPassword" => "yourpassword",		// пароль пользователя SMTP
	 * 
	 *  // Если необходима предварительная POP3 авторизация
	 *  "POPBefore" => false, 					// Использовать POP3
	 *  "POPHost" => "mail.youdomain.com", 		// POP3 сервер 
	 *	"POPPort" => 110,						// POP3 порт
	 *	"POPUsername" => "yourname@youdomain",	// имя пользователя POP3
	 *	"POPPassword" => "yourpassword"			// пароль пользователя POP3
	 * );
	 * 
	 * @param string $email
	 * @param string $subject
	 * @param string $message
	 * @param string $from
	 * @param string $fromName
	 */
	
	public function SendByMailer($email, $subject, $message, $from='', $fromName=''){
		
		$cfg = &CMSRegistry::$instance->config['module']['notify'];
		
		$mailer = new NotifyMailer();
		if (!$mailer->ValidateAddress($email)){
			return false;
		}
		
		if ($cfg['POPBefore']){ // авторизация POP перед SMTP
			require_once 'phpmailer/class.pop3.php';
			$pop = new POP3();
			$res = $pop->Authorise($cfg['POPHost'], $cfg['POPPort'], 30, $cfg['POPUsername'], $cfg['POPPassword']);
		}
		
		if ($cfg['SMTP']){ // использовать SMTP
			$mailer->IsSMTP();
			$mailer->Host = $cfg['SMTPHost'];
			if (intval($cfg['SMTPPort']) > 0){
				$mailer->Port = intval($cfg['SMTPPort']);
			}
			
		if ($cfg['SMTPAuth']){
				$mailer->SMTPAuth = true;
				$mailer->Username = $cfg['SMTPUsername'];
				$mailer->Password = $cfg['SMTPPassword'];
			}
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
		
		if (!$result && $cfg['errorlog']){
			$filepath = CWD."/cache/eml";
			@mkdir($filepath);
			$filename = $filepath."/error.log";
				
			$fh = fopen($filename, 'a');
		
			if (!$fh){
				return false;
			}
				
			$str = date("YmdHis", time())." ";
			$str .=  $mailer->ErrorInfo."\n";
				
			fwrite($fh, $str);
			fflush($fh);
			fclose($fh);
		}
		
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