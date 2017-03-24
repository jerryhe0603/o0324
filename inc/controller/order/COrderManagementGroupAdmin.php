<?php

include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CUserIwant.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderManagementGroup.php');

class COrderManagementGroupAdmin extends CGalaxyController {

	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'ORDER';

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
				case "edit_user":
					return $this->tGroupUserEdit();
					break;
				default:
				case "list":
					return $this->tGroupList();
					break;
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=list';
					/*
					if(!empty($_GET['group_no']))
						$sUrl .= '&goid='.$_GET['group_no'];
						*/
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_VIEW:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=view&group_no='.$_GET['group_no']);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&group_no='.$_GET['group_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tGroupUserEdit(){
		$iManagementNo=$_GET['management_no'];
		if(empty($iManagementNo)){
			CJavaScript::vAlertRedirect("management no required", $_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list");
		}

		$oOrderManagementGroup = COrderManagementGroup::oGetGroup($iManagementNo);
		$aAllManagement=COrderManagementGroup::aGetManagement();
		$aManagement=array();
		foreach($aAllManagement as $aManagementData){
			if($aManagementData['mm_id']==$iManagementNo){
				$aManagement=$aManagementData;
				break;
			}
		}
		if(empty($aManagement)){
			CJavaScript::vAlertRedirect("this management does not exist. ", $_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list");
		}

		if(!$_POST){
			$Smarty=self::$Smarty;
			if(!empty($oOrderManagementGroup))
				$aAllGroupUser=$oOrderManagementGroup->aUser();
			$aAllUser= CUserIwant::aAllUser();

			if(!empty($aAllGroupUser)){
				foreach($aAllGroupUser as $oGroupUser){
					foreach($aAllUser as $oUser){
						if($oGroupUser->iUserNo==$oUser->iUserNo){
							$oUser->bSelected=1;
							break;
						}
					}
				}
			}
			$Smarty->assign('aManagement', $aManagement);
			$Smarty->assign('aAllUser', $aAllUser);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_management_group_user_edit.html');
		}else{
			//CMisc::vPrintR($_POST);exit;
			$oOrderManagementGroup = new COrderManagementGroup($_POST);
			$aUser=array();
			$aGroupUserNo = isset($_POST['group_user_no'])?$_POST['group_user_no']:array();
			if(!empty($aGroupUserNo)){
				foreach( $aGroupUserNo as $iUserNo){
					$aUser[]=CUserIwant::oGetUser($iUserNo);
				}
			}

			$oOrderManagementGroup->vSetUser($aUser);
			try{
				$oOrderManagementGroup->vUpdateUser();
			}catch(Exception $e){
				throw new Exception('COrderManagementGroupAdmin->tGroupUserEdit:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('management group user edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list");
	}

	private function tGroupList(){
		$Smarty = self::$Smarty;

		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];
		/*
		if(empty($_GET['order'])) $sOrder = "create_time";
		else $sOrder = $_GET['order'];

		if(empty($_GET['sort'])) $sSort = "DESC";
		else $sSort = $_GET['sort'];

		if(empty($_GET['goid'])) $goid = 0;
		else $goid = $_GET['goid'];
		*/

		$iPg = isset($_GET['page'])?$_GET['page']:0;

		$sSearchSql='';

		//共幾筆
		$aAllManagement=COrderManagementGroup::aGetManagement();
		$iAllItems = count($aAllManagement);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			//$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			//$aAllManagementGroup = COrderManagementGroup::aAllGroup();
			foreach($aAllManagement as $aManagementData){
				$iManagementNo=$aManagementData['mm_id'];
				$aAllManagementGroup[$iManagementNo]=COrderManagementGroup::oGetGroup($iManagementNo);
			}
			/*
			if(count($aAllManagement)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&items=$PageItem");
				*/
		}else{
			CJavaScript::vAlertRedirect("No management available",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&items=$PageItem");
		}
		$Smarty->assign('aAllManagement', $aAllManagement);
        		$Smarty->assign("aAllManagementGroup",$aAllManagementGroup);
        		/*
		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);
		$Smarty->assign("NowSort",$sSort);

		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);

		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		*/
		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_management_group_list.html');
	}

	/*
		change search project name into sql string
	*/
	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_project_key");
			$sTerms =  $session->get("s_project_terms");
		}
		$sSql = "";

		if(!$sKey) {
			$session->set("s_project_key","");
			$session->set("s_project_terms","");
			return $sSql;
		}
		$session->set("s_project_key",$sKey);
		$session->set("s_project_terms",$sTerms);

		switch($sTerms){
			default :
				$sSql = $sSql." (`$sTerms` LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}

?>