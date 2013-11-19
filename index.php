<?php
//index简单路由
echo md5("gzautosky");

$via=rand();
@$ID=$_GET['ID'];
$ID=!empty($_GET['ID'])?$_GET['ID']:'1';
$MType=!empty($_GET['type'])?$_GET['type']:"index";
?>
<?php
$Title=array(
    "index"=>"微信墙",
    "admin"=>"WX管理后台",
);
$Style=array(
    "index"=>array(
	    0=>"CSS/weixin_index_style.css?via=".$via,
	    1=>"CSS/emoji.css",
	),
    "admin"=>array(
	    0=>"CSS/weixin_admin_style.css?via=".$via,
	    1=>"CSS/emoji.css",
	),
);
$Script=array(
    "index"=>array(
	    0=>"../jquery-1.8.3.min.js",
	    1=>"JS/php.websocket.js?via=".$via,
	    2=>"JS/weixin_main.js?via=".$via,
	),
    "admin"=>array(
	    0=>"../jquery-1.8.3.min.js",
	    1=>"JS/weixin_admin.js?via=".$via,
	),
);
if(@!empty($MType)){
	$head['title']=@$Title[$MType];
	$head['style']=@$Style[$MType];
	$head['script']=@$Script[$MType];
	
	include_once 'html/head.html.php';
	if(file_exists('html/'.$MType.'.html.php')){
	    include_once 'html/'.$MType.'.html.php';
	}else{
		echo "<script type='text/javascript'>window.onload=function (){alert('未找到相应模块');}</script>";
	}
	if($MType=="index"){
		echo "<script type='text/javascript'>var ID=".$ID.";</script>";
	}
	include_once 'html/foot.html.php';
}
?>