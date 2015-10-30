<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class NotifyManager
 *
 * @property NotifyModule $module
 */
class NotifyManager extends Ab_ModuleManager {

    public function IsAdminRole(){
        return $this->IsRoleEnable(NotifyAction::ADMIN);
    }

    public function IsWriteRole(){
        if ($this->IsModerRole()){
            return true;
        }
        return $this->IsRoleEnable(NotifyAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(NotifyAction::VIEW);
    }

    private $_app = null;

    /**
     * @return NotifyApp
     */
    public function GetApp(){
        if (!is_null($this->_app)){
            return $this->_app;
        }
        $this->module->ScriptRequire('includes/app.php');
        return $this->_app = new NotifyApp($this);
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    /*
    public function Bos_MenuData(){
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "notify",
                "title" => $i18n->Translate('title'),
                "role" => NotifyAction::ADMIN,
                "icon" => "/modules/notify/images/forum-24.png",
                "url" => "forum/wspace/ws"
            )
        );
    }
    /**/

}

?>