<?php
include_once('../inc/model/CGalaxyClass.php');

class CScriptType extends CGalaxyClass
{
	private $iScriptTypeNo;
	public $sName;
	public $fSort;	//float

	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static public $aInstancePool = array();

	static public function oGetType($iScriptTypeNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iScriptTypeNo]))
			return self::$aInstancePool[$iScriptTypeNo];

		$sSql = "SELECT * FROM galaxy_script_type WHERE script_type_no=$iScriptTypeNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCScriptType = new CScriptType($aRow);
		self::$aInstancePool[$iScriptTypeNo] = $oCScriptType;
		return $oCScriptType;
	}

	static public function aAllType($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_script_type";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllScriptType = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['script_type_no']])){
				self::$aInstancePool[$aRow['script_type_no']] = new CScriptType($aRow);
			}
			$aAllScriptType[] = self::$aInstancePool[$aRow['script_type_no']];
		}
		return $aAllScriptType;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(script_type_no) as total FROM galaxy_script_type";
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
			throw new Exception("CScriptType: __construct failed, require an array");
		//initialize vital member
		$this->iScriptTypeNo = $multiData['script_type_no'];
		$this->sName = $multiData['script_type_name'];
		$this->fSort = $multiData['script_type_sort'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['script_type_status'];
		$this->sCreateTime = $multiData['script_type_createtime'];
		$this->sModifiedTime = $multiData['script_type_modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

	
	public function iAddScriptType(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'script_type_name'=>$this->sName, 
							'script_type_sort'=>$this->fSort,
							'script_type_status'=>$this->bStatus,
							'user_no'=>$oCurrentUser->iUserNo,
							'script_type_createtime'=>date("Y-m-d H:i:s"),
							'script_type_modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->sInsert("`galaxy_script_type`", array_keys($aValues), array_values($aValues));
			$this->iScriptTypeNo = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog("galaxy_script_type",$this->iScriptTypeNo,$_GET['func'],$_GET['action']);
			return $this->iScriptTypeNo;
		}catch (Exception $e){
			throw new Exception("CScriptType->iAddScriptType: ".$e->getMessage());
		}

	}

	public function vUpdateScriptType(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'script_type_name'=>$this->sName, 
							'script_type_sort'=>$this->fSort,
							'script_type_status'=>$this->bStatus,
							'script_type_modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->sUpdate("`galaxy_script_type`", array_keys($aValues), array_values($aValues), "`script_type_no` = {$this->iScriptTypeNo}");
			$oCurrentUser->vAddUserLog("galaxy_script_type",$this->iScriptTypeNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CScriptType->vUpdateScriptType: ".$e->getMessage());
		}
	}

	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		if($this->bStatus==='1')
			$this->bStatus='0';
		else
			$this->bStatus='1';
		$aValues = array('script_type_status'=>$this->bStatus);
		try{
			$oDB->sUpdate("`galaxy_script_type`", array_keys($aValues), array_values($aValues), "`script_type_no` = {$this->iScriptTypeNo}");
			$oCurrentUser->vAddUserLog("galaxy_script_type",$this->iScriptTypeNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CScriptType->vActivate: ".$e->getMessage());
		}
	}

}
?>