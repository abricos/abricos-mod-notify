<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'models.php';
require_once 'dbquery.php';

/**
 * Class NotifyApp
 *
 * @property NotifyManager $manager
 */
class NotifyApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Owner' => 'NotifyOwner',
            'OwnerList' => 'NotifyOwnerList',
            'Subscribe' => 'NotifySubscribe',
            'SubscribeList' => 'NotifySubscribeList',
            'Event' => 'NotifyEvent',
            'EventList' => 'NotifyEventList',
        );
    }

    protected function GetStructures(){
        return 'Owner,Subscribe';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'ownerBaseList':
                return $this->OwnerBaseListToJSON();
            case 'subscribeBaseList':
                return $this->SubscribeBaseListToJSON();
            case 'subscribeSave':
                return $this->SubscribeSaveToJSON($d->ownerid, $d->subscribe);
        }
        return null;
    }

    protected $_cache = array();

    public function CacheClear(){
        $this->_cache = array();
    }

    protected function GetOwnerApp($moduleName){
        if (!isset($this->_cache['app'])){
            $this->_cache['app'] = array();
        }
        if (isset($this->_cache['app'][$moduleName])){
            return $this->_cache['app'][$moduleName];
        }
        $module = Abricos::GetModule($moduleName);
        if (empty($module)){
            return null;
        }
        $manager = $module->GetManager();
        if (empty($manager)){
            return null;
        }
        if (!method_exists($manager, 'GetApp')){
            return null;
        }
        return $this->_cache['app'][$moduleName] = $manager->GetApp();
    }

    protected function OwnerAppFunctionExist($module, $fn){
        $ownerApp = $this->GetOwnerApp($module);
        if (empty($ownerApp)){
            return false;
        }
        if (!method_exists($ownerApp, $fn)){
            return false;
        }
        return true;
    }

    /* * * * * * * * * * * * * Owner * * * * * * * * * * * * */

    public function OwnerBaseAppend($d){
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);
        $owner->isBase = true;

        switch ($owner->recordType){
            case NotifyOwner::TYPE_MODULE:
                $owner->parentid = 1;
                $owner->type = '';
                $owner->method = '';
                $owner->itemid = 0;
                break;
            case NotifyOwner::TYPE_CONTAINER:
                $owner->method = '';
                $owner->itemid = 0;
                $owner->defaultStatus = NotifySubscribe::STATUS_ON;
                $owner->defaultEmailStatus = NotifySubscribe::EML_STATUS_PARENT;
                break;
            case NotifyOwner::TYPE_METHOD:
                $owner->itemid = 0;
                break;
            default:
                throw new ErrorException('Owner is not base');
        }

        return NotifyQuery::OwnerAppend($this, $owner);
    }

    /**
     * @param string|NotifyOwner $ownerCont
     * @param int $itemid
     * @return NotifyOwner|int
     */
    protected function OwnerItemAppend($ownerCont, $itemid){
        if (is_string($ownerCont)){
            $ownerCont = $this->OwnerBaseList()->GetByKey($ownerCont);
            if (empty($ownerCont)){
                return AbricosResponse::ERR_BAD_REQUEST;
            }
        }
        if ($ownerCont->recordType !== NotifyOwner::TYPE_CONTAINER){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', array(
            'parentid' => $ownerCont->id,
            'module' => $ownerCont->module,
            'type' => $ownerCont->type,
            'itemid' => $itemid,
            'status' => NotifyOwner::STATUS_ON,
            'defaultStatus' => NotifySubscribe::STATUS_ON,
            'defaultEmailStatus' => NotifySubscribe::EML_STATUS_PARENT,
            'recordType' => NotifyOwner::TYPE_ITEM
        ));

        $ownerid = NotifyQuery::OwnerAppend($this, $owner);
        if ($ownerid === 0){
            return AbricosResponse::ERR_SERVER_ERROR;
        }

        // add item subscribe methods
        $ownerList = $this->OwnerBaseList();
        $count = $ownerList->Count();
        for ($i = 0; $i < $count; $i++){
            $ownerMethod = $ownerList->GetByIndex($i);
            if (!($ownerCont->id === $ownerMethod->parentid
                && $ownerMethod->recordType === NotifyOwner::TYPE_METHOD)
            ){
                continue;
            }

            $ownerItemMethod = $this->InstanceClass('Owner', array(
                'parentid' => $ownerMethod->id,
                'module' => $ownerMethod->module,
                'type' => $ownerMethod->type,
                'method' => $ownerMethod->method,
                'itemid' => $itemid,
                'status' => NotifyOwner::STATUS_ON,
                'defaultStatus' => NotifySubscribe::STATUS_ON,
                'defaultEmailStatus' => NotifySubscribe::EML_STATUS_PARENT,
                'recordType' => NotifyOwner::TYPE_ITEM_METHOD
            ));
            NotifyQuery::OwnerAppend($this, $ownerItemMethod);
        }

        return $this->OwnerItemByContainer($ownerCont, $itemid);
    }

    public function OwnerBaseListToJSON(){
        $res = $this->OwnerBaseList();
        return $this->ResultToJSON('ownerBaseList', $res);
    }

    /**
     * @return NotifyOwnerList|int
     */
    public function OwnerBaseList(){
        if (isset($this->_cache['OwnerBaseList'])){
            return $this->_cache['OwnerBaseList'];
        }
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifyOwnerList $list */
        $list = $this->InstanceClass('OwnerList');

        $rows = NotifyQuery::OwnerBaseList($this);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Owner', $d));
        }

        return $this->_cache['OwnerBaseList'] = $list;
    }

    /**
     * @return NotifyOwnerList
     */
    protected function OwnerCacheList(){
        if (isset($this->_cache['OwnerList'])){
            return $this->_cache['OwnerList'];
        }

        /** @var NotifyOwnerList $list */
        $list = $this->InstanceClass('OwnerList');

        return $this->_cache['OwnerList'] = $list;
    }

    /**
     * @param int $ownerid
     * @return NotifyOwner
     */
    public function OwnerById($ownerid){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $owner = $this->OwnerBaseList()->Get($ownerid);
        if (!empty($owner)){
            return $owner;
        }

        return $this->OwnerItemById($ownerid);
    }

    /**
     * @param int $ownerid
     * @return NotifyOwner|int
     */
    public function OwnerItemById($ownerid){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $ownerList = $this->OwnerCacheList();

        $owner = $ownerList->Get($ownerid);
        if (!empty($owner)){
            return $owner;
        }

        $d = NotifyQuery::OwnerById($this, $ownerid);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);
        $ownerList->Add($owner);

        return $owner;
    }

    /**
     * @param NotifyOwner $ownerCont
     * @param int $itemid
     * @return NotifyOwner|int
     */
    protected function OwnerItemByContainer(NotifyOwner $ownerCont, $itemid){
        $ownerList = $this->OwnerCacheList();
        $owner = $ownerList->GetByContainer($ownerCont, $itemid);

        if (!empty($owner)){
            return $owner;
        }

        $rows = NotifyQuery::OwnerListByContainer($this, $ownerCont, $itemid);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifyOwner $own */
            $own = $this->InstanceClass('Owner', $d);
            $ownerList->Add($own);
            if ($own->recordType === NotifyOwner::TYPE_ITEM){
                $owner = $own;
            }
        }

        if (empty($owner)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        return $owner;
    }

    public function OwnerByKey($key, $itemid = 0){
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
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);
        $ownerList->Add($owner);
        return $owner;
    }

    /* * * * * * * * * * * * * Subscribe * * * * * * * * * * * * */

    public function SubscribeBaseListToJSON(){
        $res = $this->SubscribeBaseList();
        return $this->ResultToJSON('subscribeBaseList', $res);
    }

    private $_isSubscribeBaseListUpdate = false;

    public function SubscribeBaseList(){
        if (isset($this->_cache['SubscribeBaseList'])){
            return $this->_cache['SubscribeBaseList'];
        }
        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');

        if (!$this->manager->IsViewRole() || Abricos::$user->id === 0){
            return $list;
        }

        $rows = NotifyQuery::SubscribeBaseList($this);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifySubscribe $subscribe */
            $subscribe = $this->InstanceClass('Subscribe', $d);
            $list->Add($subscribe);
        }

        $ownerBaseList = $this->OwnerBaseList();

        if ($list->Count() !== $ownerBaseList->Count()){
            if ($this->_isSubscribeBaseListUpdate){
                return AbricosResponse::ERR_SERVER_ERROR;
            }
            $this->_isSubscribeBaseListUpdate = true;

            $ownerCount = $ownerBaseList->Count();
            for ($i = 0; $i < $ownerCount; $i++){
                $owner = $ownerBaseList->GetByIndex($i);
                $subscribe = $list->GetBy('ownerid', $owner->id);
                if (empty($subscribe)){
                    NotifyQuery::SubscribeAppend($this, $owner);
                }
            }
            return $this->SubscribeBaseList();
        }

        return $this->_cache['SubscribeBaseList'] = $list;
    }

    protected function SubscribeCacheList(){
        if (isset($this->_cache['SubscribeList'])){
            return $this->_cache['SubscribeList'];
        }

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');

        return $this->_cache['SubscribeList'] = $list;
    }

    /**
     * @param NotifyOwner $owner
     * @return int|NotifySubscribe
     */
    protected function Subscribe(NotifyOwner $owner){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /*
        if (!$owner->IsSubscribe()){
            return AbricosResponse::ERR_BAD_REQUEST;
        }
        /**/

        if ($owner->isBase){
            $subscribe = $this->SubscribeBaseList()->GetByOwner($owner);
        } else {
            $subscribeCacheList = $this->SubscribeCacheList();
            $subscribe = $subscribeCacheList->GetByOwner($owner);

            if (empty($subscribe)){
                $d = NotifyQuery::Subscribe($this, $owner);
                if (!empty($d)){
                    /** @var NotifySubscribe $subscribe */
                    $subscribe = $this->InstanceClass('Subscribe', $d);
                    $subscribeCacheList->Add($subscribe);
                }
            }
        }
        if (!empty($subscribe)){
            return $subscribe;
        }

        if ($owner->recordType !== NotifyOwner::TYPE_ROOT){
            if ($this->OwnerAppFunctionExist($owner->module, 'Notify_IsSubscribeAppend')){
                $ownerApp = $this->GetOwnerApp($owner->module);
                if (!$ownerApp->Notify_IsSubscribeAppend($owner)){
                    return AbricosResponse::ERR_FORBIDDEN;
                }
            }
        }
        $subscribeid = NotifyQuery::SubscribeAppend($this, $owner);
        if ($subscribeid === 0){
            return AbricosResponse::ERR_SERVER_ERROR;
        }

        return $this->Subscribe($owner);
    }

    public function SubscribeSaveToJSON($ownerid, $d){
        $res = $this->SubscribeSave($ownerid, $d);
        return $this->ResultToJSON('subscribeSave', $res);
    }

    public function SubscribeSave($ownerid, $d){
        if (!$this->manager->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        if ($ownerid instanceof NotifyOwner){
            $owner = $ownerid;
        } else {
            $owner = $this->OwnerById($ownerid);
            if (AbricosResponse::IsError($owner)){
                return AbricosResponse::ERR_BAD_REQUEST;
            }
        }

        $curSubscribe = $this->Subscribe($owner);
        if (AbricosResponse::IsError($curSubscribe)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        /** @var NotifySubscribe $subscribe */
        $subscribe = $this->InstanceClass('Subscribe', $d);

        $curSubscribe->status = $subscribe->status;
        $curSubscribe->emailStatus = $subscribe->emailStatus;

        NotifyQuery::SubscribeUpdate($this, $owner, $curSubscribe);

        return $curSubscribe;
    }

    /**
     * @param $parentKey
     * @param $key
     * @param $itemid
     * @return NotifyOwner|int
     */
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

    public function SubscribeByKey($key, $itemid = 0){
        $owner = $this->OwnerByKey($key, $itemid);
        if (AbricosResponse::IsError($owner)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        return $this->Subscribe($owner);
    }

    /* * * * * * * * * * * * * Notify * * * * * * * * * * * * */

    /**
     * Example: $notifyApp->NotifyAppendByKey('forum:topic:new', 112);
     *
     * @param string $methodKey
     * @param int $itemid
     * @return NotifyOwner|int
     */
    public function NotifyAppendByKey($methodKey, $itemid){
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