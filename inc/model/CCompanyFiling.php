<?php

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');



class CCompanyFiling extends CGalaxyClass
{


	protected static $sDBName = 'COMPANY';

	private $iCompanyFilingNo;
	public $sName;
	public $sTel;
	public $iAddressMapNo;
	public $sAddress;
	public $sNote;
	public $iVerifyStatus;
	public $iComapnyNo;
	public $iStatus;

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
			throw new Exception("CCompanyFiling: __construct failed, require an array");
			
		$this->iCompanyFilingNo = $multData['cf_id'];
		$this->sName = $multData['cf_name'];
		$this->sTel = $multData['cf_tel'];
		$this->iAddressMapNo = $multData['addr_id'];
		$this->sAddress = $multData['addr'];
		$this->sNote = $multData['note'];
		$this->iVerifyStatus = $multData['status'];
		$this->sCreateTime = $multData['created'];
		$this->iComapnyNo = $multData['co_id'];
		$this->__iUserNo = $multData['user_no'];
		$this->iStatus = $multData['flag'];
		$this->sModifiedTime = $multData['modified'];
	
	}
	

	
	public static function oGetCompanyFiling($iCompanyFilingNo=0){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iCompanyFilingNo]))
			return self::$aInstancePool[$iCompanyFilingNo];

		$sSql = "SELECT * FROM company_filing WHERE cf_id=$iCompanyFilingNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oCompanyFiling = new CCompanyFiling($aRow);
		self::$aInstancePool[$iCompanyFilingNo] = $oCompanyFiling;
		return $oCompanyFiling;
	}
	
	public static function oGetCompanyFilingByCompanyNo($iComapnyNo=0)
	{
	
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM company_filing WHERE co_id=$iComapnyNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oCompanyFiling = new CCompanyFiling($aRow);
		if(is_null(self::$aInstancePool[$oCompanyFiling->iCompanyFilingNo]))
			$aInstancePool[$oCompanyFiling->iCompanyFilingNo]=$oCompanyFiling;
		return $oCompanyFiling;
	
	
	}
	

	
	
	
	





}
?>