<?php

class CUserCompany extends CUser
{
	
	protected static $sDBName = 'USER';


	
	
	public function __get($varName)
    {
        return $this->$varName;
    }

	
	
	
	/*
		constructor 
	*/
	public function __construct($multiData){
		if(!is_array($multiData))
			throw new Exception("CUserCompany: __construct failed, require an array");
		//initialize inherited member
		parent::__construct($multiData);
		//initialize custom member
		$this->co_id = $multiData['co_id'];
		$this->co_title = $multiData['co_title'];
		$this->co_call = $multiData['co_call'];
		$this->note = $multiData['note'];
		$this->status = $multiData['status'];
	}
	
	
	
	
	/*
		get $oCUserIwant by certain user_no
		example: $oCUserIwant = CUserCompany::oGetUser(16);
	*/
	public static function oGetUser($user_no){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM `galaxy_user_company` AS a LEFT JOIN `galaxy_user` AS b ON a.user_no=b.user_no WHERE a.user_no=$user_no";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$oCUserCompany = new CUserCompany($aRow);
		return $oCUserCompany;
	}

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUserCompany::aAlluser('galaxy_user.addr_id=12')
		example2: $aCUsers = CUserCompany::aAlluser('galaxy_user_oldcat.mr_id=1')
		example3: $aCUsers = CUserCompany::aAlluser('','ORDER BY galaxy_user.createtime DESC LIMIT 0,10')
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	static public function aAllUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		$sSql = "SELECT * FROM `galaxy_user_company` LEFT JOIN `galaxy_user` ON galaxy_user_company.user_no=galaxy_user.user_no";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllUser[] = new CUserCompany($aRow);
		}
		return $aAllUser;
	}



}
?>