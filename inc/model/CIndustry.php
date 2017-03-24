<?php

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');


class CIndustry extends CGalaxyClass 
{

	protected static $sDBName = 'OLDCAT';

	private $iIndustryNo;
	public $sName;
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
			throw new Exception("CIndustry: __construct failed, require an array");
			
		$this->iIndustryNo = $multData['ind_id'];
		$this->sName = $multData['ind_name'];
		$this->iStatus = $multData['flag'];
		$this->sCreateTime = $multData['created'];
		$this->sModifiedTime = $multData['modified'];
		$this->iManagementNo = $multData['mm_id'];
	
	}
	
	
	public static function oGetIndustry($iIndustryNo=0){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iIndustryNo]))
			return self::$aInstancePool[$iIndustryNo];

		$sSql = "SELECT * FROM industry WHERE ind_id=$iIndustryNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oIndustry = new CIndustry($aRow);
		self::$aInstancePool[$iIndustryNo]=$oIndustry;
		return $oIndustry;
	}	
	


	
	/**
	* @desc 抓取有效產業
	* @created 2013/07/04
	*/
	public static function aGetIndustryList() {
		$oDB = self::oDB(self::$sDBName);
		$aIndRows = array();
		$iDbq = $oDB->iQuery("SELECT ind_id,ind_name FROM industry WHERE flag=0");
		while($aRow=$oDB->aFetchAssoc($iDbq)){
			$aIndRows[] = new CIndustry($aRow);
		}
		return $aIndRows;
	}
	
	




}
?>