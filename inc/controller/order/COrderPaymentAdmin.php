<?php
include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderPayment.php');
include_once('../inc/CMisc.php');

class COrderPaymentAdmin extends CGalaxyController
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
					return $this->tOrderPaymentAdd();
					break;
				case "edit":
					return $this->tOrderPaymentEdit();
					break;
				case "view":
					return $this->tOrderPaymentView();
					break;
				default:
				case "list":
					return $this->tOrderPaymentList();
					break;
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=list';
					if(!empty($_GET['payment_id']))
						$sUrl .= '&goid='.$_GET['payment_id'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_VIEW:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=view&order_no='.$_GET['order_no'].'&payment_id='.$_GET['payment_id']);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&order_no='.$_GET['order_no'].'&payment_id='.$_GET['payment_id']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tOrderPaymentAdd(){
		$Smarty = self::$Smarty;
		$sOrderUuid=$_GET['order_no'];
		$oCOrder=COrder::oGetOrder($sOrderUuid);
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
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_payment_edit.html');
		}else{
			$oCOrderPayment=new COrderPayment($_POST);
			try{
				$oCOrderPayment->iAdd();
			}catch (Exception $e){
				throw new Exception('COrderPaymentAdmin->tOrderPaymentAdd: '.$e->getMessage(), self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('payment add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&order_no={$oCOrder->sOrderUuid}&payment_id={$oCOrderPayment->iPaymentId}$sAdmin");
	}

	private function tOrderPaymentEdit(){
		$Smarty = self::$Smarty;
		$sOrderUuid=$_GET['order_no'];
		$oCOrder=COrder::oGetOrder($sOrderUuid);
		$Smarty->assign('oOrder',$oCOrder);

		$iOrderPaymentId = $_GET['payment_id'];
		$oCOrderPayment = COrderPayment::oGetOrderPayment($iOrderPaymentId);
		if(is_null($oCOrderPayment))
			throw new Exception('payment not found',self::BACK_TO_LIST);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}

		$Smarty->assign('sAdmin', $sAdmin);

		if(empty($_POST)){
			$Smarty->assign('oOrderPayment',$oCOrderPayment);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_payment_edit.html');
		}else{
			$oCOrderPaymentUpdate = new COrderPayment($_POST);	//create project object from $_POST
			$oCOrderPaymentUpdate->bStatus=isset($_POST['status'])?1:0;
			
			$oCOrderPayment->vOverWrite($oCOrderPaymentUpdate);	//overwrite
			try{
				$oCOrderPayment->vUpdate();

			}catch (Exception $e){
				throw new Exception('COrderPaymentAdmin->tOrderPaymentEdit: '.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('payment edit success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=view&order_no={$oCOrder->sOrderUuid}&payment_id={$oCOrderPayment->iPaymentId}$sAdmin");
	}

	/*
		only admin user can access 
	*/
	private function tOrderPaymentView(){
		$Smarty = self::$Smarty;
		if(empty($_GET['payment_id']))
			throw new Exception('',self::BACK_TO_LIST);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}

		$Smarty->assign('sAdmin', $sAdmin);
		
		$sOrderUuid = $_GET['order_no'];
		$oCOrder = COrder::oGetOrder($sOrderUuid);

		$iOrderPaymentId = $_GET['payment_id'];
		$oCOrderPayment = COrderPayment::oGetOrderPayment($iOrderPaymentId);
		
		$Smarty->assign('oOrder',$oCOrder);
		$Smarty->assign('oOrderPayment',$oCOrderPayment);
		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_payment_view.html');
	}

	/*
		admin user return admin page
		other user return list page
	*/
	private function tOrderPaymentList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);

		if(isset($_GET['admin'])){
			$sAdmin = '&admin=1';
			$bAdmin = true;
		}else{
			$sAdmin = '';
			$bAdmin = false;
		}

		$Smarty->assign('sAdmin', $sAdmin);

		$sOrderUuid=$_GET['order_no'];
		$oCOrder = COrder::oGetOrder($sOrderUuid);
		//$oCOrder->vGetCompany();
		$Smarty->assign('oOrder',$oCOrder);
		$Smarty->assign('aType', COrder::$aType);

		$sSearchSql="`order_no` = '$sOrderUuid'";

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

		if($_GET['search'] === '1'){
			$sSearchSql = ' AND '.$this->sGetSearchSql($_POST);
			$sSearch = '&search=1';
		}else{
			$sSearch = '';
		}
			
		//得到某筆資料是在第幾頁
		if($goid){
			$iPg = $oDB->iGetItemAtPage("order_payment","payment_id",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = COrderPayment::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aOrderPayments = COrderPayment::aAllOrderPayment($sSearchSql,$sPostFix);
			if(count($aOrderPayments)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&items=".$PageItem."&order_no=$sOrderUuid$sAdmin");	
		}
        		$Smarty->assign("aOrderPayments",$aOrderPayments);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&page=$iPg&order_no=$sOrderUuid$sAdmin");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_project_key") );
		$Smarty->assign('searchTerm',	$session->get("s_project_terms") );
		//$Smarty->assign('searchOption',	self::$aSearchOption);
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."$sSearch&order=$sOrder&sort=$sSort&order_no=$sOrderUuid$sAdmin"));

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/order_payment_list.html');
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