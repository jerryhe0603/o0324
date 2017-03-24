<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CElementMapping.php');

class CElement extends CGalaxyClass
{	
	private $iElementNo;
	public $iElementMappingNo;
	public $sTagName;
	public $sTagType;
	private $__oElementMapping;

	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetElement($iElementNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_element WHERE element_no=$iElementNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oElement = new CElement($aRow);
		return $oElement;
	}

	static public function aAllElement($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_element";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllElement = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllElement[] = new CElement($aRow);
		}
		return $aAllElement;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CElement: __construct failed, require an array");
		//initialize vital member
		$this->iElementNo = $multiData['element_no'];
		$this->sTagName = $multiData['element_tag_name'];
		$this->sTagType = $multiData['element_tagtype'];
		$this->iElementMappingNo = $multiData['element_mapping_no'];
		//galaxy class memeber
		$this->bStatus =  $multiData['element_status'];
		$this->sCreateTime = $multiData['element_createtime'];
		$this->sModifiedTime = $multiData['element_modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    public function oElementMapping(){
    	if(is_null($this->__oElementMapping)){
    		$this->__oElementMapping = CElementMapping::oGetElementMapping($this->iElementMappingNo);
    	}
    	return $this->__oElementMapping;
    }
}
?>