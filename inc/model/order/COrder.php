<?php

$sNowPath = realpath(dirname(dirname(dirname(dirname( __FILE__ )))));
include_once($sNowPath.'/inc/model/CGalaxyClass.php');
include_once($sNowPath.'/inc/model/order/COrderProject.php');
include_once($sNowPath.'/inc/model/order/COrderPayment.php');
include_once($sNowPath.'/inc/model/order/CProduct.php');
//include_once($sNowPath.'/inc/model/order/COrderGroup.php');
include_once($sNowPath.'/inc/model/CCompany.php');
include_once($sNowPath.'/inc/model/CCompanyOldcat.php');
include_once($sNowPath.'/inc/model/CUser.php');
include_once($sNowPath.'/inc/model/CManagement.php');


Class COrder extends CGalaxyClass{
	private $sOrderUuid;
	public $sOrderNo;
	public $sName;
	public $iType;
	public $iManagementId;
	public $iSalespersonNo;
	public $sProposalDate;
	public $sSealDate;
	public $iSealTimes;
	public $sSignDate;
	public $sSignBackDate;
	public $sFailDate;
	public $sCompletionDate;
	public $iCanceled;
	public $sCanceledDate;
	public $sStartDate;
	public $sEndDate;
	public $sQuotationNo;
	public $sNote;
	public $iPhaseNo;
	//public $iGroupNo;
	public $iVerifyStatus; //0: 退回 1:待審 2:通過
	public $sVerifyTime;
	public $iVerifyUserNo;
	public $bStatus;
	public $iUserNo;

	public $iClientNo;
	public $sClientName;
	public $iContactUserNo;
	public $sContactUserName;
	public $iContractClientNo;
	public $sContractClientName;

	public $iTaxId;

	private $__aProject=array();
	private $__aPayment=array();

	private $__aErroneousItems=array();

	private $__oClient;
	private $__oSalesperson;
	private $__oContact;
	private $__oManagement;
	//private $__oGroup;

	static protected $sDBName = 'ORDER';

	static public $aType=array(
			1=>'直客',
			2=>'簽約代',
			3=>'一般代',
			4=>'銀河',
			5=>'其他'

		);
	/*
	static public $aManagementInitial=array(
			1=>"G", //Goods
			2=>"B" //Boutique
		);
	*/
	static public $aOrderPhase=array(
			1=>'提案中',
			2=>'用印申請',
			3=>'已簽回',
			4=>'失敗',
			//5=>'完工',
		);
	static public $aCancelScope=array(
			0=>'(無)',
			1=>'取消部份',
			2=>'取消全部'
		);

	static public $aOrderPhaseField=array(
			1=>'sProposalDate',
			2=>'sSealDate',
			3=>'sSignBackDate',
			4=>'sFailDate',
			//5=>'sCompletionDate',
		);

	static public function oGetOrder($sOrderUuid){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT * FROM `order` WHERE `order_no`='$sOrderUuid' ";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		if(!$aRow || $oDB->iNumRows($iDBq)>1)
			return null;
		$oCOrder = new COrder($aRow);
		return $oCOrder;
	}

	static public function aAllOrder($sSearchSql='', $sPostfix=''){
		$aAllOrder = array();
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT * FROM `order` ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql";
		if($sPostfix!=='')
			$sSql.=" $sPostfix ";
		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllOrder[]=new COrder($aRow);
		}
		return $aAllOrder;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT count(`order_no`) as total FROM `order` ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql ";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		if($aRow)
			$iCount=(int)$aRow['total'];
		else
			$iCount=0;
		return $iCount;
	}

	static public function sOrderNoGenerator($iManagementId){
		$oDB=self::oDB(self::$sDBName);
		//$aResult[]=self::$aManagementInitial[$iManagementId];
		$aResult[]=$iManagementId;
		$aResult[]=date('Ym');

		$aOrderNo=array();
		$sSql="SELECT `order_id` FROM `order`";
		$iDbq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDbq)){
			$aOrderNo[]=$aRow['order_id'];
		}
		$sResult=implode('-', $aResult);
		do{
			$iRand=rand ( 1000 , 9999 );
		}while(array_search($sResult.'-'.$iRand, $aOrderNo));
		$sResult.='-'.$iRand;

		return	$sResult;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("COrder: __construct failed: require an array.");
		if(empty($multiData['order_no']))
			$this->sOrderUuid='order_'.CMisc::uuid_v1();
		else
			$this->sOrderUuid=$multiData['order_no'];

		if(!isset($multiData['user_no'])){
			$oCUser = self::$session->get('oCurrentUser');
			$multiData['user_no'] = $oCUser->iUserNo;
		}else{
			$this->iUserNo = $multiData['user_no'];
		}

		$sDate = date("Y-m-d H:i:s");

		$this->sOrderNo = isset($multiData['order_id'])?$multiData['order_id']:'';
		$this->sName = isset($multiData['name'])?$multiData['name']:'';
		$this->iType = isset($multiData['type'])?$multiData['type']:0;
		$this->iManagementId = isset($multiData['management_id'])?$multiData['management_id']:0;
		$this->iSalespersonNo = isset($multiData['salesperson_no'])?$multiData['salesperson_no']:0;
		$this->sProposalDate = isset($multiData['proposal_date'])?$multiData['proposal_date']:'';
		$this->sSealDate = isset($multiData['seal_date'])?$multiData['seal_date']:'0000-00-00';
		$this->iSealTimes = isset($multiData['seal_times'])?$multiData['seal_times']:0;
		$this->sSignBackDate = isset($multiData['sign_back_date'])?$multiData['sign_back_date']:'0000-00-00';
		$this->sFailDate = isset($multiData['fail_date'])?$multiData['fail_date']:'0000-00-00';
		$this->sCompletionDate = isset($multiData['completion_date'])?$multiData['completion_date']:'0000-00-00';
		$this->iCanceled = isset($multiData['canceled'])?$multiData['canceled']:0;
		$this->sCanceledDate = isset($multiData['canceled_date'])?$multiData['canceled_date']:'0000-00-00';
		$this->sSignDate = isset($multiData['sign_date'])?$multiData['sign_date']:'0000-00-00';
		$this->sStartDate = isset($multiData['start_date'])?$multiData['start_date']:'0000-00-00';
		$this->sEndDate = isset($multiData['end_date'])?$multiData['end_date']:'0000-00-00';
		$this->sQuotationNo = isset($multiData['quotation_no'])?$multiData['quotation_no']:'';
		$this->sNote = isset($multiData['note'])?$multiData['note']:'';
		$this->iPhaseNo = isset($multiData['phase_no'])?$multiData['phase_no']:0;
		$this->iGroupNo = isset($multiData['group_no'])?$multiData['group_no']:0;
		$this->iVerifyStatus = isset($multiData['verify_status'])?$multiData['verify_status']:0;
		$this->sVerifyTime = isset($multiData['verify_time'])?$multiData['verify_time']:'0000-00-00 00:00:00';
		$this->iVerifyUserNo = isset($multiData['verify_user_no'])?$multiData['verify_user_no']:0;
		$this->bStatus = isset($multiData['status'])?$multiData['status']:0;

		$this->iContractClientNo = isset($multiData['contract_client_no'])?$multiData['contract_client_no']:0;
		$this->sContractClientName = isset($multiData['contract_client_name'])?$multiData['contract_client_name']:'';
		$this->iContactUserNo = isset($multiData['contact_user_no'])?$multiData['contact_user_no']:0;
		$this->sContactUserName = isset($multiData['contact_user_name'])?$multiData['contact_user_name']:'';
		$this->iClientNo = isset($multiData['client_no'])?$multiData['client_no']:0;
		$this->sClientName = isset($multiData['client_name'])?$multiData['client_name']:'';
		$this->sErroneousItems = isset($multiData['erroneous_items'])?$multiData['erroneous_items']:'';

		$this->iTaxId = isset($multiData['tax_id'])?$multiData['tax_id']:0;

		$this->sCreateTime = isset($multiData['create_time'])?$multiData['create_time']:$sDate;
		$this->sModifiedTime = isset($multiData['modify_time'])?$multiData['modify_time']:$sDate;

		if (!$this->sSealDate) $this->sSealDate = '0000-00-00';
		if (!$this->sSignBackDate) $this->sSignBackDate = '0000-00-00';
		if (!$this->sFailDate) $this->sFailDate = '0000-00-00';
		if (!$this->sCompletionDate) $this->sCompletionDate = '0000-00-00';
		if (!$this->sCanceledDate) $this->sCanceledDate = '0000-00-00';
		if (!$this->sSignDate) $this->sSignDate = '0000-00-00';
		if (!$this->sStartDate) $this->sStartDate = '0000-00-00';
		if (!$this->sEndDate) $this->sEndDate = '0000-00-00';
		if (!$this->sVerifyTime) $this->sVerifyTime = '0000-00-00 00:00:00';
		if (!$this->sCreateTime) $this->sCreateTime = $sDate;
		if (!$this->sModifiedTime) $this->sModifiedTime = $sDate;
		if (!$this->iSealTimes) $this->iSealTimes = 0;
		if (!$this->iContractClientNo) $this->iContractClientNo = 0;
		if (!$this->iContactUserNo) $this->iContactUserNo = 0;
		if (!$this->iType) $this->iType = 0;
		if (!$this->iClientNo) $this->iClientNo = 0;
		if (!$this->iManagementId) $this->iManagementId = 0;
		if (!$this->iSalespersonNo) $this->iSalespersonNo = 0;
		if (!$this->iCanceled) $this->iCanceled = 0;
		if (!$this->iPhaseNo) $this->iPhaseNo = 0;
		if (!$this->iGroupNo) $this->iGroupNo = 0;
		if (!$this->iVerifyStatus) $this->iVerifyStatus = 0;
		if (!$this->iVerifyUserNo) $this->iVerifyUserNo = 0;
		if (!$this->bStatus) $this->bStatus = 0;
	}

	public function __get($varName){
		   return $this->$varName;
	}

	/**
	 *  @desc 新增訂單
	 *  @created 2015/10/28
	 */
	public function sAdd(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");
		try{
			$oDB->vBegin();
			$aValues=array(
				'order_no' => $this->sOrderUuid,
				'order_id' => $this->sOrderNo,
				'name' => $this->sName,
				'type' => $this->iType,
				'management_id' => $this->iManagementId,
				'salesperson_no' => $this->iSalespersonNo,
				'proposal_date' => $this->sProposalDate,
				'seal_date' => $this->sSealDate,
				'seal_times' => $this->iSealTimes,
				'sign_back_date' => $this->sSignBackDate,
				'fail_date' => $this->sFailDate,
				'completion_date' => $this->sCompletionDate,
				'canceled' => 0,
				'sign_date' => $this->sSignDate,
				'start_date' => $this->sStartDate,
				'end_date' => $this->sEndDate,
				'quotation_no' => $this->sQuotationNo,
				'note' => $this->sNote,
				'phase_no' => $this->iGetPhaseNo(),
				//'group_no'=>$this->iGroupNo,
				'verify_status' => 1,
				'status' => $this->bStatus,
				'user_no' => $oCurrentUser->iUserNo,
				'create_time' => $sDate,
				'modify_time' => $sDate,
				'client_no' => $this->iClientNo,
				'client_name' => $this->sClientName,
				'contract_client_no' => $this->iContractClientNo,
				'contract_client_name' => $this->sContractClientName,
				'contact_user_no' => $this->iContactUserNo,
				'contact_user_name' => $this->sContactUserName,
				'tax_id' => $this->iTaxId,
				'canceled_date'=> $this->sCanceledDate,
				'group_no' => $this->iGroupNo,
				'erroneous_items' => $this->sErroneousItems,
				'verify_time' => $this->sVerifyTime,
				'verify_user_no' => $this->iVerifyUserNo,
			);
			$oDB->sInsert("`order`", array_keys($aValues), array_values($aValues));
			/*
			//insert contract client info
			if(!empty($this->iContractClientNo)){
				$aValues=array(
					'order_no'=>$this->sOrderUuid,
					'co_id'=>$this->iContractClientNo,
					'type'=>1,
					'name'=>$this->sContractClientName,
					'contact_user_no'=>$this->iContactUserNo,
					'contact_user_name'=>$this->sContactUserName,
				);
				$oDB->sInsert("order_company", array_keys($aValues), array_values($aValues));
			}

			//insert client info
			if(!empty($this->iClientNo)){
				$aValues=array(
					'order_no'=>$this->sOrderUuid,
					'co_id'=>$this->iClientNo,
					'type'=>2,
					'name'=>$this->sClientName,
				);
				$oDB->sInsert("order_company", array_keys($aValues), array_values($aValues));
			}
			*/
			$oCurrentUser->vAddUserLog('order',$this->sOrderUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
			return $this->sOrderUuid;
		}
		catch(Exception $e){
			$oDB->vRollback();
			throw new Exception('COrder->sAdd:'.$e->getMessage());
		}

	}
	/*
	public function vOverwrite($oCOrder){
		if(get_class($oCOrder)!=='COrder'||$this->sOrderUuid!==$oCOrder->sOrderUuid)
			throw new Exception("COrder->vOverwrite: fatal error.");
		foreach($this as $key => $value){
			if($key==='sOrderUuid'|| is_null($oCOrder->$key))
				continue;
			$this->$key=$oCOrder->$key;
		}
	}
	*/
	public function vUpdate($oCOrderUpdate){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser=self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{
			$oDB->vBegin();
			$aValues=array(
					'order_id'=>$oCOrderUpdate->sOrderNo,
					'name'=>$oCOrderUpdate->sName,
					'type'=>$oCOrderUpdate->iType,
					'management_id'=>$oCOrderUpdate->iManagementId,
					'salesperson_no'=>$oCOrderUpdate->iSalespersonNo,
					'proposal_date'=>$oCOrderUpdate->sProposalDate,
					'seal_date'=>$oCOrderUpdate->sSealDate,
					'seal_times'=>$oCOrderUpdate->iSealTimes,
					'sign_back_date'=>$oCOrderUpdate->sSignBackDate,
					'fail_date'=>$oCOrderUpdate->sFailDate,
					'completion_date'=>$oCOrderUpdate->sCompletionDate,
					'canceled'=>$oCOrderUpdate->iCanceled,
					'canceled_date'=>$this->sGetCanceledDate($oCOrderUpdate),
					'sign_date'=>$oCOrderUpdate->sSignDate,
					'start_date'=>$oCOrderUpdate->sStartDate,
					'end_date'=>$oCOrderUpdate->sEndDate,
					'quotation_no'=>$oCOrderUpdate->sQuotationNo,
					'note'=>$oCOrderUpdate->sNote,
					'phase_no'=>$oCOrderUpdate->iGetPhaseNo(),
					//'group_no'=>$oCOrderUpdate->iGroupNo,
					'verify_status'=>$oCOrderUpdate->iVerifyStatus,
					'erroneous_items'=>json_encode($oCOrderUpdate->__aErroneousItems),
					'verify_user_no'=>$oCOrderUpdate->iVerifyUserNo,
					'verify_time'=>$oCOrderUpdate->sVerifyTime,
					'status'=>$oCOrderUpdate->bStatus,
					'user_no'=>$oCurrentUser->iUserNo,
					'modify_time'=>$sDate,
					'client_no'=>$oCOrderUpdate->iClientNo,
					'client_name'=>$oCOrderUpdate->sClientName,
					'contract_client_no'=>$oCOrderUpdate->iContractClientNo,
					'contract_client_name'=>$oCOrderUpdate->sContractClientName,
					'contact_user_no'=>$oCOrderUpdate->iContactUserNo,
					'contact_user_name'=>$oCOrderUpdate->sContactUserName,
					'tax_id'=>$oCOrderUpdate->iTaxId
				);
			$oDB->sUpdate('`order`', array_keys($aValues), array_values($aValues), "`order_no`='{$this->sOrderUuid}'");
			/*
			//update contract client info
			$oDB->vDelete("order_company","`order_no`='{$this->sOrderUuid}'");
			if(!empty($oCOrderUpdate->iContractClientNo)){
				$aValues=array(
					'order_no'=>$oCOrderUpdate->sOrderUuid,
					'co_id'=>$oCOrderUpdate->iContractClientNo,
					'name'=>$oCOrderUpdate->sContractClientName,
					'type'=>1,
					'contact_user_no'=>$oCOrderUpdate->iContactUserNo,
					'contact_user_name'=>$oCOrderUpdate->sContactUserName,
				);
				$oDB->sInsert("order_company", array_keys($aValues), array_values($aValues));
			}

			//update client info
			if(!empty($oCOrderUpdate->iClientNo)){
				$aValues=array(
					'order_no'=>$oCOrderUpdate->sOrderUuid,
					'co_id'=>$oCOrderUpdate->iClientNo,
					'type'=>2,
					'name'=>$oCOrderUpdate->sClientName,
				);
				$oDB->sInsert("order_company", array_keys($aValues), array_values($aValues));
			}
			*/
			$oCurrentUser->vAddUserLog('order', $this->sOrderUuid, $_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception ("COrder->vUpdate: ".$e->getMessage());
		}
	}

	public function vDelete(){

	}

	public function vVerify(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser=self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");
		try{
			$aValues=array(
				'verify_status'=>$this->iVerifyStatus,
				'erroneous_items'=>json_encode($this->__aErroneousItems),
				'verify_time'=>$sDate,
				'verify_user_no'=>$oCurrentUser->iUserNo,
				'user_no'=>$oCurrentUser->iUserNo,
				'modify_time'=>$sDate,
				);
			$oDB->sUpdate('`order`', array_keys($aValues), array_values($aValues), "`order_no`='{$this->sOrderUuid}'");

			$oCurrentUser->vAddUserLog('order', $this->sOrderUuid, $_GET['func'],$_GET['action']);
		}catch(Exception $e){
			throw new Exception ("COrder->vVerify: ".$e->getMessage());
		}
	}


	public function aProject(){
		$oDB=self::oDB(self::sDBName);
		if(empty($this->__aProject)){
			$sSql="SELECT * FROM `order_project` WHERE `order_no` = {$this->sOrderUuid}";
			$iDBq=$oDB->iQuery($sSql);
			while($aRow=$oDB->aFetchAssoc($iDBq)){
				$this->__aProject[]=new COrderProject($aRow);
			}
		}
		return $this->__aProject;
	}

	public function aPayment(){
		$oDB=self::oDB(self::$sDBName);
		if(empty($this->__aPayment)){
			$sSql="SELECT * FROM `order_payment` WHERE `order_no` = {$this->sOrderUuid}";
			$iDBq=$oDB->iQuery($sSql);
			while($aRow=$oDB->aFetchAssoc($iDBq)){
				$this->__aPayment[]=new COrderPayment($aRow);
			}
		}
		return $this->__aPayment;
	}
	/*
	public function oClient(){
		if(empty($this->__oClient)){
			$this->__oClient=CCompany::oGetCompany($this->iClientNo);
			$iCompanyType=CCompanyOldcat::oGetCompanyOldcat($this->iClientNo)->iCompanyType;
			$this->__oClient->iCompanyType=CCompanyOldcat::$aCompanyType[$iCompanyType];
		}
		return $this->__oClient;
	}

	public function oContractClient(){
		if(empty($this->__oContractClient)){
			$this->__oContractClient=CCompany::oGetCompany($this->iContractClientNo);
			$iCompanyType=CCompanyOldcat::oGetCompanyOldcat($this->iContractClientNo)->iCompanyType;
			$this->__oContractClient->iCompanyType=CCompanyOldcat::$aCompanyType[$iCompanyType];
		}
		return $this->__oContractClient;
	}
	*/
	public function oSalesperson(){
		if(empty($this->__oSalesperson)){
			$this->__oSalesperson=CUser::oGetUser($this->iSalespersonNo);
		}
		return $this->__oSalesperson;
	}
	/*
	public function oContact(){
		if(empty($this->__oContact)){
			$this->__oContact=CUser::oGetUser($this->iContactUserNo);
		}
		return $this->__oContact;
	}
	*/
	public function oManagement(){
		if(empty($this->__oManagement)){
			$this->__oManagement=CManagement::oGetManagement($this->iManagementId);
		}
		return $this->__oManagement;
	}

	private function iGetPhaseNo(){
		$iPhaseNo=1;
		$sDate=date('Y-m-d');
		foreach(self::$aOrderPhaseField as $key => $sPhase){
			$sPhaseDate=$this->$sPhase;
			if($sPhaseDate==''||$sPhaseDate=='0000-00-00') continue;
			if($sDate>=$sPhaseDate)
				$iPhaseNo=$key;
		}
		return $iPhaseNo;
	}

	private function sGetCanceledDate($oCOrderUpdate){
		if($oCOrderUpdate->iCanceled==0)
			return '0000-00-00';
		if($this->iCanceled==$oCOrderUpdate->iCanceled)
			return $this->sCanceledDate;
		return date('Y-m-d');
	}

	public function bIsCompleted(){
		$sDate=date('Y-m-d');
		if($this->sCompletionDate!=''&&$this->sCompletionDate!='0000-00-00'&&$sDate>=$this->sCompletionDate)
			return true;
		return false;
	}
	/*
	public function vSetCompany($aCompany){
		$this->iContractClientNo=$aCompany['contract_client_no'];
		$this->sContractClientName=$aCompany['contract_client_name'];
		$this->iContactUserNo=$aCompany['contact_user_no'];
		$this->sContactUserName=$aCompany['contact_user_name'];
		$this->iClientNo=$aCompany['client_no'];
		$this->sClientName=$aCompany['client_name'];
	}

	public function vGetCompany(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM `order_company` WHERE `order_no`='{$this->sOrderUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			switch($aRow['type']){
				case 1:
					$this->iContractClientNo=$aRow['co_id'];
					$this->sContractClientName=$aRow['name'];
					$this->iContactUserNo=$aRow['contact_user_no'];
					$this->sContactUserName=$aRow['contact_user_name'];
					break;
				case 2:
					$this->iClientNo=$aRow['co_id'];
					$this->sClientName=$aRow['name'];
					break;
				default: break;
			}
		}
	}
	*/
	public function vSetErroneousItems($aData){
		$this->__aErroneousItems=$aData;
	}

	public function aErroneousItems(){
		if(empty($this->__aErroneousItems)){
			$oDB = self::oDB(self::$sDBName);
			$sSql="SELECT  `erroneous_items` FROM `order` WHERE `order_no` = '{$this->sOrderUuid}' ";
			$iDBq=$oDB->iQuery($sSql);
			$aRow=$oDB->aFetchAssoc($iDBq);

			$this->__aErroneousItems=json_decode($aRow['erroneous_items'],1);
		}
		return $this->__aErroneousItems;
	}
	/*
	public function oGroup(){
		if(empty($this->__oGroup)){
			$this->__oGroup=COrderGroup::oGetGroup($this->iGroupNo);
		}
		return $this->__oGroup;
	}
	*/


	/**
	 *  @desc 取得該訂單所有產品
	 *  @created 2015/10/23
	 */
	public function aGetProduct(){
		if (!$this->sOrderUuid) return array();
		$oDB=self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT * FROM order_product_rel WHERE order_no='{$this->sOrderUuid}'");
		$aAllData = array();
		while ($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllData[] = CProduct::oGetProduct($aRow['product_no']);
		}
		return $aAllData;
	}


	/**
	 *  @desc 新增訂單與產品關連
	 *  @created 2015/10/23
	 */
	public function vAddOrderProductRel($aPost){
		if (!$this->sOrderUuid) return false;
		$oDB = self::oDB(self::$sDBName);
		if (!$aPost) return true;
		foreach($aPost as $val){
			$sSql = "INSERT INTO order_product_rel
				SET order_no = '{$this->sOrderUuid}',
				product_no = $val";
			$oDB->iQuery($sSql);
		}
		return true;
	}


	/**
	 *  @desc 刪除訂單與產品關連
	 *  @created 2015/10/23
	 */
	public function vDelOrderProductRel(){
		if (!$this->sOrderUuid) return false;
		$oDB = self::oDB(self::$sDBName);
		$oDB->iQuery("DELETE FROM order_product_rel WHERE order_no = '{$this->sOrderUuid}'");
		return true;
	}


	/**
	 *  @desc 顯示訂單產品
	 *  @created 2015/10/23
	 */
	public function sGetProductName($comma='<br>'){
		if (!$this->sOrderUuid) return '';
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT * FROM order_product_rel AS a INNER JOIN product AS b ON a.product_no=b.product_no WHERE a.order_no='{$this->sOrderUuid}' ORDER BY b.product_order, b.created");
		$aProductName = array();
		while ($aRow = $oDB->aFetchAssoc($iDbq)){
			$oCProduct = CProduct::oGetProduct($aRow['product_no']);
			array_push($aProductName,$oCProduct->product_name);
		}
		return implode($comma,$aProductName);
	}


}
?>