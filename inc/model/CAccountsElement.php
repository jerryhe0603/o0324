<?php
/*
global:
$oDB
*/
include_once('../inc/model/CElementMapping.php');

Class CAccountsElement extends CGalaxyClass
{
	static private $aElementPool = array();	//all element
	static private $aOptValPool =array();	//all puppet's element option $aOptValPool[$sAccountsUuid][$iElementMappingNo] is an array of values

	private $sAccountsUuid;			//accounts_site_no
	private $iElementMappingNo;		//element_mapping_no
	//CAUTION: $sAccountsUuid.$iElementMappingNo is unique, but single of them is not

	private $oElementMapping;			

	public $sValue;	//element_mapping_value
	public $aOption = array();	//element_mapping_option
	public $bStatus;	//field_status
	public $iOrder;	//field_order
	
	//database setting
	static protected $sDBName = 'PUPPETS';

	/*
		get $oCAccountsElement by certain accounts_site_no & element_mapping_no
	*/
	static public function oGetAccountsElement($sAccountsUuid,$iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		//set option pool of given docs_no
		self::vSetOptionMap($sAccountsUuid);
		//set element mapping(static)
		if(empty(self::$aElementPool))
			self::$aElementPool = CElementMapping::aAllElementMapping();	//or "element_mapping_status='1'"

		
		$sSql = "SELECT * FROM galaxy_accounts_site_mapping WHERE `accounts_site_no`='$sAccountsUuid' AND `element_mapping_no`=$iElementMappingNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCAccountsElement = new CAccountsElement($aRow);
		//if this puppets element is checkbox or select or radio
		if($oCAccountsElement->oElementMapping->sTagType === 'checkbox'
				|| $oCAccountsElement->oElementMapping->sTagType === 'select'
				|| $oCAccountsElement->oElementMapping->sTagType === 'radio'
				){
			$oCAccountsElement->aOption = self::$aOptValPool[$sAccountsUuid][$iElementMappingNo];
		}
		return $oCAccountsElement;
	}

	/*
		get all doc element of a certain doc
		CAUTION: $sDocUuid must be given!!!
		CAUTION: return a map of element_mapping_no to $oCDocElement
	*/
	static public function aAllAccountsElement($sAccountsUuid,$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		//set option pool of given docs_no
		self::vSetOptionMap($sAccountsUuid);
		//set element mapping(static)
		if(empty(self::$aElementPool))
			self::$aElementPool = CElementMapping::aAllElementMapping();	//or "element_mapping_status='1'"

		$aAllAccountsElement = array();
		$iTableNo = substr($sAccountsUuid,0,1);
		$sSql = "SELECT * FROM galaxy_accounts_site_mapping WHERE `accounts_site_no`='$sAccountsUuid'";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";		
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oCAccountsElement = new CAccountsElement($aRow);
			
			//if this puppets element is checkbox or select or radio, set option value
			if($oCAccountsElement->oElementMapping->sTagType === 'checkbox'
				|| $oCAccountsElement->oElementMapping->sTagType === 'select'
				|| $oCAccountsElement->oElementMapping->sTagType === 'radio'
				){
				$oCAccountsElement->aOption = self::$aOptValPool[$sAccountsUuid][$oCAccountsElement->iElementMappingNo];
			}
			$aAllAccountsElement[$oCAccountsElement->iElementMappingNo] = $oCAccountsElement;
		}
		return $aAllAccountsElement;
	}

	
	/*
		constructor of $oCAccountsElement
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CAccountsElement: __construct failed, require an array");
		//initialize vital member
		$this->sAccountsUuid = $multiData['accounts_site_no'];
		$this->iElementMappingNo = $multiData['element_mapping_no'];
		if(!isset($this->sAccountsUuid) || !isset($this->iElementMappingNo))
			throw new Exception("CAccountsElement: __construct failed, lack of vital member");
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
		set element_mapping_option by accounts_site_no
		it would insert into static option pool
		same accounts_site_no won't run this function more than once
   	 */
	static private function vSetOptionMap($sAccountsUuid){
		$oDB = self::oDB(self::$sDBName);
		if(isset(self::$aOptValPool[$sAccountsUuid]))
			return;
		$aOptionMap = array();
		$iTableNo = substr($sAccountsUuid,0,1);
		$sSql = "SELECT * FROM galaxy_accounts_site_mapping_option WHERE `accounts_site_no`='{$sAccountsUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			self::$aOptValPool[$sAccountsUuid][$aRow['element_mapping_no']][] = $aRow['element_mapping_option_no'];
		}
		return;
	}
	

	
	
	public function sAddAccountsElement(){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'accounts_site_no'=>$this->sAccountsUuid,
								'element_mapping_no'=>$this->iElementMappingNo,
								'element_mapping_value'=>$this->sValue,
								'fields_status'=>$this->bStatus,
								'fields_order'=>$this->iOrder
								
							);
			$oDB->sInsert("galaxy_accounts_site_mapping",array_keys($aValues),array_values($aValues));
			

			for($i=0;$i<count($this->aOption);$i++){

				$aValues = array(	'accounts_site_no'=>$this->sAccountsUuid,
							'element_mapping_no'=>$this->iElementMappingNo,
							'element_mapping_option_no'=>$this->aOption[$i]
						);
				$oDB->sInsert("galaxy_accounts_site_mapping_option",array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			return $this->sAccountsUuid;
		}catch (Exception $e){
			
			$oDB->vRollback();
			throw new Exception('oCAccountsElement->sAddAccountsElement: '.$e->getMessage());
		}

	}
}
?>