<?php
/*
global:
$oDB
*/
include_once('../inc/model/CGalaxyClass.php');

Class CElementMappingOption extends CGalaxyClass
{
	private $iElementOptionNo;
	private $iElementMappingNo;
	private $sName;
	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static public $aInstancePool = array();

	//php default function, let private member become read-only class member for others
	public function __get($varName)
	{
		return $this->$varName;
	}
	/*
		get certain element_mapping by element_mapping_no
	*/
	static public function oGetElementMappingOption($iElementMappingOptionNo){
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT * FROM galaxy_element_mapping_option WHERE element_mapping_option_no=$iElementMappingOptionNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCElementMappingOption = new CElementMappingOption($aRow);
		return $oCElementMappingOption;
	}

	/*
		get all element_mapping_option group by element_mapping_no
		CAUTION: this function returns a map , instead of array
	*/
	static public function aAllOption($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllOption = array($multiData);
		$sSql = "SELECT * FROM galaxy_element_mapping_option";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oCOption = new CElementMappingOption($aRow);
			$aAllOption[$oCOption->iElementMappingNo][$oCOption->iElementOptionNo] = $oCOption;
		}
		return $aAllOption;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CElementMappingOption: __construct failed, require an array");
		//initialize vital member
		$this->iElementOptionNo = $multiData['element_mapping_option_no'];
		$this->sName = $multiData['element_mapping_option_name'];
		if(!isset($this->iElementOptionNo) || !isset($this->sName))
			throw new Exception("CElementMappingOption: __construct failed, lack of vital member");
		//initialize optional member
		$this->iElementMappingNo = $multiData['element_mapping_no'];
		//galaxy class member
		$this->sCreateTime = $multiData['element_mapping_option_createtime'];
		$this->sModifiedTime = $multiData['element_mapping_option_modifiedtime'];
		$this->bStatus = $multiData['element_mapping_option_status'];
	}

	public function vUpdateElementMapOption(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');


		try{
	
			$oDB->vBegin();
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'element_mapping_option_name'=>$this->sName,
						'element_mapping_no'=>$this->iElementMappingNo,
					
						'element_mapping_option_status'=>$this->bStatus,
						'user_no'=>$oCurrentUser->iUserNo,
						'element_mapping_option_modifiedtime'=>$sDate		
							);
			$oDB->vUpdate("galaxy_element_mapping_option",array_keys($aValues),array_values($aValues),"element_mapping_option_no={$this->iElementOptionNo}");

			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_element_mapping_option",$this->iElementOptionNo,'element_mapping_option','update');
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCElementMappingOption->vUpdateElementMapOption: '.$e->getMessage());
		}
		
		
	}

	public function iAddElementMapOption(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');


		try{
			$oDB->vBegin();

			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'element_mapping_option_name'=>$this->sName,
						'element_mapping_no'=>$this->iElementMappingNo,
					
						'element_mapping_option_status'=>$this->bStatus,
						'user_no'=>$oCurrentUser->iUserNo,
						'element_mapping_option_createtime'=>$sDate,
						'element_mapping_option_modifiedtime'=>$sDate		
							);
			$oDB->sInsert("galaxy_element_mapping_option",array_keys($aValues),array_values($aValues));
			$this->iElementOptionNo = $oDB->iGetInsertId();
			
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_element_mapping_option",$this->iElementOptionNo,'element_mapping_option','add');
			return $this->iElementOptionNo;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCElementMappingOption->iAddElementMapOption: '.$e->getMessage());
		}
	}
}
?>