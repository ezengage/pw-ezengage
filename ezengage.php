<?php
require_once ('global.php');
require_once(D_P.'data/bbscache/ezengage_config.php');
if($_GET['mod'] == 'token'){
    require_once(R_P.'hack/renren/token.inc.php');
}
if($_GET['mod'] == 'bind'){
    require_once(R_P.'hack/renren/common.func.php');
    register_shutdown_function(eze_on_bind_shutdown());
    require_once(R_P.'hack/renren/bind.inc.php');
}
?>
