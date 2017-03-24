<?php
include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');

class CCompanyTel extends CGalaxyClass
{

	protected static $sDBName = 'COMPANY';
	
	public $iCompanyNo;
	public $sTel;
	public $iType;

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
			throw new Exception("CCompanyTel: __construct failed, require an array");
			
		$this->iCompanyNo = $multData['co_id'];
		$this->sTel = $multData['co_tel'];
		$this->iType = $multData['type'];
	
	}
	
	
	
	public static function aGetCompanyTel($iCompanyNo)
	{

		$aCompanyTel = array();
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM company_tel WHERE co_id = '$iCompanyNo'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$iCompanyNo])){
				self::$aInstancePool[$iCompanyNo] = new CCompanyTel($aRow);
			}
			$aCompanyTel[] = self::$aInstancePool[$iCompanyNo];
		}
		return $aCompanyTel;
	
	}
	




}
?>