<?php

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

include_once($sNowPath.'/inc/model/CGalaxyClass.php');

class CUSerLog extends CGalaxyClass
{
	
	private $iLogNo;
	public $sTableName;
	public $sTableUuid;
	public $sFunc;
	public $sAction;

	private $__oCRule;

	//extra member not in DB
	private $sYear;
	private $sMonth;

	//database setting
	static protected $sDBName = 'GENESIS_LOG';


	public function __construct($multiData,$sYear='',$sMonth=''){
		parent::__construct($multiData);
		$this->iLogNo = isset($multiData['log_id'])?$multiData['log_id']:0;
		$this->sTableName = isset($multiData['table_name'])?$multiData['table_name']:'';
		$this->sTableUuid = isset($multiData['table_id'])?$multiData['table_id']:'';
		$this->sFunc = isset($multiData['table_func'])?$multiData['table_func']:'';
		$this->sAction = isset($multiData['table_action'])?$multiData['table_action']:'';
		//extra member
		if($sYear!=='')
			$this->sYear = $sYear;
		if($sMonth!=='')
			$this->sMonth = $sMonth;
		//galaxy class memeber
		$this->sModifiedTime = isset($multiData['modifiedtime'])?$multiData['modifiedtime']:'';
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

	/*
		get $oCUserLig by certain log_id
	*/
	static public function oGetLog($iLogNo,$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("user_log_{$sYear}_{$sMonth}"))
			return null;
		$sSql = "SELECT * FROM user_log_{$sYear}_{$sMonth} WHERE log_id='$iLogNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oLog = new CUserLog($aRow,$sYear,$sMonth);
		return $oLog;
	}

	static public function aAllLog($sSearchSql='',$sPostFix='',$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("user_log_{$sYear}_{$sMonth}"))
			return array();
		$sSql = "SELECT * FROM user_log_{$sYear}_{$sMonth}";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllLog = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllLog[] = new CUserLog($aRow,$sYear,$sMonth);
		}
		return $aAllLog;
	}

    /*
    	set & get rule of this log
    */
    public function oRule(){
    	if(empty($this->__oCRule)){
    		$aCRule = CRule::aAllRule("func='{$this->sFunc}' AND action='{$this->sAction}'");
    		if(count($aCRule)>1)
				return null;
    		$this->__oCRule = $aCRule[0];
    	}
    	return $this->__oCRule;
    }

	public function iAddLog(){
		$oDB = self::oDB(self::$sDBName);
		$aValues = array(	'table_name'=>$this->sTableName,
							'table_id'=>$this->sTableUuid,
							'table_func'=>$this->sFunc,
							'table_action'=>$this->sAction,
							'user_no'=>$this->__iUserNo,
							'modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->vBegin();
			if(!$oDB->bIsTableExist('user_log_'.date('Y').'_'.date('m'))){
				self::vAddLogTable(date('Y'),date('m'));
			}
			$oDB->sInsert('user_log_'.date('Y').'_'.date('m'),array_keys($aValues),array_values($aValues));
			$this->iLogNo = $oDB->iGetInsertId();
			$oDB->vCommit();
			return $this->iLogNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserLog->iAddLog: ".$e->getMessage());
		}
	}

	static private function vAddLogTable($sYear,$sMonth){
    	$oDB = self::oDB(self::$sDBName);
    	$aTableInfo = $oDB->aGetCreateTableInfo("user_log");
		if(!empty($aTableInfo['Create Table'])){
			$aTableInfo['Create Table'] = preg_replace("/user_log/i", "user_log_{$sYear}_{$sMonth}", $aTableInfo['Create Table']);
		}
		$oDB->iQuery($aTableInfo['Create Table'].";\n\n");
    }
}
?>