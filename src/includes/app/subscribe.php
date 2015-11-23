<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class NotifyApp
 *
 * @property NotifyManager $manager
 */
class NotifyAppSubscribe extends AbricosApplication {

    /**
     * @return NotifyAppOwner
     * @throws Exception
     */
    public function Owner(){
        return $this->GetApp('notify.Owner');
    }

    protected function GetClasses(){
        return array(
            'Subscribe' => 'NotifySubscribe',
            'List' => 'NotifySubscribeList',
        );
    }

    protected function GetStructures(){
        return 'Subscribe';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'subscribeBaseList':
                return $this->BaseListToJSON();
            case 'subscribeSave':
                return $this->SaveToJSON($d->subscribe);
        }
        return null;
    }

    public function BaseListUpdateByCalc(NotifySubscribeList $list){
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $subscribe = $list->GetByIndex($i);

            $recalc = $subscribe->isEnable !== $subscribe->IsEnable();
            if ($recalc || $subscribe->calcDate === 0){
                $subscribe->calcDate = TIMENOW;
                $subscribe->parentid = $subscribe->GetParentId();
                $subscribe->isEnable = $subscribe->IsEnable();
                NotifyQuery::SubscribeUpdateByCalc($this, $subscribe);
            }
        }
    }

    public function BaseListToJSON(){
        $res = $this->BaseList();
        return $this->ResultToJSON('subscribeBaseList', $res);
    }

    private $_isSubscribeBaseListUpdate = false;

    public function BaseList(){
        if (isset($this->_cache['BaseList'])){
            return $this->_cache['BaseList'];
        }
        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('List');
        $this->_cache['BaseList'] = $list;

        if (!$this->manager->IsViewRole() || Abricos::$user->id === 0){
            return $list;
        }

        $isRecalc = false;

        $rows = NotifyQuery::SubscribeBaseList($this);
        while (($d = $this->db->fetch_array($rows))){
            /** @var NotifySubscribe $subscribe */
            $subscribe = $this->InstanceClass('Subscribe', $d);
            if ($subscribe->calcDate === 0){
                $isRecalc = true;
            }
            $list->Add($subscribe);
        }

        $ownerBaseList = $this->Owner()->BaseList();

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

                    $subscribe = $this->InstanceClass('Subscribe', array(
                        'ownerid' => $owner->id,
                        'userid' => Abricos::$user->id,
                        'status' => $owner->defaultStatus,
                        'emailStatus' => $owner->defaultEmailStatus,
                        'calcDate' => TIMENOW
                    ));

                    $subscribe->parentid = $subscribe->GetParentId();
                    $subscribe->isEnable = $subscribe->IsEnable();

                    $list->Add($subscribe);

                    $subscribe->id = NotifyQuery::SubscribeAppend($this, $subscribe);

                }
            }
            return $this->BaseList();
        }

        if ($isRecalc){
            $this->BaseListUpdateByCalc($list);
        }

        return $list;
    }

    protected function CacheList(){
        if (isset($this->_cache['List'])){
            return $this->_cache['List'];
        }

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('List');

        return $this->_cache['List'] = $list;
    }

    /**
     * @param NotifyOwner $owner
     * @return NotifySubscribe|int
     */
    protected function ByOwner(NotifyOwner $owner){
        if (!$this->manager->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if ($owner->isBase){
            $subscribe = $this->BaseList()->GetByOwnerId($owner);
        } else {
            $list = $this->CacheList();
            $subscribe = $list->GetByOwnerId($owner);

            if (empty($subscribe)){
                $d = NotifyQuery::Subscribe($this, $owner);
                if (!empty($d)){
                    /** @var NotifySubscribe $subscribe */
                    $subscribe = $this->InstanceClass('Subscribe', $d);
                    $list->Add($subscribe);
                }
            }
        }
        if (!empty($subscribe)){
            return $subscribe;
        }

        if ($owner->recordType !== NotifyOwner::TYPE_ROOT){
            if ($this->IsAppFunctionExist($owner->module, 'Notify_IsSubscribeAppend')){
                $app = $this->GetApp($owner->module);
                if (!$app->Notify_IsSubscribeAppend($owner)){
                    return AbricosResponse::ERR_FORBIDDEN;
                }
            }
        }

        $subscribe = $this->InstanceClass('Subscribe', array(
            'ownerid' => $owner->id,
            'userid' => Abricos::$user->id,
            'status' => $owner->defaultStatus,
            'emailStatus' => $owner->defaultEmailStatus,
            'calcDate' => TIMENOW
        ));

        $subscribe->parentid = $subscribe->GetParentId();
        $subscribe->isEnable = $subscribe->IsEnable();

        $subscribeid = NotifyQuery::SubscribeAppend($this, $subscribe);
        if ($subscribeid === 0){
            return AbricosResponse::ERR_SERVER_ERROR;
        }

        return $this->ByOwner($owner);
    }

    public function SaveToJSON($d){
        $res = $this->Save($d);
        return $this->ResultToJSON('subscribeSave', $res);
    }

    public function Save($d){
        if (!$this->manager->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        if (!isset($d->ownerid)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $owner = $this->Owner()->ById(intval($d->ownerid));
        if (AbricosResponse::IsError($owner)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        $curSubscribe = $this->ByOwner($owner);
        if (AbricosResponse::IsError($curSubscribe)){
            return AbricosResponse::ERR_BAD_REQUEST;
        }

        /** @var NotifySubscribe $subscribe */
        $subscribe = $this->InstanceClass('Subscribe', $d);

        $curSubscribe->status = $subscribe->status;
        $curSubscribe->emailStatus = $subscribe->emailStatus;

        NotifyQuery::SubscribeUpdate($this, $owner, $curSubscribe);

        NotifyQuery::SubscribeCalcCleanByUser($this);

        $this->CacheClear();
        $this->BaseList();

        return $curSubscribe;
    }

    public function ItemMethodByKey($key, $itemid){
        $owner = $this->Owner()->ItemMethodByKey($key, $itemid);
        if (AbricosResponse::IsError($owner)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        return $this->ByOwner($owner);
    }

    public function ItemWithMethodListByKey($key, $itemid){
        $ownerList = $this->Owner()->ItemWithMethodListByKey($key, $itemid);

        /** @var NotifySubscribeList $list */
        $list = $this->InstanceClass('List');

        $cnt = $ownerList->Count();
        for ($i = 0; $i < $cnt; $i++){
            $subscribe = $this->ByOwner($ownerList->GetByIndex($i));
            $list->Add($subscribe);
        }
        return $list;
    }

}

?>