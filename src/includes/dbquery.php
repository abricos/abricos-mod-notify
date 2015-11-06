<?php
/**
 * @package Abricos
 * @subpackage Notify
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class NotifyQuery
 */
class NotifyQuery {

    public static function OwnerAppend(NotifyApp $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_owner (
			    parentid, recordType,
			    ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus,
			    defaultStatus, defaultEmailStatus
			) VALUES (
			    ".intval($owner->parentid).",
			    '".bkstr($owner->recordType)."',
			    '".bkstr($owner->module)."',
			    '".bkstr($owner->type)."',
			    '".bkstr($owner->method)."',
			    ".intval($owner->itemid).",
			    '".bkstr($owner->status)."',
			    '".bkstr($owner->defaultStatus)."',
			    '".bkstr($owner->defaultEmailStatus)."'
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function OwnerBaseList(NotifyApp $app){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.recordType<>'item'
		";
        return $db->query_read($sql);
    }

    public static function OwnerById(NotifyApp $app, $ownerid){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.ownerid=".intval($ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }


    public static function OwnerByKey(NotifyApp $app, $key, $itemid = 0){
        $key = NotifyOwner::ParseKey($key, $itemid);

        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.ownerModule='".bkstr($key->module)."'
			    AND o.ownerType='".bkstr($key->type)."'
			    AND o.ownerMethod='".bkstr($key->method)."'
			    AND o.ownerItemId=".intval($key->itemid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function old_OwnerSave(NotifyApp $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_owner (
			    parentid, ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus,
			    defaultStatus, defaultEmailStatus, isBase, isContainer
			) VALUES (
			    ".intval($owner->parentid).",
			    '".bkstr($owner->module)."',
			    '".bkstr($owner->type)."',
			    '".bkstr($owner->method)."',
			    ".intval($owner->itemid).",
			    '".bkstr($owner->status)."',
			    '".bkstr($owner->defaultStatus)."',
			    '".bkstr($owner->defaultEmailStatus)."',
			    ".intval($owner->isBase).",
			    ".intval($owner->isContainer)."
			) ON DUPLICATE KEY UPDATE
			    ownerStatus='".bkstr($owner->status)."'
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    /* * * * * * * * * * * * * Subscribe * * * * * * * * * * * * */

    public static function SubscribeBaseList(NotifyApp $app){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			INNER JOIN ".$db->prefix."notify_owner o ON s.ownerid=o.ownerid
			WHERE o.recordType<>'item' AND s.userid=".bkint(Abricos::$user->id)."
		";
        return $db->query_read($sql);
    }

    public static function Subscribe(NotifyApp $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			WHERE s.userid=".bkint(Abricos::$user->id)." AND s.ownerid=".intval($owner->id)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function SubscribeAppend(NotifyApp $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_subscribe (
			    ownerid, userid, status, emailStatus, dateline
			) VALUES (
			    ".intval($owner->id).",
			    ".intval(Abricos::$user->id).",
			    '".bkstr($owner->defaultStatus)."',
			    '".bkstr($owner->defaultEmailStatus)."',
			    ".intval(TIMENOW)."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function SubscribeUpdate(NotifyApp $app, NotifyOwner $owner, NotifySubscribe $subscribe){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_subscribe
			SET
			    status='".bkstr($subscribe->status)."',
			    emailStatus='".bkstr($subscribe->emailStatus)."'
			WHERE ownerid=".intval($owner->id)." AND userid=".intval(Abricos::$user->id)."
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function old_SubscribeByOwnerId(NotifyApp $app, $ownerid){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			WHERE s.userid=".bkint(Abricos::$user->id)." AND s.ownerid=".intval($ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

}


?>