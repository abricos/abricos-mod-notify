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

    /**
     * @param $owner
     * @return NotifyOwner
     */
    public function OwnerNormalize($owner){
        if ($owner instanceof NotifyOwner){
            return $owner;
        }

        return $this->InstanceClass('Owner', $owner);
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
    public function Owner($ownerid){
        $d = NotifyQuery::Owner($this, $ownerid);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);
        return $owner;
    }

    /**
     * @return NotifyOwner
     */
    public function OwnerRoot(){
        if (isset($this->_cache['OwnerRoot'])){
            return $this->_cache['OwnerRoot'];
        }

        $owner = $this->Owner(1);
        return $this->_cache['OwnerRoot'] = $owner;
    }

    public function OwnerSave($d){
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);

        if ($owner->parentid === 0){
            $root = $this->OwnerRoot();
            $owner->parentid = $root->id;
        }

        $ownerid = NotifyQuery::OwnerSave($this, $owner);
        return $ownerid;
    }

    public function SubscribeSaveToJSON($ownerid, $d){
        $res = $this->SubscribeSave($ownerid, $d);
        return $this->ResultToJSON('subscribe', $res);
    }

    public function SubscribeSave($ownerid, $d){
        if (!$this->manager->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        $owner = $this->Owner($ownerid);

        if (AbricosResponse::IsError($owner)){
            return AbricosResponse::ERR_BAD_REQUEST;
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
     * @param $owner
     * @return NotifySubscribe|int
     */
    public function Subscribe($owner){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        $owner = $this->OwnerNormalize($owner);

        $d = NotifyQuery::Subscribe($this, $owner);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        $subscribe = $this->InstanceClass('Subscribe', $d);
        return $subscribe;
    }

    public function SubscribeBaseListToJSON(){
        $res = $this->SubscribeBaseList();
        return $this->ResultToJSON('subscribeBaseList', $res);
    }

    public function SubscribeBaseList(){
        if (isset($this->_cache['SubscribeBaseList'])){
            return $this->_cache['SubscribeBaseList'];
        }
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');

        $rows = NotifyQuery::SubscribeBaseList($this);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifySubscribe $subscribe */
            $subscribe = $this->InstanceClass('Subscribe', $d);
            $list->Add($subscribe);
        }
        return $this->_cache['SubscribeBaseList'] = $list;
    }

}

?>