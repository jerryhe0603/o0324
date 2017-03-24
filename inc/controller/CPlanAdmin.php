<?php
include_once('../inc/controller/CGalaxyController.php');

include_once('../inc/model/CPlan.php');
include_once('../inc/model/CSite.php');
include_once('../inc/model/CScript.php');

class CPlanAdmin extends CGalaxyController
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
		try{
			switch($_GET['action']){
				case "add":
					return $this->tPlanAdd();
					break;
				case "edit":
					return $this->tPlanEdit();
					break;
				case "view":
					return $this->tPlanView();
					break;
				default:
				case "search":
				case "list":
					return $this->tPlanList();
					break;
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=list');
					break;
				case self::BACK_TO_VIEW:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=view&plan_no='.$_GET['plan_no']);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&plan_no='.$_GET['plan_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tPlanAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/plan_edit.html');
		}else{
			CMisc::vPrintR($_POST);
			exit;
			$oPlan = new CPlan($_POST);	//if $_POST has all we need
			try{
				$oPlan->vSetOrderScript($_POST['site_no']);
				$oPlan->iAddPlan();
			}catch (Exception $e){
				throw new Exception('CPlanAdmin->tPlanAdd: '.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oPlan->iPlanNo}");
	}

	private function tPlanEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['plan_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iPlanNo = $_GET['plan_no'];
		$oPlan = CPlan::oGetPlan($iPlanNo);

		if(empty($_POST)){
			$oSite = CSite::oGetSite($oPlan->iSiteNo);

			$Smarty->assign('oSite',$oSite);
			$Smarty->assign('oPlan',$oPlan);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/plan_edit.html');
		}else{
			try{

				$oPlan->sName = $_POST['plan_name'];
				$oPlan->sDesc = $_POST['plan_desc'];

				$oPlan->vSetOrderScript($_POST['site_no']);

				$oPlan->vUpdatePlan();
			}catch (Exception $e){
				throw new Exception('CPlanAdmin->tPlanEdit: '.$e->getMessage(),self::BACK_TO_DEIT);
			}
		}
		CJavaScript::vAlertRedirect('plan edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&plan_no={$oPlan->iPlanNo}");
	}

	private function tPlanView(){
		$Smarty = self::$Smarty;
		if(empty($_GET['plan_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iPlanNo = $_GET['plan_no'];
		$oPlan = CPlan::oGetPlan($iPlanNo);
		
		$Smarty->assign('oPlan',$oPlan);

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/plan_view.html');
	}

	private function tPlanList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);

		//check if $_POST for search is valid
		if(!empty($_POST)) {
			if($_GET['js_valid']==1) {
				$this->vaildPlanSearch($_POST,1);	//client javascript vaild data
			}else{
				$this->vaildPlanSearch($_POST,0);	//form submit vaild data
			}
		}
		//page frame setting
		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];

		if(empty($_GET['order'])) $sOrder = "createtime";
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
			$sSearchSql = '';

		//得到某筆資料是在第幾頁
		if($goid){
			$iPg = $oDB->iGetItemAtPage("project_plan","plan_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}

		//共幾筆
		$iAllItems = CPlan::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aPlans = CPlan::aAllPlan($sSearchSql,$sPostFix);
			if(count($aPlans)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$iPageItems);
		}
        $Smarty->assign("aPlans",$aPlans);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&project_no=$sProjectUuid&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',$session->get("s_plan_key") );
                
		$Smarty->assign("iTotal",$iAllItems);
		$Smarty->assign("iPageItem",$iPageItems);
		
		$Smarty->assign("iStartRow",$iStart+1);
		$Smarty->assign("iEndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&project_no=$sProjectUuid&order=$sOrder&sort=$sSort$sAdmin"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/plan_list.html');
	}

	/*
		check if the search string is vaild
	*/
	private function vaildPlanSearch($postData=array(),$return_type=0){
		$aErrorMsg = array();
		
		if(strlen(trim($postData['s_key'])) == 0){
			$aErrorMsg[]=_LANG_PRINCIPLE_VAILD_SEARCH_KEY;
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

	/*
		change search plan name into sql string
	*/ 
	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_plan_key");
			$sTerms =  $session->get("s_plan_terms");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_plan_key","");
			$session->set("s_plan_terms","");
			return $sSql;
		}
		$session->set("s_plan_key",$sKey);
		$session->set("s_plan_terms",$sTerms);
		
		switch($sTerms){
			default :
				$sSql = $sSql." (`plan_name` LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}

}
?>