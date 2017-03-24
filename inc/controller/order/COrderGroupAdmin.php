<?php
include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CUserIwant.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderGroup.php');

class COrderGroupAdmin extends CGalaxyController
{
	
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
				case "add":
					return $this->tGroupAdd();
					break;
				case "edit":
					return $this->tGroupEdit();
					break;
				case "view":
					return $this->tGroupView();
					break;
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
					if(!empty($_GET['group_no']))
						$sUrl .= '&goid='.$_GET['group_no'];
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

	private function tGroupAdd(){
		$aAllUser= CUserIwant::aAllUser();
		if(empty($_POST)){
			$Smarty = self::$Smarty;
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_group_edit.html');
		}else{
			$oOrderGroup=new COrderGroup($_POST);
			try{
				$oOrderGroup->iAdd();
			}catch(Exception $e){
				throw new Exception('COrderGroupAdmin->tGroupAdd:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('group add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&group_no={$oOrderGroup->iGroupNo}");
	}

	private function tGroupEdit(){
		$iGroupNo=$_GET['group_no'];
		if(empty($iGroupNo)){
			CJavaScript::vAlertRedirect('group no required',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list");
		}
		$oOrderGroup=COrderGroup::oGetGroup($iGroupNo);

		if(empty($_POST)){
			$Smarty = self::$Smarty;
			$Smarty->assign('oOrderGroup',$oOrderGroup);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_group_edit.html');
		}else{
			$oOrderGroupUpdate=new COrderGroup($_POST);
			$oOrderGroupUpdate->bStatus=isset($_POST['status'])?1:0;
			try{
				$oOrderGroupUpdate->vUpdate();
			}catch(Exception $e){
				throw new Exception('COrderGroupAdmin->tGroupEdit:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('group edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&group_no={$oOrderGroup->iGroupNo}");
	}

	private function tGroupView(){
		$Smarty = self::$Smarty;
		$iGroupNo=$_GET['group_no'];
		if(empty($iGroupNo))
			throw new Exception('',self::BACK_TO_LIST);
		$oOrderGroup=COrderGroup::oGetGroup($iGroupNo);
		$Smarty->assign('oOrderGroup',$oOrderGroup);
		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_group_view.html');
	}

	private function tGroupUserEdit(){
		$iGroupNo=$_GET['group_no'];
		if(empty($iGroupNo)){
			CJavaScript::vAlertRedirect('group no required',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list");
		}
		$oOrderGroup=COrderGroup::oGetGroup($iGroupNo);
		$aAllUser= CUserIwant::aAllUser();

		if(empty($_POST)){
			$Smarty = self::$Smarty;
			$aAllGroupUser=$oOrderGroup->aUser();
			if(!empty($aAllGroupUser)){
				foreach($aAllGroupUser as $oGroupUser){
					foreach($aAllUser as $oUser){
						if($oGroupUser->iUserNo === $oUser->iUserNo){
							$oUser->bSelected=1;
							break;
						}
					}
				}
			}
			$Smarty->assign('oOrderGroup',$oOrderGroup);
			$Smarty->assign('aAllUser', $aAllUser);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_group_user_edit.html');
		}else{
			$oOrderGroupUpdate=COrderGroup::oGetGroup($_POST['group_no']);
			$oOrderGroupUpdate->vSetUser($_POST['member_user_no']);
			try{
				$oOrderGroupUpdate->vUpdateUser();
			}catch(Exception $e){
				throw new Exception('COrderGroupAdmin->tGroupUserEdit:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('group user edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&group_no={$oOrderGroup->iGroupNo}");
	}

	private function tGroupList(){
		$Smarty = self::$Smarty;
		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];

		if(empty($_GET['order'])) $sOrder = "create_time";
		else $sOrder = $_GET['order'];
		
		if(empty($_GET['sort'])) $sSort = "DESC";
		else $sSort = $_GET['sort'];
		
		if(empty($_GET['page'])) $iPg = 0;
		else $iPg = $_GET['page'];
		
		if(empty($_GET['goid'])) $goid = 0;
		else $goid = $_GET['goid'];

		$sSearchSql='';

		//共幾筆
		$iAllItems = COrderGroup::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aOrderGroup = COrderGroup::aAllGroup($sSearchSql,$sPostFix);
			if(count($aOrderGroup)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&items=$PageItem");	
		}
        		$Smarty->assign("aOrderGroup",$aOrderGroup);

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

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_group_list.html');
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