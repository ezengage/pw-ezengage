<?php
/*
	ezEngage (C)2011  http://ezengage.com
    accept token from ezengage service, and fetch profile data via ezengage api
*/

include_once realpath(dirname(__FILE__)). '/common.func.php';
include_once realpath(dirname(__FILE__)). '/apiclient.php';
include_once realpath(dirname(__FILE__)). "/lang.$db_charset.php";

$eze_app_key = $ezengage_config['app_key'];

if(empty($eze_app_key)){
    Showmsg($eze_scriptlang['bad_config']);
    exit();
}

$ezeApiClient = new EzEngageApiClient($eze_app_key);
if(empty($_POST['token'])){
    Showmsg($eze_scriptlang['bad_request']);
    exit();
}

//may be do some basic check
$profile = $ezeApiClient->getProfile(strval($_POST['token']));
if(!$profile){
    Showmsg($eze_scriptlang['login_fail']);
    exit();
}

//convert charset 
foreach($profile as $key => $val){
    if(is_string($val)){
        $profile[$key] = eze_convert($val, 'UTF-8', $db_charset);
    }
}

$identity = S::sqlEscape($profile['identity']);
$row = $db->fetch_array($db->query("SELECT token,uid,identity FROM pw_eze_profile WHERE identity={$identity}"));

//new user
if(!$row){
    $token = md5($_POST['token'] . time());
    $data = array(
        'token' => $token,
        'uid' => 0,
        'identity' => $profile['identity'],
        'provider_code' => $profile['provider_code'],
        'provider_name' => $profile['provider_name'],
        'preferred_username' => $profile['preferred_username'],
        'sync_list' => EZE_DEFAULT_SYNC_LIST,
    );
	$pwSQL = pwSqlSingle($data);
	$db->update("INSERT INTO pw_eze_profile SET $pwSQL");
	$newid = $db->insert_id();
}
else{
    $token = $row['token'];    
}

Cookie('eze_token', $token, time() + 3600);

//这个文件只处理同ezenenge 服务的交互和身份数据的保存，同phpwind系统的集成在下一步完成。
header("Location: $basename?mod=bind");
?>
