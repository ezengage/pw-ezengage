<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

//constants

#define(EZE_ALL_SYNC_LIST, 'newthread,newblog,newshare,newdoing,reply,blogcomment,sharecomment,doingcomment');
define(EZE_ALL_SYNC_LIST, 'thread,reply');
define(EZE_DEFAULT_SYNC_LIST, 'thread');
define(EZE_MY_ACCOUNT_URL, 'hack.php?H_name=ezengage');

global $hack_name;

global $_G;
$_G['charset'] = $db_charset;

//转换编码
function eze_convert($source, $in, $out){
    $in = strtoupper($in);
    if ($in == "UTF8"){
        $in = "UTF-8";
    }   
    if ($out == "UTF8"){
        $out = "UTF-8";
    }       
    if( $in == $out ){
        return $source;
    }   
    if(function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($source, $out, $in );
    } elseif (function_exists('iconv'))  {
        return iconv($in,$out."//IGNORE", $source);
    }   
    return $source;
}   

//过滤
function eze_filter($content) {
    global $_G;
    //attach 
    $content = preg_replace('!\[(attachimg|attach)\]([^\[]+)\[/(attachimg|attach)\]!', '', $content);
    //image
    $content = preg_replace('|\[img(?:=[^\]]*)?](.*?)\[/img\]|', '\\1 ', $content);
    //UBB
    $re ="#\[([a-z]+)(?:=[^\]]*)?\](.*?)\[/\\1\]#sim";
    while(preg_match($re, $content)) {
        $content = preg_replace($re, '\2', $content);
    }
    //smiles
    $re = isset($_G['cache']['smileycodes']) ? (array)$_G['cache']['smileycodes'] : array();
    $smiles_searcharray = isset($_G['cache']['smilies']['searcharray']) ? (array)$_G['cache']['smilies']['searcharray'] : array();
    $content = str_replace($re, '', $content);
    $content = preg_replace($smiles_searcharray, '', $content);
    return $content;
}


function eze_getpwd($winduid) {
	global $db;
	return PwdCode($db->get_value("SELECT password FROM pw_members WHERE uid=".$winduid));
}

function eze_getsafecv($winduid) {
	global $db;
	return $db->get_value("SELECT safecv FROM pw_members WHERE uid=".$winduid);
}

function eze_login_user($uid){
    if (empty($uid)) return false;
    global $safecv,$cktime,$db_ckpath,$db_ckdomain;
    $pw_pwd = eze_getpwd($uid);
    $safecv = eze_getsafecv($uid);
    Cookie("winduser",StrCode($uid."\t".$pw_pwd."\t".$safecv),$cktime);
    Cookie("ck_info",$db_ckpath."\t".$db_ckdomain);
    Cookie('lastvisit','',0);//将$lastvist清空以将刚注册的会员加入今日到访会员中
    return true;
}

/**
 * 尝试注册用户,如果成功返回True,否则返回False
 */
function eze_register_user($profile){
    global $db;
	global $ezengage_config,$onlineip,$timestamp,$invcode,$db_ckpath,$db_ckdomain,$db_modes,$db_bbsname,$db_sitehash,$db_registerfile,$db_pptifopen,$db_ppttype ,$db_ppturls,$db_bbsurl;


    $password = md5(mt_rand(7,999999));
    $password = substr($password,5,8); 
    //TODO:make the email suffix as an option
    $email = substr(md5($profile['identity'] . time()), 10) . '_' . strval($profile['pid']) . '@' . $ezengage_config['app_domain'] . '.ezengage.net';

	require_once(R_P.'require/functions.php');
	$rg_config  = L::reg();
	$inv_config = L::config(null, 'inv_config');
	
	$regpwd = $regpwdrepeat = $password;
	$regemailtoall = $regemail = $email;
	$regreason = $question = $customquest = $answer=$customdata=$forward='';
	
	list($regminname,$regmaxname) = explode("\t", $rg_config['rg_namelen']);
	list($rg_regminpwd,$rg_regmaxpwd) = explode("\t", $rg_config['rg_pwdlen']);
	$sRegpwd = $regpwd;
	$register = L::loadClass('Register', 'user');
	/** @var $register PW_Register */

    $regname = $profile['preferred_username'];

    $name_check = $db->get_value('SELECT COUNT(*) AS count FROM pw_members WHERE username LIKE ' . S::sqlEscape("$regname%"));
    if($name_check > 0){
        $regname = $regname . "" . $name_check;
    }

	$ret = $register->checkSameNP($regname, $regpwd);

	$register->setStatus(11);
	$regemailtoall && $register->setStatus(7);
	$register->setName($regname);
	$register->setPwd($regpwd, $regpwdrepeat);
	$register->setEmail($regemail);
	$register->setSafecv($question, $customquest, $answer);
	$register->setReason($regreason);
	$register->setCustomdata($customdata);
	$register->execute();

	if ($rg_config['rg_allowregister']==2) {
		$register->disposeInv();
	}
	list($winduid, $rgyz, $safecv) = $register->getRegUser();
	
	$windid  = $regname;
	$windpwd = md5($regpwd);
	if ($rg_config['rg_allowsameip']) {
		if (file_exists(D_P.'data/bbscache/ip_cache.php')) {
			writeover(D_P.'data/bbscache/ip_cache.php',"<$onlineip>","ab");
		} else {
			writeover(D_P.'data/bbscache/ip_cache.php',"<?php die;?><$timestamp>\n<$onlineip>");
		}
	}
	//addonlinefile();
	if (GetCookie('userads') && $inv_linkopen && $inv_linktype == '1') {
		list($uid,$a) = explode("\t",GetCookie('userads'));
		if (is_numeric($uid) || ($a && strlen($a)<16)) {
			require_once(R_P.'require/userads.php');
		}
	}
	if (GetCookie('o_invite') && $db_modes['o']['ifopen'] == 1) {
		list($o_u,$hash,$app) = explode("\t",GetCookie('o_invite'));
		if (is_numeric($o_u) && strlen($hash) == 18) {
			require_once(R_P.'require/o_invite.php');
		}
	}
    Cookie("winduser",StrCode($winduid."\t".PwdCode($windpwd)."\t".$safecv));
    Cookie("ck_info",$db_ckpath."\t".$db_ckdomain);
    Cookie('lastvisit','',0);//将$lastvist清空以将刚注册的会员加入今日到访会员中

	//发送短消息
	if ($rg_config['rg_regsendmsg']) {
		$rg_config['rg_welcomemsg'] = str_replace('$rg_name', $regname, $rg_config['rg_welcomemsg']);
		M::sendNotice(
			array($windid),
			array(
				'title' => "Welcome To[{$db_bbsname}]!",
				'content' => $rg_config['rg_welcomemsg'],
			)
		);
	}

	//passport
	if ($db_pptifopen && $db_ppttype == 'server' && ($db_ppturls || $forward)) {
		$action = 'login';
		$jumpurl = $forward ? $forward : $db_ppturls;
		empty($forward) && $forward = $db_bbsurl;
		require_once(R_P.'require/passport_server.php');
	}
    return $winduid;
}

function eze_on_bind_shutdown(){
    global $ezengage_config;
    global $winduid;
    global $profile;
    if(!$winduid && !$profile['uid']){
        Cookie('eze_fail_auto_register', 1);
    }
}

function eze_login_widget($style = 'normal', $width = 'auto', $height = 'auto'){
    global $db_bbsurl;
    global $ezengage_config;
    $site_url = $db_bbsurl;
    $eze_options = $ezengage_config;

    $token_cb = $site_url . '/ezengage.php?mod=token';
    if(in_array($style, array('normal','medium','small', 'tiny'))){
        $html = sprintf('<iframe class="eze_widget" border="0" src="http://%s.ezengage.net/login/%s/widget/%s?token_cb=%s&w=%s&h=%s" scrolling="no" frameBorder="no" style="width:%s;height:%s;"></iframe>', 
               $eze_options['app_domain'],
               $eze_options['app_domain'],
               $style,
               urlencode($token_cb),
               $width,$height,
               $width != 'auto' ? $width .'px' : 'auto',
               $height != 'auto' ? $height .'px' : 'auto'
        );
        return $html;
    }
}

function eze_login_widget_output($style = 'normal', $width = 'auto', $height = 'auto'){
    echo eze_login_widget($style, $width, $height);
}
function eze_sync_list($profile){
    global $db_charset;
    require(R_P."hack/ezengage/lang.$db_charset.php");

    $html = array();
    foreach(explode(',', EZE_ALL_SYNC_LIST) as $sync_item){
        if (strpos($profile['sync_list'], $sync_item) === FALSE){
            $html[] = "<input name='sync_list_{$profile[pid]}[]' type='checkbox' class='checkbox'
                       value='$sync_item' />";
        }
        else{
            $html[] = "<input name='sync_list_{$profile[pid]}[]' type='checkbox' class='checkbox'
                       value='$sync_item' checked='checked' />";
        }
        $html[] = $eze_scriptlang['sync_name_' . $sync_item];
    }
    $html = implode(' ', $html);
    return $html;
}

function eze_sync_list_output($profile){
    print eze_sync_list($profile);
}

function eze_get_default_sync_to($uid, $event){
    $event = mysql_real_escape_string($event);
    $query = DB::query("SELECT pid FROM " . DB::table('eze_profile') . " WHERE uid='$uid' AND sync_list LIKE '%$event%'");
    $pids = array();
    while($profile = DB::fetch($query)) {
        $pids[] = $profile['pid'];
    }
    return $pids;
}

function eze_get_profiles($uid){
    global $db;
    $eze_profiles = array();
    $query = $db->query("SELECT * FROM pw_eze_profile WHERE uid='$uid'");
    while($profile = $db->fetch_array($query)) {
        $eze_profiles[] = $profile;
    }
    return $eze_profiles;
}

function eze_current_profile(){
    global $_G;
    if(empty($_G['cookie']['eze_token'])){
        return NULL;
    }
    $token = authcode($_G['cookie']['eze_token'], 'DECODE');
    if(empty($token)){
        return NULL;
    }
    $escaped_token = mysql_real_escape_string($token);
    $profile = DB::fetch_first("SELECT * FROM " . DB::table('eze_profile') ." WHERE token='{$escaped_token}'");
    return $profile;
}

function eze_bind($winduid, $profile, $send_pm = FALSE){
    global $db;
    //include(DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php');
    //$lang = $scriptlang['ezengage'];

    if($winduid && $profile && !$profile['uid']){
        $ret = $db->query(sprintf(
            "UPDATE pw_eze_profile SET uid = %d WHERE pid = %d",
            $winduid, $profile['pid']
        ));
        Cookie('eze_token', '', 0);
        /*
        if($send_pm){
            $replaces = array(
                '{siteurl}' => $_G['siteurl'],
                '{provider_name}' => $profile['provider_name'],
                '{preferred_username}' => $profile['preferred_username'],
            );
            $subject = addslashes(str_replace(array_keys($replaces), array_values($replaces), $lang['new_bind_pm_subject']));
            $message = addslashes(str_replace(array_keys($replaces), array_values($replaces), $lang['new_bind_pm_message']));
            sendpm($_G['uid'], $subject, $message, 0);
        }
        */
    }
}

class eze_publisher {

    //同步主题
    static function sync_newthread($pid, $sync_to){
       self::sync_post($pid, $sync_to); 
    }

    //同步回复
    static function sync_reply($pid, $sync_to){
       self::sync_post($pid, $sync_to); 
    }

    //同步主题或回复
    static function sync_post($pid, $sync_to){
        $post = DB::fetch_first("SELECT tid,pid,authorid,subject,message,first FROM " . DB::table('forum_post') . " WHERE pid={$pid};");
        if(!$post){
            return;
        }
        $uid = $post['authorid'];
        $status = self::format_post_status($post);
        self::publish($uid, $sync_to, $status);
    } 

    static function format_post_status($post){
        global $_G;
        if($post['first']){
            $url = $_G['siteurl'] . "forum.php?mod=viewthread&tid=$post[tid]";
        }
        else{
            $url = $_G['siteurl'] . "forum.php?mod=redirect&goto=findpost&pid=$post[pid]&ptid=$post[tid]";
        }
        $status = $post['subject'] . ' ' . $post['message'];
        $status = eze_convert($status, $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = $url . ' ' . $status;
        #这里的截断只是为了防止大文章时发送过大的数据。
        $status = substr($status, 0, 1000);
        return $status;
    }

    //同步记录
    static function sync_newdoing($doid, $sync_to){
        $doing = DB::fetch_first("SELECT uid,doid,message FROM " . DB::table('home_doing') . " WHERE doid={$doid};");
        if(!$doing){
            return;
        }
        $status = self::format_doing_status($doing);
        self::publish($doing['uid'], $sync_to, $status);
    }

    static function format_doing_status($doing){
        global $_G;
        $status = eze_convert($doing['message'], $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }

    //同步Blog
    static function sync_newblog($blogid, $sync_to){
        $blog = DB::fetch_first("SELECT blogid,uid,subject FROM " . DB::table('home_blog') . " WHERE blogid={$blogid}");
        if(!$blog){
            return;
        }
        $status = self::format_blog_status($blog);
        self::publish($blog['uid'], $sync_to, $status);
    }

    static function format_blog_status($blog){
        global $_G;
        $status = eze_convert($blog['subject'], $_G['charset'], 'UTF-8');
        $link = $_G['siteurl']. "home.php?mod=space&uid={$blog[uid]}&do=blog&id={$blog[blogid]}";
        $status = eze_filter($status);
        $status = $link . ' ' . $status;
        $status = substr($status, 0, 1000);
        return $status;
    }

    //同步Share
    static function sync_newshare($sid, $sync_to){
        $share = DB::fetch_first("SELECT * FROM " . DB::table('home_share') . " WHERE sid={$sid}");
        $status = self::format_share_status($share);
        if(!empty($status)){
            self::publish($share['uid'], $sync_to, $status);
        }
    }

    static function format_share_status($share){
        global $_G;
        $type_map = array(
            'space' => 'username',
			'blog' => 'subject',
			'album' => 'albumname',
			'pic' => 'albumname',
			'thread' => 'subject',
			'article' => 'title',
			'link' => 'link',
			'video' => 'link',
			'music' => 'link',
			'flash' => 'link',
		);
        $t = $type_map[$share['type']];
        if(empty($t)){
            return false;
        }

		$body_data = unserialize($share['body_data']);
		if('link' != $t){
            //如果分享的是站内的内容，把链接提取出来
			$pattern = '/^<a[ ]+href[ ]*=[ ]*"([a-zA-Z0-9\/\\\\@:%_+.~#*?&=\-]+)"[ ]*>(.+)<\/a>$/';
			preg_match($pattern, $body_data[$t], $match);
			if(count($match) !== 3){
				return false;
			}
			$link = $_G['siteurl']. $match[1];
			$title = ('pic' == $t) ? $body_data['title'] : $match[2];
		}else{
			$link = $body_data['data'];
		}
		
		$status = !empty($share['body_general']) ? $share['body_general'] : $body_data['title_template'];

		if(!empty($title)){
            $status .= '  '. strval($title);
        }
        $status = $link . ' ' . $status;

        $status = eze_convert($status, $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }

    static function sync_comment($cid, $sync_to){
        $comment = DB::fetch_first("SELECT cid,uid,idtype,id,authorid,message FROM " . DB::table('home_comment')  . " WHERE cid = $cid ");
        $status = self::format_comment_status($comment);
        self::publish($comment['authorid'], $sync_to, $status);
    }

    static function format_comment_status($comment){
        global $_G;
        switch($comment['idtype']){
            case 'blogid':
                $do = 'blog';
                break;
            case 'sid':
                $do = 'share';
                break;
            default:
                return;
        }
        $link = $_G['siteurl'] . "home.php?mod=space&do=$do&uid={$comment[uid]}&id={$comment[id]}#comment_anchor_{$comment[cid]}";
        $status = eze_convert($comment['message'], $_G['charset'], 'UTF-8');
        $status = $link . ' ' . eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }

    static function sync_doingcomment($dcid, $sync_to){
        $comment = DB::fetch_first("SELECT id,uid,message FROM " . DB::table('home_docomment')  . " WHERE id = $dcid ");
        $status = self::format_doingcomment_status($comment);
        self::publish($comment['uid'], $sync_to, $status);
    }

    static function format_doingcomment_status($docomment){
        global $_G;
        $status = eze_convert($docomment['message'], $_G['charset'], 'UTF-8');
        $status = substr($status, 0, 1000);
        return $status;
    }

    //将内容发布出去,所有的同步内容最终通过这个函数发布
    static function publish($uid, $sync_to, $status){
        global $_G;
        $eze_app_key = $_G['cache']['plugin']['ezengage']['eze_app_key'];
        if(empty($eze_app_key)){
            return ;
        }
        $ezeApiClient = new EzEngageApiClient($eze_app_key);
        foreach($sync_to as $profile_id){
            $row = DB::fetch_first("SELECT identity FROM " . DB::table('eze_profile') . " WHERE uid={$uid} AND pid={$profile_id}");
            if($row){
                $ret = $ezeApiClient->updateStatus($row['identity'], $status);
            }
        }
    }
}
