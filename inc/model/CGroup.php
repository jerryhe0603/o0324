<?php

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

include_once($sNowPath.'/inc/model/CGalaxyClass.php');
include_once($sNowPath.'/inc/model/CUser.php');
include_once($sNowPath.'/inc/model/CRule.php');

class CGroup extends CGalaxyClass
{
	//vital member
	private $iGroupNo;	//group_no in galaxy_user.galaxy_group, read only after construct
	//optional member
	public $sName;
	public $sDesc;
	public $sColor;
	public $sSystemName;
	//members that set only when corresponding function is called
	protected $__aCUser = array();
	protected $__aCRule = array();	//CRule
	//database setting
	static protected $sDBName = 'USER';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCGroup by certain group_no
	*/
	static public function oGetGroup($iGroupNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_group WHERE group_no = '$iGroupNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCGroup = new CGroup($aRow);
		self::$aInstancePool[$iGroupNo] = $oCGroup;

		return $oCGroup;
	}

	/*
		get all group in an array
		if $sSearchSql is given, query only match groups
		example:$aCGroups = CGroup::aAllGroup("ststem_name='創世紀'");
	*/
	static public function aAllGroup($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllGroup = array();
		$sSql = "SELECT * FROM galaxy_group";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['group_no']])){
				self::$aInstancePool[$aRow['group_no']] = new CGroup($aRow);
			}
			$aAllGroup[] = self::$aInstancePool[$aRow['group_no']];
		}
		return $aAllGroup;
	}

	/*
		get count of group which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(group_no) as total FROM galaxy_group";
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
		constructor of $oCGroup
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CGroup: __construct failed, require an array");
		//initialize vital member
		$this->iGroupNo = $multiData['group_no'];
		$this->sName = $multiData['group_name'];
		$this->sDesc = $multiData['group_desc'];
		$this->sColor = $multiData['group_color'];
		$this->sSystemName = $multiData['system_name'];
		//galaxy class memeber
		$this->bStatus = $multiData['group_status'];
		$this->sCreateTime = $multiData['createtime'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }

    /*
    	set & get user of this $oCGroup
    */
    public function aUser(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty($this->__aCUser)){
    		$sSql = "SELECT * FROM galaxy_group_user_rel WHERE group_no = {$this->iGroupNo}";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aCUser[$aRow['user_no']] = CUser::oGetUser($aRow['user_no']);
			}
    	}
    	return $this->__aCUser;
    }

    /*
    	set & get rules of this $oCGroup
    */
    public function aCRule(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty($this->__aCRule)){
    		$sSql = "SELECT * FROM galaxy_group_rule_rel WHERE group_no = {$this->iGroupNo}";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aCRule[$aRow['rule_no']] = CRule::oGetRule($aRow['rule_no']);
			}
    	}
    	return $this->__aCRule;
    }

	/*
		update group data in this CGroup to galaxy_user DB
		if you want to update group data in DB, get a CGroup of that group, change member value, and call this function
		$oCGroup->iGroupNo is not changeable
	*/
	public function vUpdateGroup(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'group_name'=>$this->sName, 
							'group_desc'=>$this->sDesc,
							'group_color'=>$this->sColor,
							'system_name'=>$this->sSystemName,
							'group_status'=>$this->bStatus
							);
		try{
			$oDB->vBegin();
			$oDB->sUpdate("`galaxy_group`", array_keys($aValues), array_values($aValues), "`group_no` = {$this->iGroupNo}");
			//$this->vUpdateGroupUser();	//update users
			$this->vUpdateGroupRule();	//update rules
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_group",$this->iGroupNo,'group','edit');
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CGroup->vUpdateGroup: ".$e->getMessage());
		}
	}

	/*
		update group user in this CGroup to galaxy_user DB
		if you want to update group user in DB, get a CGroup of that group, call vSetUsers(), and call this function
	*/
	public function vUpdateGroupUser(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		try{
			$oDB->vBegin();
			$oDB->vDelete('galaxy_group_user_rel',"`group_no`={$this->iGroupNo}");
			foreach ($this->__aCUser as $oCUser) {
				if($oCUser->bStatus==='0')
					continue;	//does not insert deprecated user
				$aUsrValues = array(	'group_no'=>$this->iGroupNo,
										'user_no'=>$oCUser->iUserNo
										);
				$oDB->sInsert('galaxy_group_user_rel',array_keys($aUsrValues),array_values($aUsrValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_group",$this->iGroupNo,'group','edituser');
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CGroup->vUpdateGroupUser: ".$e->getMessage());
		}
	}

	/*
		update group rule in this CGroup to galaxy_user DB
		if you want to update group rule in DB, get a CGroup of that group, call vSetRules(), and call this function
	*/
	public function vUpdateGroupRule(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		try{
			$oDB->vBegin();
			$oDB->vDelete('galaxy_group_rule_rel',"`group_no`={$this->iGroupNo}");
			foreach ($this->__aCRule as $oCRule) {
				$aRulValues = array(	'group_no'=>$this->iGroupNo,
										'rule_no'=>$oCRule->iRuleNo
										);
				$oDB->sInsert('galaxy_group_rule_rel',array_keys($aRulValues),array_values($aRulValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_group",$this->iGroupNo,'group','editrule');
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CGroup->vUpdateGroupRule: ".$e->getMessage());
		}
	}

	/*
		add $oCGroup data to galaxy_user DB
		if you want to create a new group in DB, new a CGroup and call this function
		return insert id(group_no), user may find this new CGroup by group_no
	*/
	public function iAddGroup(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'group_name'=>$this->sName, 
							'group_desc'=>$this->sDesc,
							'group_color'=>$this->sColor,
							'system_name'=>$this->sSystemName,
							'group_status'=>$this->bStatus
							);
		try{
			$oDB->vBegin();
			$oDB->sInsert("`galaxy_group`", array_keys($aValues), array_values($aValues));
			$this->iGroupNo = $oDB->iGetInsertId();
			//$this->vUpdateGroupUser();	//update users
			$this->vUpdateGroupRule();	//update rules
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_group",$this->iGroupNo,'group','add');
			return $this->iGroupNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CGroup->iAddGroup: ".$e->getMessage());
		}
	}

	/*
		activate this oCGroup
	*/
	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		
		if($this->bStatus==='1')
			$this->bStatus='0';
		else
			$this->bStatus='1';
		$aValues = array('group_status'=>$this->bStatus);
		try{
			$oDB->sUpdate("`galaxy_group`", array_keys($aValues), array_values($aValues), "`group_no` = {$this->iGroupNo}");
			$oCurrentUser->vAddUserLog("galaxy_group",$this->iGroupNo,'group','activate');
		}catch (Exception $e){
			throw new Exception("CGroup->vActivate: ".$e->getMessage());
		}
	}

	/*
		set users by array(user_no)
	*/
	public function vSetUsers($aUserNos){
		if(!is_array($aUserNos))
			return;
		$this->__aCUser = null;	//clear all user
		foreach ($aUserNos as $iUserNo) {
			$this->__aCUser[$iUserNo] = CUser::oGetUser($iUserNo);
		}
	}

	/*
		set rules by array(rule_no)
	*/
	public function vSetRules($aRuleNos){
		if(!is_array($aRuleNos))
			return;
		$this->__aCRule = array();	//clear all rule
		foreach ($aRuleNos as $iRuleNo) {
			$this->__aCRule[$iRuleNo] = new CRule(array('rule_no'=>$iRuleNo));
		}
	}
}
?>