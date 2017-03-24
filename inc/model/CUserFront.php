<?php

include_once(PATH_ROOT.'/inc/model/CUser.php');

class CUserFront extends CUser
{
	public $iDeptNo;
	public $iCompanyNo;
	public $sTitle;

	private $__aProjectUuids;
	
	//database setting
	static protected $sDBName = 'USER';

	//instance pool
	static public $aInstancePool = array();


	public function __get($varName){
        if(method_exists($this,$varName))
        	return $this->$varName();
        return $this->$varName;
    }


	/*
		constructor of $oCUserFront
	*/
	public function __construct($multiData){
		if(!is_array($multiData))
			throw new Exception("CUserFront: __construct failed, require an array");
		//initialize inherited member
		parent::__construct($multiData);
		//initialize custom member
		$this->iDeptNo = isset($multiData['dept_no'])?$multiData['dept_no']:0;
		$this->iCompanyNo = isset($multiData['co_id'])?$multiData['co_id']:0;
		$this->sTitle = isset($multiData['co_title'])?$multiData['co_title']:'';
	}


	/*
		get $oCUserFront by certain user_no
		example: $oCUserFront = CUserFront::oGetUser(16);
	*/
	public static function oGetUser($iUserNo){
		//if already queryed
		if(isset(self::$aInstancePool[$iUserNo]))
			return self::$aInstancePool[$iUserNo];

		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT *,a.user_no as user_no FROM galaxy_user AS a LEFT JOIN galaxy_user_company AS b ON a.user_no=b.user_no LEFT JOIN galaxy_user_iwant AS c ON a.user_no=c.user_no WHERE a.user_no='$iUserNo'";

		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;

		$oCUserFront = new CUserFront($aRow);
		self::$aInstancePool[$iUserNo] = $oCUserFront;
		return $oCUserFront;
	}

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	static public function aAllUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		$sSql = "SELECT *,galaxy_user.user_no as user_no FROM `galaxy_user` LEFT JOIN `galaxy_user_company` ON galaxy_user.user_no=galaxy_user_company.user_no LEFT JOIN `galaxy_user_iwant` ON galaxy_user.user_no=galaxy_user_iwant.user_no";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(!isset(self::$aInstancePool[$aRow['user_no']]))
				self::$aInstancePool[$aRow['user_no']] = new CUserFront($aRow);
			$aAllUser[] = self::$aInstancePool[$aRow['user_no']];
		}
		return $aAllUser;
	}

	static public function aUserByProject($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		$sSql = "SELECT * FROM galaxy_user_project_rel WHERE project_no='$sProjectUuid'";
		$iDbq = $oDB->iQuery($sSql);
		while ($aRow = $oDB->aFetchAssoc($iDbq)) {
			$oUser = CUserFront::oGetUser($aRow['user_no']);
			if(isset($oUser))
				$aAllUser[] = $oUser;
		}
		return $aAllUser;
	}

	static public function vSetProjectUser($sProjectUuid,$aUserNos){
		$oDB = self::oDB(self::$sDBName);
		//clear project, then insert
		try{
			$oDB->vBegin();
			$oDB->vDelete("galaxy_user_project_rel","`project_no`='$sProjectUuid'");
			if(!empty($aUserNos)){
				foreach ($aUserNos as $iUserNo) {
					$aValue = array(
						"user_no" => $iUserNo,
						"project_no" => $sProjectUuid,
						"timeout" => '1970-01-01 00:00:00',
					);
					$oDB->sInsert("galaxy_user_project_rel",array_keys($aValue),array_values($aValue));
				}
			}
			
			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserFront::vSetProjectUser:".$e->getMessage());
		}
	}

	static public function vSetBizdevUser($sProjectUuid,$aUserNos){
		$oDB = self::oDB(self::$sDBName);
		//clear project, then insert
		try{
			$oDB->vBegin();
			$oDB->vDelete("galaxy_user_bizdev_rel","`project_no`='$sProjectUuid'");
			if(!empty($aUserNos)){
				foreach ($aUserNos as $iUserNo) {
					$aValue = array(	
						"user_no" => $iUserNo,
						"project_no" => $sProjectUuid,
						"timeout" => '1970-01-01 00:00:00',
					);
					$oDB->sInsert("galaxy_user_bizdev_rel",array_keys($aValue),array_values($aValue));
				}
			}
			
			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserFront::vSetBizdevUser:".$e->getMessage());
		}
	}

	static public function aUserByBizdev($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);
		$aBizdevUser = array();
		$sSql = "SELECT user_no FROM galaxy_user_bizdev_rel WHERE project_no='$sProjectUuid'";
		$iDbq = $oDB->iQuery($sSql);
		while ($aRow = $oDB->aFetchAssoc($iDbq)) {
			$aBizdevUser[] = $aRow;
		}
		return $aBizdevUser;
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

	public function aProjectUuids($bTimeout=false){
		if(!is_null($this->__aProjectUuids))
			return $this->__aProjectUuids;

		$this->__aProjectUuids = array();	//init

		$oDB = self::oDB(self::$sDBName);
		if(!$bTimeout)
			$sSql = "SELECT * FROM galaxy_user_project_rel WHERE user_no='$this->iUserNo'";
		else
			$sSql = "SELECT * FROM galaxy_user_project_rel WHERE user_no='$this->iUserNo' AND timeout>'".date("Y-m-d")."'";

		$iDbq = $oDB->iQuery($sSql);
		while ($aRow = $oDB->aFetchAssoc($iDbq)) {
			$this->__aProjectUuids[] = $aRow['project_no'];
		}

		return $this->__aProjectUuids;
	}

	public function bAllow($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT project_no FROM galaxy_user_project_rel WHERE user_no='$this->iUserNo' AND project_no='$sProjectUuid' AND timeout>'".date("Y-m-d")."'";
		$iDbq = $oDB->iQuery($sSql);
		if($oDB->iNumRows($iDbq)!=0)
			return true;
		else
			return false;
	}

	public function vAddProject($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);
		//select first, if exist, do nothing, then return
		$sSql = "SELECT project_no FROM galaxy_user_project_rel WHERE user_no='$this->iUserNo' AND project_no='$sProjectUuid'";
		$iDbq = $oDB->iQuery($sSql);
		if($oDB->iNumRows($iDbq)!=0)
			return;

		$aValue = array(	"user_no"=>$this->iUserNo,
							"project_no"=>$sProjectUuid
						);
		try{
			$oDB->sInsert("galaxy_user_project_rel",array_keys($aValue),array_values($aValue));
		}catch(Exception $e){
			throw new Exception("CUserFront->vAddProject:".$e->getMessage());
		}
	}

	public function vDelProject($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);
		//delete directly
		try{
			$oDB->vDelete("galaxy_user_project_rel","`user_no`='$this->iUserNo' AND `project_no`='$sProjectUuid'");
		}catch(Exception $e){
			throw new Exception("CUserFront->vDelProject:".$e->getMessage());
		}
	}

	public function vSetProject($aProjectUuids){
		$oDB = self::oDB(self::$sDBName);
		//clear project, then insert
		try{
			$oDB->vBegin();
			$oDB->vDelete("galaxy_user_project_rel","`user_no`='$this->iUserNo'");
			foreach ($aProjectUuids as $sProjectUuid) {
				$aValue = array(	"user_no"=>$this->iUserNo,
									"project_no"=>$sProjectUuid
								);
				$oDB->sInsert("galaxy_user_project_rel",array_keys($aValue),array_values($aValue));
			}

			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CUserFront->vSetProject:".$e->getMessage());
		}
	}
}
?>