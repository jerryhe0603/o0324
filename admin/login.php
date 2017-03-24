<?php
header('Content-Type: text/html; charset=utf-8');

// include basic config
include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');

/*
	include classes
	CAUTION: DO NOT include any other class , unless it is used here
*/
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CGalaxyClass.php');

/*
	CAUTION: DO NOT new any other class here, unless it is used by every function controller
*/
$PHPSESSID = isset($_GET['PHPSESSID'])?$_GET['PHPSESSID']:'';
$session	= new session($PHPSESSID);
CGalaxyClass::$session =$session;	//insert to basic class static member

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);
$sUserName 	= trim(isset($_POST['username'])?$_POST['username']:'');
$sPassword 	= trim(isset($_POST['password'])?$_POST['password']:'');

//if oCurrentUser is already set or ($sUserName or $sPassword is empty), clear session and redirect to login.html
if(!is_null($session->get('oCurrentUser')) || empty($sUserName) || empty($sPassword)){
	$session->sess_unset();
	$Smarty->display('./admin/login.html');
	exit;
}

try{
	CUser::vLogin($sUserName,$sPassword);
	CJavaScript::vRedirect('./index.php');
	exit;
}catch (Exception $e){
	CJavaScript::vAlertRedirect($e->getMessage(),'./login.php');
	exit;
}
?>