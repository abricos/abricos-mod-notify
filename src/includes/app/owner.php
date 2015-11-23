<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class NotifyAppOwner
 *
 * @property NotifyApp $app
 */
class NotifyAppOwner extends AbricosApplication {

    protected function GetClasses(){
        return array(
            'Owner' => 'NotifyOwner',
            'OwnerList' => 'NotifyOwnerList',
        );
    }

    protected function GetStructures(){
        return 'Owner';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'ownerBaseList':
                return $this->BaseListToJSON();
        }
    }

    public function BaseAppend($d){
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

    private function BaseListUpdateByCalc(NotifyOwnerList $list){
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $owner = $list->GetByIndex($i);
            $recalc = $owner->isEnable !== $owner->IsEnable();
            if ($recalc || $owner->calcDate === 0
            ){
                $owner->isEnable = $owner->IsEnable();
                $owner->calcDate = TIMENOW;
                NotifyQuery::OwnerUpdateByCalc($this, $owner);
                if ($recalc){
                    NotifyQuery::SubscribeCalcClean($this, $owner);
                }
            }
        }
    }

    public function BaseListToJSON(){
        $res = $this->BaseList();
        return $this->ResultToJSON('ownerBaseList', $res);
    }

    /**
     * @return NotifyOwnerList|int
     */
    public function BaseList(){
        if (isset($this->_cache['BaseList'])){
            return $this->_cache['BaseList'];
        }
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        $isRecalc = false;

        /** @var NotifyOwnerList $list */
        $list = $this->InstanceClass('OwnerList');
        $this->_cache['BaseList'] = $list;

        $rows = NotifyQuery::OwnerBaseList($this);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifyOwner $owner */
            $owner = $this->InstanceClass('Owner', $d);

            if ($owner->calcDate === 0){
                $isRecalc = true;
            }
            $list->Add($owner);
        }

        if ($isRecalc){
            $this->BaseListUpdateByCalc($list);
        }

        return $list;
    }

    /**
     * @return NotifyOwnerList
     */
    protected function CacheList(){
        if (isset($this->_cache['List'])){
            return $this->_cache['List'];
        }

        /** @var NotifyOwnerList $list */
        $list = $this->InstanceClass('OwnerList');

        return $this->_cache['List'] = $list;
    }

    /**
     * @param string|NotifyOwner $container
     * @param int $itemid
     * @return NotifyOwner|int
     */
    public function ItemAppend($container, $itemid){
        if (is_string($container)){
            $container = $this->ContainerByKey($container);
            if (AbricosResponse::IsError($container)){
                return AbricosResponse::ERR_BAD_REQUEST;
            }
        }

        if ($container->recordType !== NotifyOwner::TYPE_CONTAINER){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        /** @var NotifyOwner $owner */
        $owner = $this->InstanceClass('Owner', array(
            'parentid' => $container->id,
            'isEnable' => $container->isEnable,
            'calcDate' => TIMENOW,
            'module' => $container->module,
            'type' => $container->type,
            'itemid' => $itemid,
            'status' => NotifyOwner::STATUS_ON,
            'defaultStatus' => NotifySubscribe::STATUS_ON,
            'defaultEmailStatus' => NotifySubscribe::EML_STATUS_PARENT,
            'recordType' => NotifyOwner::TYPE_ITEM
        ));
        $owner->isEnable = $owner->IsEnable();

        $owner->id = NotifyQuery::OwnerAppend($this, $owner);
        if ($owner->id === 0){
            return AbricosResponse::ERR_SERVER_ERROR;
        }

        // add item subscribe methods
        $ownerList = $this->BaseList();
        $count = $ownerList->Count();
        for ($i = 0; $i < $count; $i++){
            $ownerMethod = $ownerList->GetByIndex($i);
            if (!($container->id === $ownerMethod->parentid
                && $ownerMethod->recordType === NotifyOwner::TYPE_METHOD)
            ){
                continue;
            }

            /** @var NotifyOwner $ownerItemMethod */
            $ownerItemMethod = $this->InstanceClass('Owner', array(
                'parentid' => $ownerMethod->id,
                'isEnable' => $container->isEnable,
                'calcDate' => TIMENOW,
                'module' => $ownerMethod->module,
                'type' => $ownerMethod->type,
                'method' => $ownerMethod->method,
                'itemid' => $itemid,
                'status' => NotifyOwner::STATUS_ON,
                'defaultStatus' => NotifySubscribe::STATUS_ON,
                'defaultEmailStatus' => NotifySubscribe::EML_STATUS_PARENT,
                'recordType' => NotifyOwner::TYPE_ITEM_METHOD
            ));

            $ownerItemMethod->id = $id = NotifyQuery::OwnerAppend($this, $ownerItemMethod);

            if ($ownerMethod->isChildSubscribe){
                NotifyQuery::SubscribeAutoAppend($this, $ownerItemMethod);
            }
        }

        $this->CacheClear();

        $owner = $this->ItemByKey($container->GetKey(), $itemid);

        return $owner;
    }

    /**
     * @param int $ownerid
     * @return NotifyOwner|int
     */
    public function ById($ownerid){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $owner = $this->BaseList()->Get($ownerid);
        if (!empty($owner)){
            return $owner;
        }

        $ownerList = $this->CacheList();
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

        if ($owner->recordType === NotifyOwner::TYPE_ITEM){
            $rows = NotifyQuery::OwnerItemMethodList($this, $owner, $owner->itemid);
            while (($d = $this->db->fetch_array($rows))){
                /** @var NotifyOwner $own */
                $ownerList->Add($this->InstanceClass('Owner', $d));
            }
        }

        return $owner;
    }

    public function ContainerByKey($key){
        $pkey = NotifyOwner::ParseKey($key);
        $baseList = $this->BaseList();
        $count = $baseList->Count();
        for ($i = 0; $i < $count; $i++){
            $owner = $baseList->GetByIndex($i);

            if ($owner->recordType === NotifyOwner::TYPE_CONTAINER
                && $owner->module === $pkey->module
                && $owner->type === $pkey->type
            ){
                return $owner;
            }
        }
        return AbricosResponse::ERR_NOT_FOUND;
    }

    public function ItemByKey($key, $itemid){
        $pkey = NotifyOwner::ParseKey($key);
        $itemid = intval($itemid);

        $owner = AbricosResponse::ERR_NOT_FOUND;
        $list = $this->CacheList();
        $count = $list->Count();

        for ($i = 0; $i < $count; $i++){
            $owner = $list->GetByIndex($i);

            if ($owner->recordType === NotifyOwner::TYPE_ITEM
                && $owner->module === $pkey->module
                && $owner->type === $pkey->type
                && $owner->itemid === $itemid
            ){
                return $owner;
            }
        }

        $container = $this->ContainerByKey($key);

        $rows = NotifyQuery::OwnerListByContainer($this, $container, $itemid);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifyOwner $own */
            $own = $this->InstanceClass('Owner', $d);
            $list->Add($own);
            if ($own->recordType === NotifyOwner::TYPE_ITEM){
                $owner = $own;
            }
        }

        return $owner;
    }

    /**
     * @param $key
     * @param $itemid
     * @return int|NotifyOwnerList
     */
    public function ItemWithMethodListByKey($key, $itemid){
        $owner = $this->ItemByKey($key, $itemid);
        if (empty($owner)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        /** @var NotifyOwnerList $retList */
        $retList = $this->InstanceClass('OwnerList');

        $list = $this->CacheList();
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $iown = $list->GetByIndex($i);

            if ($owner->module === $iown->module
                && $owner->type === $iown->type
                && $owner->itemid === $itemid
            ){
                $retList->Add($iown);
            }
        }
        return $retList;
    }

    public function ItemMethodByKey($key, $itemid){
        $pkey = NotifyOwner::ParseKey($key);

        // preload item methods
        $this->ItemByKey($key, $itemid);

        $list = $this->CacheList();
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $owner = $list->GetByIndex($i);

            if ($owner->recordType === NotifyOwner::TYPE_ITEM_METHOD
                && $owner->module === $pkey->module
                && $owner->type === $pkey->type
                && $owner->method === $pkey->method
                && $owner->itemid === $itemid
            ){
                return $owner;
            }
        }
        return AbricosResponse::ERR_NOT_FOUND;
    }

}

?>