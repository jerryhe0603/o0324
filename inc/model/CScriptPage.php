<?php
include_once('../inc/model/CGalaxyClass.php');

class CScriptPage extends CGalaxyClass
{
	
	private $iScriptPageNo;
	public $iScriptNo;
	public $iPageNo;
	public $sUrl;

	public $iOrder;
	public $sSet;

	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetPage($iScriptPageNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_script_page WHERE script_page_no=$iScriptPageNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oPage = new CScriptPage($aRow);
		return $oPage;
	}

	static public function aAllPage($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_script_page";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllPage = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllPage[] = new CScriptPage($aRow);
		}
		return $aAllPage;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CScriptPage: __construct failed, require an array");
		//initialize vital member
		$this->iScriptPageNo = $multiData['script_page_no'];
		$this->iSctiptNo = $multiData['script_no'];
		$this->iPageNo = $multiData['page_no'];
		$this->sUrl = $multiData['script_page_url'];
		$this->iOrder = $multiData['script_page_order'];
		$this->sSet = $multiData['script_page_set'];
		//galaxy class memeber
		$this->bStatus =  $multiData['script_page_status'];
		$this->sCreateTime = $multiData['script_page_createtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }
}
?>