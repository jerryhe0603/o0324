<?php
include_once('../inc/model/CGalaxyClass.php');

class CEvent extends CGalaxyClass
{
	//database setting
	static protected $sDBName = 'GENESIS_LOG';

	/*
		get $oCEvent by certain Event_no
	*/
	static public function oGetEvent($iEventNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_event WHERE event_no = '$iEventNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCEvent = new CEvent($aRow);
		return $oCEvent;
	}

	/*
		get all event in an array
		if $sSearchSql is given, query only match Events
	*/
	static public function aAllEvent($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllEvent = array();
		$sSql = "SELECT * FROM galaxy_event";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllEvent[] = new CEvent($aRow);
		}
		return $aAllEvent;
	}

	/*
		get count of Event which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(event_no) as total FROM galaxy_event";
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

	/*
		constructor of $oCEvent
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CEvent: __construct failed, require an array");
		//initialize vital member
		$this->iEventNo = $multiData['event_no'];
		$this->sName = $multiData['event_name'];
		//galaxy class memeber
		$this->bStatus = $multiData['event_status'];
		$this->sCreateTime = $multiData['created'];
		$this->sModifiedTime = $multiData['modified'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }
}
?>