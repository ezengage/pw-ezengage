<?php
/*
	ezEngage (C)2011  http://ezengage.com
    第三方帐号登录成功后的逻辑
*/

include_once realpath(dirname(__FILE__)). '/common.func.php';
include_once realpath(dirname(__FILE__)). "/lang.$db_charset.php";

$token = GetCookie('eze_token');

$escaped_token = S::sqlEscape($token);
$profile = $db->fetch_array($db->query("SELECT * FROM pw_eze_profile WHERE token={$escaped_token}"));

//找不到profile,说明cookie 不正确或已经过期，提示用户
if(!$profile){
    if($winduid){
        refreshto(EZE_MY_ACCOUNT_URL, $eze_scriptlang['bad_request'], 3);
    }
    else {
        refreshto('index.php', $eze_scriptlang['bad_request'], 3);
    }
}
else{
    if($profile['uid'] > 0){
        if($winduid && $profile['uid'] != $winduid){
            Cookie('eze_token', '', 0);
            refreshto(EZE_MY_ACCOUNT_URL, $eze_scriptlang['already_bind_to_other_user'], 3);
        }
        else {
            if(eze_login_user($profile['uid'])){
                Cookie('eze_token', '', 0);
                header("location:index.php");
            }
            else{
                refreshto('login.php', $eze_scriptlang['login_fail'], 3);
            }
        } 
    }
    else{
        //如果当前已经有phpwind 用户登录了,将eze 用户绑定到该用户
        if($winduid) {
            eze_bind($winduid, $profile, True);
            Cookie('eze_token', '', 0);
            header("location: ". EZE_MY_ACCOUNT_URL);
        }
        //否则显示将界面要求登录或注册
        else{
            if($ezengage_config['enable_auto_register']){
                //always error
                Cookie('eze_auto_register_error', 1);
                $winduid = eze_register_user($profile);
                if($winduid){
                    eze_bind($winduid, $profile, TRUE);
                    Cookie('eze_token', '', 0);
                    //we have no error
                    Cookie('eze_auto_register_error', '', 0);
                    header("Location: index.php");
                }
            }
            else{
                header("Location: register.php");
            }
        }
    }
}
?>
