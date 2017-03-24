<?php

include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');
include_once('../inc/CDbShell.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CCompany.php');

$PHPSESSID = isset($_GET['PHPSESSID'])?$_GET['PHPSESSID']:'';
$session   = new session($PHPSESSID);

CGalaxyClass::$session = $session;	//insert to basic class static member

if(!$session->get('oCurrentUser')) {
	exit(false);
}

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang = 1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET  = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

//$oController = new CBeautyProjectAdmin();	//new target controller
//echo $oController->tManager();
if(!empty($_POST['text'])){
	$sText=$_POST['text'];
	$result=CCompany::aAllCompany(" flag=1 AND `co_nickname` LIKE '%".$sText."%'");
	exit(json_encode($result));
}else if($_POST['verified']&&!empty($_POST['co_id'])){
	$iCompanyNo = $_POST['co_id'];
	$oCompany=CCompany::oGetCompany($iCompanyNo);
	if($oCompany->iVerifyUserNo!=0)
		$result=1;
	else
		$result=0;
	exit(json_encode($result));
}
?>