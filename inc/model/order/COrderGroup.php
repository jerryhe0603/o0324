<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');


Class COrderGroup extends CGalaxyClass{
	private $iGroupNo;
	public $sName;
	public $sDesc;
	public $bStatus;
	//public $iUserNo;

	private $__aUser;


	static protected $sDBName = 'ORDER';

	static public function oGetGroup($iGroupNo){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_group` WHERE `group_no`=$iGroupNo";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		if($aRow===false||$oDB->iNumRows($iDBq)>1)
			return null;
		$oCOrderGroup= new COrderGroup($aRow);
		return $oCOrderGroup;
	}

	static public function aAllGroupByUser($iUserNo){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_group` as og left join `order_group_user_rel` as ogul on og.`group_no`=ogul.`group_no` where ogul.`member_user_no` = $iUserNo AND og.`status`=1";
		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllOrderGroup[]=new COrderGroup($aRow);
		}
		return $aAllOrderGroup;
	}

	static public function aAllGroup($sSearchSql='', $sPostFix=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_group`";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql";
		if($sPostfix!=='')
			$sSql.=" $sPostfix ";
		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllOrderGroup[]=new COrderGroup($aRow);
		}
		return $aAllOrderGroup;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT count(`group_no`) as total FROM `order_group`";
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
			throw new Exception("COrderGroup: __construct failed: require an array.");

		if(is_null($multiData['user_no'])){
			$oCUser = self::$session->get('oCurrentUser');
			$multiData['user_no'] = $oCUser->iUserNo;
		}

		$this->iGroupNo=$multiData['group_no'];
		$this->sName=$multiData['name'];
		$this->sDesc=$multiData['desc'];
		$this->bStatus=$multiData['status'];
		$this->iUserNo=$multiData['user_no'];

		$this->sCreateTime=$multiData['create_time'];
		$this->sModifiedTime=$multiData['modify_time'];
	}

	public function __get($varName){
		   return $this->$varName;
	}

	public function iAdd(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{
			$oDB->vBegin();
			$aValues=array(
				'name'=>$this->sName,
				'desc'=>$this->sDesc,
				'status'=>$this->bStatus,
				'user_no'=>$oCurrentUser->iUserNo,
				'create_time'=>$sDate,
				'modify_time'=>$sDate,
			);
			$oDB->sInsert("order_group", array_keys($aValues), array_values($aValues));
			$this->iGroupNo = $oDB->iGetInsertId();
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('order_group',$this->iGroupNo,$_GET['func'],$_GET['action']);
			return $this->iGroupNo;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception('COrderGroup->iAdd:'.$e->getMessage());
		}
	}

	public function vUpdate(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser=self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{
			$oDB->vBegin();
			$aValues=array(
				'name'=>$this->sName,
				'desc'=>$this->sDesc,
				'status'=>$this->bStatus,
				'user_no'=>$oCurrentUser->iUserNo,
				'modify_time'=>$sDate,
			);
			$oDB->sUpdate('order_group', array_keys($aValues), array_values($aValues), "`group_no`='{$this->iGroupNo}'");
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('order_group', $this->iGroupNo, $_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception ("COrderGroup->vUpdate: ".$e->getMessage());
		}
	}

	public function aUser(){
		if(empty($this->__aUser)){
			$oDB=self::oDB(self::$sDBName);
			$sSql="SELECT * FROM `order_group_user_rel` WHERE `group_no` = {$this->iGroupNo}";
			$iDBq=$oDB->iQuery($sSql);
			while($aRow=$oDB->aFetchAssoc($iDBq)){
				$this->__aUser[]=CUser::oGetUser($aRow['member_user_no']);
			}
		}
		return $this->__aUser;
	}

	public function vSetUser($aUserNo){
		if(empty($aUserNo)) return;
		$this->__aUser=array();
		foreach($aUserNo as $iUserNo){
			$this->__aUser[]=CUser::oGetUser($iUserNo);
		}
	}

	public function vUpdateUser(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser=self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{
			$oDB->vBegin();
			$oDB->vDelete("order_group_user_rel"," `group_no`={$this->iGroupNo} ");
			foreach($this->__aUser as $oUser){
				$aValues=array(
					'group_no'=>$this->iGroupNo,
					'member_user_no'=>$oUser->iUserNo,
					'user_no'=>$oCurrentUser->iUserNo,
					'create_time'=>$sDate,
				);
				$oDB->sInsert("order_group_user_rel", array_keys($aValues), array_values($aValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('order_group_user_rel', $this->iGroupNo, $_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception('COrderGroup->vUpdateUser:'.$e->getMessage());
		}
	}
}
?>