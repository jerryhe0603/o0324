<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CTask.php');
include_once('../inc/model/CUser.php');

class CTaskGroup extends CGalaxyClass
{
	private $sTaskGroupUuid;
	public $sName;

	private $__iGroupUserNo;
	private $__oGroupUser;

	private $__aCTask;

	//database setting
	static protected $sDBName = 'GENESIS_LOG';

	/*
		get $oCTask by certain task_uuid
	*/
	static public function oGetGroup($sGroupUuid){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_task_group WHERE task_group_uuid='$sGroupUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCTask = new CTaskGroup($aRow);
		return $oCTask;
	}

	/*
		get all task in an array
		if $sSearchSql is given, query only match tasks
	*/
	static public function aAllGroup($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_task_group";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllGroup = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllGroup[] = new CTaskGroup($aRow);
		}
		return $aAllGroup;
	}

	/*
		get count of task which match query
	*/
	static public function iGetCount($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(task_group_uuid) as total FROM galaxy_group";
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

	/*
		constructor of $oCTaskGroup
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CTaskGroup: __construct failed, require an array");
		//initialize vital member
		$this->sTaskGroupUuid = $multiData['task_group_uuid'];	//if someone trys to create a new group, task_group_uuid should be decided first
		$this->sName = $multiData['task_group_name'];
		$this->__iGroupUserNo = $multiData['task_group_user_no'];
		//galaxy class memeber
		$this->sCreateTime = $multiData['created'];
		$this->sModifiedTime = $multiData['modified'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }

    public function oGroupUser(){
    	if(is_null($this->__oGroupUser)){
            $this->__oGroupUser = CUser::oGetUser($this->__iGroupUserNo);
        }
        return $this->__oGroupUser;
    }

    public function aTask($sYear,$sMonth){
    	if(empty($this->__aCTask["{$sYear}_{$sMonth}"])){
    		$this->__aCTask["{$sYear}_{$sMonth}"] = CTask::aAllTask("task_group_uuid='$this->sTaskGroupUuid'",'',$sYear,$sMonth);
    	}
    	return $this->__aCTask["{$sYear}_{$sMonth}"];
    }

    public function vAddGroup(){
    	$oDB = self::oDB(self::$sDBName);
    	$oCurrentUser = self::$session->get('oCurrentUser');

    	$aValues = array(	'task_group_uuid'=>$this->sTaskGroupUuid,
							'task_group_name'=>$this->sName,
							'task_group_user_no'=>$this->__iGroupUserNo,
							'user_no'=>$oCurrentUser->iUserNo,
							'created'=>date("Y-m-d H:i:s"),
							'modified'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->sInsert("galaxy_task_group", array_keys($aValues), array_values($aValues));
			$oCurrentUser->vAddUserLog("galaxy_task_group",$this->sTaskGroupUuid,'task_group','add');
		}catch (Exception $e){
			throw new Exception("CTaskGroup->vAddGroup: ".$e->getMessage());
		}

    }

    public function vUpdateGroup(){
    	$oDB = self::oDB(self::$sDBName);
    	$oCurrentUser = self::$session->get('oCurrentUser');

    	$aValues = array(	'task_group_name'=>$this->sName,
							'modified'=>date("Y-m-d H:i:s")
							);
		try{
			$oDB->sUpdate("galaxy_task_group", array_keys($aValues), array_values($aValues), "`task_group_uuid`='{$this->sTaskGroupUuid}'");
			$oCurrentUser->vAddUserLog("galaxy_task_group",$this->sTaskGroupUuid,'task_group','edit');
		}catch (Exception $e){
			throw new Exception("CTaskGroup->vUpdateGroup: ".$e->getMessage());
		}
    }

    public function vDeleteGroup(){
    	$oDB = self::oDB(self::$sDBName);
    	$oCurrentUser = self::$session->get('oCurrentUser');
    	//only LastUser can remove this group
    	if($this->__iUserNo !== self::$session->get('oCurrentUser')->iUserNo)
    		throw new Exception("CTaskGroup->vDeleteGroup: you has no authority to this group");
    	try{
    		$oDB->vDelete("galaxy_task_group", "`task_group_uuid`='{$this->sTaskGroupUuid}'");
			$oCurrentUser->vAddUserLog("galaxy_task_group",$this->sTaskGroupUuid,'task_group','delete');
    	}catch(Exception $e){
    		throw new Exception("CTaskGroup->vDeleteGroup: ".$e->getMessage());	
    	}
    }
}
?>