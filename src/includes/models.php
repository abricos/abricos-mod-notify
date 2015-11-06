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
    public $itemid;

    public function __construct($module, $type, $method, $itemid){
        $this->module = strval($module);
        $this->type = strval($type);
        $this->method = strval($method);
        $this->itemid = intval($itemid);
    }
}

/**
 * Class NotifyOwner
 *
 * @property NotifyApp $app
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
 */
class NotifyOwner extends AbricosModel {
    const TYPE_ROOT = 'root';
    const TYPE_MODULE = 'module';
    const TYPE_CONTAINER = 'container';
    const TYPE_METHOD = 'method';
    const TYPE_ITEM = 'item';

    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';

    protected $_structModule = 'notify';
    protected $_structName = 'Owner';

    public static function NormalizeKey($key, $itemid = 0){
        // TODO: create cache normalized key
        if (!is_string($key)){
            $key = '';
        }
        $itemid = intval($itemid);
        $key = str_replace('{v#itemid}', $itemid, $key);
        $a = array();
        $aa = explode(":", $key);
        for ($i = 0; $i < 4; $i++){
            $a[] = $i < 3 ? (isset($aa[$i]) ? $aa[$i] : "") :
                (isset($aa[$i]) ? intval($aa[$i]) : 0);
        }
        return implode(":", $a);
    }

    /**
     * @param $key
     * @param int $itemid
     * @return NotifyOwnerKey
     */
    public static function ParseKey($key, $itemid = 0){
        $key = NotifyOwner::NormalizeKey($key, $itemid);
        $a = explode(":", $key);
        return new NotifyOwnerKey($a[0], $a[1], $a[2], $a[3]);
    }

    public function IsBase(){
        return $this->recordType !== NotifyOwner::TYPE_ITEM;
    }

    private $_ownerKey;

    public function GetKey(){
        if (!empty($this->_ownerKey)){
            return $this->_ownerKey;
        }

        $arr = array(
            $this->module,
            $this->type,
            $this->method,
            $this->itemid
        );

        return $this->_ownerKey = implode(":", $arr);
    }

    private $_parent;

    /**
     * @return NotifyOwner|null
     */
    public function GetParent(){
        if (!empty($this->_parent)){
            return $this->_parent;
        }
        if ($this->parentid === 0){
            return null;
        }

        return $this->parentid = $this->app->OwnerBaseList()->Get($this->parentid);
    }

    public function IsEnable(){
        $parent = $this;
        while (!empty($parent)){
            if ($this->status !== NotifyOwner::STATUS_ON){
                return false;
            }
        }
        return true;
    }
}

/**
 * Class NotifyOwnerList
 * @method NotifyOwner Get($id)
 * @method NotifyOwner GetByIndex($index)
 */
class NotifyOwnerList extends AbricosModelList {

    public function GetByKey($key, $itemid = 0){
        $key = NotifyOwner::NormalizeKey($key, $itemid);
        $count = $this->Count();
        for ($i = 0; $i < $count; $i++){
            $owner = $this->GetByIndex($i);
            if ($owner->GetKey() === $key){
                return $owner;
            }
        }
        return null;
    }
}


/**
 * Class NotifySubscribe
 *
 * @property NotifyApp $app
 *
 * @property int $ownerid
 * @property int $userid
 * @property string $status
 * @property string $emailStatus
 * @property string $pubkey
 * @property int $dateline
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

        $this->_owner = $this->app->OwnerById($this->ownerid);

        return $this->_owner;
    }

    public function IsBase(){
        return $this->GetOwner()->IsBase();
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
    public function GetByOwner($ownerid){
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

?>