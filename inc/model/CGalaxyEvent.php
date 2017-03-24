<?php
include_once('../inc/model/CGalaxyClass.php');

class CGalaxyEvent extends CGalaxyClass
{
	private $iEventNo;
	public $sName;
	public $sUrl;
	public $sDesc;
	public $iStartDate;
	public $iEndDate;
	public $bHoliday;
	public $bAllDay;

	//database setting
	static protected $sDBName = 'GENESIS_LOG';

	/*
		get $oCGalaxyEvent by certain event_no
	*/
	static public function oGetEvent($iEventNo,$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_calendar WHERE event_no='$iEventNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oEvent = new CGalaxyEvent($aRow);
		return $oEvent;
	}

	/*
		get all event in an array
		if $sSearchSql is given, query only match events
	*/
	static public function aAllEvent($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_calendar";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllEvent = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllEvent[] = new CGalaxyEvent($aRow);
		}
		return $aAllEvent;
	}

	/*
		get count of event which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(event_no) as total FROM galaxy_calendar";
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
		get holidays in array of string
	*/
	static public function aAllHoliday($iStart,$iEnd){
		$oDB = self::oDB(self::$sDBName);

		$sSql = "SELECT `event_start_date` FROM galaxy_calendar WHERE `is_holiday`='1' AND `is_allday`='1' AND (`event_start_date` BETWEEN $iStart AND $iEnd)";
		$iDbq = $oDB->iQuery($sSql);

		$aHoliday = array();
		while ($aRow = $oDB->aFetchAssoc($iDbq)) {
			$aHoliday[] = date("Y-m-d",$aRow['event_start_date']);
		}
		$aHoliday = array_unique($aHoliday);
		asort($aHoliday);

		return $aHoliday;
	}
	
	/*
		constructor of $oCGalaxyEvent
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CGalaxyEvent: __construct failed, require an array");
		//initialize vital member
		$this->iEventNo = $multiData['event_no'];
		$this->sName = $multiData['event_name'];
		$this->sUrl = $multiData['event_url'];
		$this->sDesc = $multiData['event_desc'];
		$this->iStartDate = $multiData['event_start_date'];
		$this->iEndDate = $multiData['event_end_date'];
		$this->bHoliday = $multiData['is_holiday'];
		$this->bAllDay = $multiData['is_allday'];

		//galaxy class memeber
		$this->sCreateTime = $multiData['created'];
		$this->sModifiedTime = $multiData['modified'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }

    public function iAddEvent(){

    }

    public function vUpdateEvent(){

    }

    public function vDelete(){

    }
}
?>