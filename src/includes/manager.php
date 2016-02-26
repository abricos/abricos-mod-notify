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
        if ($this->IsAdminRole()){
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


    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    private $_oldManager = null;

    /**
     * @param $email
     * @param $subject
     * @param $message
     * @param string $from
     * @param string $fromName
     *
     * @deprecated
     */
    public function SendMail($email, $subject, $message, $from = '', $fromName = ''){
        if (empty($this->_oldManager)){
            $this->module->ScriptRequireOnce('includes/old_manager.php');
            $this->_oldManager = new old_NotifyManager($this->module);
        }

        return $this->_oldManager->SendMail($email, $subject, $message, $from, $fromName);
    }

    public function Bos_MenuData(){
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "notify",
                "title" => $i18n->Translate('title'),
                "role" => NotifyAction::ADMIN,
                "icon" => "/modules/notify/images/logo-96x96.png",
                "url" => "notify/wspace/ws/",
                "parent" => "controlPanel"
            )
        );
    }

    public function Bos_SummaryData(){
        if (!$this->IsAdminRole()){
            return;
        }

        $i18n = $this->module->I18n();
        return array(
            array(
                "module" => "notify",
                "component" => "summary",
                "widget" => "SummaryWidget",
                "title" => $i18n->Translate('title'),
            )
        );
    }

    /*
    public function Bos_ExtensionData(){
        if (!$this->IsViewRole()){
            return null;
        }
        return array(
            "component" => "cron",
            "method" => "initializeCron"
        );
    }
    /**/
}

?>