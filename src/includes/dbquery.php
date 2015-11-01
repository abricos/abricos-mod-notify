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
			    parentid, ownerModule, ownerType, ownerMethod, ownerItemId, ownerStatus, isBase
			) VALUES (
			    ".intval($owner->parentid).",
			    '".bkstr($owner->module)."',
			    '".bkstr($owner->type)."',
			    '".bkstr($owner->method)."',
			    ".intval($owner->itemid).",
			    '".bkstr($owner->status)."',
			    ".intval($owner->isBase)."
			) ON DUPLICATE KEY UPDATE
			    ownerStatus='".bkstr($owner->status)."'
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function Subscribe(NotifyApp $app, NotifyOwner $owner, $userid = 0){
        $userid = $userid > 0 ? $userid : Abricos::$user->id;
        $db = $app->db;
        $sql = "
			SELECT *
			FROM ".$db->prefix."notify_subscribe
			WHERE userid=".bkint($userid)."
			    AND ownerModule='".bkstr($owner->module)."'
			    AND ownerType='".bkstr($owner->type)."'
			    AND ownerid=".intval($owner->ownerid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function SubscribeList(NotifyApp $app, $module, $userid = 0){
        $userid = $userid > 0 ? $userid : Abricos::$user->id;
        $db = $app->db;
        $sql = "
			SELECT
			  s.*,
			  o.*
			FROM ".$db->prefix."notify_subscribe s
			INNER JOIN ".$db->prefix."notify_owner o ON s.ownerid=o.ownerid
			WHERE o.ownerModule='".bkstr($module)."' AND s.userid=".bkint($userid)."
		";
        return $db->query_read($sql);
    }

}

?>