<?php

include_once('../inc/model/CUser.php');
include_once('../inc/model/CDept.php');

class CUserIwant extends CUser
{
	public $iDeptNo;
	protected $__oCDept;
	//database setting
	static protected $sDBName = 'USER';

	/*
		constructor of $oCUserIwant
	*/
	public function __construct($multiData){
		if(!is_array($multiData))
			throw new Exception("CUserIwant: __construct failed, require an array");
		//initialize inherited member
		parent::__construct($multiData);
		//initialize custom member
		$this->iDeptNo = $multiData['dept_no'];
	}

	public function __get($varName){
        return $this->$varName;
    }


	/*
		get $oCUserIwant by certain user_no
		example: $oCUserIwant = CUserIwant::oGetUser(16);
	*/
	static public function oGetUser($iUserNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM `galaxy_user_iwant` AS a LEFT JOIN `galaxy_user` AS b ON a.user_no=b.user_no WHERE a.user_no=$iUserNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1 || !$aRow['user_no'])
			return null;

		$oCUserIwant = new CUserIwant($aRow);
		return $oCUserIwant;
	}

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUserIwant::aAlluser('galaxy_user.addr_id=12')
		example2: $aCUsers = CUserIwant::aAlluser('galaxy_user_iwant.dept_no=1')
		example3: $aCUsers = CUSerIwant::aAlluser('galaxy_user_iwant.dept_no=1','ORDER BY galaxy_user.createtime DESC LIMIT 0,10')
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	static public function aAllUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		$sSql = "SELECT * FROM `galaxy_user_iwant` LEFT JOIN `galaxy_user` ON galaxy_user_iwant.user_no=galaxy_user.user_no";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if (!$aRow['user_no']) continue;
			$aAllUser[] = new CUserIwant($aRow);
		}
		return $aAllUser;
	}

	/*
		get count of iwant user who match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(galaxy_user_iwant.user_no) as total FROM galaxy_user_iwant LEFT JOIN galaxy_user ON galaxy_user_iwant.user_no=galaxy_user.user_no";
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
		set & get CDept of $oCUserIwant
	*/
	public function oDept(){
		if(is_null($this->__oCDept))
			$this->__oCDept = CDept::oGetDept($this->iDeptNo);
		return $this->__oCDept;
	}

	/*
		update iwant user data in this CUserIwant to galaxy_user DB
		if you want to update user data in DB, get a CUserIwant of that user, change member value, and call this function
		$oCUser->iUserNo is not changeable
	*/
	public function vUpdateUser(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			parent::vUpdateUser();
			$aValues = array(	'dept_no'=>$this->iDeptNo,
								'status'=>$this->bStatus
								);
			$oDB->sUpdate("`galaxy_user_iwant`", array_keys($aValues), array_values($aValues), "`user_no` = {$this->iUserNo}");
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_user_iwant",$this->iUserNo,'user_iwant','edit');
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserIwant->vUpdateUser: ".$e->getMessage());
		}
	}

	/*
		add $oCUserIwant data to galaxy_user DB
		if you want to create a new iwant user in DB, new a CUserIwant and call this function
		caution: user_account must not exist in galaxy_user, if you want to link exist user to iwant, use vAddToIwant()
	*/
	public function iAddUser(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			parent::iAddUser();
			$this->vAddToIwant();
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_user_iwant",$this->iUserNo,'user_iwant','add');
			return $this->iUserNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserIwant->vAddUser: ".$e->getMessage());
		}
	}

	/*
		link exist user to iwant
		add data into galaxy_user_iwant
		if you want to add a exist galaxy user into iwant, get a CUserIwant of that user, change iDeptNo, and call this function
	*/
	public function vAddToIwant(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		if(!is_null(self::oGetUser($this->iUserNo)))
			throw new Exception("CUserIwant->vAddToIwant: user already exist in galaxy_user_iwant");
		$aValues = array(	'user_no'=>$this->iUserNo,
							'dept_no'=>$this->iDeptNo,
							'status'=>$this->bStatus
							);
		try{
			$oDB->sInsert("`galaxy_user_iwant`", array_keys($aValues), array_values($aValues));
		}catch (Exception $e){
			throw new Exception("CUserIwant->vAddToIwant: ".$e->getMessage());
		}
	}
}
?>