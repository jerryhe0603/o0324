<?php

/**
 *  @desc 派發紀錄
 */

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');


class CDistributeLog extends CGalaxyClass
{

	protected static $sDBName = 'OLDCAT';
	private $iDistributeLogNo;
	public $iCompanyNo;
	public $iManagementNo;
	public $iCancelUserNo;
	public $iAdminUserNo;
	public $sCancelNote;

	//instance pool
	static public $aInstancePool = array();

	public function __get($varName)
	{
		return $this->$varName;
	}
	

	
	public function __construct($multData)
	{
		parent::__construct($multData);
		
		if(!is_array($multData))
			throw new Exception("CDistributeLog: __construct failed, require an array");
			
		$this->iDistributeLogNo = $multData['dl_id'];
		$this->iCompanyNo = $multData['co_id'];
		$this->iManagementNo = $multData['mm_id'];
		$this->iCancelUserNo = $multData['cancel_user_no'];
		$this->iAdminUserNo = $multData['admin_user_no'];
		$this->sCancelNote = $multData['cancel_note'];
		$this->sCreateTime = $multiData['createdtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
	
	}
	

	
	public static function oGetDistributeLog($iDistributeLogNo=0){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iDistributeLogNo]))
			return self::$aInstancePool[$iDistributeLogNo];

		$sSql = "SELECT * FROM distribute_log WHERE dl_id=$iDistributeLogNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oDistributeLog = new CDistributeLog($aRow);
		$aInstancePool[$iDistributeLogNo]=$oDistributeLog;
		return $oDistributeLog;
	}
	
	
	
	/**
	 *  @desc 最新一筆派發紀錄
	 */
	public static function oGetDistributeLogByComapnyNoUserNo($co_id,$user_no)
	{
	
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM distribute_log WHERE co_id=$co_id AND user_no=$user_no ORDER BY created DESC limit 1";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		//if already queryed
		if(!is_null(self::$aInstancePool[$aRow['dl_id']]))
			return self::$aInstancePool[$aRow['dl_id']];
		$oDistributeLog = new CDistributeLog($aRow);
		self::$aInstancePool[$aRow['dl_id']]=$oDistributeLog;
		return $oDistributeLog;
		
	}
	

	/**
	 *  @desc 最新一筆派發紀錄
	 */
	public static function oGetDistributeLogByCompanyNo($co_id)
	{
	
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM distribute_log WHERE co_id=$co_id ORDER BY created DESC limit 1";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		//if already queryed
		if(!is_null(self::$aInstancePool[$aRow['dl_id']]))
			return self::$aInstancePool[$aRow['dl_id']];
		$oDistributeLog = new CDistributeLog($aRow);
		self::$aInstancePool[$aRow['dl_id']]=$oDistributeLog;
		return $oDistributeLog;
		
	}
	
	/**
	 *  @desc 最新一筆取消資料
	 */
	public static function oGetLastCancelDistributeLogByComapnyNo($co_id)
	{
	
		if (!$co_id) return NULL;
		
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM distribute_log WHERE co_id=$co_id AND user_no=0 ORDER BY created DESC limit 1";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		//if already queryed
		if(!is_null(self::$aInstancePool[$aRow['dl_id']]))
			return self::$aInstancePool[$aRow['dl_id']];
		$oDistributeLog = new CDistributeLog($aRow);
		self::$aInstancePool[$aRow['dl_id']]=$oDistributeLog;
		return $oDistributeLog;
	}
	
	/**
	 *  @desc 取得一筆派發紀錄
	 */
	public static function aGetDistributeLogByWhere($where='')
	{
	
		if (!$where) return null;
		$aDistributeLog = array();
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM distribute_log WHERE $where";
		$iDbq = $oDB->iQuery($sSql);
		while ($aRow = $oDB->aFetchAssoc($iDbq))
		{
			//if already queryed
			if(is_null(self::$aInstancePool[$aRow['dl_id']]))
				self::$aInstancePool[$aRow['dl_id']]=new CDistributeLog($aRow);

			$aDistributeLog[]=self::$aInstancePool[$aRow['dl_id']];
		}
		return $aDistributeLog;
	
	
	}


}
?>