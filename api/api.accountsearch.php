<?php

include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');
include_once('../inc/CDbShell.php');
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');
include_once("../inc/controller/CPuppetsSelector.php");	//include controller.php


$session	= new session($_GET['PHPSESSID']);
CGalaxyClass::$session =$session;	//insert to basic class static member

if(is_null($session->get('oCurrentUser'))){
	echo "no login";
	exit;
}

$oController = new CPuppetsSelector();	//new target controller
//$Smarty->display($oController->tManager());
echo $oController->tManager();




?>