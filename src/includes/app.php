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

    public function OwnerBaseListToJSON(){
        $res = $this->OwnerBaseList();
        return $this->ResultToJSON('ownerBaseList', $res);
    }

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
     * @param $ownerid
     * @return NotifyOwner
     */
    public function OwnerById($ownerid){
        if (!isset($this->_cache['Owner'])){
            $this->_cache['Owner'] = array();
        }
        if (isset($this->_cache['Owner'][$ownerid])){
            return $this->_cache['Owner'][$ownerid];
        }
        $d = NotifyQuery::OwnerById($this, $ownerid);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);

        return $this->_cache['Owner'][$ownerid] = $owner;;
    }

    public function OwnerByKey($key, $itemid = 0){
        $key = NotifyOwner::NormalizeKey($key, $itemid);

        if (!isset($this->_cache['OwnerByKey'])){
            $this->_cache['OwnerByKey'] = array();
        }
        if (isset($this->_cache['OwnerByKey'][$key])){
            return $this->_cache['OwnerByKey'][$key];
        }
        $d = NotifyQuery::OwnerByKey($this, $key);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);

        return $this->_cache['OwnerByKey'][$key] = $owner;
    }

    /**
     * @return NotifyOwner
     */
    public function OwnerRoot(){
        if (isset($this->_cache['OwnerRoot'])){
            return $this->_cache['OwnerRoot'];
        }

        $owner = $this->OwnerById(1);
        return $this->_cache['OwnerRoot'] = $owner;
    }

    public function OwnerSave($d){
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);

        if ($owner->parentid === 0){
            $root = $this->OwnerRoot();
            $owner->parentid = $root->id;
        }

        NotifyQuery::OwnerSave($this, $owner);

        $this->CacheClear();

        return $this->OwnerByKey($owner->GetKey());
    }

    /**
     * @param $key
     * @param $parentKey
     * @return NotifyOwner|int
     */
    public function OwnerAppendByKey($parentKey, $key){
        $parentOwner = $this->OwnerBaseList()->GetByKey($parentKey);
        if (empty($parentOwner) || !$parentOwner->isBase){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $key = NotifyOwner::ParseKey($key);

        $arr = array(
            "module" => $key->module,
            "type" => $key->type,
            "method" => $key->method,
            "itemid" => $key->itemid,
            "parentid" => $parentOwner->id
        );
        return $this->OwnerSave($arr);
    }

    public function SubscribeSaveToJSON($ownerid, $d){
        $res = $this->SubscribeSave($ownerid, $d);
        return $this->ResultToJSON('subscribe', $res);
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

        if (!$this->OwnerAppFunctionExist($owner->module, 'Notify_IsSubscribeUpdate')){
            return AbricosResponse::ERR_SERVER_ERROR;
        }

        /** @var NotifySubscribe $subscribe */
        $subscribe = $this->InstanceClass('Subscribe', $d);

        $ownerApp = $this->GetOwnerApp($owner->module);
        if (!$ownerApp->Notify_IsSubscribeUpdate($owner, $subscribe)){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        NotifyQuery::SubscribeUpdate($this, $owner, $subscribe);
        return $this->Subscribe($owner);
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
        $subscribe->status = NotifySubscribe::STATUS_ON;

        NotifyQuery::SubscribeUpdate($this, $owner, $subscribe);

        return $owner;
    }

    /**
     * @param $owner
     * @return int|NotifySubscribe
     */
    public function Subscribe($owner){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if (!($owner instanceof NotifyOwner)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $d = NotifyQuery::Subscribe($this, $owner);
        if (empty($d)){
            return $this->SubscribeSave($owner->id, array());
        }
        /** @var NotifySubscribe $subscribe */
        $subscribe = $this->InstanceClass('Subscribe', $d);
        return $subscribe;
    }

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
                    $subscribe = $this->InstanceClass('Subscribe');
                    // create base subscribe
                    NotifyQuery::SubscribeUpdate($this, $owner, $subscribe);
                }
            }
            return $this->SubscribeBaseList();
        }

        return $this->_cache['SubscribeBaseList'] = $list;
    }

}

?>