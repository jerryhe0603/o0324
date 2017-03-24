<?php

include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderPayment.php');
include_once('../inc/model/order/COrderProject.php');
//include_once('../inc/model/order/COrderGroup.php');
include_once('../inc/model/order/COrderManagementGroup.php');
include_once('../inc/model/CCompany.php');
include_once('../inc/model/CManagement.php');
include_once('../inc/model/CUserCompany.php');
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/order/CProduct.php');

class COrderAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST  = 1;
	const BACK_TO_VIEW  = 2;
	const BACK_TO_EDIT  = 3;
	const BACK_TO_ADMIN = 4;

	private $sDBName = 'ORDER';

	static public $aSearchOption = array(
		"name" => '訂單名稱',
		"client_name" => '公司名稱'
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
					return $this->tOrderAdd();
					break;
				case "edit":
					return $this->tOrderEdit();
					break;
				case "view":
					return $this->tOrderView();
					break;
				case "verify":
					return $this->tOrderVerify();
					break;
				default:
				// case "search":
				case "list":
				case "admin":
					return $this->tOrderList();
					break;
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=list';
					if(!empty($_GET['order_no']))
						$sUrl .= '&goid='.$_GET['order_no'];
					if(isset($_GET['verify_status']))
						$sUrl .= '&verify_status='.$_GET['verify_status'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_VIEW:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=view&order_no='.$_GET['order_no']);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&order_no='.$_GET['order_no']);
					break;
				case self::BACK_TO_ADMIN:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=admin';
					if(!empty($_GET['order_no']))
						$sUrl .= '&goid='.$_GET['order_no'];
					if(isset($_GET['verify_status']))
						$sUrl .= '&verify_status='.$_GET['verify_status'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tOrderAdd(){
		$Smarty = self::$Smarty;

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}

		$Smarty->assign('sAdmin', $sAdmin);
		if(empty($_POST)){
			/*
			if($bAdmin){
				$aAllGroup=COrderGroup::aAllGroup(" `status`=1 ");
			}else{
				$iUserNo=self::$session->get('oCurrentUser')->iUserNo;
				$aAllGroup=COrderGroup::aAllGroupByUser($iUserNo);
			}
			
			$Smarty->assign('aAllGroup', $aAllGroup);
			*/
			$aType=COrder::$aType;
			$Smarty->assign('aType', $aType);
		
			$oCurrentUser = self::$session->get('oCurrentUser');
			
			$aManagements = COrderManagementGroup::aAllManagementByUser($oCurrentUser->iUserNo);

			$aManaNos = array();
			foreach ($aManagements as $oManagement) {
				$aManaNos[] = $oManagement->iManagementNo;
			}

			$sWhere = " `flag` = 0 AND `mm_id` in (".implode(",", $aManaNos).")";
			$aManagement=CManagement::aAllManagement($sWhere);
			$Smarty->assign('aManagement', $aManagement);

			$aCancelScope=COrder::$aCancelScope;
			$Smarty->assign('aCancelScope', $aCancelScope);

			// 產品
			$Smarty->assign("aCProduct", CProduct::aAllProduct("flag=1", "ORDER BY product_order, created"));
			$Smarty->assign("aOrderProduct", array());

			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_edit.html');
		}else{
			$iCount = COrder::iGetCount("`order_id` = '".$_POST['order_id']."'");
			if($iCount>0)
				throw new Exception('COrderAdmin->tOrderAdd: 訂單編號重複,請重新操作', $bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);
			
			//CMisc::vPrintR($_POST);
			$oCOrder = new COrder($_POST);
			//$oCOrder->vSetCompany($_POST);

			try{
				$oCOrder->sAdd();
				// 新增產品
				$product_no = isset($_POST['product_no'])?$_POST['product_no']:0;
				$oCOrder->vAddOrderProductRel($product_no);
			}catch (Exception $e){
				throw new Exception('COrderAdmin->tOrderAdd: '.$e->getMessage(), $bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('order add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&order_no={$oCOrder->sOrderUuid}$sAdmin");
	}

	private function tOrderEdit(){
		$Smarty = self::$Smarty;
		$sOrderUuid = isset($_GET['order_no'])?$_GET['order_no']:'';
		$oCOrder = COrder::oGetOrder($sOrderUuid);

		if(!isset($oCOrder))
			throw new Exception('order not found',self::BACK_TO_LIST);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}
		$Smarty->assign('sAdmin', $sAdmin);

		if(empty($_POST)){
			/*
			if($bAdmin){
				$aAllGroup=COrderGroup::aAllGroup(" `status`=1 ");
			}else{
				$iUserNo=self::$session->get('oCurrentUser')->iUserNo;
				$aAllGroup=COrderGroup::aAllGroupByUser($iUserNo);
			}
			$Smarty->assign('aAllGroup', $aAllGroup);
			*/
			//$oCOrder->vGetCompany();
			$Smarty->assign('oOrder',$oCOrder);

			$aType = COrder::$aType;
			$Smarty->assign('aType', $aType);

			$aSalespeople = CManagement::aAllMemeberByManagement($oCOrder->iManagementId);
			$Smarty->assign('aSalespeople',$aSalespeople);

			$aContact = CUserCompany::aAllUser(" `co_id` = '{$oCOrder->iContractClientNo}' ");
			$Smarty->assign('aContact', $aContact);

			$oCurrentUser = self::$session->get('oCurrentUser');
			
			$aManagements = COrderManagementGroup::aAllManagementByUser($oCurrentUser->iUserNo);

			$aManaNos = array();
			foreach ($aManagements as $oManagement) {
				$aManaNos[] = $oManagement->iManagementNo;
			}

			$sWhere = " `flag` = 0 AND `mm_id` in (".implode(",", $aManaNos).")";

			$aManagement=CManagement::aAllManagement($sWhere);
			$Smarty->assign('aManagement', $aManagement);

			$aCancelScope=COrder::$aCancelScope;
			$Smarty->assign('aCancelScope', $aCancelScope);

			if($oCOrder->iType==1){
				$aBrandId=CCompanyBrand::aGetCompanyBrand($oCOrder->iContractClientNo);
			}
			else{
				$aBrandId=CCompanyBrand::aGetCompanyBrand($oCOrder->iClientNo);
			}
			$Smarty->assign('aBrandId', $aBrandId);
			
			// 產品
			$aOrderProduct = array();
			$Smarty->assign("aCProduct", CProduct::aAllProduct("flag=1", "ORDER BY product_order, created"));
			$aCProduct = $oCOrder->aGetProduct();
			if ($aCProduct){
				foreach($aCProduct as $oCProduct) 
					array_push($aOrderProduct, $oCProduct->product_no);
			}
			$Smarty->assign("aOrderProduct",$aOrderProduct);
			

			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_edit.html');
		}else{
			$oCOrderUpdate = new COrder($_POST);	//create project object from $_POST
			//$oCOrderUpdate->vSetCompany($_POST);
			$oCOrderUpdate->bStatus=isset($_POST['status'])?1:0;

			//if it was a rejected or verified order, set erroneous items
			if($oCOrder->iVerifyStatus!=1){
				$aErroneousItems=array();
				if(!empty($_POST['verify_items'])){
					foreach($_POST['verify_items'] as $value){
						if(!in_array($value,$aErroneousItems)&&!empty($value))
							$aErroneousItems[]=$value;
					}	
				}
				

				$oCOrderUpdate->vSetErroneousItems($aErroneousItems);
			}

			//check if client data and contract client data are verified.
			//if not, set bClientsVerified to false
			$bClientsVerified=true;
			// check if contract client data is verified
			$iCompanyNo = $oCOrder->iContractClientNo;
			$oCompany=CCompany::oGetCompany($iCompanyNo);
			if($oCompany->iVerifyUserNo==0){
				$bClientsVerified=false;
			}

			// check if client data is verified
			if($oCOrder->iType!=1){
				$iCompanyNo = $oCOrder->iClientNo;
				$oCompany=CCompany::oGetCompany($iCompanyNo);
				if($oCompany->iVerifyUserNo==0){
					$bClientsVerified=false;
				}
			}

			//set  verify status
			//if no erroneous items and both contract client data and client data are verified,
			// set verify status to 通過(2), or else 待驗證(1)
			if(empty($aErroneousItems)&&$bClientsVerified)
				$oCOrderUpdate->iVerifyStatus=2;
			else{
				$oCOrderUpdate->iVerifyStatus=1;
			}

			//$oCOrder->vOverWrite($oCOrderUpdate);	//overwrite
			try{
				$oCOrder->vUpdate($oCOrderUpdate);
				
				// 更新產品
				$oCOrder->vDelOrderProductRel();
				$oCOrder->vAddOrderProductRel($_POST['product_no']);

			}catch (Exception $e){
				throw new Exception('COrderAdmin->tOrderEdit: '.$e->getMessage(), $bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('order edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&order_no={$oCOrder->sOrderUuid}$sAdmin");
	}

	/*
		only admin user can access 
	*/
	private function tOrderView(){
		$Smarty = self::$Smarty;

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}
		$Smarty->assign('sAdmin', $sAdmin);

		if(empty($_GET['order_no']))
			throw new Exception('',$bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);
		
		$sOrderUuid = $_GET['order_no'];
		$oCOrder = COrder::oGetOrder($sOrderUuid);

		if(is_null($oCOrder))
			throw new Exception('order not found',$bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);
		
		//$oCOrder->vGetCompany();
		$Smarty->assign('oOrder',$oCOrder);

		$aType=COrder::$aType;
		$Smarty->assign('aType', $aType);

		$aCancelScope=COrder::$aCancelScope;
		$Smarty->assign('aCancelScope', $aCancelScope);

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_view.html');
	}

	public function tOrderVerify(){
		$Smarty = self::$Smarty;
		if(empty($_GET['order_no']))
			throw new Exception('no order no',self::BACK_TO_LIST);
		
		$sOrderUuid = $_GET['order_no'];
		$oCOrder = COrder::oGetOrder($sOrderUuid);

		if(is_null($oCOrder))
			throw new Exception('order not found',self::BACK_TO_ADMIN);

		if(empty($_POST)){
			// check if contract client data is verified
			$iCompanyNo = $oCOrder->iContractClientNo;
			$oCompany=CCompany::oGetCompany($iCompanyNo);
			if($oCompany->iVerifyUserNo==0){
				$bContractClientVerified = 0;
			}else{
				$bContractClientVerified = 1;
			}

			// check if client data is verified
			$bClientVerified = 0;
			if($oCOrder->iType!=1){
				$iCompanyNo = $oCOrder->iClientNo;
				$oCompany=CCompany::oGetCompany($iCompanyNo);
				if($oCompany->iVerifyUserNo != 0 ){
					$bClientVerified = 1;
				}
			}
			
			$aCancelScope=COrder::$aCancelScope;

			//$oCOrder->vGetCompany();
			$Smarty->assign('oOrder', $oCOrder);

			$aType=COrder::$aType;
			$Smarty->assign('aType', $aType);
			$Smarty->assign('sAdmin',  '&admin=1');

			$Smarty->assign('bContractClientVerified', $bContractClientVerified);
			$Smarty->assign('bClientVerified', $bClientVerified);
			$Smarty->assign('aCancelScope', $aCancelScope);
			$Smarty->assign('jErroneousItems', json_encode($oCOrder->aErroneousItems()));
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_verify.html');
		}else{
			try{
				
				//set erroneous items
				$aErroneousItems=array();
				foreach($_POST as $key => $value){
					if(!$value&&!in_array($key, $aErroneousItems))
						$aErroneousItems[]=$key;
				}

				$oCOrder->vSetErroneousItems($aErroneousItems);

				//set verify status
				if(!empty($aErroneousItems)){
					$oCOrder->iVerifyStatus=0;
				}else{
					$oCOrder->iVerifyStatus=2;
				}

				$oCOrder->vVerify();
			}catch(Exception $e){
				throw new Exception('COrderAdmin->tOrderVerify: '.$e->getMessage(),self::BACK_TO_ADMIN);
			}
		}

		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=admin&goid={$oCOrder->sOrderUuid}");
	}


	/*
		admin user return admin page
		other user return list page
	*/
	private function tOrderList(){
		$Smarty  = self::$Smarty;
		$session = self::$session;
		$oDB     = self::oDB($this->sDBName);

		//若使用者為管理員，將admin參數設為1
		if($_GET['action']=='admin')
			$sAdmin = '&admin=1';
		else
			$sAdmin='';
				
		//從$_GET取得每頁筆數
		if(empty($_GET['items'])) $iPageItems = 50;
		else $iPageItems = $_GET['items'];

		//從$_GET取得排序順序：升冪/降冪
		if(empty($_GET['sort'])) $sSort = "DESC";
		else $sSort = $_GET['sort'];
		
		//從$_GET取得頁次
		if(empty($_GET['page'])) $iPg = 0;
		else $iPg = $_GET['page'];

		//從$_GET取得訂單驗證狀態
		if(empty($_GET['verify_status'])) 
			$iVerifyStatus = 0;
		else
			$iVerifyStatus=$_GET['verify_status'];

		//從$_GET取得排序欄位
		if(empty($_GET['order'])){
			//待驗證訂單排序欄位預設為修改時間
			if($iVerifyStatus==1)
				$sOrder="modify_time";
			else	
				$sOrder = "create_time";
		}else{
			$sOrder = $_GET['order'];
		}

		//取得使用者所屬體系
		$aManagement=COrderManagementGroup::aAllManagementByUser($session->get('oCurrentUser')->iUserNo);
		
		//若查無體系則throw error
		if(empty($aManagement))
			throw new Exception("您未屬於任何體系中，無資料可以顯示。");

		//從$_GET取得體系流水號
		//若$_GET沒有體系流水號則取aManagement中第一個體系的流水號
		if(!empty($_GET['management_id']))
			$iManagementNo = $_GET['management_id'];
		else{
			$aKeys = array_keys($aManagement);
			$iManagementNo = $aManagement[$aKeys[0]]->iManagementNo;
		}

		//從$_GET取得goid 並設定該訂單的體系與驗證狀態作為列表顯示的體系與驗證狀態
		if(empty($_GET['goid'])) 
			$goid = 0;
		else {
			$goid = $_GET['goid'];
			$oOrderTmp=COrder::oGetOrder($goid);
			
			//檢查使用者有該訂單的體系權限
			$aUserManaNos = array();
			foreach ($aManagement as $key=>$oManagement) {
				$aUserManaNos[] = $oManagement->iManagementNo;
			}

			if(!in_array($oOrderTmp->iManagementId, $aUserManaNos))
				throw new Exception("您沒有檢視此體系訂單的權限。",self::BACK_TO_LIST);
			
			$iVerifyStatus=$oOrderTmp->iVerifyStatus;
			$iManagementNo=$oOrderTmp->iManagementId;
		}

		//將搜尋條件寫成SQL
		$aCountByManagement=array();
		$iSearch = isset($_GET['search'])?$_GET['search']:0;
		$sSearch = '';
		if($iSearch == 1){
			$sSearch ="&search=1";
			$sSearchSql = $this->sGetSearchSql($_POST);

			//僅搜尋使用者所屬的體系
			$aManagementNo=array();
			foreach($aManagement as $oManagement){
				$aManagementNo[]=$oManagement->iManagementNo;
			}
			if ($aManagementNo) $sManagementNo = implode(",", $aManagementNo);
			else $sManagementNo = '';
			
			$iVerifyStatus = '';
			$iManagementNo = '';
			
			if ($sManagementNo) {
				if ($sSearchSql) $sSearchSql .= " AND `management_id` in ($sManagementNo)";
				else $sSearchSql = "`management_id` in ($sManagementNo)";
			}

		}else {
			//計算各體系筆數
			if(!empty($aManagement)){
				foreach($aManagement as $oManagement){
					$iNo=$oManagement->iManagementNo;
					$aCountByManagement[$iNo]=COrder::iGetCount("`management_id` = '$iNo' AND `verify_status` = '$iVerifyStatus'");
				}
			}

			$sSearchSql = " `management_id` = '$iManagementNo' AND `verify_status` = '$iVerifyStatus' ";
		}
			
		
		if($goid){ //一般排序時找特定訂單所在頁次，直接搜尋資料庫
			$iPg = $oDB->iGetItemAtPage("`order`","order_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		$iStart=$iPg*$iPageItems;

		//總筆數
		$iAllItems=COrder::iGetCount($sSearchSql);
		$aOrder = COrder::aAllOrder($sSearchSql,"ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems");

        		//統計每筆訂單下的專案與發票資料
        		if(!empty($aOrder)){
        			foreach($aOrder as $oOrder){
	        			//訂單唯一碼
	        			$sOrderUuid=$oOrder->sOrderUuid;
	        			//該訂單下的所有專案
	        			$aProject=COrderProject::aAllOrderProject("`order_no`='$sOrderUuid' AND `status`=1 ");
	        			
	        			//CURL查詢美女隊專案執行狀況
	        			if(!empty($aProject)){
	        				$aProjectNo=array();
	        				foreach($aProject as $oProject){
	        					$aProjectNo[]=" '{$oProject->sProjectUuid}' ";
	        				}

	        				//CURL參數
	        				$sUrl ="http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?PHPSESSID=".session_id();
	        				
	        				//分母：統計「資料狀態」為『開啟』
	        				$aOptionD=array(
	        					"status_query"=>1,
		        				"where" => "`project_status`=1 and `project_no` in (".implode(",", $aProjectNo).")",
	        				);

	        				$iProjectD = CMisc::aCUrl($sUrl,$aOptionD);

	        				//分子：統計上述「資料狀態」為『開啟』且「專案狀態」為『結案』『取消』『失敗』
	        				$aOptionN=array(
	        					"status_query"=>1,
		        				"where" => "`project_status`=1 and `project_no` in (".implode(",", $aProjectNo).")  and `condition_no` in (2,4,6) ",
	        				);

	        				$iProjectN = CMisc::aCUrl($sUrl,$aOptionN);
	        			}else{
	        				$iProjectD = 0;
	        				$iProjectN = 0;
	        			}

	        			//統計美女隊專案執行狀況
	        			$oOrder->sProjectProgress="$iProjectN/$iProjectD";
	        			
	        			//統計發票付款情形
	        			$iPayment=COrderPayment::iGetCount("`order_no`='$sOrderUuid' and `status`=1");
	        			$iPaid=COrderPayment::iGetCount("`order_no`='$sOrderUuid' and `status`=1 and `actual_date`<= '".date('Y-m-d')."' ");
	        			$oOrder->sPaymentProgress="$iPaid/$iPayment";
	        		}
        		}
        		
		$Smarty->assign('sAdmin', $sAdmin);
		$Smarty->assign('aManagement', $aManagement);
		$Smarty->assign('iVerifyStatus', $iVerifyStatus);
		$Smarty->assign("iManagementNo", $iManagementNo);
		$Smarty->assign("aCountByManagement", $aCountByManagement);

		$Smarty->assign("aOrder", $aOrder);
		$Smarty->assign("aType", COrder::$aType);
		$Smarty->assign("aOrderPhase", COrder::$aOrderPhase);
		$Smarty->assign("aOrderPhaseField", COrder::$aOrderPhaseField);
		$Smarty->assign("aCancelScope", COrder::$aCancelScope);

		//寫Smarty參數並回傳tpl
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."$sSearch&page=$iPg&verify_status=$iVerifyStatus&management_id=$iManagementNo");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		//$Smarty->assign('searchKey',	$session->get("s_key") );
		//$Smarty->assign('searchTerm',	$session->get("s_terms") );
		//$Smarty->assign('searchOption',	self::$aSearchOption);
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."$sSearch&order=$sOrder&sort=$sSort&verify_status=$iVerifyStatus&management_id=$iManagementNo"));

		return $Smarty->fetch('./admin/'.get_class($this).'/order_list.html');
	}
	

	private function vSetSearchSql($aPost){
		$session = self::$session;
		$aSearch=array();
		if(count($aPost)){
			foreach($aPost as $key=> $value){
				if(empty($value)) continue;
				$aSearch[$key]=$value;	
			}
		}
		$session->set("search_order",$aSearch);
	}
	/*
		change search project name into sql string
	*/ 
	private function sGetSearchSql($aPost){
		$session = self::$session;

		if(empty($aPost)){
			$aPost=$session->get('s_order');
		}else{
			$session->set('s_order', $aPost);
		}

		$sSql='';
		foreach($aPost as $key => $term){
			$term=trim($term);
			//若該欄位搜尋的值為空，或該欄位的值是某時間範圍的結束時間，跳過
			if(empty($term)||$key=='start_date_to'||$key=='end_date_to') continue;
			if(!empty($sSql)) $sSql.= " AND ";
			switch($key){
				//報價單編號 (varchar)
				case "quotation_no":
					$sSql.=" `quotation_no` regexp '$term' ";
					break;
				//訂單編號 (varchar)
				case "order_id":
					$sSql.=" `order_id` regexp '$term' ";
					break;
				//訂單類型 (int)
				case 'type':
					$sSql.=" `type`=$term ";
					break;
				//體系 (int)
				case 'management_id':
					$sSql.=" `management_id` = $term ";
					break;
				//簽單客戶名稱(varchar)
				case 'contract_client_name':
					$sSql.=" `contract_client_name` like '%$term%' ";
					break;
				//訂單名稱(varchar)
				case 'name':
					$sSql.=" `name` like '%$term%' ";
					break;
				//接單業務員(int)
				case 'salesperson_no':
					$sSql.=" `salesperson_no` = $term ";
					break;
				//開始時間(date) 起訖時間會一起放進SQL
				case 'start_date_from':
					if(!empty($aSearch['start_date_to']))
						$sSql.=" `start_date` between  '$term' and  '".$aSearch['start_date_to']."'";
					else
						$sSql.="`start_date` = '$term'";
					break;
				//結束時間(date) 起訖時間會一起放進SQL
				case 'end_date_from':
					if(!empty($aSearch['end_date_to']))
						$sSql.=" `end_date` between  '$term' and  '".$aSearch['end_date_to']."'";
					else
						$sSql.="`end_date` = '$term'";
					break;
				//訂單狀態(int)
				case 'phase_no':
					$sSql.=" `phase_no` =$term ";
					break;
				//訂單取消(int)
				case 'canceled':
					$sSql.=" `canceled`=$term ";
					break;
				//完工 0:(不篩選) 1:未完工 2:已完工
				case 'completed':
					if($term==1)
						$sSql.=" ('".date('Y-m-d')."' < `completion_date` OR `completion_date` ='0000-00-00' )";
					elseif($term==2)
						$sSql.=" ('".date('Y-m-d')."' >= `completion_date` AND `completion_date` !='0000-00-00') ";
					break;
			}
		}
		return $sSql;
	}
}

?>