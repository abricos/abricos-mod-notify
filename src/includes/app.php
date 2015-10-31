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
            'Subscribe' => 'NotifySubscribe',
            'SubscribeList' => 'NotifySubscribeList',
        );
    }

    protected function GetStructures(){
        return 'Subscribe';
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
        $ret = $this->SubscribeList();
        return $this->ResultToJSON('subscribeList', $ret);
    }

    public function SubscribeList(){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var SubscribeList $list */
        $list = $this->InstanceClass('SubscribeList');
        $rows = NotifyQuery::SubscribeList($this);

        while (($d = $this->db->fetch_array($rows))){
            /** @var Subscribe $subscribe */
            $subscribe = $this->InstanceClass('Subscribe', $d);
            $list->Add($subscribe);
        }
        return $list;
    }
}

?>