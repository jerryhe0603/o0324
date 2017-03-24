<?php

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

include_once($sNowPath.'/inc/model/CGalaxyClass.php');
include_once($sNowPath.'/inc/model/CGroup.php');
include_once($sNowPath.'/inc/model/CRule.php');
include_once($sNowPath.'/inc/model/CUserLog.php');

class CUser extends CGalaxyClass
{
	//vital member
	protected $iUserNo;	//user_no in galaxy_user.galaxy_user, read only after construct
	public $sName;	//user name
	protected $sAccount;	//account to authorized user, read only after construct
	//optional memeber
	private $sPassword;	//password to authorized user, sha1 in hex
	public $sEmail;	//email address
	public $sTel;	//telephone #
	public $sFax;	//fax #
	public $sMobile;	//mobile phone #
	public $sAddrId;	//post #
	public $sAddr;	//address
	//members that set only when corresponding function is called
	protected $__aCGroup = array();
	protected $__aCRule = array();
	//database setting
	static protected $sDBName = 'USER';

	//instance pool
	static public $aInstancePool = array();


	/*
		constructor of $oCUser
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CUser: __construct failed, require an array");
		//initialize vital member
		if(isset($multiData['user_no']))
			$this->iUserNo = $multiData['user_no'];
		else
			$this->iUserNo = 0;
		$this->sName = $multiData['user_name'];
		$this->sAccount = $multiData['user_account'];

		if(!isset($this->iUserNo) || !isset($this->sName) || !isset($this->sAccount)){
			$sError = '';
			if (!$this->iUserNo) $sError .= "No user_no";
			if (!$this->sName) $sError .= $this->iUserNo . " no Name.";
			if (!$this->sAccount) $sError .= $this->iUserNo . " no Account.";
			throw new Exception("CUser: __construct failed, lack of vital member, ".$sError);
		}

		//initialize optional member
		$this->sPassword = $multiData['user_password'];
		$this->sEmail    = $multiData['user_email'];
		$this->sTel      = $multiData['user_tel'];
		$this->sFax      = $multiData['user_fax'];
		$this->sMobile   = $multiData['user_mobile'];
		$this->sAddrId   = $multiData['addr_id'];
		$this->sAddr     = $multiData['user_addr'];
		$this->user_name = $multiData['user_name'];
		//galaxy class memeber
		$this->bStatus     = $multiData['status'];
		$this->sCreateTime = $multiData['createtime'];

	}

	public function __destruct(){
		unset($this->__aCGroup);
		unset($this->__aCRule);
	}


	//php default function, let private member become read-only class member for others
    public function __get($varName){
        return $this->$varName;
    }


	//static functions, most are used to find&get $oCUser
	/*
		get $oCUser by certain user_no
	*/
	static public function oGetUser($iUserNo){
		$oDB = self::oDB(self::$sDBName);
		//if already queryed
		if(isset(self::$aInstancePool[$iUserNo]))
			return self::$aInstancePool[$iUserNo];

		$sSql = "SELECT * FROM galaxy_user WHERE user_no=$iUserNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;

		$oCUser = new CUser($aRow);
		self::$aInstancePool[$iUserNo] = $oCUser;
		return $oCUser;
	}

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUser::aAlluser('addr_id=12')
		example3: $aCUsers = CUSer::aAlluser('','ORDER BY createtime DESC LIMIT 0,10')
		CAUTION: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	static public function aAllUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		$sSql = "SELECT * FROM `galaxy_user`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(!isset(self::$aInstancePool[$aRow['user_no']]))
				self::$aInstancePool[$aRow['user_no']] = new CUser($aRow);
			$aAllUser[] = self::$aInstancePool[$aRow['user_no']];
		}
		return $aAllUser;
	}

	/*
		get all group user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUser::aGroupUser('galaxy_group_user_rel.group_no=3')
		example2: $aCUsers = CUser::aGroupUser('galaxy_group_user_rel.group_no IN (3,26)')
		example3: $aCUsers = CUser::aGroupUser('galaxy_group_user_rel.group_no=3','ORDER BY galaxy_user.createtime DESC LIMIT 0,10')
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	static public function aGroupUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();

		$sSql = "SELECT * FROM `galaxy_user` INNER JOIN `galaxy_group_user_rel` ON galaxy_user.user_no=galaxy_group_user_rel.user_no";
		if ($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)) {
			$aAllUser[] = new CUser($aRow);
		}
		return $aAllUser;
	}

	/*
		get count of user who match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(user_no) as total FROM galaxy_user";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}

	/*
		login $oCurrentUser, and set in $session
	*/
	static public function vLogin($sUserAccount,$sUserPassword){
		$session = self::$session;

		$oCUser = self::oFindUserByAcc($sUserAccount);	//find user by acc
		if(!isset($oCUser))
			throw new Exception("CUser: not such user account($sUserAccount)");
		if($oCUser->bStatus=='0')
			throw new Exception("CUser: user account($sUserAccount) is deprecated");

		$oCUser->vAuthorize($sUserPassword);	//check the password

		//set oCurrentUser in session
		$session->sess_unset();
		$session->set('oCurrentUser', 	$oCUser);

		//for genesis & queue
		$session->set('user_name',$oCUser->sName);
		$session->set('user_no',$oCUser->iUserNo);
		$session->set('user_password',$sUserPassword);
	}

	/*
		find & get $oCUser by user_account
	*/
	static public function oFindUserByAcc($sUserAccount){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_user WHERE `user_account`='$sUserAccount'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;

		// if(!is_null(self::$aInstancePool[$aRow['user_no']]))
		if(isset(self::$aInstancePool[$aRow['user_no']]))
			return self::$aInstancePool[$aRow['user_no']];

		$oCUser = new CUser($aRow);
		self::$aInstancePool[$aRow['user_no']] = $oCUser;
		return $oCUser;
	}

	/*
		find & get $0CUser by user_account without @domain.name
	*/
	static public function oFindUserByShortAcc($sShortAccount){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_user WHERE `user_account` LIKE '$sShortAccount%'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;

		// if(!is_null(self::$aInstancePool[$aRow['user_no']]))
		if(isset(self::$aInstancePool[$aRow['user_no']]))
			return self::$aInstancePool[$aRow['user_no']];

		$oCUser = new CUser($aRow);
		self::$aInstancePool[$aRow['user_no']] = $oCUser;
		return $oCUser;
	}

	/*
		find & get $oCUser by any field and any value targeted
	*/
	static public function oFindUserByField($sField,$sValue){
		$oDB = self::oDB(self::$sDBName);
		$sSql = $sSql = "SELECT * FROM galaxy_user WHERE `$sField`=$sValue";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;

		// if(!is_null(self::$aInstancePool[$aRow['user_no']]))
		if(isset(self::$aInstancePool[$aRow['user_no']]))
			return self::$aInstancePool[$aRow['user_no']];

		$oCUser = new CUser($aRow);
		self::$aInstancePool[$iUserNo] = $oCUser;
		return $oCUser;
	}


	//class function
	/*
		public function to check if $oCUser match the password
	*/
	public function vAuthorize($sUserPassword){
		if(!isset($this->sPassword))
			throw new Exception("CUser->vAuthorize: this user has no password; not allowed to login!");
		if($this->sPassword!==md5($sUserPassword))
			throw new Exception("CUser->vAuthorize: user and password not match!");
	}

	/*
		set and get group of $oCUser
	*/
	public function aGroup(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCGroup)){
			$sSql = "SELECT * FROM galaxy_group_user_rel WHERE user_no = '{$this->iUserNo}'";
			$iDbq = $oDB->iQuery2($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)) {
				$this->__aCGroup[$aRow['group_no']] = CGroup::oGetGroup($aRow['group_no']);
			}
		}
		return $this->__aCGroup;
	}

	/*
		set and get rule of $oCUser
	*/
	public function aRule(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCRule)){
			$sGroups = array();
			foreach($this->aGroup() AS $oCGroup) {
				$aGroups[] = $oCGroup->iGroupNo;
			}

			if(empty($aGroups))
				return $this->__aCRule;

			$sGroups = implode(",", $aGroups);
			$sSql = "SELECT * FROM galaxy_group_rule_rel WHERE group_no in ($sGroups)";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)) {
				$this->__aCRule[$aRow['rule_no']] = CRule::oGetRule($aRow['rule_no']);
			}
		}
		return $this->__aCRule;
	}

	/*
		check if $oCUser is allow to access target page
		if not, throw exception
	*/
	public function IsPermit($sFunc,$sAction){
		if($sFunc=='' && $sAction=='')
			return;
		//get rule object by func and action
		$aRule = CRule::aAllRule("func='$sFunc' AND action='$sAction'");
		if(!empty($aRule)){
			$oDB = self::oDB(self::$sDBName);
			foreach ($aRule as $oRule) {
				$sSql = "SELECT * FROM galaxy_group_rule_rel AS a LEFT JOIN galaxy_group_user_rel AS b ON a.group_no=b.group_no WHERE a.rule_no='$oRule->iRuleNo' AND b.user_no='$this->iUserNo'";
				$iDbq = $oDB->iQuery($sSql);
				if($oDB->iNumRows()!=0)
					return;
			}
		}

		throw new Exception("CUser->IsPermit: current user is not allow to this page");
	}

	/*
		write log about what this user is doing
	*/
	public function vAddUserLog($sTableName="",$sTableId="",$sTableFunc="",$sTableAction=""){
		try{
			$aLogInfo = array(	'user_no'=>$this->iUserNo,
						'table_name'=>$sTableName,
						'table_id'=>$sTableId,
						'table_func'=>$sTableFunc,
						'table_action'=>$sTableAction
                       	);
			$oCUSerLog = new CUserLog($aLogInfo);
			$oCUSerLog->iAddLog();
		}catch (Exception $e){
			throw new Exception("CUser->vAddUserLog: ".$e->getMessage());
		}
	}

	/*
		update user data in this CUser to galaxy_user DB
		if you want to update user data in DB, get a CUser of that user, change member value, and call this function
		$oCUser->iUserNo & sAccount are not changeable
	*/
	public function vUpdateUser(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'user_name'=>$this->sName,
					'user_password'=>$this->sPassword,
					'user_email'=>$this->sEmail,
					'user_tel'=>$this->sTel,
					'user_fax'=>$this->sFax,
					'user_mobile'=>$this->sMobile,
					'addr_id'=>$this->sAddrId,
					'user_addr'=>$this->sAddr,
					'status'=>$this->bStatus
				);
		try{
			$oDB->vBegin();
			$oDB->sUpdate("galaxy_user", array_keys($aValues), array_values($aValues), "`user_no` = {$this->iUserNo}");
			$oDB->vDelete('galaxy_group_user_rel',"`user_no`={$this->iUserNo}");
			foreach ($this->__aCGroup as $oCGroup) {
				$aGpValues = array(	'group_no'=>$oCGroup->iGroupNo,
										'user_no'=>$this->iUserNo
										);
				$oDB->sInsert('galaxy_group_user_rel',array_keys($aGpValues),array_values($aGpValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_user",$this->iUserNo,'user','edit');
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUser->vUpdateUser: ".$e->getMessage());
		}
	}

	/*
		add $oCUser data to galaxy_user DB
		if you want to create a new user in DB, new a CUser and call this function
		if account already exist, throw exception
		return insert id(user_no), user may find this new CUser by user_no
	*/
	public function iAddUser(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$oCUser = self::oFindUserByAcc($this->sAccount);	//find user by acc
		if(isset($oCUser))
			throw new Exception("CUser->vAddUser: user account($this->sAccount) already exist");
		$aValues = array(	'user_name'=>$this->sName,
							'user_account'=>$this->sAccount,
							'user_password'=>$this->sPassword,
							'user_email'=>$this->sEmail,
							'user_tel'=>$this->sTel,
							'user_fax'=>$this->sFax,
							'user_mobile'=>$this->sMobile,
							'addr_id'=>$this->sAddrId,
							'user_addr'=>$this->sAddr,
							'createtime'=>date("Y-m-d H:i:s"),
							'status'=>$this->bStatus
							);
		try{
			$oDB->vBegin();
			$oDB->sInsert('galaxy_user',array_keys($aValues),array_values($aValues));
			$this->iUserNo = $oDB->iGetInsertId();
			foreach ($this->__aCGroup as $oCGroup) {
				$aGpValues = array(	'group_no'=>$oCGroup->iGroupNo,
										'user_no'=>$this->iUserNo
										);
				$oDB->sInsert('galaxy_group_user_rel',array_keys($aGpValues),array_values($aGpValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_user",$this->iUserNo,'user','add');
			return $this->iUserNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CUser->iAddUser: ".$e->getMessage());
		}
	}

	/*
		delete user data from DB by user_no, does not require a oCuser to
	*/

	/*
		activate this oCUser
	*/
	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		if($this->bStatus==='1')
			$this->bStatus='0';
		else
			$this->bStatus='1';
		$aValues = array('status'=>$this->bStatus);
		try{
			$oDB->sUpdate("galaxy_user", array_keys($aValues), array_values($aValues), "`user_no` = {$this->iUserNo}");
			$oCurrentUser->vAddUserLog("galaxy_user",$this->iUserNo,'user','activate');
		}catch (Exception $e){
			throw new Exception("CUser->vActivate: ".$e->getMessage());
		}
	}

	/*
		change password
	*/
	public function vChangePassword($sPassword){
		$this->sPassword = md5($sPassword);
	}

	/*
		set groups by array(group_no)
	*/
	public function vSetGroups($aGroupNos){
		if(!is_array($aGroupNos))
			return;
		$aGroupNos = array_unique($aGroupNos);
		$this->__aCGroup = array();	//clear all group
		foreach ($aGroupNos as $iGroupNo) {
			$this->__aCGroup[$iGroupNo] = new CGroup(array('group_no'=>$iGroupNo));
		}
	}
}
?>