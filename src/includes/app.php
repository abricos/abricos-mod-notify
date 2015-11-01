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
            case 'subscribeList':
                return $this->SubscribeListToJSON($d->module);
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

    public function OwnerSave($d){
        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', $d);

        $ownerid = NotifyQuery::OwnerSave($this, $owner);
        return $ownerid;
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

    public function SubscribeListToJSON($module){
        $res = $this->SubscribeList($module);
        $ret = $this->ResultToJSON('subscribeList', $res);
        if (!AbricosResponse::IsError($res)){
            $ret = $this->ImplodeJSON(
                $this->ResultToJSON('ownerList', $res->ownerList),
                $ret
            );
        }
        return $ret;
    }

    public function SubscribeList($module){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');

        /** @var NotifyOwnerList $ownerList */
        $ownerList = $this->InstanceClass('OwnerList');

        $list->ownerList = $ownerList;

        $rows = NotifyQuery::SubscribeList($this, $module);
        while (($d = $this->db->fetch_array($rows))){

            /** @var NotifyOwner $owner */
            $owner = $this->InstanceClass('Owner', $d);

            $ownerList->Add($owner);

            /** @var NotifySubscribe $subscribe */
            $subscribe = $this->InstanceClass('Subscribe', $d);
            $list->Add($subscribe);
        }
        return $list;
    }

}

?>