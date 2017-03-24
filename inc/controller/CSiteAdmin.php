<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CSite.php');
include_once('../inc/model/CSiteType.php');

class CSiteAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	static public $aSearchOption = array(	"site_title" => '名稱',
											"site_url" => '網址',	
											);

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
					return $this->tSiteAdd();
					break;
				case "edit":
					return $this->tSiteEdit();
					break;
				case "active":
					return $this->vSiteActive();
					break;
				default:
				case "search":
				case "list":
					return $this->tSiteList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&site_no='.$_GET['site_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tSiteAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			$Smarty->assign('aAllCSiteType',CSiteType::aAllType());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/site_edit.html');
		}else{
			$oSite = new CSite($_POST);	//if $_POST has all we need
			//add
			try{
				$oSite->iAddSite();
			}catch (Exception $e){
				throw new Exception('CSiteAdmin->tSiteAdd:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oSite->iSiteNo}");
	}

	private function tSiteEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['site_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iSiteNo = $_GET['site_no'];
		$oSite = CSite::oGetSite($iSiteNo);

		if(empty($_POST)){
			$Smarty->assign('oSite',$oSite);
			$Smarty->assign('aAllCSiteType',CSiteType::aAllType());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/site_edit.html');
		}else{
			try{
				$oUpdateSite = new CSite($_POST);
				$oSite->vOverwrite($oUpdateSite);
				$oSite->vUpdateSite();
			}catch (Exception $e){
				throw new Exception('CSiteAdmin->tSiteEdit:'.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oSite->iSiteNo}");
	}

	private function vSiteActive(){
		if(empty($_GET['site_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iSiteNo = $_GET['site_no'];
		$oSite = CSite::oGetSite($iSiteNo);
		try{
			$oSite->vActivate();
		}catch (Exception $e){
			throw new Exception('CSiteAdmin->vSiteActive:'.$e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oSite->iSiteNo}");
	}

	private function tSiteList(){
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
		if(empty($_GET['order'])) $sOrder = "site_createtime";
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
            $iPg = $oDB->iGetItemAtPage("galaxy_site","site_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = CSite::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aAllSites = CSite::aAllSite($sSearchSql,$sPostFix);
			if(count($aAllSites)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$_GET['items']);
		}
        $Smarty->assign("aAllSites",$aAllSites);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_site_key") );
		$Smarty->assign('searchTerm',	$session->get("s_site_terms") );
		$Smarty->assign('searchOption',	self::$aSearchOption);
                
		$Smarty->assign("iTotal",$iAllItems);
		$Smarty->assign("iPageItem",$iPageItems);
		
		$Smarty->assign("iStartRow",$iStart+1);
		$Smarty->assign("iEndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/site_list.html');
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

	/*
		change search user name into sql string
	*/ 
	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_site_key");
			$sTerms =  $session->get("s_site_terms");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_site_key","");
			$session->set("s_site_terms","");
			return $sSql;
		}
		$session->set("s_site_key",$sKey);
		$session->set("s_site_terms",$sTerms);
		
		switch($sTerms){
			default :
				$sSql = $sSql." ($sTerms LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}
?>