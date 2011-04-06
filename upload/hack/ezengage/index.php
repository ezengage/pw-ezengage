<?php
require_once(D_P.'data/bbscache/ezengage_config.php');
require_once(R_P.'hack/ezengage/common.func.php');
require_once(R_P."hack/ezengage/lang.$db_charset.php");

if(!$winduid){
    Showmsg($eze_scriptlang['need_login']);;
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($_POST['action'] == 'save'){
        if(!empty($_POST['delete']) && is_array($_POST['delete'])) {
            $to_delete = array();
            foreach($_POST['delete'] as $pid){
                if(intval($pid) > 0){
                    $to_delete[] = intval($pid);
                }
            }
            if(count($to_delete) > 0){
                $to_delete = implode(",", $to_delete);
                $db->update("DELETE FROM pw_eze_profile WHERE uid = '$winduid' AND pid IN ($to_delete);");
            }
        }

        $eze_profiles = eze_get_profiles($winduid);
        foreach($eze_profiles as &$profile){
            if(is_array($_POST['sync_list_' . $profile['pid']])){
                $sync_list = implode(',', $_POST['sync_list_' . $profile['pid']]); 
            }
            else{
                $sync_list = '';
            }
            $profile['sync_list'] = $sync_list;
            $e_sync_list = S::sqlEscape($sync_list);
            $db->update("UPDATE pw_eze_profile SET sync_list = $e_sync_list WHERE uid='$winduid' AND pid=$profile[pid];");
        }
        refreshto(EZE_MY_ACCOUNT_URL, $eze_scriptlang['updateuser_succeed']);
    }
}
else if (empty($action)) {
    $eze_profiles = eze_get_profiles($winduid);
    require_once PrintHack('index');footer();
    exit;
}
