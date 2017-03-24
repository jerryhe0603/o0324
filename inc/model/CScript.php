<?php
include_once('../inc/model/CGalaxyClass.php');

class CScript extends CGalaxyClass
{
	static private $aScriptTypePool = array();

	private $iScriptNo;
	public $sName;
	public $sDesc;
	public $bShare;
	public $bMode;
	public $iTypeNo;
	public $iSiteNo;
	public $iBoardNo;
	public $sSet;
	public $iRunTime;


	private $__aScriptElement;
	public $iStatus;	//not bStatus

	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static public $aInstancePool = array();

	static public function oGetScript($iScriptNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iScriptNo]))
			return self::$aInstancePool[$iScriptNo];

		$sSql = "SELECT * FROM galaxy_script WHERE script_no=$iScriptNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCScript = new CScript($aRow);
		self::$aInstancePool[$iScriptNo] = $oCScript;

		return $oCScript;
	}

	static public function aAllScript($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_script";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCScript = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['script_no']])){
				self::$aInstancePool[$aRow['script_no']] = new CScript($aRow);
			}
			$aAllCScript[] = self::$aInstancePool[$aRow['script_no']];
		}
		return $aAllCScript;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CScript: __construct failed, require an array");
		//initialize vital member
		$this->iScriptNo = $multiData['script_no'];
		$this->sName = $multiData['script_name'];
		$this->sDesc = $multiData['script_desc'];
		$this->bShare = $multiData['script_mode'];
		$this->bMode = $multiData['script_mode'];
		$this->iTypeNo = $multiData['script_type_no'];
		$this->iSiteNo = $multiData['site_no'];
		$this->iBoardNo = $multiData['board_no'];
		$this->sSet = $multiData['script_set'];
		$this->iRunTime = $multiData['runtime'];
		$this->iStatus = $multiData['script_status'];
		//galaxy class memeber
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    public function aScritpElement(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty($this->__aScriptElement)){

    	}
    	return $this->__aScriptElement;
    }
}
?>