<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class NotifyOwner
 *
 * @property string $module
 * @property string $type
 * @property string $method
 * @property int $ownerid
 * @property string $status
 */
class NotifyOwner extends AbricosModel {
    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';

    protected $_structModule = 'notify';
    protected $_structName = 'Owner';
}

/**
 * Class NotifyOwnerList
 * @method NotifyOwner Get($id)
 * @method NotifyOwner GetByIndex($index)
 */
class NotifyOwnerList extends AbricosModelList {
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
    const STATUS_UNSET = 'unset';
    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';
    
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