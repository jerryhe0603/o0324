<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CSiteType.php');
include_once('../inc/model/CScriptType.php');

class CSiteTypeAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'GENESIS';

	/*
		constructor of this controller
	*/
	public function __construct(){
	}

	/*
		entry of this controller
		handle exception and decide where to redirect by exception code
	*/
	public function tManager() {
		$action = isset($_GET['action'])?$_GET['action']:'';
		try{
			switch($action){
				case "add":
					return $this->tSiteTypeAdd();
					break;
				case "edit":
					return $this->tSiteTypeEdit();
					break;
				case "active":
					return $this->vSiteTypeActive();
					break;
				default:
				case "search":
				case "list":
					return $this->tSiteTypeList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&site_type_no='.$_GET['site_type_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tSiteTypeAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			$Smarty->assign('aAllScriptType',CScriptType::aAllType());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/site_type_edit.html');
		}else{
			$oSiteType = new CSiteType($_POST);	//if $_POST has all we need
			$oSiteType->vSetScriptType($_POST['script_type_no']);
			//add
			try{
				$oSiteType->iAddSiteType();
			}catch (Exception $e){
				throw new Exception('CSiteTypeAdmin->tSiteTypeAdd:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oSiteType->iSiteTypeNo}");
	}

	private function tSiteTypeEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['site_type_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iSiteTypeNo = $_GET['site_type_no'];
		$oSiteType = CSiteType::oGetType($iSiteTypeNo);

		if(empty($_POST)){

			$aAllScriptType = CScriptType::aAllType();
			foreach ($aAllScriptType as $oScriptType) {
				if(array_key_exists($oScriptType->iScriptTypeNo, $oSiteType->aScriptType()))
					$oScriptType->bSelected = true;
			}

			$Smarty->assign('oSiteType',$oSiteType);
			$Smarty->assign('aAllScriptType',$aAllScriptType);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/site_type_edit.html');
		}else{
			if($_POST['site_type_no']!==$iSiteTypeNo)
				throw new Exception('CSiteTypeAdmin->tSiteTypeEdit:GET and POST not match',self::BACK_TO_EDIT);
			
			$oSiteType->sName = $_POST['site_type_name'];
			$oSiteType->bStatus = $_POST['site_type_status'];
			$oSiteType->fSort = $_POST['site_type_sort'];
			$oSiteType->vSetScriptType($_POST['script_type_no']);

			//update
			try{
				$oSiteType->vUpdateSiteType();
			}catch (Exception $e){
				throw new Exception('CSiteTypeAdmin->tSiteTypeEdit:'.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oSiteType->iSiteTypeNo}");
	}

	private function vSiteTypeActive(){
		if(empty($_GET['site_type_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iSiteTypeNo = $_GET['site_type_no'];
		$oSiteType = CSiteType::oGetType($iSiteTypeNo);
		try{
			$oSiteType->vActivate();
		}catch (Exception $e){
			throw new Exception('CSiteTypeAdmin->vSiteTypeActive:'.$e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oSiteType->iSiteTypeNo}");
	}

	private function tSiteTypeList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);
		if(!empty($_POST)) {
			if($_GET['js_valid']==1) {
				$this->vaildSearch($_POST,1);	//client javasite vaild data
			}else{
				$this->vaildSearch($_POST,0);	//form submit vaild data
			}
		}
		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];

		//because this function join two tables, order must be full name with table, caution in tpl!
		if(empty($_GET['order'])) $sOrder = "site_type_createtime";
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
			$sSearchSql ='';

		//得到某筆資料是在第幾頁
		if($goid){
            $iPg = $oDB->iGetItemAtPage("galaxy_site_type","site_type_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = CSiteType::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aAllSiteTypes = CSiteType::aAllType($sSearchSql,$sPostFix);
			if(count($aAllSiteTypes)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$_GET['items']);
		}
        $Smarty->assign("aAllSiteTypes",$aAllSiteTypes);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_site_type_key") );
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/site_type_list.html');
	}

	/*
		check if the search string is vaild
	*/
	private function vaildSearch($postData=array(),$return_type=0){
		$aErrorMsg = array();
		
		if(strlen(trim($postData['s_key'])) == 0){
			$aErrorMsg[]=_LANG_USER_VAILD_SEARCH_KEY;
		}	
		$sErrorMsg = "";

		//client javasite vaild data
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

	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_site_type_key");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_site_type_key","");
			return $sSql;
		}
		$session->set("s_site_type_key",$sKey);
		
		switch($sTerms){
			default :
				$sSql = $sSql." (`site_type_name` LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}
?>