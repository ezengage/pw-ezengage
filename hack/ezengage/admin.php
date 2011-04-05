<?php
!function_exists('adminmsg') && exit('Forbidden');

if (empty($action)) {
	include_once(D_P.'data/bbscache/ezengage_config.php');
	ifcheck($ezengage_config['open'],'open');
	include PrintHack('admin');exit;
}elseif($action=='submit'){
	InitGP(array('ezengage_config'),'P');
	$value=serialize($ezengage_config);
    updatemysql($value);
    updatecache_rr();
	adminmsg('operate_success');
}

function updatemysql($value){
	global $db;
	$rt = $db->get_one("SELECT * FROM pw_hack WHERE hk_name='ezengage_config'");
	if($rt){
		$db->update("UPDATE pw_hack SET hk_value=".pwEscape($value)."WHERE hk_name='ezengage_config'");
	} else{
		$db->update("INSERT INTO pw_hack SET hk_name='ezengage_config',hk_value=".pwEscape($value));
	}
}
function updatecache_rr() {
	global $db;
	$rs = $db->get_one("SELECT hk_value FROM pw_hack WHERE hk_name='ezengage_config'");
	$ar = (array)unserialize($rs['hk_value']);
	//print_r($ar);
	writeover(D_P.'data/bbscache/ezengage_config.php',"<?php\r\n\$ezengage_config=".pw_var_export($ar).";\r\n?>");
}
?>
