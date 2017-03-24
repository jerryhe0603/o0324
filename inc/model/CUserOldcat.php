<?php
include_once('../inc/model/CUser.php');

class CUserOldcat extends CUser
{
	public $iMrId;

	/*
		get $oCUserOldcat by certain user_no
		example: $oCUserOldcat = CUserOldcat::oGetUser(45);
	*/
	static public function oGetUser($iUserNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM `galaxy_user_oldcat` AS a LEFT JOIN `galaxy_user` AS b ON a.user_no=b.user_no WHERE a.user_no=$iUserNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oCUserOldcat = new CUserOldcat($aRow);
		return $oCUserOldcat;
	}

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUserOldcat::aAlluser('galaxy_user.addr_id=12')
		example2: $aCUsers = CUserOldcat::aAlluser('galaxy_user_oldcat.mr_id=1')
		example3: $aCUsers = CUSerOldcat::aAlluser('','ORDER BY galaxy_user.createtime DESC LIMIT 0,10')
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	static public function aAllUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		$sSql = "SELECT * FROM `galaxy_user_oldcat` LEFT JOIN `galaxy_user` ON galaxy_user_oldcat.user_no=galaxy_user.user_no";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllUser[] = new CUserOldcat($aRow);
		}
		return $aAllUser;
	}

	/*
		get count of oldcat user who match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(galaxy_user_oldcat.user_no) as total FROM galaxy_user_oldcat LEFT JOIN galaxy_user ON galaxy_user_oldcat.user_no=galaxy_user.user_no";
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
		constructor of $oCUserOldcat
	*/
	public function __construct($multiData){
		if(!is_array($multiData))
			throw new Exception("CUserOldcat: __construct failed, require an array");
		//initialize inherited member
		parent::__construct($multiData);
		//initialize custom member
		$this->iMrId = $multiData['mr_id'];
	}

	/*
		update oldcat user data in this CUserOldcat to galaxy_user DB
		if you want to update user data in DB, get a CUserOldcat of that user, change member value, and call this function
		$oCUser->iUserNo is not changeable
	*/
	public function vUpdateUser(){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			parent::vUpdateUser();
			$aValues = array(	'mr_id'=>$this->iMrId,
								'status'=>$this->bStatus
								);
			$oDB->sUpdate("`galaxy_user_oldcat`", array_keys($aValues), array_values($aValues), "`user_no` = {$this->iUserNo}");
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserOldcat->vUpdateUser: ".$e->getMessage());
		}
	}

	/*
		add $oCUserOldcat data to galaxy_user DB
		if you want to create a new iwant user in DB, new a CUserIwant and call this function
		caution: user_account must not exist in galaxy_user, if you want to link exist user to oldcat, use another way
	*/
	public function iAddUser(){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			parent::iAddUser();
			$this->vAddToOldcat();
			$oDB->vCommit();
			return $this->iUserNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserOldcat->iAddUser: ".$e->getMessage());
		}
	}

	/*
		link exist user to oldcat
		add data into galaxy_user_oldcat
		if you want to add a exist galaxy user into oldcat, get a CUserOldcat of that user, change iMrId, and call this function
	*/
	public function vAddToOldcat(){
		$oDB = self::oDB(self::$sDBName);
		if(!is_null(self::oGetUser($this->iUserNo)))
			throw new Exception("CUserOldcat->vAddToOldcat: user already exist in galaxy_user_oldcat");
		$aValues = array(	'user_no'=>$this->iUserNo,
							'mr_id'=>$this->iMrId,
							'status'=>$this->bStatus
							);
		try{
			$oDB->sInsert("`galaxy_user_oldcat`", array_keys($aValues), array_values($aValues));
		}catch (Exception $e){
			throw new Exception("CUserOldcat->vAddToOldcat: ".$e->getMessage());
		}
	}
	
	
	
	/**
	* @desc 抓取有效業務資料
	* @created 2013/07/05
	*/
	public static function aGetActiveSaleUserData() {
		$oDB = self::oDB(self::$sDBName);
		$aRtn = array(); // 公司業務
		$iDbq = $oDB->iQuery("SELECT a.* FROM galaxy_user AS a left join galaxy_user_oldcat AS b ON a.user_no=b.user_no WHERE b.mr_id in (4,5,10) AND a.status='1'");
		while($aRow = $oDB->aFetchArray($iDbq)) {
			$aRtn[] = new CUserOldcat($aRow);
		}
		return $aRtn;
	}
	
	
	
	
	
	
	
	
	
}
?>