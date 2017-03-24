<?php
include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');
include_once('../inc/CDbShell.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CCompany.php');

$session	= new session($_GET['PHPSESSID']);
CGalaxyClass::$session =$session;	//insert to basic class static member

if(is_null($session->get('oCurrentUser'))){
	exit(false);
}

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

$iCompanyId=$_POST['co_id'];
$aAllBrand=CCompanyBrand::aGetCompanyBrand($iCompanyId);
$aResult=array();
if(!empty($aAllBrand)){
	foreach($aAllBrand as $oBrand){
		$aResult[$oBrand->iBrandNo]=$oBrand;
	}
}
exit(json_encode($aResult));
?>