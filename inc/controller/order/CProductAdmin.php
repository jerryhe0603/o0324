<?php

/**
 *  @desc 產品管理
 *  @created 2015/10/23
 */

include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/model/order/CProduct.php');

class CProductAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;
	const BACK_TO_ADMIN=4;

	private static $sDBName = 'ORDER';

	static public $aSearchOption = array(
		"product_name" => '產品名稱'
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
		try{
			switch($_GET['action']){
				case "add":
					return $this->tProductAdd();
					break;
				case "edit":
					return $this->tProductEdit();
					break;
				case "active":
					return $this->vProductActive();
					break;
				case "del":
					return $this->vProductDel();
					break;
				default:
				case "list":
					return $this->tProductList();
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
	

	/**
	 *  @desc 列表
	 *  @created 2015/10/23
	 */
	private function tProductList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);

		$js_valid = isset($_GET['js_valid'])?$_GET['js_valid']:0;
		
		if(!empty($_POST)) {
			if($js_valid==1) {
				$this->vaildSearch($_POST,1);	//client javascript vaild data
			}else{
				$this->vaildSearch($_POST,0);	//form submit vaild data
			}
		}
		
		$iPg 	    = isset($_GET['page'])?$_GET['page']:0; // 分頁
		$sOrder     = isset($_GET['order'])?$_GET['order']:'modified'; // 排序欄位
		$sSort 	    = isset($_GET['sort'])?$_GET['sort']:'DESC'; // 排序方向
		$goid		= isset($_GET['goid'])?$_GET['goid']:0; // 跳到某一筆
		$iPageItems = isset($_GET['items'])?$_GET['items']:PAGING_NUM; // 一頁幾筆
		$func 	    = isset($_GET['func'])?$_GET['func']:''; // func
		$action		= isset($_GET['action'])?$_GET['action']:'list'; // action
		$js_valid 	= isset($_GET['js_valid'])?$_GET['js_valid']:0;
		$tab 		= isset($_GET['tab'])?$_GET['tab']:'';
		
		if(isset($_GET['search']) AND $_GET['search'] === '1'){
			$sSearchSql = $this->sGetSearchSql($_POST);
			$sSearch = '&search=1';
		}else{
			$sSearchSql ='';
			$sSearch = '';
		}
		
		if(strlen($sSearchSql)!==0)
	        $sSearchSql .= " AND flag!=9";
		else
			$sSearchSql=" flag!=9";
		
		// 得到某筆資料是在第幾頁
		if($goid){
			$oDB = self::oDB(self::$sDBName);
			$iPg = $oDB->iGetItemAtPage("product","product_no",$goid,$iPageItems,$sSearchSql,"ORDER BY $sOrder $sSort");
		}
		
		//共幾筆
		$iAllItems = CProduct::iGetCount($sSearchSql);
		$iStart=$iPg*$iPageItems;

		//get objects
		$aAllData = array();
		if($iAllItems!==0){
			$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
						//get which projects are available for current user(not admin user)
			$aAllData = CProduct::aAllProduct($sSearchSql, $sPostFix);

			if(count($aAllData)===0)
				CJavaScript::vAlertRedirect("",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=admin&items=".$PageItem);	
		}
        $Smarty->assign("aAllData",$aAllData);

		//assign frame attribute
		$Smarty->assign("NowOrder",$sOrder);		
		$Smarty->assign("NowSort",$sSort);
		
		$Smarty->assign("OrderUrl",$_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&items=$iPageItems");
		$Smarty->assign("OrderSort",(strtoupper($sSort)=="DESC")?"ASC":"DESC");

		$Smarty->assign('searchKey',	$session->get("s_key".$func.$action) );
		$Smarty->assign('searchTerm',	$session->get("s_terms") );
		$Smarty->assign('searchOption',	self::$aSearchOption);
                
		$Smarty->assign("Total",$iAllItems);
		$Smarty->assign("PageItem",$iPageItems);
		
		$Smarty->assign("StartRow",$iStart+1);
		$Smarty->assign("EndRow",$iStart+$iPageItems);

		$Smarty->assign("iPg",$iPg);
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."$sSearch&order=$sOrder&sort=$sSort"));

		// list
		return $Smarty->fetch('./admin/'.get_class($this).'/product_list.html');
	}
	

	
	
	/**
	 *  @desc 新增
	 *  @created 2015/10/23
	 */
	private function tProductAdd(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		
		if(empty($_POST)){
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/product_add.html');
		}else{
			//client javascript vaild data
			$js_valid	= isset($_GET['js_valid'])?$_GET['js_valid']:''; 
			if($js_valid==1) {
				$this->vaildProduct($_POST,1);
			} else {
				//form submit vaild data
				$this->vaildProduct($_POST,0);
			}
			try{
				$oCProduct = new CProduct($_POST);	//if $_POST has all we need
				$oCProduct->user_no = $session->get("user_no");
				$oCProduct->edit_user_no = $session->get("user_no");
				$product_no = $oCProduct->vAddProduct();
			}catch (Exception $e){
				throw new Exception('CProductAdmin->tProductAdd: '.$e->getMessage(),self::BACK_TO_ADMIN);
			}
		}
		CJavaScript::vAlertRedirect(_LANG_PRODUCT_ADD_SUCCESS, $_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=show&goid=$product_no");
		exit;
	}
	

	/**
	 *  @desc 編修
	 *  @created 2015/10/15
	 */
	private function tProductEdit(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		
		if(empty($_GET['product_no']))
			throw new Exception('',$bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);
		
		$product_no = $_GET['product_no'];
		
		$oCProduct = CProduct::oGetProduct($product_no);
		
		if(is_null($oCProduct))
			throw new Exception('product not found',self::BACK_TO_LIST);
		
		if(empty($_POST)){
			$Smarty->assign('oCProduct',$oCProduct);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/product_edit.html');
		}else{
			//client javascript vaild data
			$js_valid	= isset($_GET['js_valid'])?$_GET['js_valid']:''; 
			if($js_valid==1) {
				$this->vaildProduct($_POST,1);
			} else {
				//form submit vaild data
				$this->vaildProduct($_POST,0);
			}
			try{
				$oCProductUpdate = new CProduct($_POST);	//create project object from $_POST
				$oCProduct->vOverWrite($oCProductUpdate);	//overwrite
				$oCProduct->flag = $oCProductUpdate->flag;
				$oCProduct->edit_user_no = $session->get("user_no");
				$oCProduct->vUpdateProduct();
			}catch (Exception $e){
				throw new Exception('CProductAdmin->tProductEdit: '.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect(_LANG_PRODUCT_EDIT_SUCCESS,$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&product_no={$oCProduct->product_no}");
	}
	
	
	/**
	 *  @desc 狀態: 啟用/停用
	 *  @created 2015/10/23
	 */
	private function vProductActive(){
		if(empty($_GET['product_no']))
			throw new Exception('',$bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);

		$product_no = $_GET['product_no'];
		$oCProduct = CProduct::oGetProduct($product_no);

		if(is_null($oCProduct))
			throw new Exception('product not found',($bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST));
		try{
			$oCProduct->vActivate();
		}catch (Exception $e){
			throw new Exception($e->getMessage(),($bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST));
		}
		CJavaScript::vAlertRedirect(_LANG_PRODUCT_ACTIVE_SUCCESS,$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=".($bAdmin?"admin":"list")."&goid={$oCProduct->product_no}");
	}
			
	
	/**
	 *  @desc 刪除
	 *  @created 2015/10/23
	 */
	private function vProductDel(){
		if(empty($_GET['product_no']))
			throw new Exception('',$bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST);

		$product_no = $_GET['product_no'];
		$oCProduct = CProduct::oGetProduct($product_no);

		if(is_null($oCProduct))
			throw new Exception('product not found',($bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST));
		try{
			$oCProduct->vDelete();
		}catch (Exception $e){
			throw new Exception($e->getMessage(),($bAdmin?self::BACK_TO_ADMIN:self::BACK_TO_LIST));
		}
		CJavaScript::vAlertRedirect(_LANG_PRODUCT_DELETE_SUCCESS,$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCStage->product_no}");
	}
		
	
	/*
		check if the search string is vaild
	*/
	private function vaildSearch($postData=array(),$return_type=0){
		$aErrorMsg = array();
		
		if(strlen(trim($postData['s_key'])) == 0){
			$aErrorMsg[]=_LANG_PRODUCT_VAILD_SEARCH_KEY;
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
		change search project name into sql string
	*/ 
	private function sGetSearchSql($aPost){
		$session = self::$session;
		
		$func	= isset($_GET['func'])?$_GET['func']:''; // func
		$action = isset($_GET['action'])?$_GET['action']:''; // action

		if(count($aPost)){
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
		}else{
			$sKey = $session->get("s_key".$func.$action);
			$sTerms =  $session->get("s_terms".$func.$action);
		}	
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_key".$func.$action,"");
			$session->set("s_terms".$func.$action,"");
			return $sSql;
		}
		$session->set("s_key".$func.$action,$sKey);
		$session->set("s_terms".$func.$action,$sTerms);
		
		switch($sTerms){
			default :
				$sSql = $sSql." (`$sTerms` LIKE '%$sKey%')";
				break;
		}
		return $sSql;
	}
	

	/**
	* @param $postData POST DATA 
	* @param $return_type = 0 一般字串 =1 client javascript 
	* @param $check_id int 資料庫內序號，檢查判斷資料是否重覆
	* @return error msg
	* @desc user post data vaild
	* @created 2013/11/25
	*/
	protected function vaildProduct($postData=array(),$return_type=0,$check_id=0) {
		$aErrorMsg = array();
		
		if(strlen(trim($postData['product_name'])) == 0){ // 產品名稱
			$aErrorMsg[] = _LANG_PRODUCT_VAILD_PRODUCT_NAME;
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
			$sErrorMsg = implode("\\n",$aErrorMsg);
			CJavaScript::vAlert($sErrorMsg);
			exit;
		}	
		return 	$aErrorMsg;
	}
	
	
	
}
/* End of File */