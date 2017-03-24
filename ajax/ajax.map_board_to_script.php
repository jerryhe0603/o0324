<?php
include_once('../inc/config.php');
include_once('../inc/class.session.php');
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CGalaxyClass.php');

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

include_once('../inc/model/CScript.php');

$aBoardNos = $_POST['text'];

$aScript = array();
if(!empty($aBoardNos)){
	$sBoards = "('";
	$sBoards .= implode("','",$aBoardNos);
	$sBoards .="')";
	$aScript = CScript::aAllScript("`board_no` IN $sBoards");
}
$aReturn = array();
foreach ($aScript as $oScript) {
	$aReturn[] = array(	'script_no'=>$oScript->iScriptNo,
						'script_name'=>$oScript->sName
						);
}

if(!is_null($aReturn))
	exit(json_encode($aReturn));
exit(false);
?>