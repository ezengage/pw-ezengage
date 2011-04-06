<?php
header("Content-Type:text/javascript;charset=$db_charset");
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
    elseif($_GET['scr'] == 'register'){
        require_once(R_P. 'hack/ezengage/common.func.php');
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
    elseif($_GET['scr'] == 'login'){
        
    }
    else {
        require_once(R_P.'hack/ezengage/common.func.php');
        $html = "<div id='eze_footer_wrap' style=\"display:none;position:absolute;right:480px;top:45px\">"
                . eze_login_widget('tiny', 150, 54)
                . "</div>";
        $js = sprintf(
            "try{
            var _eze_html = '%s';
            var _ele = document.createElement('div');
            _ele.innerHTML = _eze_html;
            document.body.appendChild(_ele);
            var _banner = document.getElementById('banner');
            var _form = _banner.getElementsByTagName('form').item(0);    
            _eze_login = document.getElementById('eze_footer_wrap');
            _banner.insertBefore(_eze_login, _form); 
            _eze_login.style.display = '';
            }catch(e){}
            ",
            addslashes($html)
        );
        echo $js;
    }
}
?>
