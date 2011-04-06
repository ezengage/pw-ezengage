<?php
/*
	ezEngage (C)2011  http://ezengage.com
    sync post to bind accounts
*/

require_once(D_P.'data/bbscache/ezengage_config.php');
require_once realpath(dirname(__FILE__)). '/common.func.php';

class ezengage_sync_handler {
    public static $sync_event = null;
    public static $sync_to = array();

    static function register_shutdown(){
        global $action;
        global $winduid;
        if(SCR == 'post'){
            if($action == 'new'){
                self::$sync_event = 'thread';
            }
            elseif ($action == 'reply' || $action == 'quote'){
                self::$sync_event = 'reply';
            }
        }

        if(self::$sync_event){
            self::$sync_to = eze_get_default_sync_to($winduid, self::$sync_event);
            if(count(self::$sync_to) > 0){
                $func = array('ezengage_sync_handler', '_sync_' . self::$sync_event);
                if(is_callable($func)){
                    register_shutdown_function($func);
                }
            }
        }
    }

    static function _sync_thread(){
        global $tid;
        eze_publisher::sync_newthread($tid, self::$sync_to);
    }

    static function _sync_reply(){
        global $pid;
        eze_publisher::sync_reply($pid, self::$sync_to);
    }

    static function _sync_newblog(){
        global $_G;
        $blogid = isset($GLOBALS['newblog']['blogid']) ? (int)$GLOBALS['newblog']['blogid'] : 0;
        if($blogid >= 1){
            eze_publisher::sync_newblog($blogid, $_G['gp_eze_should_sync']);
        }
    }

}


ezengage_sync_handler::register_shutdown();
