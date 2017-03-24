<?php

include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderProject.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CCompanyBrand.php');
include_once('../inc/model/order/COrderManagementGroup.php');
include_once('../inc/model/order/COrderProjectSearchTmp.php');

class COrderProjectAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'ORDER';
	//子系統搜尋欄位
	static public $aSearchOption=array(
		//Beauty2
		4=>array(
			"project_id"=>"專案編號",
			"project_name"=>"專案名稱",
			"cb_id"=>"品牌",
			"condition_no"=>"狀態"
			),
	);

	/*
	static public $aApiUrl=array(
		3=>"http://goods.lab.net/api/api.GoodsOrder.php?&func=goods_project",
		//3=>"http://localhost/goods/api/api.GoodsOrder.php?&func=goods_project",
		4=>"http://beauty2.lab.net/api/api.BeautyOrder.php?func=beauty_project"
		//4=>"http://localhost/php5beauty2/api/api.BeautyOrder.php?func=beauty_project"
		);
	*/

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
					return $this->tOrderProjectAdd();
					break;
				case "view":
					return $this->tOrderProjectView();
					break;
				case "deactivate":
					return $this->tOrderProjectDeactivate();
					break;
				case "edit_client":
					return $this->tOrderProjectEditClient();
					break;
				case "edit_bizdev":
					return $this->tOrderProjectEditBizdev();
					break;
				case "list_by_order":
					return $this->tOrderProjectListByOrder();
					break;
				case "edit_brand":
					return $this->tOrderProjectEditBrand();
					break;
				case "search":
					return $this->tOrderProjectSearchList();
					break;
				default:
				case "list":
				case "admin":
					return $this->tOrderProjectList();
					break;
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=list';
					if(!empty($_GET['order_no']))
						$sUrl .= '&goid='.$_GET['order_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_VIEW:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=view&order_no='.$_GET['order_no']);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&order_no='.$_GET['order_no']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tOrderProjectAdd(){
		$Smarty = self::$Smarty;
		$sOrderUuid=$_GET['order_no'];
		$oCOrder=COrder::oGetOrder($sOrderUuid);
		//$oCOrder->vGetCompany();
		$Smarty->assign('oOrder',$oCOrder);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}

		$Smarty->assign('sAdmin', $sAdmin);

		if(empty($_POST)){
			$aService=COrderProject::$aService;
			$Smarty->assign('aService', $aService);
			$iCompanyId=0;
			if($oCOrder->iType==1){
				$iCompanyId=$oCOrder->iContractClientNo;
			}
			else{
				$iCompanyId=$oCOrder->iClientNo;
			}
			$Smarty->assign('iCompanyId', $iCompanyId);
			$aBrandId=CCompanyBrand::aGetCompanyBrand($iCompanyId);
			$Smarty->assign('aBrandId', $aBrandId);

			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_project_edit.html');
		}else{
			//CMisc::vPrintR($_POST);
			$aPost=$_POST;
			
			$iServiceId=$aPost['service_id'];
			$sOrderProjectNo='';

			$sOrderProjectNo=COrderProject::$aProjectNoPrefix[$iServiceId];
			$sOrderProjectNo.=CMisc::uuid_v1();

			$aPost['project_no']=$sOrderProjectNo;
			$oCOrderProject=new COrderProject($aPost);
			try{
				$oCOrderProject->sAdd();
			}catch (Exception $e){
				throw new Exception('COrderProjectAdmin->tOrderProjectAdd: '.$e->getMessage(), self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('project add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list_by_order&order_no={$oCOrder->sOrderUuid}&service_id=$iServiceId&new_project_no=$sOrderProjectNo$sAdmin");
	}


	/*
		only admin user can access 
	*/

	private function tOrderProjectDeactivate(){
		$sProjectUuid=$_GET['project_no'];
		$sOrderUuid=$_GET['order_no'];
		$oCOrderProject=COrderProject::oGetOrderProject($sProjectUuid);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}

		$Smarty->assign('sAdmin', $sAdmin);

		try{	
			$oCOrderProject->vDeactivate();
		}catch(Exception $e){
			throw new Exception("COrderProjectAdmin->tOrderProjectDeactivate ".$e->getMessage(), self::BACK_TO_LIST);
		}

		CJavaScript::vAlertRedirect("Deactivated. ",$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$sProjectUuid}&order_no=$sOrderUuid$sAdmin");
	}

	private function tOrderProjectEditClient(){

		$sProjectUuid = $_GET['project_no'];
		$oProject = COrderProject::oGetOrderProject($sProjectUuid);

		if(empty($_POST)){
			$Smarty = self::$Smarty;

			$oOrder = $oProject->oOrder();

			$oGroup = COrderManagementGroup::oGetGroup($oOrder->iManagementId);

			$aIwantUser = array(); // 銀河人員
			foreach ($oGroup->aUser() as $oUserFront) {
				if($oUserFront->iDeptNo!='0')
					$aIwantUser[$oUserFront->iUserNo] = $oUserFront;
			}

			include_once('../inc/model/CManagement.php');
			$aMangUser = CManagement::aAllMemeberByManagement($oOrder->iManagementId);
			foreach ($aMangUser as $oUser) {
				$aIwantUser[$oUser->iUserNo] = CUserFront::oGetUser($oUser->iUserNo);
			}
			
			$oCompany = CCompany::oGetCompany($oOrder->iClientNo);
			$oContract = CCompany::oGetCompany($oOrder->iContractClientNo);

			// 服務客戶
			$aCompanyClient = CUserFront::aAllUser("galaxy_user_company.co_id = $oOrder->iClientNo");
			
			// 簽單客戶
			$aContractClient = CUserFront::aAllUser("galaxy_user_company.co_id = $oOrder->iContractClientNo");

			foreach ($oProject->aClient() as $oClient) {
				$oClient->bSelected = true;
			}

			$Smarty->assign('oProject',$oProject);
			$Smarty->assign('oCompany',$oCompany);
			$Smarty->assign('oContract',$oContract);
			$Smarty->assign('aCompanyClient',$aCompanyClient);
			$Smarty->assign('aContractClient',$aContractClient);
			$Smarty->assign('aIwantUser',$aIwantUser);

			$Smarty->display('./admin/'.get_class($this).'/edit_client.html');
			
		}else{

			if(empty($_POST['project_user']))
				$aAllUser = array();
			else
				$aAllUser = array_keys($_POST['project_user']);

			CUserFront::vSetProjectUser($sProjectUuid,$aAllUser);

			echo '<script>parent.postMessage("", "*");</script>';	//close colorbox
		}
		exit;
	}


	private function tOrderProjectEditBizdev(){
		$sProjectUuid = $_GET['project_no'];
		$oProject = COrderProject::oGetOrderProject($sProjectUuid);
		
		if(empty($_POST)){
			$Smarty = self::$Smarty;

			$oOrder = $oProject->oOrder();
			
			$oGroup = COrderManagementGroup::oGetGroup($oOrder->iManagementId);

			$aIwantUser = array(); // 銀河人員
			foreach ($oGroup->aUser() as $oUserFront) {
				if($oUserFront->iDeptNo!='0')
					$aIwantUser[$oUserFront->iUserNo] = $oUserFront;
			}

			include_once('../inc/model/CManagement.php');
			$aMangUser = CManagement::aAllMemeberByManagement($oOrder->iManagementId);
			foreach ($aMangUser as $oUser) {
				$aIwantUser[$oUser->iUserNo] = CUserFront::oGetUser($oUser->iUserNo);
			}
			// $oCompany = CCompany::oGetCompany($oOrder->iClientNo);
			// $oContract = CCompany::oGetCompany($oOrder->iContractClientNo);

			// 服務客戶
			// $aCompanyClient = CUserFront::aAllUser("galaxy_user_company.co_id = $oOrder->iClientNo");
			
			// 簽單客戶
			// $aContractClient = CUserFront::aAllUser("galaxy_user_company.co_id = $oOrder->iContractClientNo");

			// foreach ($oProject->aClient() as $oClient) {
			// 	$oClient->bSelected = true;
			// }

			
			$aSelectedUsers = CUserFront::aUserByBizdev($sProjectUuid);

			if(is_array($aSelectedUsers)){
			
				foreach ($aIwantUser as $oIwantUser ) {
					foreach ($aSelectedUsers as $aSelectedUser ) {
						if($oIwantUser->iUserNo == $aSelectedUser['user_no']){
							$oIwantUser->bSelected = true;
						}
					}
				}
			}

			$Smarty->assign('oProject',$oProject);

			// $Smarty->assign('oCompany',$oCompany);
			// $Smarty->assign('oContract',$oContract);
			// $Smarty->assign('aCompanyClient',$aCompanyClient);
			// $Smarty->assign('aContractClient',$aContractClient);

			$Smarty->assign('aIwantUser',$aIwantUser);
			$Smarty->display('./admin/'.get_class($this).'/edit_bizdev.html');
			
		}else{

			if(empty($_POST['project_user']))
				$aAllUser = array();
			else
				$aAllUser = array_keys($_POST['project_user']);

			CUserFront::vSetBizdevUser($sProjectUuid,$aAllUser);

			echo '<script>parent.postMessage("", "*");</script>';	//close colorbox
		}
		exit;
	}

	/**
	 *  當前專案對品牌是1對1，如果之後有要改成 1對多，會影響到帳號選擇器品牌戶斥的部份
	 */
	private function tOrderProjectEditBrand(){
		if(empty($_POST)){
			$Smarty = self::$Smarty;
			$sProjectUuid = $_GET['project_no'];
			$oProject = COrderProject::oGetOrderProject($sProjectUuid);

			$oOrder = $oProject->oOrder();

			if($oOrder->iType==1){
				$aBrandId=CCompanyBrand::aGetCompanyBrand($oOrder->iContractClientNo);
			}
			else{
				$aBrandId=CCompanyBrand::aGetCompanyBrand($oOrder->iClientNo);
			}
			$Smarty->assign('oProject',$oProject);
			$Smarty->assign('aBrandId', $aBrandId);
			$Smarty->display('./admin/'.get_class($this).'/order_project_edit_brand.html');
			
		}else{
			$sProjectUuid = $_POST['project_no'];
			$oProject = COrderProject::oGetOrderProject($sProjectUuid);
			$oProject->iBrandId=$_POST['cb_id'];
			$oProject->vUpdate();
			echo '<script>parent.postMessage("reload", "*");</script>';	//close colorbox
		}
		exit;
	}

	/*
		admin user return admin page
		other user return list page
	*/
	private function tOrderProjectList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);

		//若使用者為管理員，將admin參數設為1
		if($_GET['action']=='admin'){
			$sAdmin = '&admin=1';
		}else{
			$sAdmin='';
		}
		$Smarty->assign('sAdmin', $sAdmin);

		//從$_GET取得每頁筆數
		if(empty($_GET['items'])) $iPageItems = 50;
		else $iPageItems = $_GET['items'];

		//從$_GET取得排序欄位
		if(empty($_GET['order'])) $sOrder = " create_time";
		else $sOrder = $_GET['order'];
		
		//從$_GET取得排序順序：升冪/降冪
		if(empty($_GET['sort'])) $sSort = "DESC";
		else $sSort = $_GET['sort'];
		
		//從$_GET取得頁次
		if(empty($_GET['page'])) $iPg = 0;
		else $iPg = $_GET['page'];
		
		//從$_GET取得goid 
		if(empty($_GET['goid'])) $goid = 0;
		else $goid = $_GET['goid'];

		//取得使用者所屬體系
		$aManagement=COrderManagementGroup::aAllManagementByUser($session->get('oCurrentUser')->iUserNo);

		//若查無體系則throw error
		if(empty($aManagement))
			throw new Exception("您未屬於任何體系中，無資料可以顯示。");

		$aManagementNo=array();
		foreach($aManagement as $oManagement){
			$aManagementNo[]=$oManagement->iManagementNo;
		}
		
		$sSearch = '';

		//唯驗證通過的訂單才可檢視編輯專案
		$sSearchSql = " o.verify_status = 2 ";

		//將體系組成SQL
		$sManagementNo = "(".implode(",", $aManagementNo).")";
		$sSearchSql .= "AND o.`management_id` in $sManagementNo";

		//總筆數
		$iAllItems=COrderProject::iGetCountProjectJoinOrder($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//子系統API URl
		$aApiUrl=array(
			4=>"http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?func=beauty_project"
		);
		$sApiUrl=$aApiUrl[4];
		
		//取專案資料
		if($iAllItems!==0){
			$sPostFix = "ORDER BY o.$sOrder $sSort LIMIT $iStart,$iPageItems";
			$aProject = COrderProject::aAllProjectJoinOrder($sSearchSql,$sPostFix);
        		
			//取得美女隊專案資料
			foreach($aProject as $oProject){
				$sUrl=$sApiUrl."&action=order_fetch_project&PHPSESSID=".session_id();
				$aOptions=array('project_no'=>$oProject->sProjectUuid);
				$aResult=CMisc::aCurl($sUrl,$aOptions);
				
				if(!empty($aResult)){
					$oProject->bExist=1;
					foreach($aResult as $key => $value){
						$oProject->$key=$value;
					}
				}
			}
		}
        		
		$Smarty->assign('sApiUrl',$sApiUrl);
		$Smarty->assign('aProject', $aProject);

		//訂單項目
		$Smarty->assign('aType', COrder::$aType);
		$Smarty->assign('aService', COrderProject::$aService);
		$Smarty->assign('aOrderPhase', COrder::$aOrderPhase);
		$Smarty->assign('aCancelScope', COrder::$aCancelScope);

		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);

		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."$sSearch&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_project_list.html');
	}

	/**
	 * @desc 專案列表
	 * @return string html
	 * @created 2016/09/06
	 */
	private function tOrderProjectListByOrder(){
		$Smarty  = self::$Smarty;
		$session = self::$session;
		$oDB     = self::oDB($this->sDBName);

		//從$_GET取得專案服務流水號，預設為美女隊(4)
		if(empty($_GET['service_id'])) $iServiceId = 4;
		else $iServiceId = $_GET['service_id'];

		$sSearchSql = '';

		//從$_GET取得新專案color box 參數
		if(!empty($_GET['new_project_no'])){
			$iServiceId=$_GET['service_id'];
			$sProjectUuid=$_GET['new_project_no'];
			$Smarty->assign('iServiceId', $iServiceId);
			$Smarty->assign('sProjectNo', $sProjectUuid);
		}
		$sSearchSql.=" `service_id`= '$iServiceId' ";

		//取訂單資料
		$sOrderUuid = $_GET['order_no'];
		$oCOrder = COrder::oGetOrder($sOrderUuid);
		$Smarty->assign('oOrder', $oCOrder);

		//依訂單唯一碼搜尋
		if(strlen($sSearchSql)!==0)
			$sSearchSql.=" AND ";
		$sSearchSql.=" `order_no`= '$sOrderUuid' ";

		//訂單類型
		$Smarty->assign('aType', COrder::$aType);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
		}else{
			$sAdmin = '';
		}
		$Smarty->assign('sAdmin', $sAdmin);

		//子系統API
		$aApiUrl = array(
			4=>"http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?func=beauty_project"
		);
		$sApiUrl = $aApiUrl[$iServiceId];
		$Smarty->assign('sApiUrl', $sApiUrl);

		//專案服務項目
		$aService = COrderProject::$aService;
		$Smarty->assign('aService', $aService);

		//該訂單下所有專案
		$aProject = COrderProject::aAllOrderProject($sSearchSql);

		//取得美女隊專案資料
		if(!empty($aProject)){
			foreach($aProject as $oProject){
				$sUrl=$sApiUrl."&action=order_fetch_project&PHPSESSID=".session_id();
				$aOptions=array( 
						'project_no'=>$oProject->sProjectUuid,
						);
				$aResult=CMisc::aCurl($sUrl,$aOptions);
				
				if(!empty($aResult)){
					$oProject->bExist=1;
					foreach($aResult as $key => $value){
						$oProject->$key=$value;
					}
				}else{
					$oProject->bStatus = 0;
				}
			}
		}

		/*sort project*/
		$order = isset($_GET['order'])?$_GET['order']:'';
		$sort = isset($_GET['sort'])?$_GET['sort']:'DESC';
		$sSort = (strtoupper($sort)=="DESC")?"ASC":'DESC';

		if($order == "end_date") 
			$sOrder = 'bSortProjectByEndDate';
		elseif ($order == "condition") 
			$sOrder = 'bSortProjectByCondition';
		elseif ($order == "project_name	") 
			$sOrder = 'bSortProjectByProjectName';
		else
	 		$sOrder = 'bSortProjectByStartDate';

		function bSortProjectByStartDate($a, $b) {
			global $sort;
			if($sort=="DESC")
				return strcmp($b->sStartDate, $a->sStartDate);
			else	
				return strcmp($a->sStartDate, $b->sStartDate);
		} 
		function bSortProjectByEndDate($a, $b) {
			global $sort;
			if($sort=="DESC")
				return strcmp($b->sEndDate, $a->sEndDate);
			else	
				return strcmp($a->sEndDate, $b->sEndDate);
		} 
		function bSortProjectByCondition($a, $b) {
			global $sort;
			if($sort=="DESC")
				return strcmp($b->sCondition, $a->sCondition);
			else	
				return strcmp($a->sCondition, $b->sCondition);
		}
		function bSortProjectByProjectName($a, $b) {
			global $sort;
			if($sort=="DESC")
				return strcmp($b->sName, $a->sName);
			else	
				return strcmp($a->sName, $b->sName);
		}

		if ($aProject) usort($aProject,$sOrder);	
		$Smarty->assign('aProject', $aProject);
		$Smarty->assign('OrderSort', (strtoupper($sSort)=="DESC")?"ASC":"DESC");
		$Smarty->assign('OrderUrl', $_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&order_no=".$_GET['order_no']);

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_project_list_by_order.html');
	}


	private function tOrderProjectSearchList(){
		$Smarty  = self::$Smarty;
		$session = self::$session;		

		//從$_GET取得每頁筆數
		$iPageItems = isset($_GET['items'])?$_GET['items']:100;

		//從$_GET取得排序欄位
		if(empty($_GET['order'])) $sOrder = " start_date";
		else $sOrder = $_GET['order'];
		
		//從$_GET取得排序順序：升冪/降冪
		if(empty($_GET['sort'])) $sSort = "DESC";
		else $sSort = $_GET['sort'];
		
		//從$_GET取得頁次
		if(empty($_GET['page'])) $iPg = 0;
		else $iPg = $_GET['page'];
		
		// $iStart=$iPg*$iPageItems;
		// //從$_GET取得goid 
		// if(empty($_GET['goid'])) $goid = 0;
		// else $goid = $_GET['goid'];
		
		$aPost=$_POST;

		$reload = isset($_GET['reload'])?$_GET['reload']:0;
		if($reload==1){
			COrderProjectSearchTmp::vMakeSearchTmp($aPost);
			CJavaScript::vRedirect($_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']);
		}

		//如果 project_condition 不存在，就是沒有輸入搜尋條件，清空該使用者搜尋的紀錄
		$iCheckSession = $session->get('project_condition');
		if(is_null($iCheckSession)){
			COrderProjectSearchTmp::vEmptySearchTmp();
			$aPost['project_condition'] = 1; //要求一進搜尋頁面，直接顯示進行中的專案
		}

		if(!empty($aPost)){
			COrderProjectSearchTmp::vMakeSearchTmp($aPost);
		}	

		$iUserNo = $session->get('oCurrentUser')->iUserNo;
		$sSearchSql = "`user_no`='$iUserNo'";
		$sFix = "Order By $sOrder $sSort ";
		$iAllItems = COrderProjectSearchTmp::iCountResult($sSearchSql);
		$aAllSearhTmp = COrderProjectSearchTmp::aGetSearchTmpResult($sSearchSql,$sFix);

		$sApiUrl = "http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?func=beauty_project";
		$Smarty->assign('sApiUrl', $sApiUrl);
		$Smarty->assign('aAllSearhTmp', $aAllSearhTmp);

		$sProjectId 	= $session->get('project_id');
		$sProjectName 	= $session->get('project_name');
		$iConditionNo 	= $session->get('project_condition');
		
		// null時改成-1，因為會被當 0
		if($iConditionNo == Null)
			$iConditionNo = -1;		

		//selected condition
		$Smarty->assign('sProjectId', $sProjectId);		
		$Smarty->assign('sProjectName', $sProjectName);		
		$Smarty->assign('iConditionNo', $iConditionNo);		
		$Smarty->assign('aAllConditions', COrderProjectSearchTmp::$aConditionTW);		

		//訂單項目
		$Smarty->assign('aType', COrder::$aType);
		$Smarty->assign('aService', COrderProject::$aService);
		$Smarty->assign('aOrderPhase', COrder::$aOrderPhase);
		$Smarty->assign('aCancelScope', COrder::$aCancelScope);

		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);

		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		// $Smarty->assign("StartRow",$iStart+1);
		// $Smarty->assign("EndRow",$iStart+$iPageItems);

		// $Smarty->assign("iPg",$iPg);
		// $Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."$sSearch&order=$sOrder&sort=$sSort"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_project_search.html');
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