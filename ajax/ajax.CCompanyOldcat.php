<?php
include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');
include_once('../inc/CDbShell.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CCompanyOldcat.php');

$PHPSESSID = isset($_GET['PHPSESSID'])?$_GET['PHPSESSID']:'';
$session   = new session($PHPSESSID);

CGalaxyClass::$session = $session;	//insert to basic class static member

if(!$session->get('oCurrentUser')) {
	exit(false);
}

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

$iCompanyNo=$_POST['company_no'];
$iCompanyType=CCompanyOldcat::oGetCompanyOldcat($iCompanyNo)->iCompanyType;
$sCompanyType=CCompanyOldcat::$aCompanyType[$iCompanyType];
exit(json_encode($sCompanyType));
?>