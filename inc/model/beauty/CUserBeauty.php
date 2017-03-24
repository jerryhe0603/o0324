<?php
include_once('../inc/model/CUser.php');
include_once('../inc/model/beauty/CBeautyDoc.php');

class CUserBeauty extends CUser
{
	public $iDailyDocCount;
	private $__iCurrentDocCount;

	//database setting
	static protected $sDBName = 'USER';

	static public $aInstancePool = array();

	static public function oGetUser($iUserNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT *,a.user_no FROM `galaxy_user` AS a LEFT JOIN `galaxy_user_beauty` AS b ON a.user_no=b.user_no WHERE a.user_no=$iUserNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$oCUserBeauty = new CUserBeauty($aRow);
		self::$aInstancePool[$iUserNo] = $oCUserBeauty;

		return $oCUserBeauty;
	}

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUserBeauty::aAllUser('galaxy_user.addr_id=12')
	*/
	static public function aAllUser($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllUser = array();
		//get who is in dept: beauty
		$sPreSql = "SELECT user_no FROM `galaxy_user_iwant` WHERE `dept_no`='3'";
		$iPreDbq = $oDB->iQuery($sPreSql);
		$aFilter = array();
		while($aPreRow = $oDB->aFetchAssoc($iPreDbq)){
			$aFilter[] = $aPreRow['user_no'];
		}
		$sFilter = "WHERE galaxy_user.user_no IN (";
		$sFilter .= implode(',', $aFilter);
		$sFilter .= ")";
	
		//select from galaxy_user and galaxy_user_beauty
		//use galaxy_user left join galaxy_user_beauty because user info may not be set yet in galaxy_user_beauty
		$sSql = "SELECT *,galaxy_user.user_no FROM `galaxy_user` LEFT JOIN `galaxy_user_beauty` ON galaxy_user.user_no=galaxy_user_beauty.user_no $sFilter";
		if($sSearchSql!=='')
			$sSql .= " AND $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['user_no']])){
				self::$aInstancePool[$aRow['user_no']] = new CUserBeauty($aRow);
			}
			$aAllUser[] = self::$aInstancePool[$aRow['user_no']];
		}
		return $aAllUser;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CUserBeauty: __construct failed, require an array");

		if(isset($multiData['daily_doc_count']))
			$this->iDailyDocCount = (int)$multiData['daily_doc_count'];
		else
			$this->iDailyDocCount = 0;

	}

    public function __get($varName)
    {
        return $this->$varName;
    }

    public function iCurrentDocCount(){
    	if(empty($this->__iCurrentDocCount)){
    		$sLocalDate = date("Y-m-d",time()+8*60*60);
			$sStart = date("Y-m-d H:i:s", strtotime($sLocalDate)-8*60*60);
			$sEnd = date("Y-m-d H:i:s", strtotime($sLocalDate)+16*60*60);
    		$this->__iCurrentDocCount = CBeautyDoc::iGetCount("`write_user_no`='{$this->iUserNo}' AND (`create_time` BETWEEN '$sStart' AND '$sEnd')");
    	}
    	return $this->__iCurrentDocCount;
    }

    public function vUpdateCount(){
    	$oDB = self::oDB(self::$sDBName);
    	try{
    		$oDB->iQuery("INSERT INTO `galaxy_user_beauty` SET `user_no`='{$this->iUserNo}',`daily_doc_count`='{$this->iDailyDocCount}' ON DUPLICATE KEY UPDATE `user_no`='{$this->iUserNo}',`daily_doc_count`='{$this->iDailyDocCount}'");
    	}catch(Exception $e){
    		throw new Exception("CUserBeauty->vUpdateCount: ".$e->getMessage());
    	}
    }


    /*
		usage: 1. $aAllUser = CUserBeauty::aAllUser()
			   2. $oProPerUser = CUserBeauty::oFinProperUser($aAllUser);	//this user is who we want
    */
    static public function oFindProperUser($aAllUser){
    	if(!is_array($aAllUser))
    		throw new Exception("CUserBeauty::oFindProperUser(users): require array of CUserBeauty");

    	$aSortUser = array();	//dimentional array, first index is daily_doc_count

    	foreach ($aAllUser as $oUser) {

    		if(get_class($oUser)!=='CUserBeauty')
    			throw new Exception("CUserBeauty::oFindProperUser(users): single object is not CUserBeauty");
    			
    		//if this user has reached daily doc count,skip him/her
    		if($oUser->iCurrentDocCount() >= $oUser->iDailyDocCount)
    			continue;
    		$aSortUser[$oUser->iDailyDocCount][] = $oUser;
    	}

    	ksort($aSortUser);
    	$aRandUsers = array_pop($aSortUser);
    	$oProperUser = $aRandUsers[rand(0,count($aRandUsers)-1)];

    	return $oProperUser;
    }
}

?>