<?php

include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/order/COrderProject.php');
include_once('../inc/model/order/COrderManagementGroup.php');

Class  COrderProjectSearchTmp extends CGalaxyClass{

	static public $aConditionTW = array(
						'5'=>'用印申請',
						'0'=>'準備中',
						'1'=>'進行中',
						'2'=>'結案',
						'6'=>'失敗',
						'3'=>'暫停',
						'4'=>'取消'
					);
	//data from galaxy_order2.order
	public $sOrderUuid;			//訂單唯一碼	
	public $iManagementId;		//業務體系流水號
	public $iType;				//訂單類型
	public $iSalespersonNo;		//業務人員流水號
	public $iPhaseNo;			//訂單狀態
	public $iCanceled;			//訂單取消狀態
	public $sCanceledDate;		//訂單取消日期

	//data from galaxy_order2.order_project
	public $iBrandId;			//品牌流水號

	//data from beauty2
	public $sProjectUuid;			//專案唯一碼
	public $sId;				//專案編號
	public $sName;			//專案名稱
	public $sStartDate;			//專案開始日期
	public $sEndDate;			//專案結束日期
	public $aManageUser;		//專案PM
	public $iPRTotal;			//報價PR總篇數
	public $iExtraTotal;			//委託篇數
	public $iPromiseResRate;		//保證回應率
	public $iConditionNo;			//專案狀態
	public $bTimeConfirm;			//

	public $iSearchUserNo;		//搜尋使用者流水號
	public $sExpiryTime;			//搜尋有效期限
	public $sOrderStatusDate;			//狀態日期

	private $__oManagement;
	private $__oSalesperson;
	private $__oCompanyBrand;


	static protected $sDBName = 'ORDER';

	public function __construct($multiData){
		if(!is_array($multiData))
			throw new Exception("COrderProjectSearchTmp:__construct failed, require an array");
		
		if(!isset($multiData['user_no'])){
			$oCUser = self::$session->get('oCurrentUser');
			$multiData['user_no'] = $oCUser->iUserNo;
		}else{
			$this->iUserNo=$multiData['user_no'];
		}

		$this->sOrderUuid = isset($multiData['order_no'])?$multiData['order_no']:'';
		$this->iManagementId = isset($multiData['management_id'])?$multiData['management_id']:0;
		$this->iType = isset($multiData['type'])?$multiData['type']:0;
		$this->iSalespersonNo = isset($multiData['salesperson_no'])?$multiData['salesperson_no']:0;
		$this->iPhaseNo = isset($multiData['phase_no'])?$multiData['phase_no']:0;
		$this->iCanceled = isset($multiData['canceled'])?$multiData['canceled']:0;
		$this->sCanceledDate = isset($multiData['canceled_date'])?$multiData['canceled_date']:'';
		$this->iBrandId = isset($multiData['cb_id'])?$multiData['cb_id']:0;
		$this->sProjectUuid = isset($multiData['project_no'])?$multiData['project_no']:0;
		$this->sId = isset($multiData['project_id'])?$multiData['project_id']:'';
		$this->sName = isset($multiData['project_name'])?$multiData['project_name']:'';
		$this->sStartDate = isset($multiData['start_date'])?$multiData['start_date']:'';
		$this->sEndDate = isset($multiData['end_date'])?$multiData['end_date']:'';
		$manage_user_name = isset($multiData['manage_user_name'])?$multiData['manage_user_name']:'';
		$this->aManageUser = json_decode($manage_user_name);
		$this->iPRTotal = isset($multiData['pr_total'])?$multiData['pr_total']:0;
		$this->iExtraTotal = isset($multiData['extra_total'])?$multiData['extra_total']:0;
		$this->iPromiseResRate = isset($multiData['promise_res_rate'])?$multiData['promise_res_rate']:0;
		$this->iConditionNo = isset($multiData['condition_no'])?$multiData['condition_no']:0;
		$this->bTimeConfirm = isset($multiData['time_confirm'])?$multiData['time_confirm']:0;
		$this->iSearchUserNo = isset($multiData['user_no'])?$multiData['user_no']:0;
		$this->sExpiryTime = isset($multiData['expiry_time'])?$multiData['expiry_time']:'';
		$this->sOrderStatusDate = isset($multiData['order_status_date'])?$multiData['order_status_date']:'';
		$this->bStatus = isset($multiData['status'])?$multiData['status']:0;
	}

	static public function vMakeSearchTmp($aPost){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');

		try{
			//vMakeSearchSession 將搜尋條件寫入session
			if($aPost) self::vMakeSearchSession($aPost);

			//vEmptySearchTmp 清空使用者的搜尋暫存 (order_project_search_tmp)
			self::vEmptySearchTmp();
			
			//搜尋子系統專案資料
			$aProjects = self::aGetSearchProjectResult();
			// $aProject=json_decode(json_encode($aResult['project']), false);

			//子系統回傳的專案筆數			
			if($aProjects) {
				$aTemp=array();
				foreach($aProjects as $oProject){
					//將回傳的專案資料結合訂單系統資料彙整成暫存資料(COrderProjectSearchTmp)
					$aTemp[]=self::oSearchTmp($oProject);
				}
				
				//以使用者所屬體系篩選暫存資料
				$aTemp=self::aFilterByManagement($aTemp);
				
				/*
				//以品牌篩選暫存資料
				if($session->get('s_term')=='cb_id'){
					$iBrandId=$session->get('s_key');
					$aTemp=self::aFilterByBrand($aTemp, $iBrandId);	
				}
				*/

				//寫入暫存資料
				if($aTemp){
					foreach($aTemp as $oTemp)	{
						$oTemp->vInsert();
					}
				}
			}
		}catch(Exception $e){
			throw new Exception("COrderProjectSearchTmp->vMakeSearchTmp: ".$e->getMessage());
		}
	}

	static public function vMakeSearchSession($aPost){
		$session = self::$session;

		$project_id = isset($aPost['project_id'])?trim($aPost['project_id']):'';
		$project_name = isset($aPost['project_name'])?trim($aPost['project_name']):'';
		$project_condition = isset($aPost['project_condition'])?$aPost['project_condition']:'';

		$session->set("project_id", $project_id);
		$session->set("project_name", $project_name);
		$session->set("project_condition", $project_condition);
	}

	static public function vEmptySearchTmp(){
		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');
		try{
			//清空使用者上一次搜尋的暫存資料
			$sSql="DELETE FROM `order_project_search_tmp` WHERE `user_no` ={$oCUser->iUserNo}";
			$oDB->iQuery($sSql);
		}catch(Exception $e){
			throw new Exception("COrderProjectSearchTmp->vEmptySearchTmp: ".$e->getMessage());
		}	
	}

	static public function aGetSearchProjectResult(){
		$session = self::$session;

		$sProjectId 	= $session->get('project_id');
		$sProjectName 	= $session->get('project_name');
		$iConditionNo 	= $session->get('condition_no');
		
		$aOptions['project_id']   = $session->get('project_id');
		$aOptions['project_name'] = $session->get('project_name');
		
		$iConditionNo = $session->get('project_condition');
		$aOptions['project_condition'] 	= (int)$iConditionNo;

		//將搜尋條件傳給子系統搜尋專案
		$aResult = CMisc::aCurl("http://".BEAUTY2_SERVER."/api/api.CBeautyProject.php?action=list",$aOptions);
		return $aResult['project'];
	}

	static public function aFilterByManagement($aTemp){
		$aResult=array();
		$oCUser = self::$session->get('oCurrentUser');
		
		//取得使用者所屬體系的體系流水號
		$aManagement=COrderManagementGroup::aAllManagementByUser($oCUser->iUserNo);
		$aManagementNo=array();
		if(!empty($aManagement)){
			foreach($aManagement as $oManagement){
				$aManagementNo[]=$oManagement->iManagementNo;
			}
		}

		//依體系過濾暫存資料
		if(!empty($aManagementNo)){
			foreach($aTemp as $oTemp){
				//若該筆資料體系符合使用者所屬的體系則加入回傳的陣列($aResult)
				if(in_array($oTemp->iManagementId, $aManagementNo))
					$aResult[]=$oTemp;
			}
		}

		return $aResult;
	}

	static public function aFilterByBrand($aTemp, $iBrandId){
		$aResult=array();
		if(!empty($aTemp)){
			foreach($aTemp as $oTemp){
				if($oTemp->iBrandId==$iBrandId){
					$aResult[]=$oTemp;
				}
			}
		}
		return $aResult;
	}

	static public function aGetSearchTmpResult($sSearchSql='', $sPostFix=''){
		$oDB=self::oDB(self::$sDBName);

		$aAllSearchTmp = array();
		$sSql=" SELECT * FROM `order_project_search_tmp` ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql.=" $sPostFix ";

		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllSearchTmp[]=new COrderProjectSearchTmp($aRow);
		}

		return $aAllSearchTmp;
	}

	static public function iCountResult($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);

		$sSql=" SELECT count(*) as total FROM `order_project_search_tmp` ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql";

		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$iCount = $aRow['total'];
		}

		return $iCount;
	}

	static public function oSearchTmp($oProject){
		$oCUser = self::$session->get('oCurrentUser');

		//取得訂單系統內的專案資料
		$oOrderProject=COrderProject::oGetOrderProject($oProject['sUuid']);
		//若訂單系統內有該筆專案資料, 取得該訂單資料
		if(isset($oOrderProject)){
			$oOrder=COrder::oGetOrder($oOrderProject->sOrderUuid);	
		}
		
		//計算報價PR總篇數: aBasicPostTypeCount (報價篇數1) + aBonusPostTypeCount (報價篇數2 (PR))
		$iPRTotal=$oProject['iBasicPostTypeCount']+$oProject['iBonusPostTypeCount'];

		//彙整
		$oSearchTmp = new COrderProjectSearchTmp(array());

		$aOrderData=array();
		$iPhaseNo = 0;
		if(isset($oOrder)){
			$iPhaseNo = $oOrder->iPhaseNo;
			$oSearchTmp->sOrderUuid 	= $oOrder->sOrderUuid;
			$oSearchTmp->iManagementId = $oOrder->iManagementId;
			$oSearchTmp->iType 		= $oOrder->iType;
			$oSearchTmp->iSalespersonNo 	= $oOrder->iSalespersonNo;
			$oSearchTmp->iPhaseNo 	= $oOrder->iPhaseNo;
			$oSearchTmp->iCanceled 	= $oOrder->iCanceled;
			$oSearchTmp->sCanceledDate 	= $oOrder->sCanceledDate;
		}

		if(isset($oOrderProject)){
			$oSearchTmp->iBrandId 	= $oOrderProject->iBrandId;
		}

		$oSearchTmp->sProjectUuid 	= $oProject['sUuid'];
		$oSearchTmp->sId 		= $oProject['sId'];
		$oSearchTmp->sName 		= $oProject['sName'];
		$oSearchTmp->sStartDate 	= $oProject['sStartDate'];
		$oSearchTmp->sEndDate 	= $oProject['sEndDate'];
		$oSearchTmp->aManageUser 	= $oProject['aUserName'];
		$oSearchTmp->iPRTotal 		= $iPRTotal;
		$oSearchTmp->iExtraTotal 	= isset($oProject['iExtraPostTypeCount'])?$oProject['iExtraPostTypeCount']:0;
		$oSearchTmp->iPromiseResRate = $oProject['iPromiseResRate'];
		$oSearchTmp->iConditionNo 	= $oProject['iConditionNo'];
		$oSearchTmp->bTimeConfirm 	= $oProject['bTimeConfirm'];
		$oSearchTmp->sCreateTime 	= $oProject['sCreateTime'];
		$oSearchTmp->sModifiedTime 	= $oProject['sModifiedTime'];
		$oSearchTmp->bStatus 		= $oProject['bStatus'];
		$oSearchTmp->iSearchUserNo 	= $oCUser->iUserNo;

		$aOrderPhaseField = COrder::$aOrderPhaseField;
		
		if (isset($oOrder)){
			$oSearchTmp->sOrderStatusDate = isset($oOrder->$aOrderPhaseField[$iPhaseNo])?$oOrder->$aOrderPhaseField[$iPhaseNo]:'';
		}else{
			$oSearchTmp->sOrderStatusDate = '';
		}

		return $oSearchTmp;
	}

	public function vInsert(){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');
		try{	
			$oDB->vBegin();
			$aValues=array(
				'order_no'          => $this->sOrderUuid,
				'management_id'     => $this->iManagementId,
				'type'              => $this->iType,
				'salesperson_no'    => $this->iSalespersonNo,
				'phase_no'          => $this->iPhaseNo,
				'canceled'          => $this->iCanceled,
				'canceled_date'     => $this->sCanceledDate,
				'cb_id'             => $this->iBrandId,
				'project_no'        => $this->sProjectUuid,
				'project_id'        => $this->sId,
				'project_name'      => CMisc::my_quotes($this->sName),
				'start_date'        => $this->sStartDate,
				'end_date'          => $this->sEndDate,
				'manage_user_name'  => json_encode($this->aManageUser,JSON_UNESCAPED_UNICODE),
				'pr_total'          => $this->iPRTotal,
				'extra_total'       => $this->iExtraTotal,
				'promise_res_rate'  => $this->iPromiseResRate,
				'condition_no'      => $this->iConditionNo,
				'time_confirm'      => $this->bTimeConfirm,
				'user_no'           => $this->iSearchUserNo,
				'status'            => $this->bStatus,
				'order_status_date' => $this->sOrderStatusDate,
				'expiry_time'       => '1970-01-01 00:00:00',
				'extra_total'		=> 0,
			);

			$oDB->sInsert("order_project_search_tmp",array_keys($aValues),array_values($aValues));			
			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("COrderProjectSearchTmp->vInsert :".$e->getMessage());
		}
	}


	public function oManagement() {
		if(empty($this->__oManagement)){
			$this->__oManagement=CManagement::oGetManagement($this->iManagementId);
		}
		return $this->__oManagement;
	}

	public function oSalesperson(){
		if(empty($this->__oSalesperson)){
			$this->__oSalesperson=CUser::oGetUser($this->iSalespersonNo);
		}
		return $this->__oSalesperson;
	}

	public function oCompanyBrand(){
		if(empty($this->__oCompanyBrand)){
			$this->__oCompanyBrand=CCompanyBrand::oGetCompanyBrand($this->iBrandId);
		}
		return $this->__oCompanyBrand;
	}

	public function sCondition(){
    		return self::$aConditionTW[$this->iConditionNo];
	}

	public function aManageUser(){
		if(empty($this->__aManageUser)){
			$this->__oCompanyBrand=CCompanyBrand::oGetCompanyBrand($this->iBrandId);
		}
		return $this->__oCompanyBrand;
	}
    
    /**
     *  @desc 取得已執行篇數
     *  @created 2016/03/22
     */
    public function iGetRunArticleCount(){
        $sApiUrl = "http://".BEAUTY2_SERVER."/api/api.get_execute_article.php?sProjectUuid=".$this->sProjectUuid;
        $sUrl = $sApiUrl."&PHPSESSID=".session_id();
        $aOptions=array();
        $aResult=CMisc::aCurl($sUrl,$aOptions);
        
        if(!empty($aResult)){
            return $aResult;
        }
        return 0;
    }
    
    
}
?>