<?php
include_once('../inc/model/CGalaxyClass.php');

class CDept extends CGalaxyClass
{
	//vital member
	private $iDeptNo;	//dept_no in genesis.dept, read only after construct
	//optional member
	public $sName;
	public $sDesc;
	public $bStatus;
	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCDept by certain dept_no
	*/
	static public function oGetDept($iDeptNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iDeptNo]))
			return self::$aInstancePool[$iDeptNo];

		$sSql = "SELECT * FROM dept WHERE dept_no = '$iDeptNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCDept = new CDept($aRow);
		self::$aInstancePool[$iDeptNo] = $oCDept;
		return $oCDept;
	}

	/*
		get all dept in an array
		if $sSearchSql is given, query only match depts
	*/
	static public function aAllDept($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM dept";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllDept = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['dept_no']])){
				self::$aInstancePool[$aRow['dept_no']] = new CDept($aRow);
			}
			$aAllDept[] = self::$aInstancePool[$aRow['dept_no']];
		}
		return $aAllDept;
	}

	/*
		get count of dept which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(dept_no) as total FROM dept";
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

	/*
		constructor of $oCDept
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CDept: __construct failed, require an array");
		//initialize vital member
		$this->iDeptNo = $multiData['dept_no'];
		$this->sName = $multiData['name'];
		$this->sDesc = $multiData['desc'];
		$this->bStatus = $multiData['status'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }
}
?>