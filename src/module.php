<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class NotifyModule
 *
 * @method NotifyManager GetManager()
 */
class NotifyModule extends Ab_Module {

    function __construct(){
        $this->version = "0.1.4";
        $this->name = "notify";

        $this->permission = new NotifyPermission($this);
    }

    public function Bos_IsExtension(){
        return true;
    }
}

class NotifyAction {
    const ADMIN = 50;
    const WRITE = 30;
    const VIEW = 10;
}

class NotifyPermission extends Ab_UserPermission {

    public function NotifyPermission(NotifyModule $module){
        $defRoles = array(
            new Ab_UserRole(NotifyAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(NotifyAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(NotifyAction::WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(NotifyAction::WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(NotifyAction::ADMIN, Ab_UserGroup::ADMIN)
        );
        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            NotifyAction::VIEW => $this->CheckAction(NotifyAction::VIEW),
            NotifyAction::WRITE => $this->CheckAction(NotifyAction::WRITE),
            NotifyAction::ADMIN => $this->CheckAction(NotifyAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new NotifyModule());
?>