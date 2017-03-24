<?php
/*
global:
$oDB
*/
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CElementMappingOption.php');
include_once('../inc/model/CPageType.php');

Class CElementMapping extends CGalaxyClass
{
	static private $aOptionPool = array();
	private $iElementMappingNo;
	public $sName;
	public $sTagType;
	public $iTool;
	public $iValueType;
	public $bMultiple;
	private $aOption;

	private $__aPageType = array();

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
	static public function oGetElementMapping($iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		if(empty(self::$aOptionPool))
			self::$aOptionPool = CElementMappingOption::aAllOption();
		
		$sSql = "SELECT * FROM galaxy_element_mapping WHERE element_mapping_no=$iElementMappingNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCEleMap = new CElementMapping($aRow);
		self::$aInstancePool[$iElementMappingNo] = $oCEleMap;
		return $oCEleMap;
	}

	/*
		get all element_mapping
		CAUTION: this function returns a map , instead of array
	*/
	static public function aAllElementMapping($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		if(empty(self::$aOptionPool))
			self::$aOptionPool = CElementMappingOption::aAllOption();	//or "element_mapping_option_status='1'"
		$aAllElement = array();
		$sSql = "SELECT * FROM galaxy_element_mapping";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['element_mapping_no']])){
				self::$aInstancePool[$aRow['element_mapping_no']] = new CElementMapping($aRow);
			}
			$aAllElement[$aRow['element_mapping_no']] = self::$aInstancePool[$aRow['element_mapping_no']];
		}
		return $aAllElement;
	}
	static public function aAllElementMappingGroupByTagtype($sSearchSql='',$sPostFix=''){
		
		$aAllElement =  self::aAllElementMapping($sSearchSql,$sPostFix);

		$aAllElementGroup = array();
		$aAllElementGroup[0] = array("name"=>_LANG_ELEMENT_MAPPING_NO_GROUP,"element"=>array());

		if(count($aAllElement)){
			foreach($aAllElement AS $key => $val){//key = iElementMappingNo , val= oCElementMapping
				
				
					
				$pgData = self::aGetElementMappingPageType($key);

				if($pgData){
					for($i=0;$i<count($pgData);$i++){
			
						$aAllElementGroup[$pgData[$i]["page_type_no"]]["name"] = $pgData[$i]["page_type_name"];
						$aAllElementGroup[$pgData[$i]["page_type_no"]]["element"][] = $val;
					}	
				}else
					$aAllElementGroup[0]["element"][] = $val;
			}
			
		}
		
		return $aAllElementGroup;
	}


	static public function aGetElementMappingPageType($iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_page_type_element_mapping AS tb1
				LEFT JOIN galaxy_page_type AS tb2 ON (tb2.page_type_no = tb1.page_type_no)
					WHERE element_mapping_no = $iElementMappingNo";

		$iDbq = $oDB->iQuery($sSql);
			

		$aPageType = array();
		while( $aRow = $oDB->aFetchAssoc($iDbq) ){
			$aPageType[] = $aRow;
		}

		return $aPageType;
	}

	static public function bIsExistElementMapping($iElementMappingNo=0){
		if($iElementMappingNo===0) return false;
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT element_mapping_no FROM galaxy_element_mapping WHERE element_mapping_no=$iElementMappingNo";
		$iDbq = $oDB->iQuery($sSql);
		
		if($oDB->iNumRows($iDbq)==1)
			return true;
		return false;


	}

	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(element_mapping_no) as total FROM galaxy_element_mapping";
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
		//set options(static)
		if(empty(self::$aOptionPool))
			self::$aOptionPool = CElementMappingOption::aAllOption();	//or "elemenet_mapping_option_status='1'"

		if(!is_array($multiData))
			throw new Exception("CElementMapping: __construct failed, require an array");
		//initialize vital member
		$this->iElementMappingNo = $multiData['element_mapping_no'];
		if(!isset($this->iElementMappingNo))
			throw new Exception("CElementMapping: __construct failed, lack of vital member");
		//initialize optional member
		$this->sName = $multiData['element_mapping_name'];
		$this->sTagType = $multiData['element_mapping_tagtype'];
		$this->iTool = $multiData['element_mapping_tool'];
		$this->iValueType = $multiData['element_mapping_valuetype'];
		$this->bMultiple = $multiData['element_mapping_multiple'];
		//galaxy class memeber
		$this->sCreateTime = $multiData['element_mapping_createtime'];
		$this->sModifiedTime = $multiData['element_mapping_modifiedtime'];
		$this->bStatus = $multiData['element_mapping_status'];

		if($this->sTagType==='select' || $this->sTagType==='radio' || $this->sTagType==='checkbox')
			$this->aOption = self::$aOptionPool[$this->iElementMappingNo];
	}

	public function aPageType(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aPageType)){
			$sSql = "SELECT page_type_no FROM galaxy_page_type_element_mapping WHERE element_mapping_no='{$this->iElementMappingNo}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aPageType[$aRow['page_type_no']] = CPageType::oGetType($aRow['page_type_no']);
			}
		}
		return $this->__aPageType;
	}

	public function vSetPageType($aTypeNos){
		if(!is_array($aTypeNos))
			return 
		$this->__aPageType = array();	//clear
		foreach ($aTypeNos as $iPageTypeNo) {
			$this->__aPageType[] = CPageType::oGetType($iPageTypeNo);
		}
	}

	public function vUpdateElementMap(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		try{
	
			$oDB->vBegin();
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'element_mapping_name'=>$this->sName,
						'element_mapping_tagtype'=>$this->sTagType,
						'element_mapping_tool'=>$this->iTool,
						'element_mapping_valuetype'=>$this->iValueType,
						'element_mapping_multiple'=>$this->bMultiple,
						'element_mapping_status'=>$this->bStatus,
						'user_no'=>$oCurrentUser->iUserNo,
						'element_mapping_modifiedtime'=>$sDate		
							);
			$oDB->sUpdate("galaxy_element_mapping",array_keys($aValues),array_values($aValues),"element_mapping_no={$this->iElementMappingNo}");

			$oDB->vDelete("galaxy_page_type_element_mapping","`element_mapping_no`={$this->iElementMappingNo}");
			foreach ($this->__aPageType as $oPageType) {
				$aTypeVal = array(	'page_type_no'=>$oPageType->iPageTypeNo,
									'element_mapping_no'=>$this->iElementMappingNo
									);

				$oDB->sInsert("galaxy_page_type_element_mapping",array_keys($aTypeVal),array_values($aTypeVal));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_element_mapping",$this->iElementMappingNo,'element_mapping','update');
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCElementMapping->vUpdateElementMap: '.$e->getMessage());
		}
		
		
	}

	public function iAddElementMap(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');


		try{
			$oDB->vBegin();

			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'element_mapping_name'=>$this->sName,
						'element_mapping_tagtype'=>$this->sTagType,
						'element_mapping_tool'=>$this->iTool,
						'element_mapping_valuetype'=>$this->iValueType,
						'element_mapping_multiple'=>$this->bMultiple,
						'element_mapping_status'=>$this->bStatus,
						'user_no'=>$oCurrentUser->iUserNo,
						'element_mapping_createtime'=>$sDate,
						'element_mapping_modifiedtime'=>$sDate		
							);
			$oDB->sInsert("galaxy_element_mapping",array_keys($aValues),array_values($aValues));
			$this->iElementMappingNo = $oDB->iGetInsertId();

			foreach ($this->__aPageType as $oPageType) {
				$aTypeVal = array(	'page_type_no'=>$oPageType->iPageTypeNo,
									'element_mapping_no'=>$this->iElementMappingNo
									);

				$oDB->sInsert("galaxy_page_type_element_mapping",array_keys($aTypeVal),array_values($aTypeVal));
			}
			
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_element_mapping",$this->iElementMappingNo,'element_mapping','add');
			return $this->iElementMappingNo;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCElementMapping->iAddElementMap: '.$e->getMessage());
		}
	}
}

?>