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
 * @property int $parentid
 * @property string $module
 * @property string $type
 * @property string $method
 * @property int $itemid
 * @property string $status
 * @property string $defaultStatus Default status for User Subscribe
 * @property string $defaultEmailStatus Default EMail Status for User Subscribe
 * @property boolean $isBase
 * @property boolean $isContainer
 */
class NotifyOwner extends AbricosModel {
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
}

?>