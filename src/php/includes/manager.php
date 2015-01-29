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

    private static $_counter = 1;

    /**
     *
     * @var Ab_Database
     */
    public $db = null;

    public function NotifyManager(NotifyModule $module){
        $this->module = $module;
        $this->db = Abricos::$db;
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
    public function SendMail($email, $subject, $message, $from = '', $fromName = ''){

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

        if (isset($cfg['POPBefore']) && $cfg['POPBefore']){ // авторизация POP перед SMTP
            require_once $scriptPath.'class.pop3.php';
            $pop = new POP3();
            $pop->Authorise($cfg['POPHost'], $cfg['POPPort'], 30, $cfg['POPUsername'], $cfg['POPPassword']);
        }

        if (isset($cfg['SMTP']) && $cfg['SMTP']){ // использовать SMTP
            require_once $scriptPath.'class.smtp.php';
            $mailer->IsSMTP();
            if (isset($cfg['SMTPHost'])){
                $mailer->Host = $cfg['SMTPHost'];
            }

            if (intval($cfg['SMTPPort']) > 0){
                $mailer->Port = intval($cfg['SMTPPort']);
            }

            if (isset($cfg['SMTPAuth']) && $cfg['SMTPAuth']){
                $mailer->SMTPAuth = true;
                $mailer->Username = $cfg['SMTPUsername'];
                $mailer->Password = $cfg['SMTPPassword'];
                $mailer->SMTPSecure = $cfg['SMTPSecure'];
            }
        }

        $messageId = md5($message.(NotifyManager::$_counter++));
        $this->messageId = $mailer->MessageID = $messageId;

        $mailer->Subject = $subject;
        $mailer->MsgHTML($message);
        $mailer->AddAddress($email);
        if (!empty($from)){
            $mailer->From = $from;
        }
        if (!empty($fromName)){
            $mailer->FromName = $fromName;
        }

        $this->errorInfo = "";

        $result = $mailer->Send();

        if (!$result){
            $this->errorInfo = $mailer->ErrorInfo;
        }

        return $result;
    }
}

?>