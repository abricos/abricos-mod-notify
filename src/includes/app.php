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

    protected function GetClasses(){
        return array(
            'Event' => 'NotifyEvent',
            'EventList' => 'NotifyEventList',
            'Summary' => 'NotifySummary',
            'SummaryList' => 'NotifySummaryList',
            'Notice' => 'NotifyNotice',
            'NoticeList' => 'NotifyNoticeList',
            'Config' => 'NotifyConfig'
        );
    }

    protected function GetStructures(){
        return 'Summary,Config';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'summaryList':
                return $this->SummaryListToJSON();
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

    public function Config(){
        $d = isset(Abricos::$config['module']['notify']) ?
            Abricos::$config['module']['notify'] : array();



        $phrases = $this->manager->module->GetPhrases();


        /*
        for ($i = 0; $i < $phrases->Count(); $i++){
            $ph = $phrases->GetByIndex($i);
            $d[$ph->id] = $ph->value;
        }
        /**/

        return $this->InstanceClass('Config', $d);
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

        $phs = FeedbackModule::$instance->GetPhrases();
        $phs->Set("adm_emails", $utmf->Parser($sd->adm_emails));

        Abricos::$phrases->Save();
    }

}

?>