<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'models.php';
require_once 'dbquery.php';

/**
 * Class NotifyApp
 *
 * @property NotifyManager $manager
 */
class NotifyApp extends AbricosApplication {

    protected function GetClasses(){
        return array();
    }

    protected function GetStructures(){
        return '';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
        }
        return null;
    }

    protected $_cache = array();

    public function CacheClear(){
        $this->_cache = array();
    }

}

?>