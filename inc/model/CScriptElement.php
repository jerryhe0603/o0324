<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CElement.php');

class CScriptElement extends CGalaxyClass
{
	
	private $iScriptElementNo;
	public $iScriptNo;
	public $iScriptPageNo;
	public $iElementNo;
	public $iSource;
	public $iOrder;
	public $sAction;
	public $sArgs;
	public $bContinuity;
	public $sValue;
	public $sPathAbs;
	public $bPathContunuity;

	private $__oElement;

	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetElement($iScriptElementNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_script_element WHERE script_element_no=$iScriptElementNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oElement = new CScriptElement($aRow);
		return $oElement;
	}

	static public function aAllElement($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_script_element";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllElement = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllElement[] = new CScriptElement($aRow);
		}
		return $aAllElement;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CScriptElement: __construct failed, require an array");
		//initialize vital member
		$this->iScriptElementNo = $multiData['script_element_no'];
		$this->iScriptNo = $multiData['script_no'];
		$this->iScriptPageNo = $multiData['script_page_no'];
		$this->iElementNo = $multiData['element_no'];
		$this->iSource = $multiData['script_element_source'];
		$this->iOrder = $multiData['script_element_order'];
		$this->sAction = $multiData['script_element_action'];
		$this->sArgs = $multiData['script_element_args'];
		$this->bContinuity = $multiData['script_element_continuity'];
		$this->sValue = $multiData['script_element_val'];
		$this->sPathAbs = $multiData['element_path_abs'];
		$this->bPathContunuity = $multiData['element_path_contuinity'];
		//galaxy class memeber
		$this->bStatus =  $multiData['script_element_status'];
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    public function oElement(){
    	if(is_null($this->__oElement)){
    		$this->__oElement = CElement::oGetElement($this->iElementNo);
    	}
    	return $this->__oElement;
    }
}
?>