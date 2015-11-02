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
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_subscribe (
            subscribeid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            ownerid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner ID',
            userid INT(10) UNSIGNED NOT NULL COMMENT 'User ID',

			status ENUM('unset', 'on', 'off') DEFAULT 'unset' COMMENT '',
			emailStatus ENUM('unset', 'on', 'off') DEFAULT 'unset' COMMENT '',

			pubkey CHAR(32) NOT NULL DEFAULT '' COMMENT 'Public Key',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            PRIMARY KEY (subscribeid),
            UNIQUE KEY subscribe (ownerid, userid),
            KEY userid (userid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify (
            notifyid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            ownerid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner ID',
            userid INT(10) UNSIGNED NOT NULL COMMENT 'User ID',

            emailSubject VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			emailBody TEXT NOT NULL COMMENT '',
            emailURI VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			emailDate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Send email date',

            bosSubject VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			bosBody TEXT NOT NULL COMMENT '',
            bosURI VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			bosDate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Read notify date in BosUI',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            PRIMARY KEY (notifyid),
            UNIQUE KEY notify (ownerid, userid),
            KEY userid (userid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_owner (
            ownerid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            parentid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent Owner ID',

            ownerModule VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Owner Module Name',
            ownerType VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Type',
            ownerMethod VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Method',
            ownerItemId INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner Item ID',

			ownerStatus ENUM('on', 'off') DEFAULT 'on' COMMENT '',

			isBase TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			isContainer TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            PRIMARY KEY (ownerid),
            UNIQUE KEY owner (ownerModule, ownerType, ownerMethod, ownerItemId),
            KEY isBase (isBase)
        )".$charset
    );

    $db->query_write("
        INSERT INTO ".$pfx."notify_owner (
            ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus, isBase
        ) VALUES ('', '', '', 0, 'on', 1)
    ");

}
?>