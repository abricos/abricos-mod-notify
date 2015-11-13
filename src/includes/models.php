<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class NotifyOwnerKey
 */
class NotifyOwnerKey {
    public $module;
    public $type;
    public $method;

    public function __construct($module, $type, $method){
        $this->module = strval($module);
        $this->type = strval($type);
        $this->method = strval($method);
    }
}

/**
 * Class NotifyOwner
 *
 * @property NotifyAppOwner $app
 *
 * @property int $parentid
 * @property string $recordType
 * @property string $module
 * @property string $type
 * @property string $method
 * @property int $itemid
 * @property string $status
 * @property string $defaultStatus Default status for User Subscribe
 * @property string $defaultEmailStatus Default EMail Status for User Subscribe
 * @property bool $isBase
 * @property int $eventTimeout
 * @property bool $isChildSubscribe
 * @property bool $isEnable
 * @property int $calcDate
 */
class NotifyOwner extends AbricosModel {
    const TYPE_ROOT = 'root';
    const TYPE_MODULE = 'module';
    const TYPE_CONTAINER = 'container';
    const TYPE_METHOD = 'method';
    const TYPE_ITEM = 'item';
    const TYPE_ITEM_METHOD = 'imethod';

    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';

    protected $_structModule = 'notify';
    protected $_structName = 'Owner';

    private $_ownerKey;

    public function GetKey(){
        if (!empty($this->_ownerKey)){
            return $this->_ownerKey;
        }

        $arr = array(
            $this->module,
            $this->type,
            $this->method
        );

        return $this->_ownerKey = implode(":", $arr);
    }

    private $_ownerParent;

    /**
     * @return NotifyOwner|null
     */
    public function GetParent(){
        if (isset($this->_ownerParent)){
            return $this->_ownerParent;
        }
        if ($this->parentid === 0){
            return $this->_ownerParent = null;
        }

        return $this->_ownerParent = $this->app->BaseList()->Get($this->parentid);
    }

    public function IsEnable(){
        $parent = $this;
        while (!empty($parent)){
            if ($parent->status !== NotifyOwner::STATUS_ON){
                return false;
            }
            $parent = $parent->GetParent();
        }
        return true;
    }

    /* * * * * * * * * * * * * Static * * * * * * * * * * * */

    public static function NormalizeKey($key){
        // TODO: create cache normalized key
        if (!is_string($key)){
            $key = '';
        }
        $a = array();
        $aa = explode(":", $key);
        for ($i = 0; $i < 3; $i++){
            $a[] = (isset($aa[$i]) ? $aa[$i] : "");
        }
        return implode(":", $a);
    }

    /**
     * @param $key
     * @param int $itemid
     * @return NotifyOwnerKey
     */
    public static function ParseKey($key){
        $key = NotifyOwner::NormalizeKey($key);
        $a = explode(":", $key);
        return new NotifyOwnerKey($a[0], $a[1], $a[2]);
    }

}

/**
 * Class NotifyOwnerList
 * @method NotifyOwner Get($id)
 * @method NotifyOwner GetByIndex($index)
 */
class NotifyOwnerList extends AbricosModelList {

    /**
     * @param string $key
     * @param int $itemid
     * @return NotifyOwner|null
     */
    public function GetByKey($key, $itemid = 0){
        $itemid = intval($itemid);
        $key = NotifyOwner::NormalizeKey($key, $itemid);
        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $owner = $this->GetByIndex($i);
            if ($owner->GetKey() === $key && $owner->itemid === $itemid){
                return $owner;
            }
        }
        return null;
    }

    /**
     * @param NotifyOwner $ownerCont
     * @param int $itemid
     * @return NotifyOwner|null
     */
    public function GetByContainer(NotifyOwner $ownerCont, $itemid){
        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $owner = $this->GetByIndex($i);
            if ($owner->parentid === $ownerCont->id && $owner->itemid === $itemid){
                return $owner;
            }
        }
        return null;
    }
}


/**
 * Class NotifySubscribe
 *
 * @property NotifyAppSubscribe $app
 *
 * @property int $parentid
 * @property int $ownerid
 * @property int $userid
 * @property string $status
 * @property string $emailStatus
 * @property string $pubkey
 * @property int $dateline
 * @property bool $isEnable
 * @property int $calcDate
 */
class NotifySubscribe extends AbricosModel {
    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';

    const EML_STATUS_OFF = 'off';
    const EML_STATUS_PARENT = 'parent';
    const EML_STATUS_ALWAYS = 'always';
    const EML_STATUS_FIRST = 'first';
    const EML_STATUS_DAILY = 'daily';
    const EML_STATUS_WEEKLY = 'weekly';

    protected $_structModule = 'notify';
    protected $_structName = 'Subscribe';


    private $_owner;

    public function GetOwner(){
        if (!empty($this->_owner)){
            return $this->_owner;
        }
        $ownerid = $this->ownerid;
        $ownerApp = $this->app->Owner();
        $this->_owner = $ownerApp->BaseList()->Get($ownerid);
        if (!empty($this->_owner)){
            return $this->_owner;
        }

        $this->_owner = $ownerApp->ById($ownerid);

        return $this->_owner;
    }

    /*
    public function IsBase(){
        return $this->GetOwner()->isBase;
    }
    /**/

    private $_parent;

    /**
     * @return NotifySubscribe|null
     */
    public function GetParent(){
        if (isset($this->_parent)){
            return $this->_parent;
        }
        $parentOwnerId = $this->GetOwner()->parentid;
        $this->_parent = $this->app->BaseList()->GetBy('ownerid', $parentOwnerId);;

        return $this->_parent;
    }

    public function GetParentId(){
        $parent = $this->GetParent();
        return empty($parent) ? 0 : $parent->id;
    }

    private function IsEnableMethod(){
        $owner = $this->GetOwner();
        $parent = $this->GetParent();
        $isEnableParent = empty($parent) ? true : $parent->IsEnableMethod();
        print_r(
            array(
                'id=' => $this->id,
                'parent=' => empty($parent) ? 0 : $parent->id,
                '!$isEnableParent=' => !$isEnableParent,
                '!$owner->isEnable=' => !$owner->isEnable,
                '$this->status !== NotifySubscribe::STATUS_ON=' => $this->status !== NotifySubscribe::STATUS_ON
            )
        );
        return
            !$isEnableParent || !$owner->isEnable
            || $this->status !== NotifySubscribe::STATUS_ON;
    }

    public function IsEnable(){
        $parent = $this;
        while (!empty($parent)){
            if (!$parent->IsEnableMethod()){
                return false;
            }
            $parent = $parent->GetParent();
        }
        return true;
    }
}

/**
 * Class NotifySubscribeList
 * @method NotifySubscribe Get($id)
 * @method NotifySubscribe GetByIndex($index)
 */
class NotifySubscribeList extends AbricosModelList {

    /**
     * @var NotifyOwnerList
     */
    public $ownerList;

    /**
     * @param NotifyOwner|int $ownerid
     * @return NotifySubscribe|null
     */
    public function GetByOwnerId($ownerid){
        if ($ownerid instanceof NotifyOwner){
            $ownerid = $ownerid->id;
        }
        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $subscribe = $this->GetByIndex($i);
            if ($subscribe->ownerid === $ownerid){
                return $subscribe;
            }
        }
        return null;
    }
}

/**
 * Class NotifyEvent
 *
 * @property int $ownerItemId
 * @property int $ownerMethodId
 * @property int $userid
 * @property string $status
 * @property int $dateline
 * @property int $timeout
 */
class NotifyEvent extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Event';

    const STATUS_EXPECT = 'expect';
    const STATUS_PERFOMED = 'perfomed';
    const STATUS_FINISHED = 'finished';
}

/**
 * Class NotifyEventList
 *
 * @method NotifyEvent Get($id)
 * @method NotifyEvent GetByIndex($index)
 */
class NotifyEventList extends AbricosModelList {

}

?>