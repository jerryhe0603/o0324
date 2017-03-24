<?php

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');

class CCompanyOldcat extends CGalaxyClass
{

	private $iCompanyNo;
	public $sLevel;
	public $iIndustryNo;
	public $sCompanyNote;
	public $iVisitStatus;
	public $iManagementNo;
	public $iCallUserNo;
	public $iImportUserNo;
	public $iCompanyType;
	public $bQuota;

	//instance pool
	static public $aInstancePool = array();

	protected static $sDBName = 'COMPANY';

	public function __get($varName)
	{
	
		return $this->$varName;
	
	}
	public static $aCompanyType=array(
		0=>"直客",
		1=>"代理商",
		2=>"銀河內部",
		4=>"其他",
		5=>"好東西專案"
	);
	
	public function __construct($multData)
	{
		parent::__construct($multData);
		
		if(!is_array($multData))
			throw new Exception("CCompanyOldcat: __construct failed, require an array");
			
		$this->iCompanyNo = isset($multData['co_id'])?$multData['co_id']:0;
		$this->sLevel = isset($multData['level'])?$multData['level']:'';
		$this->iIndustryNo = isset($multData['ind_id'])?$multData['ind_id']:0;
		$this->sCompanyNote = isset($multData['co_note'])?$multData['co_note']:'';
		$this->iVisitStatus = isset($multData['visit_status'])?$multData['visit_status']:0;
		$this->iManagementNo = isset($multData['mm_id'])?$multData['mm_id']:0;
		$this->iCallUserNo = isset($multData['call_user_no'])?$multData['call_user_no']:0;
		$this->iImportUserNo = isset($multData['import_user_no'])?$multData['import_user_no']:0;
		$this->iCompanyType = isset($multData['co_type'])?$multData['co_type']:0;
		$this->bQuota = isset($multData['is_quota'])?$multData['is_quota']:false;
	}
	
	
	
	public static function oGetCompanyOldcat($iCompanyNo=0){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(isset(self::$aInstancePool[$iCompanyNo]))
			return self::$aInstancePool[$iCompanyNo];

		$sSql = "SELECT * FROM company_oldcat WHERE co_id=$iCompanyNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oCompanyOldcat = new CCompanyOldcat($aRow);
		self::$aInstancePool[$iCompanyNo]=$oCompanyOldcat;
		return $oCompanyOldcat;
	}	
}
?>