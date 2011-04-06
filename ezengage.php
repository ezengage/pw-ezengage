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
    require_once(R_P.'hack/ezengage/js.inc.php');
}
?>
