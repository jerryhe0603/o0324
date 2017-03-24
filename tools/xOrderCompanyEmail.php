<?php

/**
 * @desc 抓取訂單專案內的客戶Email
 * @created 2016/09/05
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

// User init
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderProject.php');
include_once('../inc/model/CUserFront.php');
include_once('../inc/class.validator.php');

/*
	CAUTION: DO NOT new any other class here, unless it is used by every function controller
*/
$session	= new session($_GET['PHPSESSID']);
CGalaxyClass::$session = $session;	//insert to basic class static member

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

echo "Start...\r\n";

// 1. 抓取所有核可訂單
$iVerifyStatus = 2;
$sSort = "DESC";
$sOrder = "create_time";
$sSearchSql = "`verify_status` = '$iVerifyStatus'";
$aOrder = COrder::aAllOrder($sSearchSql,"ORDER BY $sOrder $sSort");

// 2. 所有訂單專案狀態為準備中與進行中
$iServiceId = 4; // 預設: 美女隊
//子系統API
$aApiUrl = array(
	4=>"http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?func=beauty_project"
);

$aOrderProject = array();

foreach($aOrder as $oOrder){
	$order_no = $oOrder->sOrderUuid;
	$sSearchSql =" `service_id`= '$iServiceId' AND `order_no`= '$order_no' "; 

	// 訂單下所有專案
	$aProject = COrderProject::aAllOrderProject($sSearchSql);

	if (!$aProject) continue;
	foreach($aProject as $iKey => $oProject){
		$sApiUrl = $aApiUrl[$iServiceId];
		$sUrl = $sApiUrl."&action=order_fetch_project&nologin=1&PHPSESSID=".session_id();
		$aOptions = array('project_no'=>$oProject->sProjectUuid);
		$iDebug = 0;
		$aResult = CMisc::aCurlPost($sUrl, $aOptions, $iDebug);
		
		if($aResult){
			$oProject->bExist=1;
			foreach($aResult as $key => $value){
				$oProject->$key=$value;
			}
		}else{
			$oProject->bStatus = 0;
		}

		if ($aResult){
			if ($oProject->iConditionNo==0 OR $oProject->iConditionNo==1) {
				// 訂單專案狀態為準備中與進行中
				$aProject[$iKey] = $oProject;
				$aOrderProject[] = $aProject[$iKey];
			}
		}		

 	}
	
}

// echo "<pre>";print_r($aOrderProject);echo "</pre>";exit;

// 3. 設定前台權限有打勾
echo "專案名稱,Email\r\n";
foreach($aOrderProject as $oOrderProject){
	$sProjectUuid = $oOrderProject->sProjectUuid;
	$oProject = COrderProject::oGetOrderProject($sProjectUuid);
	$oOrder = $oProject->oOrder();

	// 簽單客戶
	$aContractClient = CUserFront::aAllUser("galaxy_user_company.co_id = $oOrder->iContractClientNo");
	//echo "<pre>";print_r($aContractClient);echo "</pre>";

	// 服務客戶
	$aCompanyClient = CUserFront::aAllUser("galaxy_user_company.co_id = $oOrder->iClientNo");
	//echo "<pre>";print_r($aCompanyClient);echo "</pre>";
	
	foreach($aContractClient as $oCUserFront){
		if (validator::bCheckEmail($oCUserFront->sAccount)){
			echo $oOrderProject->sName.", ".$oCUserFront->sAccount."\r\n";
		}
	}
	foreach($aCompanyClient as $oCUserFront){
		if (validator::bCheckEmail($oCUserFront->sAccount)){
			echo $oOrderProject->sName.", ".$oCUserFront->sAccount."\r\n";
		}
	}

	
}

echo "Finish...\r\n";

$time_after_tpl = getMicrotime() - $start_time;
$memory_usage = function_exists('memory_get_usage') ? number_format( memory_get_usage()/(1024*1024), 2 ) : 'N/A';
// echo 'Total Execution Time: ' .number_format($time_after_tpl, 4). ' seconds; Memory usage: ' .$memory_usage;
exit;

function getMicrotime(){
    list( $usec, $sec ) = explode( ' ', microtime() );
    return ( (float)$usec + (float)$sec );
}

?>
