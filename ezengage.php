<?php
define('SCR', 'ezengage');
require_once ('global.php');
require_once(D_P.'data/bbscache/ezengage_config.php');
if($_GET['mod'] == 'token'){
    require_once(R_P.'hack/ezengage/token.inc.php');
}
else if($_GET['mod'] == 'bind'){
    require_once(R_P.'hack/ezengage/bind.inc.php');
}
else if($_GET['mod'] == 'js'){
    header("Content-Type:text/javascript");
    if($winduid){
        //如果是登录用户，而且有未绑定eze_profile, 绑定之
        require_once(R_P.'hack/ezengage/common.func.php');
        $profile = eze_current_profile();
        if($profile && !$profile['uid']){
            eze_bind($winduid, $profile);
            Cookie('eze_token', '', 0);
            Cookie('eze_auto_register_error', '', 0);
            echo "//bind ok;";
        }
    }
    else{
        if($_GET['scr'] == 'ezengage'){
            require_once(R_P.'hack/ezengage/common.func.php');
            $profile = eze_current_profile();
            $is_auto_register_error = GetCookie('eze_auto_register_error');
            if($profile && $is_auto_register_error){
                echo "window.location.href='register.php';";
            }
        }
        if($_GET['scr'] == 'register'){
            require_once(R_P.'hack/ezengage/common.func.php');
            require_once(R_P. "hack/ezengage/lang.$db_charset.php");
            $profile = eze_current_profile();
            $is_auto_register_error = GetCookie('eze_auto_register_error');
            //auto register fail or disabled
            if($profile && ($is_auto_register_error || !$ezengage_config['enable_auto_register'])){
                $html = $eze_scriptlang['register_notice'];
                foreach(array('provider_name', 'preferred_username') as $item){
                    $html = str_replace('%(' . $item .  ')s', $profile[$item], $html);
                }
                $js = "try{
                    var h5  = document.getElementsByTagName('h5').item(0);
                    h5.innerHTML = '$html';
                }
                catch(e){
                }";
                echo $js;
            }
        }
    }
}
?>
