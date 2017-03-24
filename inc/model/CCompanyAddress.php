<?php

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');
include_once(PATH_ROOT.'/inc/model/CAddrMap.php');

class CCompanyAddress extends CGalaxyClass
{

	protected static $sDBName = 'COMPANY';
	
	private $iCompanyNo;
	
	protected $_oAddressMap;
	
	public $iAddressMapNo;
	public $sCompanyAddress;
	public $iType;
	public $iZipCode;

	//instance pool
	static public $aInstancePool = array();
	
	public function __get($varName)
	{
	
		return $this->$varName;
	
	}
	
	
	public function __construct($multData)
	{
		parent::__construct($multData);
		
		if(!is_array($multData))
			throw new Exception("CCompanyAddress: __construct failed, require an array");
			
		$this->iCompanyNo = $multData['co_id'];
		$this->iAddressMapNo = $multData['addr_id'];
		$this->sCompanyAddress = $multData['co_addr'];
		$this->iType = $multData['type'];
		$this->iZipCode = $multiData['zipcode'];
	
	}
	
	
	public static function aGetCompanyAddress($iCompanyNo)
	{

		$aCompanyAddress = array();
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM company_address WHERE co_id = '$iCompanyNo'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){

			if(is_null(self::$aInstancePool[$aRow['co_id']])){
				self::$aInstancePool[$aRow['co_id']] = new CCompanyAddress($aRow);
			}

			$aCompanyAddress[] = self::$aInstancePool[$aRow['co_id']];
		}
		return $aCompanyAddress;
	
	}
	
	public function oAddrMap()
	{
	
		if(is_null($this->_oAddressMap))
			$this->_oAddressMap = CAddrMap::oGetAddrMap($this->iAddressMapNo);
		return $this->_oAddressMap;
		
	}
	
	
	
	
	


}
?>