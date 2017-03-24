<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CPageType.php');

class CPage extends CGalaxyClass
{
	private $iPageNo;
	public $sName;
	public $sUrl;
	public $iTypeNo;
	public $iDNSNo;
	public $sSha1;
	private $__aBlockSha1=array();	//array of sha1 in blocks
	private $__oType;

	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetPage($iPageNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_page WHERE page_no=$iPageNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCPage = new CPage($aRow);
		return $oCPage;
	}

	static public function aAllPage($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_page";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllPage = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllPage[] = new CPage($aRow);
		}
		return $aAllPage;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CPage: __construct failed, require an array");
		//initialize vital member
		$this->iPageNo = $multiData['page_no'];
		$this->sName = $multiData['page_name'];
		$this->sUrl = $multiData['page_url'];
		$this->iTypeNo = $multiData['page_type_no'];
		$this->iDNSNo = $multiData['dns_no'];
		$this->sSha1 = $multiData['page_sha1'];

		//galaxy class memeber
		$this->bStatus = $multiData['page_status'];
		$this->sCreateTime = $multiData['page_createtime'];
		$this->sModifiedTime = $multiData['page_modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

	public function oType(){
		if(empty($this->__oType))
			$this->__oType = CPageType::oGetType($this->iTypeNo);
		return $this->__oType;
	}

	public function aBlockSha1(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aBlockSha1)){
			$sSql = "SELECT * FROM galaxy_page_sha1 WHERE page_no = {$this->iPageNo}";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				
			}
		}
		return $this->__aBlockSha1;
	}
}
?>