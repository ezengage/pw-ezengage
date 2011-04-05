<?php
/*
	ezEngage (C)2011  http://ezengage.com
    第三方帐号登录成功后的逻辑
*/

include_once realpath(dirname(__FILE__)). '/common.func.php';

$token = GetCookie('eze_token');

$escaped_token = S::sqlEscape($token);
$profile = $db->fetch_array($db->query("SELECT * FROM pw_eze_profile WHERE token={$escaped_token}"));

//找不到profile,说明cookie 不正确或已经过期，提示用户
if(!$profile){
    if($winduid){
        exit('bad request');
        #showmessage('ezengage:bad_request', EZE_MY_ACCOUNT_URL);
    }
    else {
        exit('bad request');
        #showmessage('ezengage:bad_request', 'index.php');
    }
}
else{
    if($profile['uid'] > 0){
        if($winduid && $profile['uid'] != $winduid){
            Cookie('eze_token', '', 0);
            #showmessage('ezengage:already_bind_to_other_user', EZE_MY_ACCOUNT_URL);
        }
        else {
            if(eze_login_user($profile['uid'])){
                Cookie('eze_token', '', 0);
                header("location:index.php");
            }
            else{
                showmessage('ezengage:login_fail', 'member.php?mod=logging&action=login');
            }
        } 
    }
    else{
        //如果当前已经有phpwind 用户登录了,将eze 用户绑定到该用户
        if($winduid) {
            eze_bind($winduid, $profile);
            Cookie('eze_token', '', 0);
            header("location: ". EZE_MY_ACCOUNT_URL);
        }
        //否则显示将界面要求登录或注册
        else{
            if($ezengage_config['enable_auto_register']){
                $winduid = eze_register_user($profile);
                if($winduid){
                    eze_bind($winduid, $profile, TRUE);
                    header("Location: index.php");
                }
                #showmessage('login_succeed', $_G['gp_refer'], 
                #    array('username' => $_G['member']['username'],'uid' => $winduid)
                #);
            }
            else{
                header("Location: register.php");
                #header("location: member.php?mod=register&referer=" .urlencode(EZE_MY_ACCOUNT_URL));
            }
        }
    }
}
?>
