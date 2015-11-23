<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class NotifyAppEvent
 *
 * @property NotifyManager $manager
 */
class NotifyAppEvent extends AbricosApplication {

    /**
     * @return NotifyAppOwner
     * @throws Exception
     */
    public function Owner(){
        return $this->GetApp('notify.Owner');
    }

    /**
     * @return NotifyAppSubscribe
     * @throws Exception
     */
    public function Subscribe(){
        return $this->GetApp('notify.Subscribe');
    }

    protected function GetClasses(){
        return array(
            'Event' => 'NotifyEvent',
            'List' => 'NotifyEventList',
        );
    }

    protected function GetStructures(){
        return '';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
        }
        return null;
    }

    public function EventAppend($key, $itemid){

    }
}

?>