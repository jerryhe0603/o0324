<?php

include_once (PATH_ROOT.'/inc/model/CGalaxyClass.php');

class CAddrMap extends CGalaxyClass
{


	protected static $sDBName = 'GENESIS';
	
	private $iAddressMapNo;
	public $iProvinceNo;
	public $iCityNo;
	public $sProvinceName;
	public $sCityName;
	public $sAreaName;
	public $iZipCode;
	public $iOrder;
	public $iStatus;
	public $sArea;
	
	//instance pool
	static public $aInstancePool = array();

	public function __construct($multData)
	{
	
		global $gArea;
	
		parent::__construct($multData);
		
		if(!is_array($mulsData))
			throw new Exception("CAddrMap: __construct failed, require an array");
			
		$this->iAddressMapNo = $multData['addr_id'];
		$this->iProvinceNo = $multData['addr_prov_id'];
		$this->iCityNo = $multData['addr_city_id'];
		$this->sProvinceName = $multData['addr_prov_name'];
		$this->sCityName = $multData['addr_city_name'];
		$this->sAreaName = $multData['addr_area_name'];
		$this->iZipCode = $multData['addr_code'];
		//$this->pub_prov_ready = $multData['pub_prov_ready'];
		$this->iOrder = $multData['addr_order'];
		$this->iStatus = $multData['flag'];
		$this->sCreateTime = $multData['created'];
		$this->sModifiedTime = $multData['modified'];
		$iCode = substr($this->iZipCode,0,1);
		$this->sArea = isset($gArea[$iCode])?$gArea[$iCode]:'';
	
	}
	
	public function __get($varName)
	{
		return $this->$varName;
	}
	
	public static function oGetAddrMap($iAddressMapNo)
	{
		$oDB = self::oDB(self::$sDBName);

		if(!is_null(self::$aInstancePool[$iAddressMapNo]))
			return self::$aInstancePool[$iAddressMapNo];
		
		$sSql = "SELECT * FROM addr_map WHERE addr_id = '$iAddressMapNo'";
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$oCAddressMap = new CAddrMap($aRow);
		self::$aInstancePool[$iAddressMapNo]=$oCAddressMap;
		
		return $oCAddressMap;
	}


	/**
	* @param  $addr_id 地址流水號 
	* @return 地址的郵遞區號 
	* @desc 得到某筆地址的郵遞區號 
	* @created 2008/09/02
	*/	
	public static function sGetZipCode($iAddressMapNo = 0) {
		if(!$iAddressMapNo) return "";
		$oDB = self::oDB(self::$sDBName);
		
		$sSql="SELECT * FROM addr_map WHERE addr_id=$iAddressMapNo";
		$iDbq = $oDB->iQuery($sSql);
		if($oDB->iNumRows($iDbq)==0) return '';
		$aRow = $oDB->aFetchArray($iDbq);
		return $aRow['addr_code'];
	}
}
?>