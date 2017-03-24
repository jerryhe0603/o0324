<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/beauty/CBeautyDoc.php');
class CBeautyPrinciple extends CGalaxyClass
{
	static public $aType = array(	'0'=>"NULL",
        							'1'=>"大綱類型1",
        							'2'=>"大綱類型2",
        							'3'=>"大綱類型3",
        							'4'=>"大綱類型4",
        							'5'=>"大綱類型5"
        							);
	private $iPrincipleNo;
	private $sProjectUuid;
	public $sName;
	public $iTypeNo;
	public $sDesc;

	private $__aCBeautyDoc;

	//database setting
	static protected $sDBName = 'SITE';

	/*
		get $oCBeautyPrinciple by certain principle_no
	*/
	static public function oGetPrinciple($iPrincipleNo){
		$oDB = self::oDB(self::$sDBName);
		//query from beauty DB
		$sSql = "SELECT * FROM project_principle WHERE principle_no='$iPrincipleNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCPrinciple = new CBeautyPrinciple($aRow);

		return $oCPrinciple;
	}

	/*
		get all beauty principle in an array
		if $sSearchSql is given, query only match principles
	*/
	static public function aAllPrinciple($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllProject = array();
		$sSql = "SELECT * FROM `project_principle`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllPrinciple[] = new CBeautyPrinciple($aRow);
		}
		return $aAllPrinciple;
	}

	/*
		get count of doc which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(principle_no) as total FROM project_principle";
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
			throw new Exception("CBeautyPrinciple: __construct failed, require an array");
		//initialize member
		$this->iPrincipleNo = $multiData['principle_no'];
		$this->sProjectUuid = $multiData['project_no'];
		$this->sName = $multiData['principle_name'];
		$this->iTypeNo = $multiData['principle_type'];
		$this->sDesc = $multiData['principle_desc'];
		//galaxy class member
		$this->sCreateTime = $multiData['create_time'];
		$this->sModifiedTime = $multiData['modify_time'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

	public function vOverWrite($oCBeautyPrinciple){
		//if not a CDoc object or uuid not match
		if(get_class($oCBeautyPrinciple)!=='CBeautyPrinciple' || $this->iPrincipleNo!==$oCBeautyPrinciple->iPrincipleNo)
			throw new Exception('CBeautyPrinciple->vOverwrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='sProjectUuid' || $name==='iPrincipleNo' || is_null($oCBeautyPrinciple->$name))
				continue;
			$this->$name = $oCBeautyPrinciple->$name;	//overwrite
		}
	}

	public function sType(){
		return self::$aType[$this->iTypeNo];
	}

	/*
		set & get doc of this principle
	*/
	public function aDoc(){
		if(empty($this->__aCBeautyDoc)){
			$this->__aCBeautyDoc = CBeautyDoc::aAllDoc("principle_no='{$this->iPrincipleNo}'");
		}
		return $this->__aCBeautyDoc;
	}

	public function oAcceptor(){
		if(empty($this->__oAcceptor)){
			$this->__oAcceptor = CUser::oGetUser($this->iAcceptUserNo);
		}
		return $this->__oAcceptor;
	}

	public function iDocCount(){
		return CBeautyDoc::iGetCount("principle_no='{$this->iPrincipleNo}'");
	}

	public function iAddPrinciple(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$aValues = array(	'project_no'=>$this->sProjectUuid,
								'principle_name'=>$this->sName,
								'principle_desc'=>$this->sDesc,
								'principle_type'=>$this->iTypeNo,
								'user_no'=>$oCurrentUser->iUserNo,
								'create_time'=>date("Y-m-d H:i:s"),
								'modify_time'=>date("Y-m-d H:i:s")
								);
			$oDB->sInsert("project_principle", array_keys($aValues), array_values($aValues));
			$this->iPrincipleNo = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog('project_principle',$this->iPrincipleNo,$_GET['func'],$_GET['action']);
			return $this->iPrincipleNo;
		}catch(Exception $e){
			throw new Exception("CBeautyPrinciple->iAddPrinciple: ".$e->getMessage());
		}
	}

	public function vUpdatePrinciple(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$aValues = array(	'principle_name'=>$this->sName,
								'principle_desc'=>$this->sDesc,
								'principle_type'=>$this->iTypeNo,
								'user_no'=>$oCurrentUser->iUserNo,
								'modify_time'=>date("Y-m-d H:i:s")
								);
			$oDB->sUpdate("project_principle", array_keys($aValues), array_values($aValues), "`principle_no` = {$this->iPrincipleNo}");
			$oCurrentUser->vAddUserLog('project_principle',$this->iPrincipleNo,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			throw new Exception("CBeautyPrinciple->vUpdatePrinciple: ".$e->getMessage());
		}
	}
}
?>