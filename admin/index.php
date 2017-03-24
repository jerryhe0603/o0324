<?php

/**
 *  @desc 後台進入點
 *  @created 2015/10/28
 */

$start_time = getMicrotime();
header('Content-Type: text/html; charset=utf-8');

// include basic config
include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');

/*
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
$session = new session($PHPSESSID);
CGalaxyClass::$session = $session;	//insert to basic class static member

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

$func = isset($_GET['func'])?$_GET['func']:'';
$action = isset($_GET['action'])?$_GET['action']:'';


//if user is login, there should be a $oCUser named oCurrentUser in $_SESSION
if(is_null($session->get('oCurrentUser'))){
	CJavaScript::vRedirect('./login.php');
	exit;
}

//use $oCUser->IsPermit() to check if current user is allowed to given func & action
try{
	$session->get('oCurrentUser')->IsPermit($func, $action);
}catch (Exception $e){
	CJavaScript::vAlertRedirect($e->getMessage(),'./index.php');
	exit;
}

//assign session id
$Smarty->assign('PHPSESSID',session_id());
$Smarty->assign('sGenesisServer',GENESIS_SERVER);
$Smarty->assign('sCssCompanyServer', CSSCOMPANY_SERVER);

//map func to controller name; if more functions are add in system, add them here
switch($func)
{
	case 'order':
		$sController = 'COrderAdmin';
		break;
	case 'order_payment':
		$sController = 'COrderPaymentAdmin';
		break;
	case 'order_project':
		$sController = 'COrderProjectAdmin';
		break;
	case 'order_project_list':
		$sController = 'COrderProjectListAdmin';
		break;
		/*
	case 'order_group':
		$sController = 'COrderGroupAdmin';
		break;
		*/
	case 'order_management_group':
		$sController = 'COrderManagementGroupAdmin';
		break;
	case 'order_product_admin':
		$sController = 'CProductAdmin';
		break;
	default:
		$sController = '';
		break;
}
if($sController!==''){
	//include, new target controller, and run tManager
	include_once("../inc/controller/order/$sController.php");	//include controller.php
	$oController = new $sController();	//new target controller
	$Smarty->assign("bodyTpl", $oController->tManager());	//call controller entry function
}else{
	/*
	echo '<pre>';
	print_r($_SESSION);
	echo '</pre>';
	exit;
	*/
}

$Smarty->assign('oCurrentUser',$session->get('oCurrentUser'));
$Smarty->display('./admin/index.html');

$time_after_tpl = getMicrotime() - $start_time;
$memory_usage = function_exists('memory_get_usage') ? number_format( memory_get_usage()/(1024*1024), 2 ) : 'N/A';
echo '<!-- Total Execution Time: ' .number_format($time_after_tpl, 4). ' seconds; Memory usage: ' .$memory_usage. ' -->';
exit;

function getMicrotime()
{
    list( $usec, $sec ) = explode( ' ', microtime() );
    return ( (float)$usec + (float)$sec );
}
?>