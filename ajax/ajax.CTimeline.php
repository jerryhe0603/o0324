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

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

include_once('../inc/controller/CTimeline.php');

$oCTimeline = new CTimeline;
try{
	switch($_GET['action']){
		/*
		case "project_task":
			$aReturn = $oCTimeline->aGetProjectTask();
			break;
		*/
		case "user_task":
			$aReturn = $oCTimeline->aGetUserTask();
			break;
		/*
		//subsystem dependent
		case "project_log":
			$aReturn = $oCTimeline->aGetProjectLog();
			break;
		*/
		case "user_log":
			$aReturn = $oCTimeline->aGetUserLog();
			break;
		default:
			$aReturn = array("errorMsg"=>'got nothing');
			break;
	}
}catch (Exception $e){
	$aReturn = array("errorMsg"=>$e->getMessage());
}

if(!is_null($aReturn))
	exit(json_encode($aReturn));
exit(false);
?>