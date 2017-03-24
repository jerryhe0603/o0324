<?php
include_once('../inc/config.php');
include_once('../inc/class.session.php');
include_once('../inc/smarty.config.php');
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CGalaxyClass.php');

$session	= new session($_GET['PHPSESSID']);
CGalaxyClass::$session =$session;	//insert to basic class static member

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;
include_once("../lang/"._LANG.".php");

if(is_null($session->get('oCurrentUser'))){
	exit(false);
}

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);


include_once('../inc/controller/order/COrderProjectListAdmin.php');

$oCOrderProjectList=new COrderProjectListAdmin;
$oCOrderProjectList->tManager();
?>