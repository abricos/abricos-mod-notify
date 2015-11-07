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
        );
    }

    protected function GetStructures(){
        return 'Owner,Subscribe';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'ownerBaseList':
                return $this->OwnerBaseListToJSON();
            case 'subscribeSave':
                return $this->SubscribeSaveToJSON($d->ownerid, $d->subscribe);
            case 'subscribeBaseList':
                return $this->SubscribeBaseListToJSON();
        }
        return null;
    }

    protected $_cache = array();

    public function CacheClear(){
        $this->_cache = array();
    }

    private function GetOwnerApp($moduleName){
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

    private function OwnerAppFunctionExist($module, $fn){
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
     * @param NotifyOwner $ownerCont
     * @param int $itemid
     * @return NotifyOwner|int
     */
    public function OwnerItemAppend(NotifyOwner $ownerCont, $itemid){
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

        return $this->OwnerById($ownerid);
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

            /** @var NotifyOwner $owner */
            $owner = $this->InstanceClass('Owner', $d);

            $list->Add($owner);
        }

        return $this->_cache['OwnerBaseList'] = $list;
    }

    /**
     * @return NotifyOwnerList
     */
    private function OwnerCacheList(){
        if (isset($this->_cache['OwnerList'])){
            return $this->_cache['OwnerList'];
        }

        /** @var NotifyOwnerList $list */
        $list = $this->InstanceClass('OwnerList');

        return $this->_cache['OwnerList'] = $list;
    }

    /**
     * @param $ownerid
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
     * @param inte $itemid
     * @param bool|false $createIfNotFound
     * @return NotifyOwner|int
     */
    public function OwnerByContainer(NotifyOwner $ownerCont, $itemid, $createIfNotFound = false){
        $ownerList = $this->OwnerCacheList();
        $owner = $ownerList->GetByContainer($ownerCont, $itemid);

        if (!empty($owner)){
            return $owner;
        }

        $d = NotifyQuery::OwnerByContiner($this, $ownerCont, $itemid);
        if (!empty($d)){
            /** @var NotifyOwner $owner */
            $owner = $this->InstanceClass('Owner', $d);
            $ownerList->Add($owner);
            return $owner;
        }
        if (!$createIfNotFound){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        return $this->OwnerItemAppend($ownerCont, $itemid);
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
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');

        if (Abricos::$user->id === 0){
            return $list;
        }

        $rows = NotifyQuery::SubscribeBaseList($this);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifySubscribe $subscribe */
            $subscribe = $this->InstanceClass('Subscribe', $d);
            $list->Add($subscribe);
        }

        $ownerBaseList = $this->OwnerBaseList();
        $ownerCount = $ownerBaseList->Count();
        if ($list->Count() != $ownerCount){

            if ($this->_isSubscribeBaseListUpdate){
                return AbricosResponse::ERR_SERVER_ERROR;
            }

            $this->_isSubscribeBaseListUpdate = true;
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

    private function SubscribeCacheList(){
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
    public function Subscribe(NotifyOwner $owner){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if ($owner->IsBase()){
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
    public function SubscribeItemAppend($parentKey, $key, $itemid){
        $parentKey = NotifyOwner::NormalizeKey($parentKey);
        $key = NotifyOwner::NormalizeKey($key, $itemid);

        $owner = $this->OwnerAppendByKey($parentKey, $key);

        $subscribe = $this->Subscribe($owner);
        $subscribe->status = $owner->defaultStatus;
        $subscribe->emailStatus = $owner->defaultEmailStatus;

        NotifyQuery::SubscribeUpdate($this, $owner, $subscribe);

        return $owner;
    }

    /* * * * * * * * * * * * * Notify * * * * * * * * * * * * */

    /**
     * @param string $methodKey Example 'forum:topic:new'
     * @param int $itemid
     * @return NotifyOwner|int
     */
    public function NotifyAppend($methodKey, $itemid){
        $ownerMethod = $this->OwnerBaseList()->GetByKey($methodKey);
        if (empty($ownerMethod) || $ownerMethod->recordType !== NotifyOwner::TYPE_METHOD){
            return AbricosResponse::ERR_BAD_REQUEST;
        }
        $ownerCont = $ownerMethod->GetParent();

        $owner = $this->OwnerByContainer($ownerCont, $itemid, true);

        return $owner;
    }

}

?>