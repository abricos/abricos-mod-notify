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
        );
    }

    protected function GetStructures(){
        return '';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
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

    /*
    public function old_OwnerByKey($key, $itemid = 0){
        $itemid = intval($itemid);
        $key = NotifyOwner::NormalizeKey($key, $itemid);

        if ($itemid === 0){
            return $this->OwnerBaseList()->GetByKey($key);
        }
        $ownerList = $this->OwnerCacheList();
        $owner = $ownerList->GetByKey($key);
        if (!empty($owner)){
            return $owner;
        }

        $d = NotifyQuery::OwnerByKey($this, $key, $itemid);

        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        //** @var NotifyOwner $owner * /
        $owner = $this->InstanceClass('Owner', $d);
        $ownerList->Add($owner);
        return $owner;
    }

    public function old_SubscribeItemAppend($parentKey, $key, $itemid){
        $parentKey = NotifyOwner::NormalizeKey($parentKey);
        $key = NotifyOwner::NormalizeKey($key, $itemid);

        $owner = $this->OwnerAppendByKey($parentKey, $key);

        $subscribe = $this->Subscribe($owner);
        $subscribe->status = $owner->defaultStatus;
        $subscribe->emailStatus = $owner->defaultEmailStatus;

        NotifyQuery::SubscribeUpdate($this, $owner, $subscribe);

        return $owner;
    }

    public function old_SubscribeByKey($key, $itemid = 0){
        $owner = $this->OwnerByKey($key, $itemid);
        if (AbricosResponse::IsError($owner)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        return $this->Subscribe($owner);
    }
    /**/

    /* * * * * * * * * * * * * Notify * * * * * * * * * * * * */

    /**
     * Example: $notifyApp->NotifyAppendByKey('forum:topic:new', 112);
     *
     * @param string $methodKey
     * @param int $itemid
     * @return NotifyOwner|int
     */
    public function NotifyAppend($methodKey, $itemid){
        $ownerMethod = $this->OwnerBaseList()->GetByKey($methodKey);
        if (empty($ownerMethod) || $ownerMethod->recordType !== NotifyOwner::TYPE_METHOD){
            return AbricosResponse::ERR_BAD_REQUEST;
        }
        $ownerCont = $ownerMethod->GetParent();

        $owner = $this->OwnerItemByContainer($ownerCont, $itemid, true);

        // Добавить событие в очередь
        NotifyQuery::EventAppend($this, $owner, $ownerMethod);

        $this->EventCheck();

        return $owner;
    }

    public function EventCheck(){
        $rows = NotifyQuery::EventListByExpect($this);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifyEvent $event */
            $event = $this->InstanceClass('Owner', $d);
            NotifyQuery::EventPerfomed($this, $event);
        }
    }

    public function EventRead($key, $itemid){
        $owner = $this->OwnerByKey($key, $itemid);
        if (AbricosResponse::IsError($owner)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        NotifyQuery::ActivityUpdate($this, $owner);
    }

}

?>