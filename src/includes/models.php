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
        $this->_parent = $this->app->BaseList()->GetBy('ownerid', $parentOwnerId);

        return $this->_parent;
    }

    public function GetParentId(){
        $parent = $this->GetParent();
        return empty($parent) ? 0 : $parent->id;
    }

    private $_isEnableMethodCache = null;

    private function IsEnableMethod(){
        if (!is_null($this->_isEnableMethodCache)){
            return $this->_isEnableMethodCache;
        }
        $owner = $this->GetOwner();
        $parent = $this->GetParent();
        $isEnableParent = empty($parent) ? true : $parent->IsEnableMethod();

        $this->_isEnableMethodCache = $isEnableParent
            && $owner->isEnable
            && $this->status === NotifySubscribe::STATUS_ON;

        /*
        print_r(
            array(
                'id=' => $this->id,
                'parentid=' => empty($parent) ? 0 : $parent->id,
                '$isEnableParent=' => $isEnableParent,
                '$isEnable=' => $this->_isEnableMethodCache,
                '$owner->isEnable=' => $owner->isEnable,
                '$this->status=' => $this->status
            )
        );
        /**/

        return $this->_isEnableMethodCache;
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

    const STATUS_WAITING = 'waiting';
    const STATUS_RUNS = 'runs';
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


/**
 * Class NotifySummary
 *
 * @property int $count
 */
class NotifySummary extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Summary';
}

/**
 * Class NotifySummaryList
 *
 * @method NotifySummary Get($id)
 * @method NotifySummary GetByIndex($index)
 */
class NotifySummaryList extends AbricosModelList {

}

/**
 * Class NotifyNotice
 *
 * @property int $eventid
 * @property int $ownerid
 * @property string $module
 * @property string $type
 * @property string $method
 * @property int $itemid
 * @property int $dateline
 */
class NotifyNotice extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Notice';
}

/**
 * Class NotifyNoticeList
 *
 * @method NotifyNotice Get($id)
 * @method NotifyNotice GetByIndex($index)
 */
class NotifyNoticeList extends AbricosModelList {

}

/**
 * Class NotifyConfig
 *
 * @property string $fromName
 * @property string $fromEmail
 *
 * @property bool $totestfile
 *
 * @property bool $SMTP
 * @property string $SMTPHost
 * @property int $SMTPPort
 * @property bool $SMTPAuth
 * @property string $SMTPUsername
 * @property string $SMTPPassword
 * @property string $SMTPSecure
 *
 * @property bool $POPBefore
 * @property string $POPHost
 * @property int $POPPort
 * @property string $POPUsername
 * @property string $POPPassword
 */
class NotifyConfig extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Config';
}

/**
 * Class NotifyMail
 *
 * @property string $fromName
 * @property string $fromEmail
 * @property string $toName
 * @property string $toEmail
 * @property string $subject
 * @property string $body
 * @property int $userid
 * @property string $globalid
 * @property boolean $isDebug
 * @property int $dateline
 * @property int $sendDate
 * @property boolean $sendError
 * @property string $sendErrorInfo
 */
class NotifyMail extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Mail';
}

/**
 * Class NotifyMailList
 *
 * @method NotifyMail Get($id)
 * @method NotifyMail GetByIndex($index)
 */
class NotifyMailList extends AbricosModelList {

}
