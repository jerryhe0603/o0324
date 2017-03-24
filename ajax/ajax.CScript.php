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

include_once('../inc/model/CScript.php');

$multidata = $_POST['text'];
try{
	switch($_GET['action']){
		case 'by_board':
			$sBoardNos = '(';
			$sBoardNos .= implode(',', $multidata);
			$sBoardNos .= ')';
			$sSearchSql = "board_no IN ".$sBoardNos;
			if($_GET['type']){
				$sSearchSql .= ' AND script_type_no='.$_GET['type'];
			}
			$aScripts = CScript::aAllScript($sSearchSql);

			foreach ($aScripts as $oScript) {
				$aMap = array(	'script_no'=>$oScript->iScriptNo,
								'script_name'=>$oScript->sName
								);
				$aReturn[] = $aMap;
			}
			break;
		case 'by_site':
			$iSiteNo = $multidata;
			$sSearchSql = "site_no=".$iSiteNo;
			if($_GET['type']){
				$sSearchSql .= ' AND script_type_no='.$_GET['type'];
			}
			$aScripts = CScript::aAllScript($sSearchSql);

			foreach ($aScripts as $oScript) {
				$aMap = array(	'script_no'=>$oScript->iScriptNo,
								'script_name'=>$oScript->sName
								);
				$aReturn[] = $aMap;
			}
			break;
		default:
			break;	
	}
}catch (Exception $e){
	$aReturn = array("errorMsg"=>$e->getMessage());
}

if(!is_null($aReturn))
	exit(json_encode($aReturn));
exit(false);
?>