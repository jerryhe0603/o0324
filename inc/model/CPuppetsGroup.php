<?php

/**
 * 身份 file
 */
include_once('../inc/model/CGalaxyClass.php');
class CPuppetsGroup extends CGalaxyClass
{
	private $sPuppetsUuid;	//身份uuid
	public $aGroupNo;		//使用群組流水號
	static const $aGroupName = array("all","beauty","goods");
	

	//database setting
	static protected $sDBName = 'PUPPETS';

	/*
		get $oCPuppetsGroup by certain group_no($sPuppetsUuid)
	*/
	static public function oGetGroup($sPuppetsUuid="") {
		if($sPuppetsUuid==="") return null;
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT group_no FROM galaxy_puppets_group WHERE puppets_no = '$sPuppetsUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aGroupNo = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aGroupNo[] = $aRow['group_no'];
		}
		$oCPuppetsGroup = new CPuppetsGroup($sPuppetsUuid,$aGroupNo);

		return $oCPuppetsGroup;
	}


	/*
		constructor of $oCPuppetsGroup
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($sPuppetsUuid,$multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CPuppetsGroup: __construct failed, require an array");

		if($sPuppetsUuid==="" || !isset($sPuppetsUuid))
			throw new Exception("CPuppetsGroup: __construct failed, require an puppets_no uuid");	

		//initialize optional member
		$this->sPuppetsUuid = $sPuppetsUuid;
		$this->aGroupNo = $multiData;
		

		
	}

	

	/*
		update file data
		step1: $oCPuppetsGroup = CPuppetsGroup: oGetGroup($sPuppetsUuid)  and create a CPuppetsGroup with all info
		step2: $oCPuppetsGroup->overwrite($oNewCPuppetsGroup);
		step3: call this function
	*/
	public function vUpdateGroup(){
		$oDB = self::oDB(self::$sDBName);
		
		try{
			$oDB->vBegin();
			
			$sSql = "DELETE FROM galaxy_puppets_group WHERE  puppets_no = '{$this->sPuppetsUuid}'";
			$iRes = $oDB->iQuery($sSql);

			for($i=0;$i<count($this->aGroupNo);$i++){
				$aValues = array(	'puppets_no'=>$this->sPuppetsUuid,
							'group_no'=>$this->aGroupNo[$i]
						);
				$oDB->sInsert("galaxy_puppets_group",array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CPuppetsGroup->vUpdateGroup: '.$e->getMessage());
		}
	}
	
}
?>