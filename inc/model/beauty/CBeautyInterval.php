<?php
include_once('../inc/model/CGalaxyClass.php');

class CBeautyInterval extends CGalaxyClass
{
	private $iIntervalNo;
	private $sProjectUuid;
	public $iOrder;

	public $sNextHint;

	public $sStartDate;
	public $sEndDate;

	private $__aOpinion;

	//database setting
	static protected $sDBName = 'SITE';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCBeautyInterval by certain interval)no
	*/
	static public function oGetInterval($iIntervalNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iBeautyDocNo]))
			return self::$aInstancePool[$iBeautyDocNo];

		//query from beauty DB
		$sSql = "SELECT * FROM project_interval WHERE interval_no='$iIntervalNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oInterval = new CBeautyInterval($aRow);
		self::$aInstancePool[$iIntervalNo] = $oInterval;

		return $oInterval;
	}

	/*
		get all beauty interval in an array
		if $sSearchSql is given, query only match intervals
	*/
	static public function aAllInterval($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllInterval = array();
		$sSql = "SELECT * FROM `project_interval`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['interval_no']])){
				self::$aInstancePool[$aRow['interval_no']] = new CBeautyInterval($aRow);
			}
			$aAllInterval[] = self::$aInstancePool[$aRow['interval_no']];
		}
		return $aAllInterval;
	}

	/*
		get count of interval which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(interval_no) as total FROM project_interval";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}	


	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CBeautyInterval: __construct failed, require an array");

		$this->iIntervalNo = $multiData['interval_no'];
		$this->sProjectUuid = $multiData['project_no'];
		$this->iOrder = $multiData['interval_order'];
		$this->sStartDate = $multiData['start_date'];
		$this->sEndDate = $multiData['end_date'];
		$this->sNextHint = $multiData['next_hint'];

		//galaxy class member
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['createdtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];

	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    public function aOpinion(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty($this->__aOpinion)){
    		$sSql = "SELECT * FROM project_interval_opinion WHERE `interval_no`='$this->iIntervalNo'";
    		$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aOpinion[] = $aRow['content'];
			}
    	}
    	return $this->__aOpinion;
    }

    public function vSetOpinion($aOpinion){
    	$this->__aOpinion = $aOpinion;
    }

	public function iAdd(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$aValues = array(	'project_no'=>$this->sProjectUuid,
								'interval_order'=>$this->iOrder,
								'start_date'=>$this->sStartDate,
								'end_date'=>$this->sEndDate,
								'user_no'=>$oCurrentUser->iUserNo,
								'status'=>$this->bStatus,
								'createdtime'=>date("Y-m-d H:i:s"),
								'modifiedtime'=>date("Y-m-d H:i:s")
								);
			$oDB->sInsert("project_interval", array_keys($aValues), array_values($aValues));
			$this->iIntervalNo = $oDB->iGetInsertId();
			if(!empty($this->__aOpinion)){
				foreach ($$this->__aOpinion as $iCount=>$sOpinion) {
					$aOpinionVal = array(	"interval_no"=>$this->iIntervalNo,
											"opinion_no"=>$iCount,
											"content"=>$sOpinion
											);
					$oDB->sInsert("project_interval_opinion", array_keys($aValues), array_values($aValues));
				}
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_interval',$this->iIntervalNo,$_GET['func'],$_GET['action']);
			return $this->iIntervalNo;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyInterval->vAdd: ".$e->getMessage());
		}

	}

	public function vUpdate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$aValues = array(	'start_date'=>$this->sStartDate,
								'end_date'=>$this->sEndDate,
								'user_no'=>$oCurrentUser->iUserNo,
								'status'=>$this->bStatus,
								'modifiedtime'=>date("Y-m-d H:i:s")
								);
			$oDB->sUpdate("project_interval", array_keys($aValues), array_values($aValues),"`interval_no`='{$this->iIntervalNo}'");
			$oCurrentUser->vAddUserLog('project_interval',$this->iIntervalNo,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			throw new Exception("CBeautyInterval->vUpdate: ".$e->getMessage());
		}
	}

	public function vUpdateWriting(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$aValues = array(	'next_hint'=>$this->sNextHint
								);
			$oDB->sUpdate("project_interval", array_keys($aValues), array_values($aValues),"`interval_no`='{$this->iIntervalNo}'");
			//delete elder opinions and insert new ones
			$oDB->vDelete("project_interval_opinion","`interval_no`='{$this->iIntervalNo}'");
			if(!empty($this->__aOpinion)){
				foreach ($this->__aOpinion as $iCount=>$sOpinion) {
					if(empty($sOpinion))
						continue;

					$aOpinionVal = array(	"interval_no"=>$this->iIntervalNo,
											"opinion_no"=>$iCount,
											"content"=>$sOpinion
											);
					$oDB->sInsert("project_interval_opinion", array_keys($aOpinionVal), array_values($aOpinionVal));
				}
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_interval',$this->iIntervalNo,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyInterval->vUpdateWriting: ".$e->getMessage());
		}
	}

	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			if($this->bStatus==='1')
				$this->bStatus='0';
			else
				$this->bStatus='1';
			$aValues = array('status'=>$this->bStatus);
			$oDB->sUpdate("project_interval", array_keys($aValues), array_values($aValues),"`interval_no`='{$this->iIntervalNo}'");
			$oCurrentUser->vAddUserLog('project_interval',$this->iIntervalNo,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			throw new Exception("CBeautyInterval->vActivate: ".$e->getMessage());
		}
	}

	public function vDelete(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$oDB->vDelete("project_interval","`interval_no`='{$this->iIntervalNo}'");
			$oDB->vDelete("project_interval_opinion","`interval_no`={$this->iIntervalNo}");
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_interval',$this->iIntervalNo,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyInterval->vDelete: ".$e->getMessage());
		}
	}


}
?>