<?php

/********************************************************************
 * @heading(標題): 
			company_admin_view 公司瀏覽
 * @author(作者) : 
 * @purpose(目的) :
			公司瀏覽
 * @usage(用法) : 
 * @reference(參考資料) :
 * @restriction(限制) :
 * @revision history(修改紀錄) : 
			修改日期: 
			修改人姓名: 
			修改內容:
 * @copyright(版權所有) : 
			銀河互動網路股份有限公司 iWant-in inc.。
 * @note(說明) :
 * @created(建立日期) : 
			2014/04/11
 ********************************************************************/
$start_time = getMicrotime();


include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');


include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CCompany.php'); // 公司
include_once('../inc/model/CUserCompany.php');
include_once('../inc/model/CCompanyDept.php'); // 公司部門
include_once('../inc/model/CcontactTel.php'); // 公司聯絡人電話



$session = new session($_COOKIE['PHPSESSID']);
CGalaxyClass::$session =$session;	//insert to basic class static member



//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);


$func   = isset($_GET['func'])?$_GET['func']:'';
$action	= isset($_GET['action'])?$_GET['action']:'';
$co_id	= isset($_GET['co_id'])?$_GET['co_id']:0; 


if($co_id == 0){
	echo "No Company!";
	exit;
}	





// get data
$oCompany = CCompany::oGetCompany($co_id);


$aRow['co_capital'] = number_format($aRow['co_capital']); // 資本額
$aCompanyTel = array();
if (isset($aRow['tel'])) {
	foreach($aRow['tel'] as $key => $value) {
		// 手機
		if ($value['type']==2 and strlen($value['co_tel'])==10) {
			$value['co_tel'] = preg_replace("/([0-9]{4})([0-9]{3})([0-9]{3})/","$1-$2-$3",$value['co_tel']);
		}
		$aCompanyTel[] = array("teltype_id"=>$value['type'],"co_tel"=>$value['co_tel'],"teltype_name"=>$gCoTelType[$value['type']]);
	}
	$Smarty->assign('CompanyTelData', $aCompanyTel);
}

$aCompanyEmail = array();
if (isset($aRow['email'])) {
	foreach($aRow['email'] as $key => $value) {
		$aCompanyEmail[] = array("emailtype_id"=>$value['type'],"co_email"=>$value['co_email'],"emailtype_name"=>$gCoEmailType[$value['type']]);
	}
	$Smarty->assign('CompanyEmailData', $aCompanyEmail);
}

// 顯示公司網址
$aCompanyWww = array();
if (isset($aRow['www'])) {
	foreach($aRow['www'] as $key => $value) {
		$aCompanyWww[] = array("wwwtype_id"=>$value['type'],"co_www"=>$value['co_www'],"wwwtype_name"=>$gCoWwwType[$value['type']]);
	}
	$Smarty->assign("CompanyWwwData",$aCompanyWww);
}

// 顯示公司地址
$aCompanyAddress = array();
if (isset($aRow['address'])) {
	foreach($aRow['address'] as $key => $value) {
		$aZipCode = $CZipCode->aGetAddrData($value['addr_id']);
		$addr_code = $aZipCode['addr_code'].$aZipCode['addr_city_name'].$aZipCode['addr_area_name'];
		$aCompanyAddress[] = array(
			"addr_code"=>$addr_code,
			"addr_type"=>$value['type'],
			"co_addr"=>$value['co_addr'],
			"addr_id"=>$value['addr_id'],
			"addrtype_name"=>$gCoAddrType[$value['type']],
			"zipcode"=>ltrim($value['zipcode'],"0"));
	}
	$Smarty->assign('CompanyAddressData', $aCompanyAddress);
}

$aCompanyDept = array();
if (isset($aRow['dept'])) {
	foreach($aRow['dept'] as $key => $value) {
		$aCompanyDept[] = array("cd_name"=>$value['cd_name']);
	}
	$Smarty->assign('CompanyDeptData', $aCompanyDept);
}

$aCompanyBrand = array();
if (isset($aRow['brand'])) {
	foreach($aRow['brand'] as $key => $value) {
		$aCompanyBrand[] = array("cb_name"=>$value['cb_name']);
	}
	$Smarty->assign('CompanyBrandData', $aCompanyBrand);
}

// 公司聯絡人
$aCompanyUserData = array();
$aCUsers = CUserCompany::aAlluser("galaxy_user_company.co_id=$co_id");
foreach($aCUsers as $oCUser)
{
	// 公司聯絡人部門
	$oCUser->cd_name = CCompanyDept::sGetCompanyDeptNameByCoIdUserNo($co_id,$oCUser->user_no);

	
	// 公司聯絡人品牌
	$oCUser->cb_name = CCompanyBrand::sGetCompanyBrandNameByCoIdUserNo($co_id,$oCUser->user_no,"、");
	
	
	// 公司聯絡人電話
	$oCUser->ct_tel = CContactTel::sGetContactTel($oCUser->user_no,0);
	
/*
	

	
	// 公司聯絡人電話
	$sCoContactTel = '';
	$iDbq5 = $CDbShell_Company->iQuery("SELECT * FROM contact_tel WHERE user_no=".$aRow2['user_no']);
	while($aRow5 = $CDbShell_Company->aFetchAssoc($iDbq5)) {
		$sCoContactTel .= $gCoTelType[$aRow5['type']] .':'. $aRow5['ct_tel']."<br/>";
	}
	$aRow2['ct_tel'] = rtrim($sCoContactTel,"<br/>");
	
	// 公司聯絡人Email
	$sCoContactEmail = '';
	$iDbq6 = $CDbShell_Company->iQuery("SELECT * FROM contact_email WHERE user_no=".$aRow2['user_no']);
	while($aRow6 = $CDbShell_Company->aFetchAssoc($iDbq6)) {
		$sCoContactEmail .= $gCoEmailType[$aRow6['type']] .':'. $aRow6['ct_email']."<br/>";
	}
	$aRow2['ct_email'] = rtrim($sCoContactEmail,"<br/>");
	
	// 公司聯絡人地址
	$sCoContactAddr = '';
	$iDbq7 = $CDbShell_Company->iQuery("SELECT * FROM contact_address WHERE user_no=".$aRow2['user_no']);
	while($aRow7 = $CDbShell_Company->aFetchAssoc($iDbq7)) {
		$sCoContactAddr .= $gCoAddrType[$aRow7['type']] .':'. $aRow7['ct_addr']."<br/>";
	}
	$aRow2['ct_addr'] = rtrim($sCoContactAddr,"<br/>");
	
	// 公司聯絡人狀態
	$sStatus = _LANG_FLAG_STOP;
	if ($aRow2['a_status']==1 AND $aRow2['b_status']==1) {
		$sStatus = _LANG_FLAG_START;
	}
	$aRow2['all_status'] = $sStatus;
	
*/	
	$aCompanyUserData[] = $oCUser;

}


$Smarty->assign('CompanyUserData', $aCompanyUserData);

// 取得貓舍公司資訊
$oCCompanyOldcatRow = CCompanyOldcat::oGetCompanyOldcat($co_id);
//echo "<pre>";print_r($oCCompanyOldcatRow);echo "</pre>";
$Smarty->assign('oCCompanyOldcatRow', $oCCompanyOldcatRow);

$Smarty->assign('gCoType',$gCoType);

/*
$aRow['mm_name'] = $CUserOldcat->sGetManagementName($aOldcatData['mm_id']); // 體系
if (!$aRow['mm_name']) $aRow['mm_name'] = '-';

// 地區
$fe['address'] = $CCompany->aGetCompanyAddrByCoId($co_id); // 地址
$aRow['addr_city_name'] = '-';
if ($fe['address']) {
	if (isset($fe['address'][0]['addr_id'])) { // 地區
		$aAddrRow = $CZipCode->aGetAddrData($fe['address'][0]['addr_id']);
		if (isset($aAddrRow['addr_city_name'])) $aRow['addr_city_name'] = $aAddrRow['addr_city_name'];
	}
}
$aRow['addr_city_name'] = str_replace(array("縣","市"), array("",""), $aRow['addr_city_name']);

// 產業
$aRow['ind_name'] = $CIndustry->sGetIndustryName($aOldcatData['ind_id']);
if (!$aRow['ind_name']) $aRow['ind_name'] = "-";

// 負責業務
$aRow['user_name'] = $CUser->sGetUserName($aOldcatData['user_no']); 
if (!$aRow['user_name']) $aRow['user_name'] = '-';

// 報備人員
$aRow['cf_name'] = $CFiling->sGetCfUserNameByCoId($co_id);
if (!$aRow['cf_name']) $aRow['cf_name'] = "-";

// 拜訪狀態
$aRow['visit_status_name'] = $gVisitStatus[$aOldcatData['visit_status']];
if (!$aRow['visit_status_name']) $aRow['visit_status_name'] = '-';

// 進行天數
$aRow['run_day'] = $CCompany->iGetCompanyRunDay($co_id,$aOldcatData['user_no']); 

// 取消原因
$aCancelRow = $CCompany->aDistributeLog($co_id);
$aRow['cancel_note'] = $aCancelRow['cancel_note'];
$aRow['cancel_created'] = $aCancelRow['created'];

// 派發過業務
$aRow['saled_user_name'] = $CCompany->sGetDistributedSalerByCoId($co_id);

// 公司類別
$aRow['co_type_name'] = $gCoType[$aOldcatData['co_type']];

// 等級
$aRow['level'] = $aOldcatData['level'];

//echo "<pre>";print_r($aOldcatData);echo "</pre>";
$Smarty->assign('company', $aRow);*/

$Smarty->assign('company', $oCompany);
$Smarty->assign('parentcompany',CCompany::oGetCompany($oCompany->parent_id));
$Smarty->assign('cancelrow',CCompany::oGetSalerCancelCompanyRow($co_id));


// output
$Smarty->display('./admin/CCompanyAdmin/company_view.html');


$time_after_tpl = getMicrotime() - $start_time;
$memory_usage = function_exists('memory_get_usage') ? number_format( memory_get_usage()/(1024*1024), 2 ) : 'N/A';
echo 'Total Execution Time: ' .number_format($time_after_tpl, 4). ' seconds; Memory usage: ' .$memory_usage;

exit;

function getMicrotime(){
    list( $usec, $sec ) = explode( ' ', microtime() );
    return ( (float)$usec + (float)$sec );
}

?>