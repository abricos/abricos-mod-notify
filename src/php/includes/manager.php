<?php

/**
 * @package Abricos
 * @subpackage Notify
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @author Alexander Kuzmin (roosit@abricos.org)
 */
class NotifyManager extends Ab_Notification {

    /**
     *
     * @var NotifyModule
     */
    public $module = null;

    /**
     *
     * @var Ab_Database
     */
    public $db = null;

    private $emlcounter = 1;

    public function NotifyManager(NotifyModule $module){
        $this->module = $module;
        $this->db = Abricos::$db;
    }

    public function SendMail($email, $subject, $message, $from = '', $fromName = ''){
        $cfg = isset(Abricos::$config['module']['notify']) ? Abricos::$config['module']['notify'] : array();

        if (isset($cfg['totestfile']) && $cfg['totestfile']){

            $filepath = CWD."/cache/eml";
            @mkdir($filepath);
            $filename = $filepath."/".date("YmdHis", time())."-".($this->emlcounter++).".htm";

            $fh = @fopen($filename, 'a');

            if (!$fh){
                return false;
            }

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
        } else {
            return $this->SendByMailer($email, $subject, $message, $from, $fromName);
        }
    }

    /**
     * Отправка почты через PHPMailer
     *
     * Настройка SMTP в файле config.php
     * $config["module"]["notify"] = array(
     *    "SMTP" => true,                           // Использовать SMTP
     *    "SMTPHost" => "mail.yourdomain.com",      // SMTP сервер
     *    "SMTPPort" => 26,                         // SMTP порт
     *    "SMTPAuth" => true,                       // использовать авторизацию SMTP
     *    "SMTPSecure" => true,                     // использовать SMTP Secure
     *    "SMTPUsername" => "yourname@youdomain",   // имя пользователя SMTP
     *    "SMTPPassword" => "yourpassword",         // пароль пользователя SMTP
     *
     *  // Если необходима предварительная POP3 авторизация
     *  "POPBefore" => false,                       // Использовать POP3
     *  "POPHost" => "mail.youdomain.com",          // POP3 сервер
     *    "POPPort" => 110,                         // POP3 порт
     *    "POPUsername" => "yourname@youdomain",    // имя пользователя POP3
     *    "POPPassword" => "yourpassword"           // пароль пользователя POP3
     * );
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param string $from
     * @param string $fromName
     */

    public function SendByMailer($email, $subject, $message, $from = '', $fromName = ''){

        $cfg = &Abricos::$config['module']['notify'];

        $scriptPath = CWD."/vendor/PHPMailer/";

        require_once $scriptPath.'class.phpmailer.php';

        $mailer = new PHPMailer();

        $mailer->FromName = SystemModule::$instance->GetPhrases()->Get('site_name');
        $mailer->From = SystemModule::$instance->GetPhrases()->Get('admin_mail');
        $mailer->AltBody = "To view the message, please use an HTML compatible email viewer!";
        $mailer->Priority = 3;
        $mailer->CharSet = "utf-8";

        if (!$mailer->ValidateAddress($email)){
            return false;
        }

        if ($cfg['POPBefore']){ // авторизация POP перед SMTP
            require_once $scriptPath.'class.pop3.php';
            $pop = new POP3();
            $pop->Authorise($cfg['POPHost'], $cfg['POPPort'], 30, $cfg['POPUsername'], $cfg['POPPassword']);
        }

        if ($cfg['SMTP']){ // использовать SMTP
            require_once $scriptPath.'class.smtp.php';
            $mailer->IsSMTP();
            $mailer->Host = $cfg['SMTPHost'];
            if (intval($cfg['SMTPPort']) > 0){
                $mailer->Port = intval($cfg['SMTPPort']);
            }

            if ($cfg['SMTPAuth']){
                $mailer->SMTPAuth = true;
                $mailer->Username = $cfg['SMTPUsername'];
                $mailer->Password = $cfg['SMTPPassword'];
                $mailer->SMTPSecure = $cfg['SMTPSecure'];
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
            $str .= $mailer->ErrorInfo."\n";

            fwrite($fh, $str);
            fflush($fh);
            fclose($fh);
        }

        return $result;
    }
}

?>