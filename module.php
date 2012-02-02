<?php
/**
 * Модуль "Сообщения"
 * 
 * @version $Id$
 * @package Abricos
 * @subpackage Notify
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class NotifyModule extends Ab_Module {
	
	private $_manager = null;
	
	function __construct(){
		$this->version = "0.1.1";
		$this->name = "notify";
	}
	
	/**
	 * Получить менеджер
	 *
	 * @return NotifyManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/phpmailer/class.phpmailer.php';
			require_once 'includes/manager.php';
			
			$this->_manager = new NotifyManager($this);
		}
		return $this->_manager;
	}
}

Abricos::ModuleRegister(new NotifyModule());

?>