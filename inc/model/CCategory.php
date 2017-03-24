<?php

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

include_once($sNowPath.'/inc/model/CGalaxyClass.php');

class CCategory extends CGalaxyClass
{
	static private $aCCategoryPool = array();

	private $iCategoryNo;
	public $sName;
	public $iParentCategoryNo;

	public $aChildCategory =array();
	public $aCRule = array();

	//database setting
	static protected $sDBName = 'USER';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCCategory by certain category_no
	*/
	static public function oGetCategory($iCategoryNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_rule_category WHERE category_no = $iCategoryNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCCategory = new CCategory($aRow);
		self::$aInstancePool[$iCategoryNo] = $oCCategory;
		return $oCCategory;
	}

	/*
		get all category in an array
		if $sSearchSql is given, query only match categories
		CAUTION: this function returns a map , instead of array(for tree recursive function)
	*/
	static public function aAllCategory($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_rule_category";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCategory = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['category_no']])){
				self::$aInstancePool[$aRow['category_no']] = new CCategory($aRow);
			}
			$aAllCategory[$aRow['category_no']] = self::$aInstancePool[$aRow['category_no']];
		}
		return $aAllCategory;
	}

	/*
		get count of category which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(category_no) as total FROM galaxy_rule_category";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}	

	public function __construct($multiData){
		parent::__construct($multiData);
		$this->iCategoryNo = $multiData['category_no'];
		$this->sName = $multiData['category_name'];
		$this->iParentCategoryNo = $multiData['parent_category_no'];
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }
}
?>