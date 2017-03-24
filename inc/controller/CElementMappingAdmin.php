<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CElementMapping.php');
include_once('../inc/model/CPageType.php');

class CElementMappingAdmin extends CGalaxyController
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
					return $this->tElementMappingAdd();
					break;
				case "edit":
					return $this->tElementMappingEdit();
					break;
				case "active":
					return $this->vElementMappingActive();
					break;
				default:
				case "search":
				case "list":
					return $this->tElementMappingList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&element_mapping_no='.$_GET['element_mapping_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tElementMappingAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			$Smarty->assign('aAllPageType',CPageType::aAllType());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/element_mapping_edit.html');
		}else{
			$oEleMap = new CElementMapping($_POST);	//if $_POST has all we need
			$oEleMap->vSetPageType($_POST['page_type']);
			//add
			try{
				$oEleMap->iAddElementMap();
			}catch (Exception $e){
				throw new Exception('CElementMappingAdmin->tElementMappingAdd:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oEleMap->iElementMappingNo}");
	}

	private function tElementMappingEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['element_mapping_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iElementMappingNo = $_GET['element_mapping_no'];
		$oEleMap = CElementMapping::oGetElementMapping($iElementMappingNo);

		if(empty($_POST)){

			$aAllPageType = CPageType::aAllType();
			foreach ($aAllPageType as $oPageType) {
				if(array_key_exists($oPageType->iPageTypeNo, $oEleMap->aPageType()))
					$oPageType->bSelected = true;
			}

			$Smarty->assign('oEleMap',$oEleMap);
			$Smarty->assign('aAllPageType',$aAllPageType);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/element_mapping_edit.html');
		}else{
			if($_POST['element_mapping_no']!==$iElementMappingNo)
				throw new Exception('CElementMappingAdmin->tElementMappingEdit:GET and POST not match',self::BACK_TO_EDIT);
			
			$oEleMap->sName = $_POST['element_mapping_name'];
			$oEleMap->bStatus = $_POST['element_mapping_status'];
			$oEleMap->vSetPageType($_POST['page_type']);
			//update
			try{
				$oEleMap->vUpdateElementMap();
			}catch (Exception $e){
				throw new Exception('CElementMappingAdmin->tElementMappingEdit:'.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oEleMap->iElementMappingNo}");
	}

	private function vElementMappingActive(){
		if(empty($_GET['element_mapping_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iElementMappingNo = $_GET['element_mapping_no'];
		$oEleMap = CElementMapping::oGetElementMapping($iElementMappingNo);
		try{
			$oEleMap->vActivate();
		}catch (Exception $e){
			throw new Exception('CElementMappingAdmin->vElementMappingActive:'.$e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oEleMap->iElementMappingNo}");
	}

	private function tElementMappingList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);
		if(!empty($_POST)) {
			if($_GET['js_valid']==1) {
				$this->vaildSearch($_POST,1);	//client javascript vaild data
			}else{
				$this->vaildSearch($_POST,0);	//form submit vaild data
			}
		}
		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];

		//because this function join two tables, order must be full name with table, caution in tpl!
		if(empty($_GET['order'])) $sOrder = "element_mapping_createtime";
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
            $iPg = $oDB->iGetItemAtPage("galaxy_element_mapping","element_mapping_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = CElementMapping::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aCElementMappings = CElementMapping::aAllElementMapping($sSearchSql,$sPostFix);
			if(count($aCElementMappings)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$_GET['items']);
		}
        $Smarty->assign("aCElementMappings",$aCElementMappings);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_element_mapping_key") );
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/element_mapping_list.html');
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

	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_element_mapping_key");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_element_mapping_key","");
			return $sSql;
		}
		$session->set("s_element_mapping_key",$sKey);
		
		switch($sTerms){
			default :
				$sSql = $sSql." (`element_mapping_name` LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}
?>