<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CScriptType.php');

class CSiteType extends CGalaxyClass
{
	private $iSiteTypeNo;
	public $sName;
	public $fSort;	//float

	private $__aScriptType;

	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static public $aInstancePool = array();

	static public function oGetType($iSiteTypeNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_site_type WHERE site_type_no=$iSiteTypeNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCSiteType = new CSiteType($aRow);
		self::$aInstancePool[$iSiteTypeNo] = $oCSiteType;

		return $oCSiteType;
	}

	static public function aAllType($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_site_type";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllSiteType = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['site_type_no']])){
				self::$aInstancePool[$aRow['site_type_no']] = new CSiteType($aRow);
			}
			$aAllSiteType[] = self::$aInstancePool[$aRow['site_type_no']];
		}
		return $aAllSiteType;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(site_type_no) as total FROM galaxy_site_type";
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

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CSiteType: __construct failed, require an array");
		//initialize vital member
		$this->iSiteTypeNo = $multiData['site_type_no'];
		$this->sName = $multiData['site_type_name'];
		$this->fSort = $multiData['site_type_sort'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['site_type_status'];
		$this->sCreateTime = $multiData['site_type_createtime'];
		$this->sModifiedTime = $multiData['site_type_modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    public function aScriptType(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty($this->__aScriptType)){
    		$this->__aScriptType = array();
			$sSql = "SELECT * FROM galaxy_site_type_script WHERE site_type_no = {$this->iSiteTypeNo}";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aScriptType[$aRow['script_type_no']] = CScriptType::oGetType($aRow['script_type_no']);
			}    		
    	}
    	return $this->__aScriptType;
    }

    public function vSetScriptType($aScriptTypeNos){
    	$this->__aScriptType = array();
    	foreach ($aScriptTypeNos as $iScriptTypeNo) {
    		$this->__aScriptType[$iScriptTypeNo] = CScriptType::oGetType($iScriptTypeNo);
    	}
    }
	
	public function iAddSiteType(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'site_type_name'=>$this->sName, 
							'site_type_sort'=>$this->fSort,
							'site_type_status'=>$this->bStatus,
							'user_no'=>$oCurrentUser->iUserNo,
							'site_type_createtime'=>date("Y-m-d H:i:s"),
							'site_type_modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->vBegin();
			$oDB->sInsert("`galaxy_site_type`", array_keys($aValues), array_values($aValues));
			$this->iSiteTypeNo = $oDB->iGetInsertId();

			//rel script_types
			foreach ($this->__aScriptType as $oScriptType) {
				$aTypeVal = array(	'site_type_no'=>$this->iSiteTypeNo,
									'script_type_no'=>$oScriptType->iScriptTypeNo
									);
				$oDB->sInsert('galaxy_site_type_script',array_keys($aTypeVal), array_values($aTypeVal));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_site_type",$this->iSiteTypeNo,$_GET['func'],$_GET['action']);
			return $this->iSiteTypeNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CSiteType->iAddSiteType: ".$e->getMessage());
		}

	}

	public function vUpdateSiteType(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'site_type_name'=>$this->sName, 
							'site_type_sort'=>$this->fSort,
							'site_type_status'=>$this->bStatus,
							'site_type_modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->vBegin();
			$oDB->sUpdate("`galaxy_site_type`", array_keys($aValues), array_values($aValues), "`site_type_no` = {$this->iSiteTypeNo}");

			//rel script_types
			$oDB->vDelete('galaxy_site_type_script',"`site_type_no`={$this->iSiteTypeNo}");
			foreach ($this->__aScriptType as $oScriptType) {
				$aTypeVal = array(	'site_type_no'=>$this->iSiteTypeNo,
									'script_type_no'=>$oScriptType->iScriptTypeNo
									);
				$oDB->sInsert('galaxy_site_type_script',array_keys($aTypeVal), array_values($aTypeVal));
			}

			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_site_type",$this->iSiteTypeNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CSiteType->vUpdateSiteType: ".$e->getMessage());
		}
	}

	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		
		if($this->bStatus==='1')
			$this->bStatus='0';
		else
			$this->bStatus='1';
		$aValues = array('site_type_status'=>$this->bStatus);
		try{
			$oDB->sUpdate("`galaxy_site_type`", array_keys($aValues), array_values($aValues), "`site_type_no` = {$this->iSiteTypeNo}");
			$oCurrentUser->vAddUserLog("galaxy_site_type",$this->iSiteTypeNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CSiteType->vActivate: ".$e->getMessage());
		}
	}

}
?>