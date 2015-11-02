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

    public static function Owner(NotifyApp $app, $ownerid){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.ownerid=".intval($ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function OwnerRoot(NotifyApp $app){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.ownerModule='' AND o.ownerType='' AND o.ownerMethod='' AND o.ownerItemId=0
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function OwnerBaseList(NotifyApp $app){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.isBase=1
		";
        return $db->query_read($sql);
    }

    public static function OwnerSave(NotifyApp $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_owner (
			    parentid, ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus, isBase, isContainer
			) VALUES (
			    ".intval($owner->parentid).",
			    '".bkstr($owner->module)."',
			    '".bkstr($owner->type)."',
			    '".bkstr($owner->method)."',
			    ".intval($owner->itemid).",
			    '".bkstr($owner->status)."',
			    ".intval($owner->isBase).",
			    ".intval($owner->isContainer)."
			) ON DUPLICATE KEY UPDATE
			    ownerStatus='".bkstr($owner->status)."'
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function SubscribeUpdate(NotifyApp $app, NotifyOwner $owner, NotifySubscribe $subscribe){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_subscribe (
			    ownerid, userid, status, dateline
			) VALUES (
			    ".intval($owner->id).",
			    ".intval(Abricos::$user->id).",
			    '".bkstr($subscribe->status)."',
			    ".intval(TIMENOW)."
			) ON DUPLICATE KEY UPDATE
			    status='".bkstr($subscribe->status)."'
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function Subscribe(NotifyApp $app, NotifyOwner $owner, $userid = 0){
        $userid = $userid > 0 ? $userid : Abricos::$user->id;
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			WHERE s.userid=".bkint($userid)." AND s.ownerid=".intval($owner->id)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function SubscribeBaseList(NotifyApp $app){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			INNER JOIN ".$db->prefix."notify_owner o ON s.ownerid=o.ownerid
			WHERE o.isBase=1 AND s.userid=".bkint(Abricos::$user->id)."
		";
        return $db->query_read($sql);
    }
}


?>