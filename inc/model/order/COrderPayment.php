<?php
include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');

Class COrderPayment extends CGalaxyClass{
	private $iPaymentId;
	private $sOrderUuid;
	public $sScheduleDate;
	public $sActualDate;
	public $bStatus;
	public $sInvoiceNo;
	public $sInvoiceScheduleDate;
	public $sInvoiceActualDate;
	public $bPaid;
	public $iUserNo;
	public $sNote;

	static protected $sDBName = 'ORDER';

	static public function oGetOrderPayment($iPaymentId){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_payment` WHERE `payment_id` = $iPaymentId";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		if($aRow===false||$oDB->iNumRows($iDBq)>1)
			return null;
		$oCOrderPayment=new COrderPayment($aRow);
		return $oCOrderPayment;
	}

	static public function aAllOrderPayment($sSearchSql='', $sPostfix=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT * FROM `order_payment` ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql ";
		if($sPostfix!=='')
			$sSql.=" $sPostfix ";
		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllOrderPayment[]=new COrderPayment($aRow);
		}
		return $aAllOrderPayment;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT count(`payment_id`) as total  FROM `order_payment` ";
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

	public function __construct($multiData){
		parent::__construct($multiData);

		if(!is_array($multiData))
			throw new Exception('COrderPayment: __construct failed: an array required.');

		if(is_null($multiData['user_no'])){
			$oCUser = self::$session->get('oCurrentUser');
			$multiData['user_no'] = $oCUser->iUserNo;
		}else{
			$this->iUserNo = $multiData['user_no'];
		}

		$this->iPaymentId=$multiData['payment_id'];
		$this->sOrderUuid=$multiData['order_no'];
		$this->sScheduleDate=$multiData['schedule_date'];
		$this->sActualDate=$multiData['actual_date'];
		$this->bStatus=$multiData['status'];
		$this->sInvoiceNo=$multiData['invoice_no'];
		$this->sInvoiceScheduleDate=$multiData['invoice_schedule_date'];
		$this->sInvoiceActualDate=$multiData['invoice_actual_date'];
		$this->bPaid=$this->bPaid();
		$this->sNote=$multiData['note'];

		$this->sCreateTime=$multiData['create_time'];
		$this->sModifiedTime=$multiData['modify_time'];

	}

	public function __get($varName){
		   return $this->$varName;
	}

	public function iAdd(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser=self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{
			$oDB->vBegin();
			$aValues=array(
				'order_no'=>$this->sOrderUuid,
				'schedule_date'=>$this->sScheduleDate,
				'actual_date'=>$this->sActualDate,
				'status'=>$this->bStatus,
				'invoice_no'=>$this->sInvoiceNo,
				'invoice_schedule_date'=>$this->sInvoiceScheduleDate,
				'invoice_actual_date'=>$this->sInvoiceActualDate,
				'note'=>$this->sNote,
				'user_no'=>$oCurrentUser->iUserNo,
				'create_time'=>$sDate,
				'modify_time'=>$sDate
				);
			$oDB->sInsert('order_payment', array_keys($aValues), array_values($aValues));
			$this->iPaymentId = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog('order_payment', $this->iPaymentId, $_GET['func'],$_GET['action']);
			$oDB->vCommit();
			return $this->iPaymentId;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("COrderPayment->iAdd: ".$e->getMessage());
		}
	}

	public function vOverwrite($oCOrderPayment){
		if(get_class($oCOrderPayment)!=='COrderPayment'||$this->iPaymentId!==$oCOrderPayment->iPaymentId)
			throw new Exception("COrderPayment: vOverwrite failed: fatal error.");
		foreach($this as $key => $value){
			if($key==="iPaymentId" || $key==='sOrderUuid' || is_null($oCOrderPayment->$key))
				continue;
			$this->$key=$oCOrderPayment->$key;
		}
	}

	public function vUpdate(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser=self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");
		try {
			$oDB->vBegin();
			$aValues=array(
				'order_no'=>$this->sOrderUuid,
				'schedule_date'=>$this->sScheduleDate,
				'actual_date'=>$this->sActualDate,
				'status'=>$this->bStatus,
				'invoice_no'=>$this->sInvoiceNo,
				'invoice_schedule_date'=>$this->sInvoiceScheduleDate,
				'invoice_actual_date'=>$this->sInvoiceActualDate,
				'note'=>$this->sNote,
				'user_no'=>$oCurrentUser->iUserNo,
				'modify_time'=>$sDate
			);
			$oDB->sUpdate("order_payment", array_keys($aValues), array_values($aValues),"`payment_id`=".$this->iPaymentId);
			$oCurrentUser->vAddUserLog('order_payment', $this->iPaymentId, $_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("COrderPayment->vUpdate: ".$e->getMessage());
		}
		
	}

	public function vDelete(){

	}

	private function bPaid(){
		if(empty($this->sActualDate)||$this->sActualDate=='0000-00-00') return false;
		if($this->sActualDate<=date('Y-m-d')) return true;
		return false;
	}
}
?>