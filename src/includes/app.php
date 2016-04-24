<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'app/owner.php';
require_once 'app/subscribe.php';

/**
 * Class NotifyApp
 *
 * @property NotifyManager $manager
 */
class NotifyApp extends AbricosApplication {

    protected function GetAppClasses(){
        return array(
            'Owner' => 'NotifyAppOwner',
            'Subscribe' => 'NotifyAppSubscribe'
        );
    }

    protected function GetClasses(){
        return array(
            'Event' => 'NotifyEvent',
            'EventList' => 'NotifyEventList',
            'Summary' => 'NotifySummary',
            'SummaryList' => 'NotifySummaryList',
            'Notice' => 'NotifyNotice',
            'NoticeList' => 'NotifyNoticeList',
            'Config' => 'NotifyConfig',
            'Mail' => 'NotifyMail',
            'MailList' => 'NotifyMailList',
        );
    }

    protected function GetStructures(){
        return 'Summary,Mail,Config';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'summaryList':
                return $this->SummaryListToJSON();
            case 'mailList':
                return $this->MailListToJSON();
            case 'mail':
                return $this->MailToJSON($d->mailid);
            case 'mailTestSend':
                return $this->MailTestSendToJSON($d->email);
            case "config":
                return $this->ConfigToJSON();
            case "configSave":
                return $this->ConfigSaveToJSON($d->config);
        }
        return null;
    }

    /**
     * @return NotifyAppOwner
     */
    public function Owner(){
        return $this->GetChildApp('Owner');
    }

    /**
     * @return NotifyAppSubscribe
     */
    public function Subscribe(){
        return $this->GetChildApp('Subscribe');
    }

    public function ActivityUpdate($key, $itemid){
        if (!isset($this->_cache['ActivityUpdate'])){
            $this->_cache['ActivityUpdate'] = array();
        }
        $cacheKey = $key.":".$itemid;
        if (isset($this->_cache['ActivityUpdate'][$cacheKey])){
            return;
        }
        $this->_cache['ActivityUpdate'][$cacheKey] = true;

        $ownerItem = $this->Owner()->ItemByKey($key, $itemid);
        if (AbricosResponse::IsError($ownerItem)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        NotifyQuery::ActivityUpdate($this, $ownerItem);

        $noticeList = $this->NoticeListByOwnerItemIds($key, array($itemid));
        NotifyQuery::NoticeRemove($this, $noticeList);
    }

    public function EventAppend($key, $itemid){
        $ownerMethod = $this->Owner()->ItemMethodByKey($key, $itemid);
        if (AbricosResponse::IsError($ownerMethod)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $eventid = NotifyQuery::EventAppend($this, $ownerMethod);
    }

    /**
     * @return NotifyEventList|null
     */
    public function EventListByWaiting(){
        $list = null;
        $rows = NotifyQuery::EventListByExpect($this);
        while (($d = $this->db->fetch_array($rows))){
            if (empty($list)){
                /** @var NotifyEventList $list */
                $list = $this->InstanceClass('EventList');
            }
            $event = $this->InstanceClass('Owner', $d);
            $list->Add($event);
        }
        return $list;
    }

    public function Cron(){
        if (isset($this->_cache['Cron'])){
            return;
        }
        $this->_cache['Cron'] = true;
        $list = $this->EventListByExpect();
        $cnt = $list->Count();
        for ($i = 0; $i < $cnt; $i++){

        }
    }

    public function SummaryListToJSON(){
        $ret = $this->SummaryList();
        return $this->ResultToJSON('summaryList', $ret);
    }

    public function SummaryList(){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifySummaryList $list */
        $list = $this->InstanceClass('SummaryList');

        $rows = NotifyQuery::SummaryList($this);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Summary', $d));
        }
        return $list;
    }

    /**
     * @param $key
     * @param $ids
     * @return NotifyNoticeList
     */
    public function NoticeListByOwnerItemIds($key, $ids){
        $pkey = NotifyOwner::ParseKey($key);

        /** @var NotifyNoticeList $list */
        $list = $this->InstanceClass('NoticeList');

        $rows = NotifyQuery::NoticeListByOwnerItemIds($this, $pkey, $ids);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Notice', $d));
        }
        return $list;
    }

    /* * * * * * * * * * Config * * * * * * * * * * */

    public function ConfigToJSON(){
        $res = $this->Config();
        return $this->ResultToJSON('config', $res);
    }

    /**
     * @return NotifyConfig
     */
    public function Config(){

        if (isset($this->_cache['Config'])){
            return $this->_cache['Config'];
        }

        $d = isset(Abricos::$config['module']['notify']) ?
            Abricos::$config['module']['notify'] : array();


        $sysPhrases = Abricos::GetModule('sys')->GetPhrases();
        $d['fromName'] = $sysPhrases->Get('site_name');
        $d['fromEmail'] = $sysPhrases->Get('admin_mail');

        // $phrases = $this->manager->module->GetPhrases();
        /*
        for ($i = 0; $i < $phrases->Count(); $i++){
            $ph = $phrases->GetByIndex($i);
            $d[$ph->id] = $ph->value;
        }
        /**/

        $config = $this->InstanceClass('Config', $d);

        $this->_cache['Config'] = $config;

        return $config;
    }

    public function ConfigSaveToJSON($sd){
        $this->ConfigSave($sd);
        return $this->ConfigToJSON();
    }

    public function ConfigSave($sd){
        if (!$this->manager->IsAdminRole()){
            return 403;
        }
        $utmf = Abricos::TextParser(true);

        // $phs = FeedbackModule::$instance->GetPhrases();
        // $phs->Set("adm_emails", $utmf->Parser($sd->adm_emails));

        Abricos::$phrases->Save();
    }


    /* * * * * * * * * * Mail * * * * * * * * * * */

    private static $_globalCounter = 1;

    /**
     * @param $toEmail
     * @param $subject
     * @param $body
     *
     * @return NotifyMail
     */
    public function MailByFields($toEmail, $subject, $body){
        /** @var NotifyMail $mail */
        $mail = $this->InstanceClass('Mail');

        $mail->toEmail = $toEmail;
        $mail->subject = $subject;
        $mail->body = $body;

        $config = $this->Config();
        $mail->fromName = $config->fromName;
        $mail->fromEmail = $config->fromEmail;
        $mail->userid = Abricos::$user->id;

        $mail->globalid = md5(TIMENOW + (NotifyApp::$_globalCounter++));

        return $mail;
    }

    public function MailSend(NotifyMail $mail){
        $mail->id = intval($mail->id);

        $config = $this->Config();

        if ($mail->id === 0){
            $mail->isDebug = !!$config->totestfile;
            $mail->id = NotifyQuery::MailAppend($this, $mail);

            if (!$config->totestfile){
                return;
            }
        }

        if ($mail->sendDate > 0){
            return;
        }

        $this->manager->module->ScriptRequireOnce('includes/phpmailer/class.phpmailer.php');

        $config = $this->Config();

        $mailer = new PHPMailer();
        $mailer->FromName = $mail->fromName;
        $mailer->From = $mail->fromEmail;
        $mailer->AltBody = "To view the message, please use an HTML compatible email viewer!";
        $mailer->Priority = 3;
        $mailer->CharSet = "utf-8";

        if ($config->POPBefore){ // авторизация POP перед SMTP
            $this->manager->module->ScriptRequireOnce('includes/phpmailer/class.pop3.php');
            $pop = new POP3();

            $authResult = @$pop->Authorise($config->POPHost, $config->POPPort, 30, $config->POPUsername, $config->POPPassword);
            if (!$authResult){
                $mail->sendError = true;
                $mail->sendErrorInfo = 'POP authorise error';
            }
        }

        if (!$mail->sendError && $config->SMTP){ // использовать SMTP
            $mailer->IsSMTP();
            $mailer->Host = $config->SMTPHost;
            if ($config->SMTPPort > 0){
                $mailer->Port = $config->SMTPPort;
            }

            if ($config->SMTPAuth){
                $mailer->SMTPAuth = true;
                $mailer->Username = $config->SMTPUsername;
                $mailer->Password = $config->SMTPPassword;
                $mailer->SMTPSecure = $config->SMTPSecure;
            }
        }

        $mailer->Subject = $mail->subject;
        $mailer->MsgHTML("<html><body>".$mail->body."</body></html>");
        $mailer->AddAddress($mail->toEmail);

        $mail->sendError = !$mailer->Send();

        if (!$mail->sendError){
            $mail->sendDate = TIMENOW;
        } else {
            $mail->sendDate = 0;
            $mail->sendErrorInfo = $mailer->ErrorInfo;
        }

        NotifyQuery::MailSetSended($this, $mail);
    }

    public function MailListToJSON(){
        $res = $this->MailList();
        return $this->ResultToJSON('mailList', $res);
    }

    /**
     * @return NotifyMailList
     */
    public function MailList(){
        if (!$this->manager->IsAdminRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifyMailList $list */
        $list = $this->InstanceClass('MailList');
        $rows = NotifyQuery::MailList($this);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->models->InstanceClass('Mail', $d));
        }
        return $list;
    }

    public function MailToJSON($mailid){
        $res = $this->Mail($mailid);
        return $this->ResultToJSON('mail', $res);
    }

    /**
     * @param $mailid
     * @return NotifyMail
     */
    public function Mail($mailid){
        if (!$this->manager->IsAdminRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $d = NotifyQuery::Mail($this, $mailid);

        if (empty($d)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        /** @var NotifyMail $mail */
        $mail = $this->InstanceClass('Mail', $d);
        return $mail;
    }

    public function MailTestSendToJSON($email){
        $res = $this->MailTestSend($email);
        return $this->ResultToJSON('mailTestSend', $res);
    }

    public function MailTestSend($email){
        if (!$this->manager->IsAdminRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $user = Abricos::$user;

        $host = "http://".($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST']);

        $notifyBrick = Brick::$builder->LoadBrickS("notify", "notifyTest");
        $v = &$notifyBrick->param->var;

        $config = $this->Config();

        $mail = $this->MailByFields(
            $email,
            $v['subject'],
            Brick::ReplaceVarByData($notifyBrick->content, array(
                "fromEmail" => $config->fromEmail,
                "email" => $email,
                "host" => $host,
                "username" => $user->username,
                "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
            ))
        );

        $this->MailSend($mail);

        return $mail;
    }

}

?>