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

    public static function OwnerAppend(AbricosApplication $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_owner (
			    parentid, recordType,
			    ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus,
			    defaultStatus, defaultEmailStatus, eventTimeout, isChildSubscribe, isBase,
			    isEnable, calcDate
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
			    ".intval($owner->eventTimeout).",
			    ".intval($owner->isChildSubscribe).",
			    ".intval($owner->isBase).",
			    ".intval($owner->isEnable).",
			    ".intval($owner->calcDate)."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function OwnerUpdateByCalc(AbricosApplication $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_owner
			SET isEnable=".intval($owner->isEnable).",
			    calcDate=".intval(TIMENOW)."
			WHERE ownerid=".intval($owner->id)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function OwnerBaseList(AbricosApplication $app){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.isBase=1
		";
        return $db->query_read($sql);
    }

    public static function OwnerById(AbricosApplication $app, $ownerid){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.ownerid=".intval($ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function OwnerListByContainer(AbricosApplication $app, NotifyOwner $ownerCont, $itemid){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.ownerModule='".bkstr($ownerCont->module)."'
			    AND o.ownerType='".bkstr($ownerCont->type)."'
			    AND o.ownerItemId=".intval($itemid)."
		";
        return $db->query_read($sql);
    }

    public static function OwnerItemMethodList(AbricosApplication $app, NotifyOwner $owner, $itemid){
        $db = $app->db;
        $sql = "
			SELECT o.*
			FROM ".$db->prefix."notify_owner o
			WHERE o.recordType='".bkstr(NotifyOwner::TYPE_ITEM_METHOD)."'
			    AND o.ownerModule='".bkstr($owner->module)."'
			    AND o.ownerType='".bkstr($owner->type)."'
			    AND o.ownerItemId=".intval($itemid)."
		";
        return $db->query_read($sql);
    }

    public static function OwnerByKey(AbricosApplication $app, $key, $itemid = 0){
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

    public static function SubscribeBaseList(AbricosApplication $app){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			INNER JOIN ".$db->prefix."notify_owner o ON s.ownerid=o.ownerid
			WHERE o.isBase=1 AND s.userid=".bkint(Abricos::$user->id)."
		";
        return $db->query_read($sql);
    }

    public static function Subscribe(AbricosApplication $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			SELECT s.*
			FROM ".$db->prefix."notify_subscribe s
			WHERE s.userid=".bkint(Abricos::$user->id)." AND s.ownerid=".intval($owner->id)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function SubscribeAppend(AbricosApplication $app, NotifySubscribe $subscribe){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_subscribe (
			    parentid, ownerid, userid, status, emailStatus, isEnable, calcDate, dateline
			) VALUES (
			    ".intval($subscribe->parentid).",
			    ".intval($subscribe->ownerid).",
			    ".intval(Abricos::$user->id).",
			    '".bkstr($subscribe->status)."',
			    '".bkstr($subscribe->emailStatus)."',
			    ".intval($subscribe->isEnable).",
			    ".intval($subscribe->calcDate).",
			    ".intval(TIMENOW)."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function SubscribeUpdate(AbricosApplication $app, NotifyOwner $owner, NotifySubscribe $subscribe){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_subscribe
			SET
			    status='".bkstr($subscribe->status)."',
			    emailStatus='".bkstr($subscribe->emailStatus)."'
			WHERE ownerid=".intval($owner->id)." AND userid=".intval(Abricos::$user->id)."
		";
        $db->query_write($sql);
    }

    public static function SubscribeCalcClean(AbricosApplication $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_subscribe
			SET calcDate=0
			WHERE ownerid=".intval($owner->id)."
		";
        $db->query_write($sql);
    }

    public static function SubscribeCalcCleanByUser(AbricosApplication $app){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_subscribe
			SET calcDate=0
			WHERE userid=".intval(Abricos::$user->id)."
		";
        $db->query_write($sql);
    }

    public static function SubscribeUpdateByCalc(AbricosApplication $app, NotifySubscribe $subscribe){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_subscribe
			SET parentid=".intval($subscribe->parentid).",
			    isEnable=".intval($subscribe->isEnable).",
			    calcDate=".intval(TIMENOW)."
			WHERE subscribeid=".intval($subscribe->id)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function SubscribeAutoAppend(AbricosApplication $app, NotifyOwner $owner){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_subscribe (
			    ownerid, userid, status, emailStatus, dateline
			)
			SELECT
			    ".intval($owner->id)." as ownerid,
			    userid,
			    status,
			    emailStatus,
			    ".intval(TIMENOW)." as dateline
			FROM ".$db->prefix."notify_subscribe
			WHERE ownerid=".intval($owner->parentid)."
			    AND status='".NotifySubscribe::STATUS_ON."'
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    /* * * * * * * * * * * * * User Activity * * * * * * * * * * * * */

    public static function ActivityUpdate(AbricosApplication $app, NotifyOwner $ownerItem){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_activity (
			    ownerid, userid, activity
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

    public static function EventAppend(AbricosApplication $app, NotifyOwner $ownerItemMethod){
        $db = $app->db;
        $sql = "
			INSERT INTO ".$db->prefix."notify_event (
			    ownerid, userid, dateline, timeout
			) VALUES (
			    ".intval($ownerItemMethod->id).",
			    ".intval(Abricos::$user->id).",
			    ".intval(TIMENOW).",
			    ".intval($ownerItemMethod->eventTimeout)."
			)
		";
        $db->query_write($sql);
        $eventid = $db->insert_id();

        // Subscribe users up to this event
        $ownerid = $ownerItemMethod->id;
        $ownerMethod = $ownerItemMethod->GetParent();
        if ($ownerMethod->isChildSubscribe){
            $ownerid = $ownerMethod->id;
        }

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
			    AND s.ownerid=".intval($ownerid)."
			    AND s.isEnable=1
		";
        $db->query_write($sql);

        return $eventid;
    }

    public static function EventListByWaiting(AbricosApplication $app){
        $db = $app->db;
        $sql = "
			SELECT *
			FROM ".$db->prefix."notify_event
			WHERE status='".NotifyEvent::STATUS_WAITING."'
			    AND (dateline+timeout)<".intval(TIMENOW)."
		";
        return $db->query_read($sql);
    }

    public static function SummaryList(AbricosApplication $app){
        $db = $app->db;
        $sql = "
			SELECT
                o.ownerModule as module,
                count(n.userid) as cnt
			FROM ".$db->prefix."notify n
			INNER JOIN ".$db->prefix."notify_event e ON n.eventid=e.eventid
			INNER JOIN ".$db->prefix."notify_owner o ON o.ownerid=e.ownerid
			WHERE n.userid=".intval(Abricos::$user->id)."
			GROUP BY o.ownerModule
		";
        return $db->query_read($sql);
    }

    public static function NoticeListByOwnerItemIds(AbricosApplication $app, NotifyOwnerKey $key, $ids){
        $cnt = count($ids);
        if ($cnt === 0){
            return null;
        }
        $wh = array();
        for ($i = 0; $i < $cnt; $i++){
            $wh[] = "o.ownerItemId=".intval($ids[$i]);
        }
        $db = $app->db;
        $sql = "
			SELECT
                n.notifyid,
                n.eventid,
                o.ownerid,
                o.ownerModule,
                o.ownerType,
                o.ownerMethod,
                o.ownerItemId,
                n.dateline
			FROM ".$db->prefix."notify n
			INNER JOIN ".$db->prefix."notify_event e ON n.eventid=e.eventid
			INNER JOIN ".$db->prefix."notify_owner o ON o.ownerid=e.ownerid
			WHERE n.userid=".intval(Abricos::$user->id)."
			    AND o.ownerModule='".bkstr($key->module)."'
			    AND o.ownerType='".bkstr($key->type)."'
			    AND (".implode(" OR ", $wh).")
		";
        return $db->query_read($sql);
    }

    public static function NoticeRemove(AbricosApplication $app, NotifyNoticeList $noticeList){
        $cnt = $noticeList->Count();
        if ($cnt === 0){
            return;
        }
        $wh = array();
        for ($i = 0; $i < $cnt; $i++){
            $notice = $noticeList->GetByIndex($i);
            $wh[] = "notifyid=".intval($notice->id);
        }
        $db = $app->db;
        $sql = "
			DELETE FROM ".$db->prefix."notify WHERE ".implode(" OR ", $wh)."
		";
        return $db->query_write($sql);
    }


    public static function MailAppend(AbricosApplication $app, NotifyMail $mail){
        $db = $app->db;
        $mail->dateline = TIMENOW;
        $mail->sendDate = 0;

        $sql = "
			INSERT INTO ".$db->prefix."notify_mail (
			    toName, toEmail, fromName, fromEmail,
			    subject, body,
			    userid, globalid, isDebug,
			    sendDate, sendError, sendErrorInfo,
			    dateline
			) VALUES (
			    '".bkstr($mail->toName)."',
			    '".bkstr($mail->toEmail)."',
			    '".bkstr($mail->fromName)."',
			    '".bkstr($mail->fromEmail)."',

			    '".bkstr($mail->subject)."',
			    '".bkstr($mail->body)."',

			    ".intval($mail->userid).",
			    '".bkstr($mail->globalid)."',
			    ".($mail->isDebug ? 1 : 0).",
			    0, 0, '',

			    ".intval(TIMENOW)."
			)
		";
        $db->query_write($sql);

        return $db->insert_id();
    }

    public static function MailSetSended(AbricosApplication $app, NotifyMail $mail){
        $db = $app->db;
        $sql = "
			UPDATE ".$db->prefix."notify_mail
			SET sendDate=".intval(TIMENOW).",
			    sendError=".($mail->sendError ? 1 : 0).",
			    sendErrorInfo='".bkstr($mail->sendErrorInfo)."'
			WHERE mailid=".intval($mail->id)."
			LIMIT 1
		";
        $mail->sendDate = TIMENOW;
        $db->query_write($sql);
    }

    public static function MailList(AbricosApplication $app){
        $db = $app->db;
        $sql = "
			SELECT *
			FROM ".$db->prefix."notify_mail
			ORDER BY dateline DESC, mailid DESC
			LIMIT 50
		";
        return $db->query_read($sql);
    }

    public static function Mail(AbricosApplication $app, $mailid){
        $db = $app->db;
        $sql = "
			SELECT *
			FROM ".$db->prefix."notify_mail
			WHERE mailid=".intval($mailid)."
		";
        return $db->query_first($sql);
    }
}
