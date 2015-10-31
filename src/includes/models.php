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
 * @property int $ownerid
 */
class NotifyOwner extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Owner';
}


/**
 * Class NotifySubscribe
 *
 * @property string $module
 * @property string $type
 * @property int $ownerid
 * @property int $userid
 * @property int $emailStatus
 * @property int $bosStatus
 * @property string $pubkey
 * @property int $dateline
 */
class NotifySubscribe extends AbricosModel {
    protected $_structModule = 'notify';
    protected $_structName = 'Subscribe';
}

/**
 * Class NotifySubscribeList
 * @method NotifySubscribe Get($id)
 * @method NotifySubscribe GetByIndex($index)
 */
class NotifySubscribeList extends AbricosModelList {
}
?>