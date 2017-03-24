<?php
include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderProject.php');
include_once('../inc/CMisc.php');
include_once('../inc/model/CCompanyBrand.php');
include_once('../inc/controller/order/COrderProjectAdmin.php');
include_once('../inc/model/order/COrderManagementGroup.php');

class COrderProjectListAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'ORDER';
	/*
	static public $aApiUrl=array(
		3=>"http://goods.lab.net/api/api.GoodsOrder.php?&func=goods_project",
		//3=>"http://localhost/goods/api/api.GoodsOrder.php?&func=goods_project",
		4=>"http://beauty2.lab.net/api/api.BeautyOrder.php?func=beauty_project",
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
		try{
			switch($_GET['action']){
				/*
				case "beauty":
					return $this->tOrderProjectBeauty();
					break;
				case "goods":
					return $this->tOrderProjectGoods();
					break;
				*/
				case 'admin':
				case 'list':
					return $this->tOrderProjectList();
					break;
				case 'beauty_by_order':
				case 'goods_by_order':
					return $this->tOrderProjectListByOrder();
					break;
				default:
					return;
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
	/*
	private function tOrderProjectBeauty(){
		$Smarty = self::$Smarty;
		
		//add order id to search sql
		$sOrderUuid=$_GET['order_no'];
		if(!empty($sOrderUuid))
			$sSearchSql=" `order_no`='$sOrderUuid' ";
		$oCOrder=COrder::oGetOrder($sOrderUuid);
		$Smarty->assign('oOrder', $oCOrder);

		//add service id to search sql
		$iServiceId=4;
		$sSearchSql.=" AND `service_id`='$iServiceId' ";

		//set ApiUr;
		$sApiUrl=self::$aApiUrl[$iServiceId];
		$Smarty->assign('sApiUrl',$sApiUrl);
		
		//get all projects
		if($sSearchSql) $sSearchSql.=' AND ';
		$sSearchSql.=" `status`=1 ";
		$aAllProject=COrderProject::aAllOrderProject($sSearchSql);

		//set php session id
		$PHPSESSID=$_GET['PHPSESSID'];
		$Smarty->assign('PHPSESSID', $PHPSESSID);

		//attach beauty project data to the corresponding order projects
		if(!empty($aAllProject)){
			foreach($aAllProject as &$oOrderProject){
				$sUrl=$sApiUrl."&PHPSESSID=$PHPSESSID&action=order_fetch_project";
				$aOptions=array( 
						'project_no'=>$oOrderProject->sProjectUuid,
						);
				$curl = curl_init();
			    	curl_setopt($curl, CURLOPT_URL, $sUrl);
			    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			    	curl_setopt($curl, CURLOPT_POST, true);
			    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($aOptions));
				$jResult = curl_exec($curl);
				curl_close($curl);
				$aResult = json_decode($jResult,false);
				if(!empty($aResult)){
					$oOrderProject->bExist=1;
					$oOrderProject->sId=$aResult->sId;
					$oOrderProject->sProjectName=$aResult->sName;
					$oOrderProject->sStartDate=$aResult->sStartDate;
					$oOrderProject->sEndDate=$aResult->sEndDate;
					$oOrderProject->oUser=$aResult->oUser;
					$oOrderProject->iPromiseResRate=$aResult->iPromiseResRate;
					$oOrderProject->iPostCountBasicBonus=$aResult->iPostCountBasicBonus;
					$oOrderProject->iPostCountExtra=$aResult->iPostCountExtra;
					$oOrderProject->sCondition=$aResult->sCondition;
				}
			}
		}

		$Smarty->assign('aAllProject',$aAllProject);
		$output = $Smarty->fetch('./admin/'.get_class($this).'/beauty.html');
		echo json_encode($output);

	}

	private function tOrderProjectGoods(){
		$Smarty = self::$Smarty;
		
		//add order id to search sql
		$sOrderUuid=$_GET['order_no'];
		if(!empty($sOrderUuid))
			$sSearchSql=" `order_no`='$sOrderUuid' ";
		$oCOrder=COrder::oGetOrder($sOrderUuid);
		$Smarty->assign('oOrder', $oCOrder);

		//add service id to search sql
		$iServiceId=3;
		$sSearchSql.=" AND `service_id`='$iServiceId' ";

		//set ApiUr;
		$sApiUrl=self::$aApiUrl[$iServiceId];
		$Smarty->assign('sApiUrl',$sApiUrl);
		
		//get all projects
		if($sSearchSql) $sSearchSql.=' AND ';
		$sSearchSql.=" `status`=1 ";
		$aAllProject=COrderProject::aAllOrderProject($sSearchSql);

		//set php session id
		$PHPSESSID=$_GET['PHPSESSID'];
		$Smarty->assign('PHPSESSID', $PHPSESSID);

		//attach beauty project data to the corresponding order projects
		if(!empty($aAllProject)){
			foreach($aAllProject as &$oOrderProject){
				$sUrl=$sApiUrl."&PHPSESSID=$PHPSESSID&action=order_fetch_project";
				$aOptions=array( 
						'project_no'=>$oOrderProject->sProjectUuid,
						);
				$curl = curl_init();
			    	curl_setopt($curl, CURLOPT_URL, $sUrl);
			    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			    	curl_setopt($curl, CURLOPT_POST, true);
			    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($aOptions));
				$jResult = curl_exec($curl);
				curl_close($curl);
				$aResult = json_decode($jResult,false);
				if(!empty($aResult)){
					$oOrderProject->bExist=1;
					$oOrderProject->sProjectName=$aResult->sName;
					$oOrderProject->sStartDate=$aResult->sStartDate;
					$oOrderProject->sEndDate=$aResult->sEndDate;
					$oOrderProject->oUser=$aResult->oUser;
				}
			}
		}

		$Smarty->assign('aAllProject',$aAllProject);
		$output = $Smarty->fetch('./admin/'.get_class($this).'/goods.html');
		echo json_encode($output);

	}
	*/
	private function tOrderProjectList(){
		$Smarty = self::$Smarty;
		$session=self::$session;

		$iServiceId=$_GET['service_id'];
		switch($iServiceId){
			case 4:
				$sTemplate='beauty';
				break;
			case 3:
				$sTemplate='goods';
				break;
		}
		

		//get $_GET parameters
		if(empty($_GET['items'])) $iPageItems = 50; //PAGING_NUM; set to 50 on 20150312
		else $iPageItems = $_GET['items'];

		if(empty($_GET['sort']))$sSort = "DESC";
		else $sSort = $_GET['sort'];
		
		if(empty($_GET['page'])) $iPg = 0;
		else $iPg = $_GET['page'];
		
		if(empty($_GET['goid'])) $goid = 0;
		else $goid = $_GET['goid'];

		/*
		if(empty($_GET['order']))
			$sOrder = "create_time";
		else
			$sOrder = $_GET['order'];
		*/
		if(empty($_GET['order']))
			$sOrder = " op.`create_time` ";
		else{
			switch($_GET['order']){
				case 'management_id':
				case 'type':
				//case 'group_no':
				case 'salesperson_no':
				case 'phase_no':
				case 'canceled':
					$sOrder=" o.`".$_GET['order']."` ";
					break;
				default:
					$sOrder=" op.`".$_GET['order']."` ";
					break;
			}	
		}
		

		//check authority
		if($_GET['action']=='admin'){
			$sAdmin='&admin=1';
			$Smarty->assign('sAdmin', $sAdmin);
		}else{
			$iUserNo=self::$session->get('oCurrentUser')->iUserNo;
			/*
			$aAllGroup=COrderGroup::aAllGroupByUser($iUserNo);
			$aGroupNo=array();
			if(!empty($aAllGroup)){
				foreach($aAllGroup as $oGroup){
					$aGroupNo[]=$oGroup->iGroupNo;
				}
			}
			
			$sGroupNo=implode(',', $aGroupNo);

			if(!empty($sSearchSql))
				$sSearchSql.=" AND ";
				*/
			$aManagementNo=array();
			$aManagement=COrderManagementGroup::aAllManagementByUser($iUserNo);
			foreach($aManagement as $aManagementData){
				$aManagementNo[]=$aManagementData->iManagementNo;
			}
			$sManagementNo=implode(',', $aManagementNo);
			$sSearchSql.=" o.`management_id` in ( $sManagementNo )";
		}

		//add service id to search sql
		if(!empty($sSearchSql)) $sSearchSql.=' AND ';
		$sSearchSql.=" op.`service_id`='$iServiceId' ";

		if($sSearchSql) $sSearchSql.=' AND ';
		$sSearchSql.=" op.`status`=1 ";

		//set ApiUrl
		$aApiUrl=array(
			4=>"http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?func=beauty_project"
		);
		$sApiUrl=$aApiUrl[$iServiceId];
		$Smarty->assign('sApiUrl',$sApiUrl);
		
		
		//get all projects
		$iStart=$iPg*$iPageItems;
		$aAllProject=array();
		$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
		$aAllProject=COrderProject::aAllProjectJoinOrder($sSearchSql, $sPostFix);
		if(count($aAllProject)===0){
			//CJavaScript::vAlertRedirect("", $_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&items=".$PageItem);
			return;
		}

		//共幾筆
		$iAllItems = COrderProject::iGetCountProjectJoinOrder($sSearchSql);

		//attach beauty project data to the corresponding order projects
		if(!empty($aAllProject)){
			foreach($aAllProject as &$oOrderProject){
				$sUrl=$sApiUrl."&action=order_fetch_project";
				$aOptions=array( 
						'project_no'=>$oOrderProject->sProjectUuid,
						);
				$curl = curl_init();
			    	curl_setopt($curl, CURLOPT_URL, $sUrl);
			    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			    	curl_setopt($curl, CURLOPT_POST, true);
			    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($aOptions));
				$jResult = curl_exec($curl);
				curl_close($curl);
				$aResult = json_decode($jResult,false);
				if(!empty($aResult)){
					switch($iServiceId){
						case 3: // goods
							$oOrderProject->bExist=1;
							$oOrderProject->sProjectName=$aResult->sName;
							$oOrderProject->sStartDate=$aResult->sStartDate;
							$oOrderProject->sEndDate=$aResult->sEndDate;
							$oOrderProject->oUser=$aResult->oUser;
							break;
						case 4: // beauty
							$oOrderProject->bExist=1;
							$oOrderProject->sId=$aResult->sId;
							$oOrderProject->sProjectName=$aResult->sName;
							$oOrderProject->sStartDate=$aResult->sStartDate;
							$oOrderProject->sEndDate=$aResult->sEndDate;
							$oOrderProject->oUser=$aResult->oUser;
							$oOrderProject->iPromiseResRate=$aResult->iPromiseResRate;
							$oOrderProject->iPostCountBasicBonus=$aResult->iPostCountBasicBonus;
							$oOrderProject->iPostCountExtra=$aResult->iPostCountExtra;
							$oOrderProject->sCondition=$aResult->sCondition;
							$oOrderProject->aUser=$aResult->aUser;
							break;
					}
				}
			}
		}

		$Smarty->assign('aAllProject',$aAllProject);

		$Smarty->assign('aType', COrder::$aType);
		$Smarty->assign('aService', COrderProject::$aService);
		$Smarty->assign('aOrderPhase', COrder::$aOrderPhase);
		$Smarty->assign('aCancelScope', COrder::$aCancelScope);

		//子系統專案進行狀態
		$aConditionTW = CMisc::aCurl($sApiUrl."&action=condition_no",array());
		$Smarty->assign('aConditionTW', $aConditionTW);

		//可搜尋欄位陣列
		$Smarty->assign('aSearchOption',COrderProjectAdmin::$aSearchOption[$iServiceId]);

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
		$Smarty->assign('PageBar',	CMisc::sMakePageBar($iAllItems, $iPageItems, $iPg, "func=".$_GET['func']."&action=".$_GET['action']."$sSearch&order=$sOrder&sort=$sSort"));

		$output = $Smarty->fetch("./admin/".get_class($this)."/$sTemplate.html");
		echo json_encode($output);
	}

	private function tOrderProjectListByOrder(){
		$Smarty = self::$Smarty;
		$session=self::$session;

		switch($_GET['action']){
			case 'beauty_by_order':
				$iServiceId=4;
				$sTemplate='beauty_by_order';
				break;
			case 'goods_by_order':
				$iServiceId=3;
				$sTemplate='goods_by_order';
				break;
		}

		//add order id to search sql
		$sOrderUuid=$_GET['order_no'];
		$sSearchSql=" `order_no`='$sOrderUuid' ";
		$oCOrder=COrder::oGetOrder($sOrderUuid);
		$Smarty->assign('oOrder', $oCOrder);
		/*
		//check authority
		if($_GET['admin']=='1'){
			$sAdmin='&admin=1';
			$Smarty->assign('sAdmin', $sAdmin);
		}else{
			$iUserNo=$session->get('oCurrentUser')->iUserNo;
			$aAvailProject=COrderProject::aAllOrderProject(" `user_no`= $iUserNo ");

			foreach($aAvailProject as $oAvailProject){
				$aAvailProjectId[]=" '{$oAvailProject->sProjectUuid}' ";
			}

			$sAvailProjectId=implode(',', $aAvailProjectId);

			if(!empty($sSearchSql))
				$sSearchSql.=" AND ";
			$sSearchSql.=" `project_no` in ($sAvailProjectId) ";
		}
		*/
		//add service id to search sql
		if(!empty($sSearchSql)) $sSearchSql.=' AND ';
		$sSearchSql.=" `service_id`='$iServiceId' ";

		if($sSearchSql) $sSearchSql.=' AND ';
		$sSearchSql.=" `status`=1 ";

		//set ApiUrl
		$aApiUrl=array(
			4=>"http://".BEAUTY2_SERVER."/api/api.BeautyOrder.php?func=beauty_project"
		);
		$sApiUrl=$aApiUrl[$iServiceId];
		$Smarty->assign('sApiUrl',$sApiUrl);


		//共幾筆
		$iAllItems = COrderProject::iGetCount($sSearchSql);
		
		//get all projects
		$aAllProject=array();
		if($iAllItems!==0){
			//$sPostFix = "ORDER BY $sOrder $sSort LIMIT $iStart,$iPageItems";	//sql postfix
			$aAllProject=COrderProject::aAllOrderProject($sSearchSql, $sPostFix);
			if(count($aAllProject)===0)
				//CJavaScript::vAlertRedirect("", $_SERVER['PHP_SELF']."?func=".$_GET['func']."&action=".$_GET['action']."&items=".$PageItem);
				return;
		}

		//attach beauty project data to the corresponding order projects
		if(!empty($aAllProject)){
			foreach($aAllProject as &$oOrderProject){
				$sUrl=$sApiUrl."&action=order_fetch_project";
				$aOptions=array( 
						'project_no'=>$oOrderProject->sProjectUuid,
						);
				$curl = curl_init();
			    	curl_setopt($curl, CURLOPT_URL, $sUrl);
			    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			    	curl_setopt($curl, CURLOPT_POST, true);
			    	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($aOptions));
				$jResult = curl_exec($curl);
				curl_close($curl);
				$aResult = json_decode($jResult,false);
				if(!empty($aResult)){
					switch($iServiceId){
						case 3: // goods
							$oOrderProject->bExist=1;
							$oOrderProject->sProjectName=$aResult->sName;
							$oOrderProject->sStartDate=$aResult->sStartDate;
							$oOrderProject->sEndDate=$aResult->sEndDate;
							$oOrderProject->oUser=$aResult->oUser;
							break;
						case 4: // beauty
							$oOrderProject->bExist=1;
							$oOrderProject->sId=$aResult->sId;
							$oOrderProject->sProjectName=$aResult->sName;
							$oOrderProject->sStartDate=$aResult->sStartDate;
							$oOrderProject->sEndDate=$aResult->sEndDate;
							$oOrderProject->oUser=$aResult->oUser;
							$oOrderProject->iPromiseResRate=$aResult->iPromiseResRate;
							$oOrderProject->iPostCountBasicBonus=$aResult->iPostCountBasicBonus;
							$oOrderProject->iPostCountExtra=$aResult->iPostCountExtra;
							$oOrderProject->sCondition=$aResult->sCondition;
							$oOrderProject->aUser=$aResult->aUser;
							break;
					}
				
				}
			}
		}

		$Smarty->assign('aAllProject',$aAllProject);

		$output = $Smarty->fetch("./admin/".get_class($this)."/$sTemplate.html");
		echo json_encode($output);
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