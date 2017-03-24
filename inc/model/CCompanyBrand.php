<?php

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');

class CCompanyBrand extends CGalaxyClass
{

	private $iBrandNo;
	public $sName;
	public $iCompanyId;
	public $iStatus;

	//instance pool
	static public $aInstancePool = array();

	protected static $sDBName = 'COMPANY';

	public function __get($varName)	{
		return $this->$varName;
	}

	
	public function __construct($multData){
		parent::__construct($multData);
		
		if(!is_array($multData))
			throw new Exception("CCompanyBrand: __construct failed, require an array");
			
		$this->iBrandNo = $multData['cb_id'];
		$this->sName = $multData['cb_name'];
		$this->iCompanyId = $multData['co_id'];
		$this->iStatus = $multData['flag'];
	}
	

	public static function oGetCompanyBrand($iBrandNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iBrandNo]))
			return self::$aInstancePool[$iBrandNo];

		$sSql="SELECT * FROM `company_brand` WHERE `cb_id`='$iBrandNo' ";
		$iDbq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCCompanyBrand = new CCompanyBrand($aRow);
		self::$aInstancePool[$iBrandNo]=$oCCompanyBrand;

		return $oCCompanyBrand;
	}


	public static function aGetCompanyBrand($iCompanyId){
		$aCompanyBrand = array();
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM company_brand WHERE co_id = '$iCompanyId'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(!isset(self::$aInstancePool[$aRow['cb_id']])){
				self::$aInstancePool[$aRow['cb_id']] = new CCompanyBrand($aRow);
			}
			$aCompanyBrand[] = self::$aInstancePool[$aRow['cb_id']];
		}
		return $aCompanyBrand;
	}
	
	
	/**
	 *  @desc 取得某公司員工的品牌
	 *  @return array
	 */
	public static function aGetCompanyBrandByComapnyNoUserNo($iCompanyId=0,$iUserNo=0){

		$aCompanyBrand = array();
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT * FROM company_brand AS a INNER JOIN contact_brand_rel AS b ON a.cb_id=b.cb_id WHERE a.co_id = $iCompanyId AND b.user_no=$iUserNo");
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			// if(is_null(self::$aInstancePool[$aRow['cb_id']])){
			if(!isset(self::$aInstancePool[$aRow['cb_id']])){
				self::$aInstancePool[$aRow['cb_id']] = new CCompanyBrand($aRow);
			}
			$aCompanyBrand[] = self::$aInstancePool[$aRow['cb_id']];
		}
		return $aCompanyBrand;
	
	}
	
	
	/**
	 *  @desc 取的某公司員工的品牌
	 *  @return string
	 */
	public static function sGetCompanyBrandNameByCompanyNoUserNo($iCompanyId=0,$iUserNo=0,$sComma='<br>'){
		$aCompanyBrandName = array();
		$aCompanyBrand = self::aGetCompanyBrandByComapnyNoUserNo($iCompanyId,$iUserNo);
		if (count($aCompanyBrand))
		{
			foreach($aCompanyBrand as $oComapnyBrand)
			{
				if ($oComapnyBrand->iStatus==0) continue;
				if (in_array($oComapnyBrand->sName,$aCompanyBrandName)) continue;
				array_push($aCompanyBrandName,$oComapnyBrand->sName);
			}
		}
		return implode($sComma,$aCompanyBrandName);
	}


}
?>