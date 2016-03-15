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

    $updateManager->module->permission->Install();

    $db->query_write("
        CREATE TABLE ".$pfx."notify_owner (
            ownerid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            parentid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent Owner ID',
			recordType ENUM('root', 'module', 'container', 'method', 'item', 'imethod') DEFAULT 'item' COMMENT '',

            ownerModule VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Owner Module Name',
            ownerType VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Type',
            ownerMethod VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'Owner Method',
            ownerItemId INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner Item ID',

			ownerStatus ENUM('off', 'on') DEFAULT 'on' COMMENT 'Enable/Disable User subscribe',

			defaultStatus ENUM('off', 'on') DEFAULT 'off' COMMENT '',
			defaultEmailStatus ENUM('off', 'parent', 'always', 'first', 'daily', 'weekly') DEFAULT 'off' COMMENT '',

            isBase TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            isEnable TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'The calculated value based on the parent`s value',
			calcDate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Caclulate Date',

            isChildSubscribe TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            eventLimit INT(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Event Timeout (seconds)',
            eventTimeout INT(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Event Timeout (seconds)',

            PRIMARY KEY (ownerid),
            UNIQUE KEY owner (ownerModule, ownerType, ownerMethod, ownerItemId),
            KEY parentid (parentid),
            KEY isBase (isBase),
            KEY recordType (recordType)
        )".$charset
    );

    $db->query_write("
        INSERT INTO ".$pfx."notify_owner (
            ownerid, recordType, ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus,
            defaultStatus, defaultEmailStatus, isBase
        ) VALUES (1, 'root', '', '', '', 0, 'on', 'on', 'daily', 1)
    ");

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_subscribe (
            subscribeid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            parentid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent Subscribe ID',

            ownerid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner ID',
            userid INT(10) UNSIGNED NOT NULL COMMENT 'User ID',

			status ENUM('off', 'on') DEFAULT 'off' COMMENT '',
			emailStatus ENUM('off', 'parent', 'always', 'first', 'daily', 'weekly') DEFAULT 'off' COMMENT '',

            isEnable TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'The calculated value based on the parent`s value',
			calcDate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Caclulate Date',

			pubkey CHAR(32) NOT NULL DEFAULT '' COMMENT 'Public Key',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            PRIMARY KEY (subscribeid),
            UNIQUE KEY subscribe (ownerid, userid),
            KEY ownerid (ownerid),
            KEY userid (userid),
            KEY isEnable (isEnable)
        )".$charset
    );

    // User Activity
    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_activity (
            activityid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            ownerid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner ID',
            userid INT(10) UNSIGNED NOT NULL COMMENT 'User ID',

			activity INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Activity Date',
            viewCount INT(6) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'User ID',

            PRIMARY KEY (activityid),
            UNIQUE KEY owner (ownerid, userid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_event (
            eventid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            ownerid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner Item Method ID',
            userid INT(10) UNSIGNED NOT NULL COMMENT 'User ID',

			status ENUM('waiting', 'runs', 'finished') DEFAULT 'waiting' COMMENT '',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',
            timeout INT(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Event Timeout (seconds)',

            PRIMARY KEY (eventid),
            UNIQUE KEY ownerid (ownerid),
            KEY status (status),
            KEY eventdate (dateline, timeout)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify (
            notifyid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            eventid INT(10) UNSIGNED NOT NULL COMMENT 'Owner ID',
            userid INT(10) UNSIGNED NOT NULL COMMENT 'User ID',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',

            PRIMARY KEY (notifyid),
            UNIQUE KEY notify (eventid, userid),
            KEY userid (userid)
        )".$charset
    );

}

if ($updateManager->isUpdate('0.1.4.1')){

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."notify_mail (
            mailid INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

            toName VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
            toEmail VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',

            fromName VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
            fromEmail VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',

            subject VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',
			body TEXT NOT NULL COMMENT '',

			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'User ID',
            globalid VARCHAR(32) NOT NULL DEFAULT '' COMMENT '',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Create Date',
			sendDate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Send Date',

            PRIMARY KEY (mailid)
        )".$charset
    );

}

?>