<?php

include_once('../inc/controller/CGalaxyController.php');

include_once('../inc/model/CCompany.php');


include_once('../inc/model/CUserOldcat.php');



class CCompanyAdmin extends CGalaxyController
{


	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;
	
	
	
	public $aSearchOption = array(
									"co_name"	=> '公司名稱',
									"tax_id"	=> '統一編號',	
									"co_id"		=> '序號'
	);
	
	

	private $sDBName = 'COMPANY';
	
	

	public function __construct()
	{
	
	
	}

	function tManager()
	{
	
	
		$action = isset($_GET['action'])?$_GET['action']:'';
		
		
		try{
		
		
			switch($action){
				case "edit":
					return $this->tCompanyEdit();
					break;		
				
				case "active":
					return $this->vCompanyActive();
					break;
					
				default:
				case 'search':
				case "list": // 公司列表
					return $this->tCompanyList();
					break;
			}
			
		}catch (Exception $e){
		
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func_name'];
					if(isset($_GET['table_no']))
						$sUrl .= '&action=list&goid='.$_GET['table_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
			
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}
	
	
	
	function tCompanyList() {
	
		$Smarty = self::$Smarty;
		
		$session = self::$session;
		
		$oDB = self::oDB($this->sDBName);

	
		if(!empty($_POST)) {
			if($_GET['js_valid']==1) {
				$this->vaildUserSearch($_POST,1);	//client javascript vaild data
			}else{
				$this->vaildUserSearch($_POST,0);	//form submit vaild data
			}
		}
		
		

		$iPageItems = isset($_GET['items'])?$_GET['items']:PAGING_NUM; // 一頁幾筆

		//because this function join two tables, order must be full name with table, caution in tpl!
		if(empty($_GET['order'])) $sOrder = "a.co_id";
		else $sOrder = $_GET['order'];
		
		if(empty($_GET['sort'])) $sSort = "DESC";
		else $sSort = $_GET['sort'];
		
		if(empty($_GET['page'])) $iPg = 0;
		else $iPg = $_GET['page'];
		
		if(empty($_GET['goid'])) $goid = 0;
		else $goid = $_GET['goid'];


		if($_GET['action'] === 'search')
			$sSearchSql = $this->sGetSearchSql($_POST);
		else
			$sSearchSql ='flag!=9';
			
			
		
		$tab 		= isset($_GET['tab'])?$_GET['tab']:'';

		


		// 得到某筆資料是在第幾頁
		if($goid){
			if ($tab=='oldcat') {
				 $iPg = $oDB->iGetJoinItemAtPage("company","company_oldcat","co_id",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
			} else {
				$iPg = $oDB->iGetItemAtPage("company AS a","co_id",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
			}
		}
		
		// 共幾筆
		if ($tab=='oldcat') {
			$iAllItems = CCompany::iGetCountOldcat($sSearchSql);
		}else{
			$iAllItems = CCompany::iGetCount($sSearchSql);
		}
		
		$iStart=$iPg*$iPageItems;
		
		
		// get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			
			if ($tab=='oldcat') {
			
				$aCompanys = CCompany::aAllCompanyOldcat($sSearchSql, $sPostFix );
				
			} else {
			
				$aCompanys = CCompany::aAllCompany($sSearchSql, $sPostFix );
				
			}
			
			
			if(count($aCompanys)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$_GET['items']);
		}
        $Smarty->assign("aCompanys",$aCompanys);
	
		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']);
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_user_key") );
		$Smarty->assign('searchTerm',	$session->get("s_user_terms") );
		$Smarty->assign('searchOption',	$this->aSearchOption);
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort&tab=$tab"));

		if ($tab=='oldcat') { // 顯示搜尋選項
			$Smarty->assign("IndustryData",CIndustry::aGetIndustryList()); // 產業別
			$Smarty->assign("UserData",CUserOldcat::aGetActiveSaleUserData()); // 負責業務
			$Smarty->assign("FilingUserData",CUserOldcat::aGetActiveSaleUserData()); // 報備業務
		} else {
			//$Smarty->assign("TagData",$CTag->aGetCompanyTagList()); // 內含公司的標籤
			//$Smarty->assign("AreaData",$gArea);
		}
		
		switch($tab) {
			case 'oldcat':
				return $output = $Smarty->fetch('./admin/'.get_class($this).'/company_list_oldcat.html');
			break;
			
			case 'base':
			default:
				return $output = $Smarty->fetch('./admin/'.get_class($this).'/company_list.html');
			break;	
		}
		
		
		
	}
	
	

	/*
		check if the search string is vaild
	*/
	private function vaildUserSearch($postData=array(),$return_type=0)
	{
	
	
		$aErrorMsg = array();
		
		if(strlen(trim($postData['s_key'])) == 0){
			$aErrorMsg[]=_LANG_USER_VAILD_SEARCH_KEY;
		}	
		$sErrorMsg = "";

		//client javascript vaild data
		if($return_type==1) {
			$sErrorMsg = implode("<BR>",$aErrorMsg);
			echo $sErrorMsg;
			exit;
		}
		//form submit vaild data
		if(count($aErrorMsg) > 0){
			$sErrorMsg = implode('\n',$aErrorMsg);
			throw new Exception(sprintf($sErrorMsg),self::BACK_TO_LIST);
		}
		
		
	}


	
	
	
	private function vCompanyActive()
	{
	
		$tab 		= isset($_GET['tab'])?$_GET['tab']:'';
		
		if(empty($_GET['co_id']))
			throw new Exception('',self::BACK_TO_LIST);
		$co_id = $_GET['co_id'];
		$oCompany = CCompany::oGetCompany($co_id);
		try{
			$oCompany->vCompanyActive();
		}catch (Exception $e){
			throw new Exception($e->getMessage(),self::BACK_TO_LIST);
		}
		//CJavaScript::vAlertRedirect(_LANG_COMPANY_ACTIVE_SUCCESS,$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCompany->co_id}");
		CJavaScript::vRedirect($_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&tab=$tab&goid={$oCompany->co_id}");
		
	}	
	
	private function tCompanyEdit()
	{
	
		if(empty($_GET['co_id']))
			throw new Exception('',self::BACK_TO_LIST);
		$co_id = $_GET['co_id'];
		$oCompany = CCompany::oGetCompany($co_id);
	
		
		$Smarty = self::$Smarty;
		
			
		$session = self::$session;
		
		$oDB = self::oDB($this->sDBName);
		
		
		$oCompany = CCompany::oGetCompany($co_id);
		
		

		$Smarty->assign("oCompany",$oCompany);
		
	
	
	
	
		return $output = $Smarty->fetch('./admin/'.get_class($this).'/company_edit.html');	
	
	
	}
	
	
	
}
?>