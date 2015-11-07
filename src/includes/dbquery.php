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
			    defaultStatus, defaultEmailStatus, eventTimeout
			) VALUES (
			    ".intval($owner->parentid).",
			    '".bkstr($owner->recordType)."',
			    '".bkstr($owner->module)."',
			    '".bkstr($owner->type)."',
			    '".bkstr($owner->method)."',
			    ".intval($owner->itemid).",
			    '".bkstr($owner->status)."',
			    '".bkstr($owner->defaultStatus)."',
			    '".bkstr($owner->defaultEmailStatus)."',
			    ".intval($owner->eventTimeout)."
			)
		";
        $db->query_write($sql);
        return intval($db->insert_id());
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

    public static function OwnerByContainer(NotifyApp $app, NotifyOwner $ownerCont, $itemid){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.parentid=".intval($ownerCont->id)." AND o.ownerItemId=".intval($itemid)."
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
			    AND o.ownerItemId=".intval($itemid)."
			LIMIT 1
		";
        return $db->query_first($sql);
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

    /* * * * * * * * * * * * * User Activity * * * * * * * * * * * * */

    public static function ActivityUpdate(NotifyApp $app, NotifyOwner $ownerItem){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_activity (
			    ownerItemId, userid, activity
			) VALUES (
			    ".intval($ownerItem->id).",
			    ".intval(Abricos::$user->id).",
			    ".intval(TIMENOW)."
			) ON DUPLICATE KEY UPDATE
			    activity=".intval(TIMENOW).",
			    viewCount=viewCount+1
		";
        $db->query_write($sql);
    }

    /* * * * * * * * * * * * * Event * * * * * * * * * * * * */

    public static function EventAppend(NotifyApp $app, NotifyOwner $ownerItem, NotifyOwner $ownerMethod){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_event (
			    ownerItemId, ownerMethodId, userid, dateline, timeout
			) VALUES (
			    ".intval($ownerItem->id).",
			    ".intval($ownerMethod->id).",
			    ".intval(Abricos::$user->id).",
			    ".intval(TIMENOW).",
			    ".intval($ownerMethod->eventTimeout)."
			)
		";
        $db->query_write($sql);
        $eventid = $db->insert_id();

        $sql = "
            INSERT INTO ".$db->prefix."notify (
                eventid, userid, dateline
            )
            SELECT
                ".intval($eventid).",
                s.userid,
                ".intval(TIMENOW)." as dateline
            FROM ".$db->prefix."notify_subscribe s
			WHERE s.userid<>".intval(Abricos::$user->id)."
			    AND s.ownerid=".intval($ownerMethod->id)."
			    AND s.status='".NotifySubscribe::STATUS_ON."'
		";
        $db->query_write($sql);

        return $eventid;
    }

    public static function EventListByExpect(NotifyApp $app){
        $db = $app->db;
        $sql = "
			SELECT *
			FROM ".$db->prefix."notify_event
			WHERE status='".NotifyEvent::STATUS_EXPECT."'
			    AND (dateline+timeout)<".intval(TIMENOW)."
		";
        return $db->query_read($sql);
    }

    public static function EventPerfomed(NotifyApp $app, NotifyEvent $event){

    }
}


?>