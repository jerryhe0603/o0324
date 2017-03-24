<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CTaskGroup.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CUserLog.php');
include_once('../inc/model/CRule.php');
include_once('../inc/model/CEvent.php');
include_once('../inc/model/CTaskGroup.php');

class CTask extends CGalaxyClass
{
	private $iTaskNo;
	public $sTaskGroupUuid;
	public $sTaskUserGroupUuid;
	public $iEventNo;
	public $sName;
	public $sDesc;
	public $sFunc;
	public $sAction;
	public $sTableUuid;
	public $sUrl;
	public $iStartDate;	//timestamp
	public $iEndDate;	//timestamp
	public $bAllDay;
	public $__iTaskUserNo;

	//members that set only when corresponding function is called
	private $__oTaskUser;
	private $__oEvent;
	private $__aCUserLog=array();
	private $__aFamilies=array();	//tasks which have same task_group_user_uuid, set & return by $this->aFamilies()
	private $__oCRule;

	//extra members which not in DB, which record task's year and month
	public $sYear;
	public $sMonth;

	//database setting
	static protected $sDBName = 'GENESIS_LOG';

	/*
		get $oCTask by certain task_uuid
	*/
	static public function oGetTask($iTaskNo,$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("galaxy_task_{$sYear}_{$sMonth}"))
			return null;

		$sSql = "SELECT * FROM galaxy_task_{$sYear}_{$sMonth} WHERE task_no='$iTaskNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCTask = new CTask($aRow,$sYear,$sMonth);
		return $oCTask;
	}

	/*
		get all task in an array
		if $sSearchSql is given, query only match tasks
	*/
	static public function aAllTask($sSearchSql='',$sPostFix='',$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("galaxy_task_{$sYear}_{$sMonth}"))
			return array();

		$sSql = "SELECT * FROM galaxy_task_{$sYear}_{$sMonth}";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllTask = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllTask[] = new CTask($aRow,$sYear,$sMonth);
		}
		return $aAllTask;
	}

	/*
		get count of task which match query
	*/
	static public function iGetCount($sSearchSql='',$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(task_no) as total FROM galaxy_task_{$sYear}_{$sMonth}";
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
		get task_group objects in an array for given condition
	*/
	static public function aGetGroups($sSearchSql='',$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("galaxy_task_{$sYear}_{$sMonth}"))
			return array();
		$sSql = "SELECT task_group_uuid FROM galaxy_task_{$sYear}_{$sMonth}";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		$sSql .= " GROUP BY task_group_uuid";
		$iDbq = $oDB->iQuery($sSql);
		$aGroups = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oGroup = CTaskGroup::oGetGroup($aRow['task_group_uuid']);
			if($oGroup!==null)
				$aGroups[] = $oGroup;
		}
		return $aGroups;
	}

	/*
		get distint user by task_group_uuid
	*/
	static public function aDistintUser($sTaskGroupUuid,$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(empty($sTaskGroupUuid) || empty($sYear) || $sMonth)
			return array();

		$sSql = "SELECT DISTINT `task_user_no` FROM galaxy_task_{$sYear}_{$sMonth} WHERE `task_group_uuid`='$sProjectUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aGroupUser = array();
		while ($aRow = $oDB->aFetchAssoc($iDbq)) {
			$aGroupUser[] = CUser::oGetUser($aRow['task_user_no']);
		}
		return $aGroupUser;
	}

	/*
		constructor of $oCTask
	*/
	public function __construct($multiData,$sYear,$sMonth){
		parent::__construct($multiData);
		if(!is_array($multiData) || !is_string($sYear) || !is_string($sMonth))
			throw new Exception("CTask: __construct failed, require an array and strings");
		//initialize vital member
		$this->iTaskNo = $multiData['task_no'];
		$this->sTaskGroupUuid = $multiData['task_group_uuid'];
		$this->sTaskUserGroupUuid = $multiData['task_user_group_uuid'];
		$this->iEventNo = $multiData['event_no'];
		$this->sName = $multiData['task_name'];
		$this->sDesc = $multiData['task_desc'];
		$this->sFunc = $multiData['task_func'];
		$this->sAction = $multiData['task_action'];
		$this->sTableUuid = $multiData['table_id'];
		$this->sUrl = $multiData['task_url'];
		$this->iStartDate = $multiData['task_start_date'];	//timestamp
		$this->iEndDate = $multiData['task_end_date'];	//timestamp
		$this->bAllDay = $multiData['is_allday'];
		$this->__iTaskUserNo = $multiData['task_user_no'];
		//extra member
		$this->sYear = $sYear;
		$this->sMonth = $sMonth;
		//galaxy class memeber
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['created'];
		$this->sModifiedTime = $multiData['modified'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }

    /*
    	set & get task user
    */
    public function oTaskUser(){
    	if(is_null($this->__oTaskUser)){
            $this->__oTaskUser = CUser::oGetUser($this->__iTaskUserNo);
        }
        return $this->__oTaskUser;
    }

    public function oEvent(){
    	if(is_null($this->__oEvent)){
            $this->__oEvent = CEvent::oGetEvent($this->iEventNo);
        }
        return $this->__oEvent;
    }

    /*
    	set & get coresponding log objects
    */
    public function aLog(){
    	if(empty($this->__aCUserLog)){
    		$this->__aCUserLog = CUserLog::aAllLog("table_id='{$this->sTableUuid}' AND table_func={$this->sFunc} AND table_action={$this->sAction}");
    	}
    	return $this->__aCUserLog;
    }

    /*
    	set & get tasks with same task_group_user_uuid
    */
    public function aFamilies(){
    	if(empty($this->__aFamilies) && !empty($this->sTaskUserGroupUuid)){
    		$this->__aFamilies = CTask::aAllTask("task_user_group_uuid='{$this->sTaskUserGroupUuid}' AND task_no!={$this->iTaskNo}",'',$this->sYear,$this->sMonth);
    	}
    	return $this->__aFamilies;
    }

    /*
    	set & get rule of this rask
    */
    public function oRule(){
    	if(empty($this->__oCRule)){
    		$aCRule = CRule::aAllRule("func='{$this->sFunc}' AND action='{$this->sAction}'");
    		if(count($aCRule)>1)
				return;
    		$this->__oCRule = $aCRule[0];
    	}
    	return $this->__oCRule;
    }

    /*
    	add tasks for each given user_no
    */
    public function vAddTask($aTaskUser){
    	$oDB = self::oDB(self::$sDBName);
    	if(!is_array($aTaskUser))
    		throw new Exception("CTask->vAddTask: require task_user array");
    	if(count($aTaskUser)===0){
    		return;
    	}
    	if(count($aTaskUser)>1 && empty($this->sTaskUserGroupUuid)){
    		$this->sTaskUserGroupUuid = CMisc::uuid_v1();
    	}
    	if(!$oDB->bIsTableExist("galaxy_task_{$this->sYear}_{$this->sMonth}"))
    		self::vAddTaskTable($this->sYear,$this->sMonth);
    	try{
    		$oDB->vBegin();
    		$aTaskNos = array();
    		foreach ($aTaskUser as $iKey=>$iTaskUSerNo) {
	    		$aValues = array(	'task_group_uuid'=>$this->sTaskGroupUuid,
		 							'task_user_group_uuid'=>$this->sTaskUserGroupUuid,
									'event_no'=>$this->iEventNo,
									'task_name'=>$this->sName,
									'task_desc'=>$this->sDesc,
									'task_func'=>$this->sFunc,
									'task_action'=>$this->sAction,
									'table_id'=>$this->sTableUuid,
									'task_url'=>$this->sUrl,
									'task_start_date'=>$this->iStartDate,
									'task_end_date'=>$this->iEndDate,
									'is_allday'=>$this->bAllDay,
									'task_user_no'=>$iTaskUSerNo,
									'user_no'=>$oCurrentUser->iUserNo,
									'status'=>$this->bStatus,
									'created'=>date("Y-m-d H:i:s"),
									'modified'=>date("Y-m-d H:i:s")
	    							);
	    		$oDB->sInsert("galaxy_task_{$this->sYear}_{$this->sMonth}",array_keys($aValues),array_values($aValues));
	    		$aTaskNos[] = $oDB->iGetInsertId();
	    		if($iKey===0){
	    			$this->__iTaskUserNo = $iTaskUSerNo;
	    			$this->iTaskNo = $oDB->iGetInsertId();
	    		}
	    	}
	    	$oDB->vCommit();
	    	foreach ($aTaskNos as $iTaskNo){
	    		$oCurrentUser->vAddUserLog("galaxy_task_{$this->sYear}_{$this->sMonth}",$iTaskNo,'task','add');
	    	}
    	}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CTask->vAddTask: ".$e->getMessage());
		}
    }

    /*
    	update task
    	if task had families, update them all
    	if $aTaskUser is given, check new and remove task_user
    */
    public function vUpdateTask($aTaskUser=array()){
    	$oDB = self::oDB(self::$sDBName);
    	if(!empty($aTaskUser)){
    		//if had only one user, but update with more
			if(empty($this->sTaskUserGroupUuid) && count($aTaskUser)>1)
				$this->sTaskUserGroupUuid = CMisc::uuid_v1();

	    	//compare families to $aTaskUser
	    	$aFamilyUser = array($this->__iTaskUserNo);
	    	foreach ($this->aFamilies() as $oGroupTask) {
				$aFamilyUser[] = $oGroupTask->__iTaskUserNo;
			}
			$aNewTaskUser = array_diff($aTaskUser, $aFamilyUser);
			$aRemoveTaskUser =  array_diff($aFamilyUser, $aTaskUser);
    	}else{
    		$aNewTaskUser = array();
			$aRemoveTaskUser =  array();
    	}
		
    	//update data
    	$aValues = array(	'task_group_uuid'=>$this->sTaskGroupUuid,
    						'task_user_group_uuid'=>$this->sTaskUserGroupUuid,
    						'event_no'=>$this->iEventNo,
							'task_name'=>$this->sName,
							'task_desc'=>$this->sDesc,
							'task_func'=>$this->sFunc,
							'task_action'=>$this->sAction,
							'table_id'=>$this->sTableUuid,
							'task_url'=>$this->sUrl,
							'task_start_date'=>$this->iStartDate,
							'task_end_date'=>$this->iEndDate,
							'is_allday'=>$this->bAllDay,
							'status'=>$this->bStatus,
							'modified'=>date("Y-m-d H:i:s")
							);
    	try{
    		$oDB->vBegin();

    		//if previous task_user is removed
    		if(count($aRemoveTaskUser)!==0){
    			foreach ($aRemoveTaskUser as $iRmUserNo) {
    				//if this task it self should be remove
    				if($iRmUserNo === $this->__iTaskUserNo){
    					$oDB->vDelete("galaxy_task_{$this->sYear}_{$this->sMonth}", "`task_no`='{$this->iTaskNo}'");
    				}
    				foreach ($this->aFamilies() as $oGroupTask) {
	    				if($iRmUserNo === $oGroupTask->__iTaskUserNo){
	    					$oDB->vDelete("galaxy_task_{$oGroupTask->sYear}_{$oGroupTask->sMonth}", "`task_no`='{$oGroupTask->iTaskNo}'");
	    					break;
	    				}
	    			}
    			}
    		}

    		//update remain tasks
    		if(count($this->aFamilies())>0)
    			$oDB->sUpdate("galaxy_task_{$this->sYear}_{$this->sMonth}", array_keys($aValues), array_values($aValues), "`task_user_group_uuid`='{$this->sTaskUserGroupUuid}'");
    		else
	    		$oDB->sUpdate("galaxy_task_{$this->sYear}_{$this->sMonth}", array_keys($aValues), array_values($aValues), "`task_no`='{$this->iTaskNo}'");

	    	//if new task_user is added
    		if(count($aNewTaskUser)!==0)
    			$this->vAddTask($aNewTaskUser);

			$oDB->vCommit();    		
	    	$oCurrentUser->vAddUserLog("galaxy_task_{$this->sYear}_{$this->sMonth}",$this->iTaskNo,'task','edit');
    	}catch (Exception $e){
    		$oDB->vRollback();
			throw new Exception("CTask->vUpdateTask: ".$e->getMessage());
		}
    }

    /*
    	delete task, if task had user_group_uuid, delete them all
    */
    public function vDeleteTask(){
    	$oDB = self::oDB(self::$sDBName);
    	try{
    		if(empty($this->sTaskUserGroupUuid))
	    		$oDB->vDelete("galaxy_task_{$this->sYear}_{$this->sMonth}", "`task_no`='{$this->iTaskNo}'");
	    	else
	    		$oDB->vDelete("galaxy_task_{$this->sYear}_{$this->sMonth}", "`task_user_group_uuid`='{$this->sTaskUserGroupUuid}'");
	    	$oCurrentUser->vAddUserLog("galaxy_task_{$this->sYear}_{$this->sMonth}",$this->iTaskNo,'task','delete');
    	}catch (Exception $e){
			throw new Exception("CTask->vDeleteTask: ".$e->getMessage());
		}
    }

    /*
    	create task table
    */
    static private function vAddTaskTable($sYear,$sMonth){
    	$oDB = self::oDB(self::$sDBName);
    	$aTableInfo = $oDB->aGetCreateTableInfo("galaxy_task");
		if(!empty($aTableInfo['Create Table'])){
			$aTableInfo['Create Table'] = preg_replace("/galaxy_task/i", "galaxy_task_{$sYear}_{$sMonth}", $aTableInfo['Create Table']);
		}
		$oDB->iQuery($aTableInfo['Create Table'].";\n\n");
    }
}
?>