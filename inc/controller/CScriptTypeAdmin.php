<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CScriptType.php');

class CScriptTypeAdmin extends CGalaxyController
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
					return $this->tScriptTypeAdd();
					break;
				case "edit":
					return $this->tScriptTypeEdit();
					break;
				case "active":
					return $this->vScriptTypeActive();
					break;
				default:
				case "search":
				case "list":
					return $this->tScriptTypeList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&script_type_no='.$_GET['script_type_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tScriptTypeAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/script_type_edit.html');
		}else{
			$oScriptType = new CScriptType($_POST);	//if $_POST has all we need
			//add
			try{
				$oScriptType->iAddScriptType();
			}catch (Exception $e){
				throw new Exception('CScriptTypeAdmin->tScriptTypeAdd:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oScriptType->iScriptTypeNo}");
	}

	private function tScriptTypeEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['script_type_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iScriptTypeNo = $_GET['script_type_no'];
		$oScriptType = CScriptType::oGetType($iScriptTypeNo);

		if(empty($_POST)){
			$Smarty->assign('oScriptType',$oScriptType);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/script_type_edit.html');
		}else{
			if($_POST['script_type_no']!==$iScriptTypeNo)
				throw new Exception('CScriptTypeAdmin->tScriptTypeEdit:GET and POST not match',self::BACK_TO_EDIT);
			
			$oScriptType->sName = $_POST['script_type_name'];
			$oScriptType->fSort = $_POST['script_type_sort'];
			$oScriptType->bStatus = $_POST['script_type_status'];
			
			//update
			try{
				$oScriptType->vUpdateScriptType();
			}catch (Exception $e){
				throw new Exception('CScriptTypeAdmin->tScriptTypeEdit:'.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oScriptType->iScriptTypeNo}");
	}

	private function vScriptTypeActive(){
		if(empty($_GET['script_type_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iScriptTypeNo = $_GET['script_type_no'];
		$oScriptType = CScriptType::oGetType($iScriptTypeNo);
		try{
			$oScriptType->vActivate();
		}catch (Exception $e){
			throw new Exception('CScriptTypeAdmin->vScriptTypeActive:'.$e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oScriptType->iScriptTypeNo}");
	}

	private function tScriptTypeList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);
		if(!empty($_POST)) {
			if($_GET['js_valid']==1) {
				$this->vaildSearch($_POST,1);	//client javapage vaild data
			}else{
				$this->vaildSearch($_POST,0);	//form submit vaild data
			}
		}
		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];

		//because this function join two tables, order must be full name with table, caution in tpl!
		if(empty($_GET['order'])) $sOrder = "script_type_createtime";
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
            $iPg = $oDB->iGetItemAtPage("galaxy_script_type","script_type_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = CScriptType::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aAllScriptType = CScriptType::aAllType($sSearchSql,$sPostFix);
			if(count($aAllScriptType)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$_GET['items']);
		}

        $Smarty->assign("aAllScriptType",$aAllScriptType);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_script_type_key") );
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/script_type_list.html');
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

		//client javapage vaild data
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
			$sKey = $session->get("s_script_type_key");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_script_type_key","");
			return $sSql;
		}
		$session->set("s_script_type_key",$sKey);
		
		switch($sTerms){
			default :
				$sSql = $sSql." (`script_type_name` LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}
?>