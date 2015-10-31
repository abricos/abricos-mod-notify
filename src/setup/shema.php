<?php
/**
 * @package Abricos
 * @subpackage Forum
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isUpdate('0.1.4')){

    Abricos::GetModule('notify')->permission->Install();

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_owner (
            ownerid int(10) UNSIGNED NOT NULL auto_increment,

            ownerModule VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Owner Module Name',
            ownerType VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Type',
            ownerMethod VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Method',
            ownerItemId int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner Item ID',

			ownerStatus ENUM('on', 'off') DEFAULT 'on' COMMENT '',

            PRIMARY KEY (ownerid),
            UNIQUE KEY owner (ownerModule, ownerType, ownerMethod, ownerItemId)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_subscribe (
            subscribeid int(10) UNSIGNED NOT NULL auto_increment,

            ownerid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner ID',
            userid int(10) UNSIGNED NOT NULL COMMENT 'User ID',

			status ENUM('unset', 'on', 'off') DEFAULT 'unset' COMMENT '',
			emailStatus ENUM('unset', 'on', 'off') DEFAULT 'unset' COMMENT '',

			pubkey char(32) NOT NULL DEFAULT '' COMMENT 'Public Key',

			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            PRIMARY KEY (subscribeid),
            UNIQUE KEY subscribe (ownerid, userid),
            KEY userid (userid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify (
            notifyid int(10) UNSIGNED NOT NULL auto_increment,

            ownerid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner ID',
            userid int(10) UNSIGNED NOT NULL COMMENT 'User ID',

            emailSubject VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			emailBody text NOT NULL COMMENT '',
            emailURI VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			emailDate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Send email date',

            bosSubject VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			bosBody text NOT NULL COMMENT '',
            bosURI VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			bosDate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Read notify date in BosUI',

			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            PRIMARY KEY (notifyid),
            UNIQUE KEY notify (ownerid, userid),
            KEY userid (userid)
        )".$charset
    );

}
?>