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

    public static function SubscribeList(NotifyApp $app, $userid = 0){
        $userid = $userid > 0 ? $userid : Abricos::$user->id;
        $db = $app->db;
        $sql = "
			SELECT
			  s.*,
			  o.*
			FROM ".$db->prefix."notify_subscribe s
			INNER JOIN ".$db->prefix."notify_owner o ON s.ownerid=o.ownerid
			WHERE s.userid=".bkint($userid)."
		";
        return $db->query_read($sql);
    }

}

?>