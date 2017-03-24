<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CGroup.php');
include_once('../inc/model/CRule.php');

class CGroupAdmin extends CGalaxyController
{
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'USER';
	
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
					return $this->tGroupAdd();
					break;
				case "edit":
					return $this->tGroupEdit();
					break;
				case "active":
					return $this->vGroupActive();
					break;
				default:
				case "search":
				case "list":
					return $this->tGroupList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					if(isset($_GET['group_no']))
						$sUrl .= '&action=list&goid='.$_GET['group_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
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

	/*
		add group
	*/
	private function tGroupAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			$Smarty->assign('aCategories',CRule::aAllRuleInCategory());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/group_edit.html');
		}else{
			//use post data to create a $oCGroup
			$oCGroup = new CGroup($_POST);	//if $_POST has all we need
			$oCGroup->vSetRules($_POST['rule']);
			//add
			try{
				$oCGroup->iAddGroup();	//$oCGroup->iGroupNo will be changed to insert id
			}catch (Exception $e){
				throw new Exception($e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCGroup->iGroupNo}");
	}
	
	/*
		edit group
	*/
	private function tGroupEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['group_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iGroupNo = $_GET['group_no'];
		$oCGroup = CGroup::oGetGroup($iGroupNo);
		if(empty($_POST)){
			$Smarty->assign('oCGroup',$oCGroup);
			$Smarty->assign('aGroupRule',$oCGroup->aCRule());
			$Smarty->assign('aCategories',CRule::aAllRuleInCategory());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/group_edit.html');
		}else{
			//use post data to modify $oCGroup
			$oCGroup->sName = $_POST['group_name'];
			$oCGroup->sDesc = $_POST['group_desc'];
			$oCGroup->vSetRules($_POST['rule']);
			$oCGroup->bStatus = $_POST['group_status'];	//group_status
			//update
			try{
				$oCGroup->vUpdateGroup();
			}catch (Exception $e){
				throw new Exception($e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCGroup->iGroupNo}");
	}

	/*
		activate/deactivate group
	*/
	private function vGroupActive(){
		if(empty($_GET['group_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iGroupNo = $_GET['group_no'];
		$oCGroup = CGroup::oGetGroup($iGroupNo);
		try{
			$oCGroup->vActivate();
		}catch (Exception $e){
			throw new Exception($e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCGroup->iGroupNo}");
	}
	

	/*
		list of groups
	*/
	private function tGroupList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$CDbShell_User = $oDB = self::oDB($this->sDBName);

		if(!empty($_POST)) {
			if($_GET['js_valid']==1) {
				$this->vaildGroupSearch($_POST,1);	//client javascript vaild data
			}else{
				$this->vaildGroupSearch($_POST,0);	//form submit vaild data
			}
		}
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
			$sSearchSql ='';

		//得到某筆資料是在第幾頁
		if($goid){
			if($sSearchSql!=='') $sWhereSql = "WHERE $sSearchSql";	//no default filter
            $iPg = $oDB->iGetItemAtPage("galaxy_group","group_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}

		//共幾筆
		$iAllItems = CGroup::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$Smarty->assign("aCGroups",CGroup::aAllGroup($sSearchSql,$sPostFix));
		}

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_group_key") );
		$Smarty->assign('searchTerm',	$session->get("s_group_terms") );
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/group_list.html');
	}

	/*
		check if the search string is vaild
	*/
	private function vaildGroupSearch($postData=array(),$return_type=0){
		
		$aErrorMsg = array();
		
		if(strlen(trim($postData['s_key'])) == 0){
			$aErrorMsg[]=_LANG_GROUP_VAILD_SEARCH_KEY;
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
		change search group name into sql string
	*/ 
	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_group_key");
			$sTerms =  $session->get("s_group_terms");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_group_key","");
			$session->set("s_group_terms","");
			return $sSql;
		}
		$session->set("s_group_key",$sKey);
		$session->set("s_group_terms",$sTerms);
		
		switch($sTerms){
			default :
				$sSql = $sSql." ($sTerms LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}
?>