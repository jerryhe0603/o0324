<?php
include_once('../inc/model/CGalaxyClass.php');

class CCountryZone extends CGalaxyClass
{
	private $iZoneNo;
	public $sISO;
	public $sLanguage;
	public $sISO2;
	public $sRegion1;
	public $sRegion2;
	public $sRegion3;
	public $sRegion4;
	public $sZIP;
	public $sCity;
	public $sArea1;
	public $sArea2;
	public $$dLat;	//double
	public $dLng;	//double
	public $sTZ;
	public $sUTC;
	public $DST;
	
	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetCountryZone($iZoneNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_countryzone WHERE ID=$iZoneNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCCountryZone = new CCountryZone($aRow);
		return $oCCountryZone;
	}

	static public function aAllCountryZone($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_countryzone";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCCountryZone = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllCCountryZone[] = new CCountryZone($aRow);
		}
		return $aAllCCountryZone;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CCountryZone: __construct failed, require an array");
		$this->iZoneNo = $multiData['ID'];
		$this->sISO = $multiData['ISO'];
		$this->sLanguage = $multiData['Language'];
		$this->sISO2 = $multiData['ISO2'];
		$this->sRegion1 = $multiData['Region1'];
		$this->sRegion2 = $multiData['Region2'];
		$this->sRegion3 = $multiData['Region3'];
		$this->sRegion4 = $multiData['Region4'];
		$this->sZIP = $multiData['ZIP'];
		$this->sCity = $multiData['City'];
		$this->sArea1 = $multiData['Area1'];
		$this->sArea2 = $multiData['Area2'];
		$this->dLat = $multiData['Lat'];	//double
		$this->dLng = $multiData['Lng'];	//double
		$this->sTZ = $multiData['TZ'];
		$this->sUTC = $multiData['UTC'];
		$this->sDST = $multiData['DST'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }
?>