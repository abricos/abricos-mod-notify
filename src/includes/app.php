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
            case 'subscribeList':
                return $this->SubscribeListToJSON();
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

    public function SubscribeListToJSON(){
        $res = $this->SubscribeList();
        $ret = $this->ResultToJSON('subscribeList', $res);
        if (!AbricosResponse::IsError($res)){
            $ret = $this->ImplodeJSON(
                $this->ResultToJSON('ownerList', $res->ownerList),
                $ret
            );
        }
        return $ret;
    }

    public function SubscribeList(){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');

        /** @var NotifyOwnerList $ownerList */
        $ownerList = $this->InstanceClass('OwnerList');

        $list->ownerList = $ownerList;

        $rows = NotifyQuery::SubscribeList($this);
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