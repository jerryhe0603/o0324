<?php
include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
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

include_once('../inc/controller/CCalendar.php');

$oCCalendar = new CCalendar;
try{
	switch($_GET['action']){
		case "galaxy_event":
			$aReturn = $oCCalendar->aGetGalaxyEvent();
			break;
		case "user_group":
			$aReturn = $oCCalendar->aGetUserGroup();
			break;
		case "else_group":
			$aReturn = $oCCalendar->aGetElseGroup();
			break;
		case "get_task":
			$aReturn = $oCCalendar->aGetTask();
			break;
		case "user_task":
			$aReturn = $oCCalendar->aGetUserTask();
			break;
		case "group_task":
			$aReturn = $oCCalendar->aGetGroupTask();
			break;
		default:
			$oCCalendar->tManager();	//add,edit...etc, which exit with tpl or exec some DB controll
			break;
	}
}catch (Exception $e){
	$aReturn = array("errorMsg"=>$e->getMessage());
}

if(!is_null($aReturn))
	exit(json_encode($aReturn));
exit(false);
?>