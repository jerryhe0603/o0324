<?php
/*
global:
$oDB
*/
include_once('../inc/model/CElementMapping.php');

Class CPuppetsElement extends CGalaxyClass
{
	static private $aElementPool = array();	//all element
	static private $aOptValPool =array();	//all puppet's element option $aOptValPool[$sPuppetsUuid][$iElementMappingNo] is an array of values

	private $sPuppetsUuid;			//puppets_no
	private $iElementMappingNo;		//element_mapping_no
	//CAUTION: $sPuppetsUuid.$iElementMappingNo is unique, but single of them is not

	private $oElementMapping;			

	public $sValue;	//element_mapping_value
	public $aOption = array();	//element_mapping_option
	public $bStatus;	//field_status
	public $iOrder;	//field_order
	
	//database setting
	static protected $sDBName = 'PUPPETS';

	/*
		get $oCPuppetsElement by certain puppets_no & element_mapping_no
	*/
	static public function oGetPuppetsElement($sPuppetsUuid,$iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		//set option pool of given docs_no
		self::vSetOptionMap($sPuppetsUuid);
		//set element mapping(static)
		if(empty(self::$aElementPool))
			self::$aElementPool = CElementMapping::aAllElementMapping();	//or "element_mapping_status='1'"

		
		$sSql = "SELECT * FROM galaxy_puppets_mapping WHERE `puppets_no`='$sPuppetsUuid' AND `element_mapping_no`=$iElementMappingNo";

		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCPuppetsElement = new CPuppetsElement($aRow);
		//if this puppets element is checkbox or select or radio
		if($oCPuppetsElement->oElementMapping->sTagType === 'checkbox'
				|| $oCPuppetsElement->oElementMapping->sTagType === 'select'
				|| $oCPuppetsElement->oElementMapping->sTagType === 'radio'
				){
			$oCPuppetsElement->aOption = self::$aOptValPool[$sPuppetsUuid][$iElementMappingNo];
		}
		return $oCPuppetsElement;
	}

	/*
		get all doc element of a certain doc
		CAUTION: $sDocUuid must be given!!!
		CAUTION: return a map of element_mapping_no to $oCDocElement
	*/
	static public function aAllPuppetsElement($sPuppetsUuid,$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		//set option pool of given docs_no
		self::vSetOptionMap($sPuppetsUuid);
		//set element mapping(static)
		if(empty(self::$aElementPool))
			self::$aElementPool = CElementMapping::aAllElementMapping();	//or "element_mapping_status='1'"

		$aAllPuppetsElement = array();
		$iTableNo = substr($sPuppetsUuid,0,1);
		$sSql = "SELECT * FROM galaxy_puppets_mapping WHERE `puppets_no`='$sPuppetsUuid'";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";		
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oCPuppetsElement = new CPuppetsElement($aRow);
			
			//if this puppets element is checkbox or select or radio, set option value
			if($oCPuppetsElement->oElementMapping->sTagType === 'checkbox'
				|| $oCPuppetsElement->oElementMapping->sTagType === 'select'
				|| $oCPuppetsElement->oElementMapping->sTagType === 'radio'
				){
				$oCPuppetsElement->aOption = self::$aOptValPool[$sPuppetsUuid][$oCPuppetsElement->iElementMappingNo];
			}
			$aAllPuppetsElement[$oCPuppetsElement->iElementMappingNo] = $oCPuppetsElement;
		}
		return $aAllPuppetsElement;
	}

	
	/*
		constructor of $oCPuppetsElement
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CPuppetsElement: __construct failed, require an array");
		//initialize vital member
		$this->sPuppetsUuid = $multiData['puppets_no'];
		$this->iElementMappingNo = $multiData['element_mapping_no'];
		if(!isset($this->sPuppetsUuid) || !isset($this->iElementMappingNo))
			throw new Exception("CPuppetsElement: __construct failed, lack of vital member");
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

    	/*
		set element_mapping_option by puppets_no
		it would insert into static option pool
		same puppets_no won't run this function more than once
   	 */
	static private function vSetOptionMap($sPuppetsUuid){
		$oDB = self::oDB(self::$sDBName);
		if(isset(self::$aOptValPool[$sPuppetsUuid]))
			return;
		$aOptionMap = array();
		$iTableNo = substr($sPuppetsUuid,0,1);
		$sSql = "SELECT * FROM galaxy_puppets_mapping_option WHERE `puppets_no`='{$sPuppetsUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			self::$aOptValPool[$sPuppetsUuid][$aRow['element_mapping_no']][] = $aRow['element_mapping_option_no'];
		}
		return;
	}
	

	
	public function sAddPuppetsElement(){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'puppets_no'=>$this->sPuppetsUuid,
								'element_mapping_no'=>$this->iElementMappingNo,
								'element_mapping_value'=>$this->sValue,
								'fields_status'=>$this->bStatus,
								'fields_order'=>$this->iOrder
								
							);
			$oDB->sInsert("galaxy_puppets_mapping",array_keys($aValues),array_values($aValues));
			

			for($i=0;$i<count($this->aOption);$i++){

				$aValues = array(	'puppets_no'=>$this->sPuppetsUuid,
							'element_mapping_no'=>$this->iElementMappingNo,
							'element_mapping_option_no'=>$this->aOption[$i]
						);
				$oDB->sInsert("galaxy_puppets_mapping_option",array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			return $this->sPuppetsUuid;
		}catch (Exception $e){
			
			$oDB->vRollback();
			throw new Exception('oCPuppetsElement->sAddPuppetsElement: '.$e->getMessage());
		}

	}
}
?>