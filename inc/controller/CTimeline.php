<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CTask.php');
include_once('../inc/model/CTaskGroup.php');
include_once('../inc/model/CTask.php');
include_once('../inc/model/CUserLog.php');

class CTimeline extends CGalaxyController
{
	/*
		exception code of this controller
	*/
	const BACK_TO_INDEX = 1;

	//DB setting
	private $sDBName = 'GENESIS_LOG';
	
	/*
		constructor of this controller
	*/
	public function __construct(){
	}

	/*
		entry of this controller
		handle exception and decide where to redirect by exception code
	*/
	public function tManager() {
		try{
			switch($_GET['action']){
				default:
				case "user_view":
					return $this->tUserView();
					break;
			}
		}catch (Exception $e){
			CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
		}
		exit;
	}

	/*
		get task of target project, sub system independent
	*/
	public function aGetProjectTask(){
		if(empty($_GET['project_no']))
			throw new Exception("require target project");
		$sProjectUuid = $_GET['project_no'];

		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];

		$iNextMonth = $iStart;

		while($iNextMonth < $iEnd){
			$aUserTasks = CTask::aAllTask("task_start_date >='$iStart' AND task_start_date <='$iEnd' 
			AND task_group_uuid='$sProjectUuid'",'',date('Y',$iNextMonth),date('m',$iNextMonth));
			foreach ($aUserTasks as $oTask) {
				$aAllTaskUser = array();	//clear previous task_user array
				//set [0] be current task_user_name
				$aAllTaskUser[] = array('user_no'=>$oTask->oTaskUser()->iUserNo,
										'user_name'=>$oTask->oTaskUser()->sName
										);
				//add all family tasks's task_user_name into array
				foreach ($oTask->aFamilies() as $oGroupTask) {
					$aAllTaskUser[] = array('user_no'=>$oGroupTask->oTaskUser()->iUserNo,
											'user_name'=>$oGroupTask->oTaskUser()->sName
											);
				}
				//sub objects
				$oEvent = $oTask->oEvent();
				//add to array for return
				$aAllTask[] = array(	'id' => $oTask->iTaskNo,
	                                    'table_id' => $oTask->sFunc."_".$oTask->sAction."_".$oTask->sTableUuid,
	                                    'event_no' => $oTask->iEventNo,
	                                    'event_name' => (!is_null($oEvent))?$oEvent->sName:'Undefined Event',
	                                    'content' => $oTask->sName,
	                                    'url' => $oTask->sUrl,
	                                    'start' => $oTask->iStartDate,
	                                    'end' => ($oTask->iStartDate===$oTask->iEndDate)?0:$oTask->iEndDate,
	                                    'group' => $oTask->oTaskUser()->sName,
	                                    'editable'=> false,
	                                    'allDay' =>(bool)$oTask->bStatus,
	                                    'description' => $oTask->sDesc, //non-standard Event Object field
	                                    'task_group_uuid' => $oTask->sTaskGroupUuid,
	                                    'task_user' => $aAllTaskUser
									); 
			}
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array(	"events"=>$aAllTask,
						"errorMsg"=>''
						);
	}

	/*
		get task of target user
	*/
	public function aGetUserTask(){
		if(empty($_GET['user_no']))
			$iUserNo = self::$session->get('oCurrentUser')->iUserNo;
		else
			$iUserNo = $_GET['user_no'];

		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];

		$iNextMonth = $iStart;

		while($iNextMonth < $iEnd){
			$aUserTasks = CTask::aAllTask("task_start_date >='$iStart' AND task_start_date <='$iEnd' 
			AND task_user_no='$iUserNo'",'',date('Y',$iNextMonth),date('m',$iNextMonth));
			foreach ($aUserTasks as $oTask) {
				//clear previous task's array and set [0] be current task_user_name
				$aAllTaskUser = array();	//clear task_user array
				$aAllTaskUser[] = array('user_no'=>$oTask->oTaskUser()->iUserNo,
										'user_name'=>$oTask->oTaskUser()->sName
										);
				//add all family tasks's task_user_name into array
				foreach ($oTask->aFamilies() as $oGroupTask) {
					$aAllTaskUser[] = array('user_no'=>$oGroupTask->oTaskUser()->iUserNo,
											'user_name'=>$oGroupTask->oTaskUser()->sName
											);
				}
				//sub objects
				$oRule = $oTask->oRule();
				if(!is_null($oRule))
					$oCategory = $oRule->oCategory();
				else
					$oCategory = null;
				$oEvent = $oTask->oEvent();
				//add to array for return
				$aAllTask[] = array(	'id' => $oTask->iTaskNo,
	                                    'table_id' => $oTask->sFunc."_".$oTask->sAction."_".$oTask->sTableUuid,
	                                    'event_no' => $oTask->iEventNo,
	                                    'event_name' => (!is_null($oEvent))?$oEvent->sName:'Undefined Event',
	                                    'content' => $oTask->sName,
	                                    'url' => $oTask->sUrl,
	                                    'start' => $oTask->iStartDate,
	                                    'end' => ($oTask->iStartDate===$oTask->iEndDate)?0:$oTask->iEndDate,
	                                    'group' => (!is_null($oCategory))?$oCategory->sName:'Undefined Category',
	                                    'editable'=> false,
	                                    'allDay' =>(bool)$oTask->bAllDay,
	                                    'description' => $oTask->sDesc, //non-standard Event Object field
	                                    'task_group_uuid' => $oTask->sTaskGroupUuid,
	                                    'task_user' => $aAllTaskUser
									); 
			}
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array(	"events"=>$aAllTask,
						"errorMsg"=>''
						);
	}

	/*
		get log of target project, sub system dependent, can't compelete function here
		extendsion required
	*/
	public function aGetProjectLog(){
		//implement in child class
	}

	/*
		get log of target user
	*/
	public function aGetUserLog(){
		if(empty($_GET['user_no']))
			$iUserNo = self::$session->get('oCurrentUser')->iUserNo;
		else
			$iUserNo = $_GET['user_no'];

		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];

		$iNextMonth = $iStart;

		while($iNextMonth < $iEnd){
			$aUserLogs = CUserLog::aAllLog("UNIX_TIMESTAMP(modifiedtime) >='$iStart' AND UNIX_TIMESTAMP(modifiedtime) <='$iEnd' AND user_no='$iUserNo'",'',date('Y',$iNextMonth),date('m',$iNextMonth));
			foreach ($aUserLogs as $oLog) {
				$oRule = $oLog->oRule();
				if(!is_null($oRule))
					$oCategory = $oRule->oCategory();
				else
					$oCategory = null;
				$aAllLog[] = array(	'content' => (!is_null($oRule))?$oRule->sName:"{$oLog->sFunc}:{$oLog->sAction}",
									'start' => strtotime($oLog->sLocalModifiedTime()),
									'group' => (!is_null($oCategory))?$oCategory->sName:'Undefined Category',
									'editable'=> false,
									'id' => $oLog->iLogNo,
									'table_id' => $oLog->sFunc."_".$oLog->sAction."_".$oLog->sTableUuid,
									); 
			}
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array(	"events"=>$aAllLog,
						"errorMsg"=>''
						);
	}

	/*
		show target user's task and log
	*/
	public function tUserView(){
		$Smarty = self::$Smarty;
		if(empty($_GET['user_no']))
			$iUserNo = self::$session->get('oCurrentUser')->iUserNo;
		else
			$iUserNo = $_GET['user_no'];
		$Smarty->assign('iUserNo',$iUserNo);
		return $output = $Smarty->fetch('./admin/'.get_class($this).'/user_timeline_view.html');
	}
}
?>