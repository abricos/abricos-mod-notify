<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'owner.php';
require_once 'subscribe.php';

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
        );
    }

    protected function GetStructures(){
        return 'Summary';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'summaryList':
                return $this->SummaryListToJSON();
        }
        return null;
    }

    protected function GetAppClasses(){
        return array(
            'Owner' => 'NotifyAppOwner',
            'Subscribe' => 'NotifyAppSubscribe'
        );
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

        /** @var NoticeSummaryList $list */
        $list = $this->InstanceClass('SummaryList');

        $rows = NotifyQuery::SummaryList($this);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Summary', $d));
        }
        return $list;
    }

}

?>