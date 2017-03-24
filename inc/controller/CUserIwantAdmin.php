<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CUserIwant.php');

class CUserIwantAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'USER';

	static public $aSearchOption = array(	"galaxy_user.user_name" 	=> '名稱',
											"galaxy_user.user_account" 	=> '帳號',	
											"galaxy_user.user_no"		=> '序號'
											);

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
					return $this->tUserAdd();
					break;
				case "edit":
					return $this->tUserEdit();
					break;
				case "selfedit":
					return $this->tSelfEdit();
					break;
				case "active":
					return $this->vUserActive();
					break;
				case "del": // 刪除人員
					return $this->vUserDel();
					break;
				default:
				case "search":
				case "list":
					return $this->tUserList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					if(isset($_GET['user_no']))
						$sUrl .= '&action=list&goid='.$_GET['user_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&user_no='.$_GET['user_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	/*
		add an iwant user
	*/
	private function tUserAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			$Smarty->assign('aAllGroup',CGroup::aAllGroup());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/user_edit.html');
		}else{
			//use post data to create a $oCUserIwant
			$oCUserIwant = new CUserIwant($_POST);	//if $_POST has all we need
			//add
			try{
				$oCUserIwant->iAddUser();	//$oCUserIwant->iUserNo will be changed to insert id
			}catch (Exception $e){
				throw new Exception($e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('user add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCUserIwant->iUserNo}");
	}

	/*
		edit an iwant user
	*/
	private function tUserEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['user_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iUserNo = $_GET['user_no'];
		$oCUserIwant = CUserIwant::oGetUser($iUserNo);

		if(empty($_POST)){
			$Smarty->assign('oCUser',$oCUserIwant);
			$Smarty->assign('aUserGroup',$oCUserIwant->aGroup());
			$Smarty->assign('aAllGroup',CGroup::aAllGroup());
			$Smarty->assign('aAllDept',CDept::aAllDept());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/user_edit.html');
		}else{
			if($_POST['user_no']!==$iUserNo)
				throw new Exception('GET and POST not match',self::BACK_TO_EDIT);
			//change the value in $oCUserIwant
			$oCUserIwant->sUserName = $_POST['user_name'];
			$oCUserIwant->bStatus = $_POST['status'];
			$oCUserIwant->sEmail = $_POST['user_email'];
			$oCUserIwant->sTel = $_POST['user_tel'];
			$oCUserIwant->sFax = $_POST['user_fax'];
			$oCUserIwant->sMobile = $_POST['user_mobile'];
			$oCUserIwant->sAddrId = $_POST['addr_id'];
			$oCUserIwant->sAddr = $_POST['user_addr'];
			$oCUserIwant->iDeptNo = $_POST['dept_no'];
			//set groups
			$oCUserIwant->vSetGroups($_POST['group']);
			//change password if $_POST['password'] $_POST['password2'] is given
			if($_POST['new_password'] && $_POST['confirm_password'] && $_POST['new_password']===$_POST['confirm_password'])
				$oCUserIwant->vChangePassword($_POST['new_password']);
			//update
			try{
				$oCUserIwant->vUpdateUser();
			}catch (Exception $e){
				throw new Exception($e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('user edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCUserIwant->iUserNo}");
	}

	/*
		edit current user selfdata
		allow changing password
	*/
	private function tSelfEdit(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oCUserIwant = $session->get('oCurrentUser');

		if(empty($_POST)){
			$Smarty->assign('oCUser',$oCUserIwant);
			$Smarty->assign('aAllGroup',CGroup::aAllGroup());
			return $output = $Smarty->fetch('./admin/'.get_class($this).'self_edit.html');
		}else{
			//change the value in $oCUserIwant
			$oCUserIwant->sUserName = $_POST['user_name'];
			$oCUserIwant->bStatus = $_POST['status'];
			$oCUserIwant->sEmail = $_POST['user_email'];
			$oCUserIwant->sTel = $_POST['user_tel'];
			$oCUserIwant->sFax = $_POST['user_fax'];
			$oCUserIwant->sMobile = $_POST['user_mobile'];
			$oCUserIwant->sAddrId = $_POST['addr_id'];
			$oCUserIwant->sAddr = $_POST['user_addr'];
			$oCUserIwant->iDeptNo = $_POST['dept_no'];
			//change password if $_POST['password'] $_POST['password2'] is given
			if($_POST['new_password'] && $_POST['confirm_password'] && $_POST['new_password']===$_POST['confirm_password'])
				$oCUserIwant->vChangePassword($_POST['new_password']);
			//update
			try{
				$oCUserIwant->vUpdateUser();
			}catch (Exception $e){
				throw new Exception($e->getMessage(),self::BACK_TO_LIST);
			}
			CJavaScript::vAlertRedirect('self edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCUserIwant->iUserNo}");
		}
	}

	/*
		active/deactive an iwant user
	*/
	private function vUserActive(){
		if(empty($_GET['user_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iUserNo = $_GET['user_no'];
		$oCUserIwant = CUserIwant::oGetUser($iUserNo);
		try{
			$oCUserIwant->vActivate();
		}catch (Exception $e){
			throw new Exception($e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('active success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCUserIwant->iUserNo}");
	}

	/*
		delete an iwant user
	*/
	private function tUserDelete(){
		
	}

	/*
		list of iwant users
	*/
	private function tUserList(){
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
		if(empty($_GET['items'])) $iPageItems = PAGING_NUM;
		else $iPageItems = $_GET['items'];

		//because this function join two tables, order must be full name with table, caution in tpl!
		if(empty($_GET['order'])) $sOrder = "galaxy_user.createtime";
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
            $iPg = $oDB->iGetJoinItemAtPage("galaxy_user_iwant","galaxy_user","user_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = CUserIwant::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aCUsers = CUserIwant::aAllUser($sSearchSql,$sPostFix);

			if(count($aCUsers)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=list&items=".$_GET['items']);
		}
        $Smarty->assign("aCUsers",$aCUsers);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_user_key") );
		$Smarty->assign('searchTerm',	$session->get("s_user_terms") );
		$Smarty->assign('searchOption',	self::$aSearchOption);
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/user_list.html');
	}

	/*
		check if the search string is vaild
	*/
	private function vaildUserSearch($postData=array(),$return_type=0){
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

	/*
		change search user name into sql string
	*/ 
	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_user_key");
			$sTerms =  $session->get("s_user_terms");
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_user_key","");
			$session->set("s_user_terms","");
			return $sSql;
		}
		$session->set("s_user_key",$sKey);
		$session->set("s_user_terms",$sTerms);
		
		switch($sTerms){
			default :
				$sSql = $sSql." ($sTerms LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
}
?>