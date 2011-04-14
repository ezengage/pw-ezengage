ezengage plugin for phpwind 8.5  v0.1.5
详细安装指南请看
http://ezengage.com/support/phpwind-plugin/

上传upload 目录下的内容到服务器
到后台安装和和配置插件.
然后按下面的说明的修改文件.

需要修改的文件

post.php

在文件末尾找到下面的代码

if ($action == "new") {
  require_once(R_P.'require/postnew.php');
} elseif ($action == "reply" || $action == "quote") {
  require_once(R_P.'require/postreply.php');
} elseif ($action == "modify") {
  require_once(R_P.'require/postmodify.php');
} else {
  Showmsg('undefined_action');
}


在这一段代码前面加入

//start ezengage hack 
@require_once(R_P . 'hack/ezengage/sync.php');
//end ezengage hack 


template/wind/footer.html , template/wind/showmsg.html  和 mode/area/template/footer.html 

在文件末尾加入下面的代码

<script type="text/javascript" src="ezengage.php?mod=js&scr=<?php echo SCR;?>"></script>

