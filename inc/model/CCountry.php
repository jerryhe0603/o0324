<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CCountryZone.php');

class CCountry extends CGalaxyClass
{
	private $iISONumeric;
	public $sISO;
	public $sName;
	public $sNameTw;

	//info, default is null, use $this->vGetInfo to init these value
	public $sISO3;
	public $sFIPS;
	public $sCapital;
	public $iArea;
	public $iPopulation;
	public $sContinent;
	public $stld;
	public $sCurrencyCode;
	public $sCurrencyName;
	public $sPhone;
	public $sPostalCodeFormat;
	public $sPostalCodeRegex;
	public $sLanguages;
	public $iGeonameID;
	public $sNeighbours;
	public $sEquivalentFips;
	//only set when function is called
	private $__aZone;

	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetCountry($iISONumeric){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_country WHERE ISONumeric='$iISONumeric'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCountry = new CCountry($aRow);
		return $oCountry;
	}

	static public function aAllCountry($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_country";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCountry = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllCountry[] = new CCountry($aRow);
		}
		return $aAllCountry;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CCountry: __construct failed, require an array");
		//initialize vital member
		$this->iISONumeric = $multiData['ISONumeric'];
		$this->sISO = $multiData['ISO'];
		$this->sName = $multiData['Country'];
		$this->sNameTw = $multiData['Country_tw'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    public function vGetInfo(){
    	$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_countryinfo WHERE ISONumeric='{$this->iISONumeric}'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return;
		$this->sISO3 = $multiData['ISO3'];
		$this->sFIPS = $multiData['FIPS'];
		$this->sCapital = $multiData['Capital'];
		$this->iArea = $multiData['Area'];
		$this->iPopulation = $multiData['Population'];
		$this->sContinent = $multiData['Continent'];
		$this->stld = $multiData['tld'];
		$this->sCurrencyCode = $multiData['CurrencyCode'];
		$this->sCurrencyName = $multiData['CurrencyName'];
		$this->sPhone = $multiData['Phone'];
		$this->sPostalCodeFormat = $multiData['PostalCodeFormat'];
		$this->sPostalCodeRegex = $multiData['PostalCodeRegex'];
		$this->sLanguages = $multiData['Languages'];
		$this->iGeonameID = $multiData['GeonameID'];
		$this->sNeighbours = $multiData['Neighbours'];
		$this->sEquivalentFips = $multiData['EquivalentFips'];
    }

    public function aZone(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty($this->__aZone)){
    		$this->__aZone = CCountryZone::aAllZone("`ISO`='{$this->sISO}'");
    	}
    	return $this->__aZone;
    }
}
?>