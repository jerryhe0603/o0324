<?php

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');
include_once(PATH_ROOT.'/inc/model/CCompanyAddress.php');
include_once(PATH_ROOT.'/inc/model/CAddrMap.php');
include_once(PATH_ROOT.'/inc/model/CTag.php');
include_once(PATH_ROOT.'/inc/model/CCompanyBrand.php');
include_once(PATH_ROOT.'/inc/model/CCompanyTel.php');
include_once(PATH_ROOT.'/inc/model/CUser.php');
include_once(PATH_ROOT.'/inc/model/CCompanyOldcat.php');
include_once(PATH_ROOT.'/inc/model/CManagement.php');
include_once(PATH_ROOT.'/inc/model/CIndustry.php');
include_once(PATH_ROOT.'/inc/model/CCompanyFiling.php');
include_once(PATH_ROOT.'/inc/model/CDistributeLog.php');
include_once(PATH_ROOT.'/inc/model/CDate.php');


class CCompany extends CGalaxyClass
{

	//database setting
	static protected $sDBName = 'COMPANY';
	
	protected $_aCompanyAddress;	
	
	//vital member
	public $iCompanyNo;	//dept_no in genesis.dept, read only after construct
	//optional member
	public $sName;
	public $sEnglishName;
	public $sNickname;
	public $iParentNo;
	public $iTaxId;
	public $iPaymentDay;
	public $sOwnerName;
	public $iFirstYear;
	public $iCapital;
	public $iVerifyUserNo;
	public $sVerifyTime;
	public $sNote;
	public $iEditUserNo;
	public $iStatus;
	public $sPrivateNote;

	//galaxy class memeber
	public $sCreateTime;
	public $sModifiedTime;

	public $__aTag;
	public $__aBrand;
	public $__aTel;
	public $__aAddr;

	//instance pool
	static public $aInstancePool = array();

	//php default function, let private member become read-only class member for others
	public function __get($varName)	{
		return $this->$varName;
	}

	
	/*
		constructor of $oCCompany
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData)	{
		parent::__construct($multiData);
		
		if(!is_array($multiData))
			throw new Exception("CCompany: __construct failed, require an array");
				
		//initialize vital member
		$this->iCompanyNo = $multiData['co_id'];
		//optional membe
		$this->sName = $multiData['co_name'];
		$this->sEnglishName = $multiData['co_name_en'];
		$this->sNickname= $multiData['co_nickname'];
		$this->iParentNo = $multiData['parent_id'];
		$this->iTaxId = $multiData['tax_id'];
		$this->iPaymentDay = $multiData['payment_day'];
		$this->sOwnerName = $multiData['co_owner'];
		$this->iFirstYear = $multiData['co_first_year'];
		$this->iCapital = $multiData['co_capital'];
		$this->iVerifyUserNo=$multiData['verify_user_no'];
		$this->sVerifyTime=$multiData['verify_time'];
		$this->sNote = $multiData['note'];
		$this->iEditUserNo = $multiData['edit_user_no'];
		$this->iStatus = $multiData['flag'];
		$this->sPrivateNote = $multiData['private_note'];
		
		//galaxy class memeber
		$this->sCreateTime = $multiData['created'];
		$this->sModifiedTime = $multiData['modified'];
	}
	
	
	public static function oGetCompany($iCompanyNo=0){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(isset(self::$aInstancePool[$iCompanyNo]))
			return self::$aInstancePool[$iCompanyNo];

		$sSql = "SELECT * FROM company WHERE co_id=$iCompanyNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		// if($aRow === false || $oDB->iNumRows($iDbq)>1)
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oCompany = new CCompany($aRow);
		self::$aInstancePool[$iCompanyNo] = $oCompany;
		return $oCompany;
	}


	public static function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(1) as total FROM company";
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
		get count of iwant user who match query
	*/
	public static function iGetCountOldcat($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(a.co_id) as total FROM company AS a INNER JOIN company_oldcat AS b ON a.co_id=b.co_id";
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
		get all company in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUserIwant::aAlluser('galaxy_user.addr_id=12')
		example2: $aCUsers = CUserIwant::aAlluser('galaxy_user_iwant.dept_no=1')
		example3: $aCUsers = CUSerIwant::aAlluser('galaxy_user_iwant.dept_no=1','ORDER BY galaxy_user.createtime DESC LIMIT 0,10')
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	public static function aAllCompany($sSearchSql='',$sPostFix='')	{
		$oDB = self::oDB(self::$sDBName);
		$aAllCompany = array();
		$sSql = "SELECT * FROM `company` AS a";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq))	{
			// if(is_null(self::$aInstancePool[$aRow['co_id']])){
			if(!isset(self::$aInstancePool[$aRow['co_id']])){
				self::$aInstancePool[$aRow['co_id']] = new CCompany($aRow);
			}
			$aAllCompany[] = self::$aInstancePool[$aRow['co_id']];
		}
		return $aAllCompany;
		
		
		
	}
	

	/*
		get all iwant user in an array
		if $sSearchSql is given, query only match users
		example1: $aCUsers = CUserIwant::aAlluser('galaxy_user.addr_id=12')
		example2: $aCUsers = CUserIwant::aAlluser('galaxy_user_iwant.dept_no=1')
		example3: $aCUsers = CUSerIwant::aAlluser('galaxy_user_iwant.dept_no=1','ORDER BY galaxy_user.createtime DESC LIMIT 0,10')
		caution: this function may query lots of data from galaxy_user DB, make sure you need all of these users
	*/
	public static function aAllCompanyOldcat($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllCompanyOldcat = array();
		$sSql = "SELECT * FROM `company` AS a INNER JOIN `company_oldcat` AS b ON a.co_id=b.co_id";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['co_id']])){
				self::$aInstancePool[$aRow['co_id']] = new CCompany($aRow);
			}
			$aAllCompanyOldcat[] = self::$aInstancePool[$aRow['co_id']];
		}
		return $aAllCompanyOldcat;
	}


	public static function sGetCompanyArea($iCompanyNo=0,$sComma='<br>') {
		global $gArea;
		if (!$iCompanyNo) return '';
		$str = '';
		$aArea = array();
		$oDB = self::oDB(self::$sDBName);
		$sSql="SELECT * FROM company_address WHERE co_id=$iCompanyNo";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq))	{
			$sAddrCode = trim(CAddrMap::sGetZipCode($aRow['addr_id']));
			$iCode = substr($sAddrCode,0,1);
			if ($iCode) {
				$sArea = $gArea[$iCode];
				if (in_array($sArea,$aArea)) continue;
				array_push($aArea,$sArea);
			}
		}
		$str = implode($sComma,$aArea);
		return $str;
	}

	
	public static function sGetTagName($iCompanyNo=0,$sComma='<br>') {
	
		if (!$iCompanyNo) return '';
		$aTagData = array();
		$aTag = CTag::aGetTageLogByTableNo("GENESIS","company", "company", $iCompanyNo); // 標籤
		if (count($aTag))	{
			foreach($aTag as $oTag)	{
				if ($oTag->bStatus==0) continue;
				if (in_array($oTag->sTagName,$aTagData)) continue;
				array_push($aTagData,$oTag->sTagName);
			}
		}
		return implode($sComma,$aTagData);
	}
	
	
	public function aGetTag(){
		if(empty($this->__aTag)){
			$this->__aTag = CTag::aGetTageLogByTableNo('GENESIS','company', 'company',$this->iCompanyNo);
		}
		return $this->__aTag;
	}

	
	public function aGetBrand() {
		if(empty($this->__aBrand)){
			$this->__aBrand = CCompanyBrand::aAllCompanyBrand("co_id = ".$this->iCompanyNo);
		}
		return $this->__aBrand;
	}

	
	public function aGetTel(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aTel)){
			$sSql = "SELECT * FROM `company_tel` WHERE `co_id`='{$this->iCompanyNo}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aTel[] = $aRow;
			}
		}
		return $this->__aTel;
	}

	
	public function aGetAddr(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aAddr)){
			$sSql = "SELECT * FROM `company_address` WHERE `co_id`='{$this->iCompanyNo}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aAddr[] = $aRow;
			}
		}
		return $this->__aAddr;
	}

	
	static public function aAllCompanyBrandAndCompany($sSearchSql='',$sPostFix='') {
		$oDB = self::oDB(self::$sDBName);
		$aAllTag = array();
		$sSql = "SELECT a.*, b.*, c.*, d.* FROM `company` AS a
					LEFT JOIN `company_brand` AS b 
					ON a.`co_id` = b.`co_id`
					LEFT JOIN `company_tel` AS c
					ON a.`co_id` = c.`co_id`
					LEFT JOIN `company_address` AS d
					ON a.`co_id` = d.`co_id` ";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCompanyBrandAndCompany = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllCompanyBrandAndCompany[] = $aRow;
		}
		return $aAllCompanyBrandAndCompany;
	}

	
	public function iAdd(){
		$oDB = self::oDB(self::$sDBName);
		
		if($this->bIsExistSameCoNickName($this->sNickname))
			throw new Exception("CComoany: iAdd failed, repeated nickname");

		$aValues = array(	'co_nickname'=>$this->sNickname,
							'flag'=>$this->iStatus,
							'created'=>date("Y-m-d H:i:s"),
							'modified'=>date("Y-m-d H:i:s"),
							'user_no'=>$this->__iUserNo
							);
		$oDB->sInsert("company", array_keys($aValues), array_values($aValues));
		$this->iCompanyNo = $oDB->iGetInsertId();
		//self::$session->get('oCurrentUser')->vAddUserLog('company',$this->iCompanyNo, $_GET['func'], $_GET['action']);
		return $this->iCompanyNo;
	}

	
	public function bIsExistSameCoNickName($sNickname) {
		$oDB = self::oDB(self::$sDBName);

		$sSql = "SELECT 1 FROM `company`
					WHERE `co_nickname` = '$sNickname' ";
		$iDbq = $oDB->iQuery($sSql);
		if($oDB->iNumRows($iDbq))
			return true;
		else 
			return false;	
	}

	
	/**
	* @desc 抓取公司品牌
	* @param $co_id int 公司序號
	* @return string 公司品牌
	* @created 2014/04/23
	*/
	public static function sGetCompanyBrand($iCompanyNo=0,$sComma='<br>') {
		$str = '';
		
		if (!$iCompanyNo) return '';
		
		$aCompanyBrand = CCompanyBrand::aGetCompanyBrand($iCompanyNo);
		
		$aComapnyBrandName = array();
		
		foreach($aCompanyBrand as $oBrand)	{
			if ($oBrand->iStatus==0 OR $oBrand->iStatus==9) continue;
			if (in_array($oBrand->sName,$aComapnyBrandName)) continue;
			array_push($aComapnyBrandName,$oBrand->sName);
		}
		return implode($sComma,$aComapnyBrandName);
	}
	
	
	/**
	 *  @desc 取得派發過的業務
	 *  @param $co_id int 公司序號
	 *  @created 2014/03/10
	 */
	public static function sGetDistributedSalerByCompanyNo($iCompanyNo=0,$sComma='<br>') {
		if (!$iCompanyNo) return '';
		
		$aUserName = array();
		$aUserNo = array();
		
		$aDistributeLog = CDistributeLog::aGetDistributeLogByWhere("user_no!=0 AND cus_id=$iCompanyNo");
		
		if ($aDistributeLog){
		
			foreach($aDistributeLog as $oDistributeLog)	{
				if (in_array($oDistributeLog->__iUserNo,$aUserNo)) continue;
				array_push($aUserNo,$oDistributeLog->__iUserNo);
				
				$oUser = CUser::oGetUser($oDistributeLog->__iUserNo);
				array_push($aUserName,$oUser->sName);
			}
		}

		return implode($sComma,$aUserName);
	}	
	
	
	/**
	* @desc 抓取公司電話
	* @param $co_id int 公司序號
	* @param $type int 0:全部 1:電話 2:手機 3:傳真
	* @return string 公司電話
	* @created 2014/04/03
	*/
	public static function sGetCompanyTel($iCompanyNo=0,$iType=0,$sComma='<br>'){
		$str = '';
		if (!$iCompanyNo) return '';
		
		$aCompanyTel = CCompanyTel::aGetCompanyTel($iCompanyNo);
		
		$aTel = array();
		
		foreach($aCompanyTel as $oTel)
		{
			if ($iType!=0 AND $oTel->iType!=$iType) continue;
			if (in_array($oTel->sTel,$aTel)) continue;
			array_push($aTel,$oTel->sTel);
		}
		return implode($sComma,$aTel);
	}		
	
	
	/**
	* @desc 抓取公司地址
	* @param $co_id int 公司序號
	* @param $type int 0:全部 1:公司 2營業門市
	* @return string 公司地址料
	* @created 2014/04/03
	*/
	public static function sGetCompanyAddr($iCompanyNo=0,$iType=0,$sComma='<br>'){
		$str = '';
		if (!$iCompanyNo) return '';
		
		$aCompanyAddress = CCompanyAddress::aGetCompanyAddress($iCompanyNo);
		
		$aAddress = array();
		
		foreach($aCompanyAddress as $oAddr)
		{
			if ($iType!=0 AND $oAddr->iType!=$iType) continue;
			if (in_array($oAddr->sCompanyAddress,$aAddress)) continue;
			array_push($aAddress,$oAddr->sCompanyAddress);
		}
		return implode($sComma,$aAddress);
	}

	
	/**
	 *  @desc 編修者
	 */
	public static function sGetEditUserName($iEditUserNo=0) {
		if (!$iEditUserNo) return '';
		$oUser = CUser::oGetUser($iEditUserNo);
		return $oUser->sName;
	}
	
	
	/**
	 *  @desc 體系
	 */
	public static function sGetManagementName($iCompanyNo=0){
		if (!$iCompanyNo) return '';
		
		$oCompanyOldcat = CCompanyOldcat::oGetCompanyOldcat($iCompanyNo);

		if (!$oCompanyOldcat OR !$oCompanyOldcat->iManagementNo) return '';
		$oManagement = CManagement::oGetManagement($oCompanyOldcat->iManagementNo);

		return $oManagement->sName;
	}

	
	/**
	 *  @desc 產業
	 */
	public static function sGetIndustryName($iCompanyNo){
		if(!$iCompanyNo) return '';
		
		$oCompanyOldcat = CCompanyOldcat::oGetCompanyOldcat($iCompanyNo);

		if (!$oCompanyOldcat OR !$oCompanyOldcat->iIndustryNo) return '';
		$oIndestry = CIndustry::oGetIndustry($oCompanyOldcat->iIndustryNo);
	
		return $oIndestry->sName;
	}
	
	
	
	/**
	 *  @desc 負責業務
	 */
	public static function sGetSalerName($iCompanyNo) {
		if (!$iCompanyNo) return '';
		
		$oCompanyOldcat = CCompanyOldcat::oGetCompanyOldcat($iCompanyNo);
		if (!$oCompanyOldcat OR !$oCompanyOldcat->__iUserNo) return '';
		
		$oUser = CUser::oGetUser($oCompanyOldcat->__iUserNo);
		return $oUser->sName;
	}

	
	/**
	 *  @desc 報備人員
	 */
	public static function sGetFilingName($iCompanyNo) {
		if (!$iCompanyNo) return '';
		
		$oCompanyFiling = CCompanyFiling::oGetCompanyFilingByCoId($iCompanyNo);
		if (!$oCompanyFiling OR !$oCompanyFiling->__iUserNo) return '';
		
		$oUser = CUser::oGetUser($oCompanyFiling->__iUserNo);
		return $oUser->sName;
	}

	
	/**
	 *  @desc 拜訪狀態
	 */
	public static function sGetVisitStatusName($iCompanyNo)	{
		global $gVisitStatus;
	
		if (!$iCompanyNo) return '';
	
		$oCompanyOldcat = CCompanyOldcat::oGetCompanyOldcat($iCompanyNo);
		if (!$oCompanyOldcat) return '';
	
		return $gVisitStatus[$oCompanyOldcat->iVisitStatus];
	}
	
	
	/**
	 *  @desc 進行天數
	 */
	public static function iGetCompanyRunDay($iCompanyNo){
		if (!$iCompanyNo) return 0;
		
		$oCompanyOldcat = CCompanyOldcat::oGetCompanyOldcat($iCompanyNo);
		if (!$oCompanyOldcat OR !$oCompanyOldcat->__iUserNo) return 0;
	
		$oDistributeLog = CDistributeLog::oGetDistributeLogByComapnyNoUserNo($iCompanyNo,$oCompanyOldcat->__iUserNo);
		
		if (!$oDistributeLog OR !$oDistributeLog->sCreateTime) return 0;
		
		$sDateToday = date('Y-m-d H:i:s', mktime(date('H'),date('i'),date('s'),date('m'),date('d'),date('Y')));
		$iRunDay = CDate::dateDiff($oDistributeLog->sCreateTime,$sDateToday);
		if ($iRunDay<0) $iRunDay = 0;

		return $iRunDay;
	}
	
	
	/**
	 *  @desc 取消資料
	 */
	public static function oGetSalerCancelCompanyRow($iCompanyNo){
		if (!$iCompanyNo) return null;
		$oDistributeLog = CDistributeLog::oGetLastCancelDistributeLogByComapnyNo($iCompanyNo);
		return $oDistributeLog;
	}
	
	
	public function vCompanyActive(){
		$oDB = self::oDB(self::$sDBName);
		if($this->iStatus==='1')
			$this->iStatus='0';
		else
			$this->iStatus='1';
		$aValues = array('flag'=>$this->iStatus);
		try{
			$oDB->sUpdate("company", array_keys($aValues), array_values($aValues), "`co_id` = {$this->iCompanyNo}");
			self::$session->get('oCurrentUser')->vAddUserLog("company",$this->__iUserNo,'company','active');
		}catch (Exception $e){
			throw new Exception("CCompany->vActive: ".$e->getMessage());
		}
	}
	
	
}
?>