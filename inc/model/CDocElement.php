<?php
/*
global:
$oDB
*/
include_once('../inc/model/CElementMapping.php');

Class CDocElement extends CGalaxyClass
{
	static private $aElementPool = array();
	static private $aOptValPool =array();	//$aOptValPool[$sDocUuid][$iElementMappingNo] is an array of values

	private $sDocUuid;	//docs_no
	private $iElementMappingNo;	//element_mapping_no
	//CAUTION: $sDocUuid.$iElementMappingNo is unique, but single of them is not

	private $oElementMapping;

	public $sValue;	//element_mapping_value
	public $aOption = array();	//element_mapping_option
	public $bStatus;	//field_status
	public $iOrder;	//field_order
	
	//database setting
	static protected $sDBName = 'DOCS';

	/*
		get $oCDocElement by certain docs_no & element_mapping_no
	*/
	static public function oGetDocElement($sDocUuid,$iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		//set option pool of given docs_no
		self::vSetOptionMap($sDocUuid);
		//set element mapping(static)
		if(empty(self::$aElementPool))
			self::$aElementPool = CElementMapping::aAllElementMapping();	//or "element_mapping_status='1'"

		$iFolderNo = substr($sDocUuid,0,1);
		$sSql = "SELECT * FROM galaxy_docs_mapping_$iFolderNo WHERE `docs_no`='$sDocUuid' AND `element_mapping_no`=$iElementMappingNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCDocElement = new CDocElement($aRow);
		//if this doc element is checkbox
		if($oCDocElement->oElementMapping->sTagType === 'checkbox'){
			if(is_array(self::$aOptValPool[$sDocUuid][$iElementMappingNo]))
				$oCDocElement->aOption = self::$aOptValPool[$sDocUuid][$iElementMappingNo];
		}
		return $oCDocElement;
	}

	/*
		get all doc element of a certain doc
		CAUTION: $sDocUuid must be given!!!
		CAUTION: return a map of element_mapping_no to $oCDocElement
	*/
	static public function aAllDocElement($sDocUuid,$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		//set option pool of given docs_no
		self::vSetOptionMap($sDocUuid);
		//set element mapping(static)
		if(empty(self::$aElementPool))
			self::$aElementPool = CElementMapping::aAllElementMapping();	//or "element_mapping_status='1'"

		$iFolderNo = substr($sDocUuid,0,1);
		$sSql = "SELECT * FROM galaxy_docs_mapping_$iFolderNo WHERE `docs_no`='$sDocUuid'";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";		
		$iDbq = $oDB->iQuery($sSql);
		$aAllDocElement = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oCDocElement = new CDocElement($aRow);
			//if this doc element is checkbox, set option value
			if($oCDocElement->oElementMapping->sTagType === 'checkbox'){
				if(is_array(self::$aOptValPool[$sDocUuid][$oCDocElement->iElementMappingNo]))
					$oCDocElement->aOption = self::$aOptValPool[$sDocUuid][$oCDocElement->iElementMappingNo];
			}
			$aAllDocElement[$oCDocElement->iElementMappingNo] = $oCDocElement;
		}
		return $aAllDocElement;
	}

	/*
		constructor of $oCDocElement
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CElement: __construct failed, require an array");
		//initialize vital member
		$this->sDocUuid = $multiData['docs_no'];
		$this->iElementMappingNo = $multiData['element_mapping_no'];
		if(!isset($this->sDocUuid) || !isset($this->iElementMappingNo))
			throw new Exception("CElement: __construct failed, lack of vital member");
		//initialize optional member
		if(isset(self::$aElementPool[$this->iElementMappingNo]))
			$this->oElementMapping = self::$aElementPool[$this->iElementMappingNo];
		$this->sValue = $multiData['element_mapping_value'];
		$this->bStatus = $multiData['fields_status'];
		$this->iOrder = $multiData['fields_order'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    /*
		set CCDocElement Option
		$aOption is array of element_mapping_option_no
	*/
	public function vSetOption($aOption){
		if(!is_array($aOption))
			return;
		$this->aOption = array();	//clear prev option
		$this->aOption = $aOption;
	}

    public function vAdd(){
    	$oDB = self::oDB(self::$sDBName);
    	$iFolderNo = substr($this->sDocUuid,0,1);
    	$aValues = array(	'docs_no'=>$this->sDocUuid,
    						'element_mapping_no'=>$this->iElementMappingNo,
    						'element_mapping_value'=>$this->sValue,
    						'fields_status'=>$this->bStatus,
    						'fields_order'=>$this->iOrder
    						);
    	try{
    		$oDB->vBegin();
    		//doc element self
    		$oDB->sInsert("galaxy_docs_mapping_$iFolderNo",array_keys($aValues),array_values($aValues));
    		//options
    		if(!empty($this->aOption)){
    			foreach ($this->aOption as $iOptionNo) {
    				$aOptVal = array(	'docs_no'=>$this->sDocUuid,
    									'element_mapping_no'=>$this->iElementMappingNo,
    									'element_mapping_option_no'=>$iOptionNo
    									);
    				$oDB->sInsert("galaxy_docs_mapping_option_$iFolderNo",array_keys($aOptVal),array_values($aOptVal));
    			}
    		}
    		$oDB->vCommit();
    	}catch (Exception $e){
    		$oDB->vRollback();
    		throw new Exception("CDocElement->vAdd:".$e->getMessage());
    	}
    }

    public function vUpdate(){
    	$oDB = self::oDB(self::$sDBName);
    	$iFolderNo = substr($this->sDocUuid,0,1);
    	$aValues = array(	'element_mapping_value'=>$this->sValue,
    						'fields_status'=>$this->bStatus
    						);
    	try{
    		$oDB->vBegin();
    		//doc element self
    		$oDB->sUpdate("galaxy_docs_mapping_$iFolderNo",array_keys($aValues),array_values($aValues),"docs_no='{$this->sDocUuid}' AND element_mapping_no='{$this->iElementMappingNo}'");
    		//options
    		$oDB->vDelete("galaxy_docs_mapping_option_$iFolderNo","docs_no='{$this->sDocUuid}' AND element_mapping_no='{$this->iElementMappingNo}'");
    		if(!empty($this->aOption)){
    			foreach ($this->aOption as $iOptionNo) {
    				$aOptVal = array(	'docs_no'=>$this->sDocUuid,
    									'element_mapping_no'=>$this->iElementMappingNo,
    									'element_mapping_option_no'=>$iOptionNo
    									);
    				$oDB->sInsert("galaxy_docs_mapping_option_$iFolderNo",array_keys($aOptVal),array_values($aOptVal));
    			}
    		}
    		$oDB->vCommit();
    	}catch(Exception $e){
    		$oDB->vRollback();
    		throw new Exception("CDocElement->vUpdate:".$e->getMessage());
    	}

    }

    /*
		set element_mapping_option by docs_no
		it would insert into static option pool
		same docs_no won't run this function more than once
    */
    static private function vSetOptionMap($sDocUuid){
    	$oDB = self::oDB(self::$sDBName);
    	if(isset(self::$aOptValPool[$sDocUuid]))
    		return;
    	$aOptionMap = array();
		$iFolderNo = substr($sDocUuid,0,1);
		$sSql = "SELECT * FROM galaxy_docs_mapping_option_$iFolderNo WHERE `docs_no`='{$sDocUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			self::$aOptValPool[$sDocUuid][$aRow['element_mapping_no']][] = $aRow['element_mapping_option_no'];
		}
		return;
    }
}
?>