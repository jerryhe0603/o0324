<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CElementMapping.php');

class CPageType extends CGalaxyClass
{
	private $iPageTypeNo;
	public $sName;
	public $fSort;	//float

	private $__aElementMapping;
	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetType($iPageTypeNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_page_type WHERE page_type_no=$iPageTypeNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCPageType = new CPageType($aRow);
		return $oCPageType;
	}

	static public function aAllType($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_page_type";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllPageType = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllPageType[] = new CPageType($aRow);
		}
		return $aAllPageType;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(page_type_no) as total FROM galaxy_page_type";
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
			throw new Exception("CPageType: __construct failed, require an array");
		//initialize vital member
		$this->iPageTypeNo = $multiData['page_type_no'];
		$this->sName = $multiData['page_type_name'];
		$this->fSort = $multiData['page_type_sort'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['page_type_status'];
		$this->sCreateTime = $multiData['page_type_createtime'];
		$this->sModifiedTime = $multiData['page_type_modifiedtime'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

	public function aElementMapping(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aElementMapping)){
			$this->__aElementMapping = array();
			$sSql = "SELECT * FROM galaxy_page_type_element_mapping WHERE page_type_no = {$this->iPageTypeNo}";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aElementMapping[$aRow['element_mapping_no']] = CElementMapping::oGetElementMapping($aRow['element_mapping_no']);
			}
		}
		return $this->__aElementMapping;
	}

	public function vSetElementMapping($aElementMappingNos){
		$this->__aElementMapping = array();
		foreach ($aElementMappingNos as $iElementMappingNo) {
			$this->__aElementMapping[$iElementMappingNo] = CElementMapping::oGetElementMapping($iElementMappingNo);
		}
	}

	public function iAddPageType(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'page_type_name'=>$this->sName, 
							'page_type_sort'=>$this->fSort,
							'page_type_status'=>$this->bStatus,
							'user_no'=>$this->__iUserNo,
							'page_type_createtime'=>date("Y-m-d H:i:s"),
							'page_type_modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->vBegin();
			$oDB->sInsert("`galaxy_page_type`", array_keys($aValues), array_values($aValues));
			$this->iPageTypeNo = $oDB->iGetInsertId();

			foreach ($this->__aElementMapping as $oEleMap) {
				$aEleMapVal = array(	'page_type_no'=>$this->iPageTypeNo,
										'element_mapping_no'=>$oEleMap->iElementMappingNo
									);
				$oDB->sInsert("galaxy_page_type_element_mapping", array_keys($aEleMapVal), array_values($aEleMapVal));
			}
			
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_page_type",$this->iPageTypeNo,$_GET['func'],$_GET['action']);
			return $this->iPageTypeNo;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CPageType->iAddPageType: ".$e->getMessage());
		}

	}

	public function vUpdatePageType(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'page_type_name'=>$this->sName, 
							'page_type_sort'=>$this->fSort,
							'page_type_status'=>$this->bStatus,
							'page_type_modifiedtime'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->vBegin();
			$oDB->sUpdate("`galaxy_page_type`", array_keys($aValues), array_values($aValues), "`page_type_no` = {$this->iPageTypeNo}");

			$oDB->vDelete("galaxy_page_type_element_mapping","`page_type_no`={$this->iPageTypeNo}");
			foreach ($this->__aElementMapping as $oEleMap) {
				$aEleMapVal = array(	'page_type_no'=>$this->iPageTypeNo,
										'element_mapping_no'=>$oEleMap->iElementMappingNo
									);
				$oDB->sInsert("galaxy_page_type_element_mapping", array_keys($aEleMapVal), array_values($aEleMapVal));
			}

			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_page_type",$this->iPageTypeNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CPageType->vUpdatePageType: ".$e->getMessage());
		}
	}

	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		if($this->bStatus==='1')
			$this->bStatus='0';
		else
			$this->bStatus='1';
		$aValues = array('page_type_status'=>$this->bStatus);
		try{
			$oDB->sUpdate("`galaxy_page_type`", array_keys($aValues), array_values($aValues), "`page_type_no` = {$this->iPageTypeNo}");
			$oCurrentUser->vAddUserLog("galaxy_page_type",$this->iPageTypeNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CPageType->vActivate: ".$e->getMessage());
		}
	}

}
?>