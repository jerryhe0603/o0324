<?php
include_once('../inc/config.php');
include_once('../inc/class.session.php');
include_once('../inc/CDbShell.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/controller/order/COrderAdmin.php');
include_once('../inc/smarty.config.php');

$PHPSESSID = isset($_GET['PHPSESSID'])?$_GET['PHPSESSID']:'';
$session   = new session($PHPSESSID);

CGalaxyClass::$session = $session;	//insert to basic class static member

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

if(!$session->get('oCurrentUser')) {
	exit(false);
}

$action = isset($_GET['action'])?$_GET['action']:'';
switch($action){
	case  'get_order_no':
		$iManagementId=$_POST['management_id'];
		$sOrderNo=COrder::sOrderNoGenerator($iManagementId);		
		exit(json_encode($sOrderNo));
		break;
	case 'list_by_management':
	case 'admin_by_management':
		$oCOrder=new COrderAdmin;
		$oCOrder->tManager();
		break;
}

?>