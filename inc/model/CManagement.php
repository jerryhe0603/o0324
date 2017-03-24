<?php
include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');
include_once(PATH_ROOT.'/inc/model/CUser.php');

class CManagement extends CGalaxyClass
{

	private $iManagementNo;
	public $sName;
	public $sTel;
	public $sAddress;
	public $sTaxNo;
	public $bStatus;
	public $sCreatedTime;
	public $sModifiedTime;
	//public $iManagerId;

	//instance pool
	static public $aInstancePool = array();

	protected static $sDBName = 'OLDCAT';

	public function __get($varName)
	{
		return $this->$varName;
	}
	
	
	public function __construct($multData)
	{
		parent::__construct($multData);
		
		if(!is_array($multData))
			throw new Exception("CManagement: __construct failed, require an array");
			
		$this->iManagementNo = isset($multData['mm_id'])?$multData['mm_id']:0;
		$this->sName = isset($multData['mm_name'])?$multData['mm_name']:'';
		$this->sTel = isset($multData['mm_tel'])?$multData['mm_tel']:'';
		$this->sAddress = isset($multData['mm_addr'])?$multData['mm_addr']:'';
		$this->sTaxNo = isset($multData['mm_tax'])?$multData['mm_tax']:'';
		$this->bStatus = isset($multData['flag'])?$multData['flag']:0;
		$this->sCreatedTime = isset($multData['created'])?$multData['created']:'';
		$this->sModifiedTime = isset($multData['modified'])?$multData['modified']:'';
		//$this->iManagerId = $multData['mem_id'];
	}
	
	
	public static function oGetManagement($iManagementNo=0){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(isset(self::$aInstancePool[$iManagementNo]))
			return self::$aInstancePool[$iManagementNo];

		$sSql = "SELECT * FROM management WHERE mm_id=$iManagementNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);

		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		
		$oManagement = new CManagement($aRow);
		self::$aInstancePool[$iManagementNo] = $oManagement;
		return $oManagement;
	}

	public static function aAllManagement($sSearchSql='', $sPostfix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql=" SELECT * FROM `management` ";
		if(!empty($sSearchSql))
			$sSql.=" WHERE $sSearchSql ";
		if(!empty($sPostfix))
			$sSql.=" $sPostfix ";
		$iDbq=$oDB->iQuery($sSql);

		$aManagement=array();
		while($aRow=$oDB->aFetchAssoc($iDbq)){
			if(!isset(self::$aInstancePool[$aRow['mm_id']])){
				self::$aInstancePool[$aRow['mm_id']] = new CManagement($aRow);
			}
			$aManagement[] = self::$aInstancePool[$aRow['mm_id']];
		}
		return $aManagement;
	}

	public static function aAllMemeberByManagement($iManagementNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = " SELECT `user_no` FROM `member_management_rel` WHERE `mm_id` = $iManagementNo";
		$iDbq = $oDB->iQuery($sSql);
		$aMemeber = array();
		while($aRow=$oDB->aFetchAssoc($iDbq)){
			$aMemeber[]= CUser::oGetUser($aRow['user_no']);
		}
		return $aMemeber;
	}
}
?>