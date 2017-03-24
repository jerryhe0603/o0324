<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CUserIwant.php');
include_once('../inc/model/CUserLog.php');
include_once('../inc/model/CEvent.php');
include_once('../inc/model/CTask.php');
include_once('../inc/model/CTaskGroup.php');
include_once('../inc/model/CGalaxyEvent.php');


class CCalendar extends CGalaxyController
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
				//for ajax start
				case "group_add":
					return $this->tGroupAdd();
					break;
				case "group_edit":
					return $this->tGroupEdit();
					break;
				case "group_delete":
					return $this->tGroupDelete();		
					break;
				case "add":
					return $this->tTaskAdd();
					break;
				case "edit":
					return $this->tTaskEdit();
					break;
				case "delete":
					return $this->tTaskDelete();
					break;
				case "resize":
				case "move":
					return $this->tTaskMove();
					break;
				//for ajax end
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
		show target user's task and log
	*/
	public function tUserView(){
		$Smarty = self::$Smarty;
		if(empty($_GET['user_no'])){
			$iUserNo = self::$session->get('oCurrentUser')->iUserNo;
		}else{
			$iUserNo = $_GET['user_no'];
			$Smarty->assign('oUser',CUser::oGetUser($iUserNo));
		}

		$Smarty->assign('iUserNo',$iUserNo);
		$Smarty->assign('aEvent',CEvent::aAllEvent());
		return $output = $Smarty->fetch('./admin/'.get_class($this).'/user_calendar_view.html');
	}

	/****************************************************************
	*	following functions are for ajax return with tpl, or exec 	*
	*****************************************************************/

	private function tGroupAdd(){
		$Smarty = self::$Smarty;
		if(empty($_POST)){
			$Smarty->assign('oGroup',$oGroup);
			echo $Smarty->fetch('./admin/'.get_class($this).'/task_group_edit.html');
			exit;
		}else{
			try{
				$_POST['task_group_uuid'] = CMisc::uuid_v1('user_calendar_');	//decide task_group_uuid
				$_POST['task_group_user_no'] = self::$session->get('oCurrentUser')->iUserNo;
				$oGroup = new CTaskGroup($_POST);
				$oGroup->vAddGroup();
			}catch(Exception $e){
				echo json_encode(array("errorMsg"=>$e->getMessage()));
				exit;
			}

			echo json_encode(array(	'errorMsg'=>'',
									'task_group_uuid'=>$oGroup->sTaskGroupUuid,
									'task_group_name'=>$oGroup->sName
									));
			exit;
		}
	}

	private function tGroupEdit(){
		$Smarty = self::$Smarty;
		$sTaskGroupUuid = $_GET['task_group_uuid'];
		$oGroup = CTaskGroup::oGetGroup($sTaskGroupUuid);
		if(empty($_POST)){
			$Smarty->assign('oGroup',$oGroup);
			echo $Smarty->fetch('./admin/'.get_class($this).'/task_group_edit.html');
			exit;
		}else{
			try{
				$oGroup->sName = $_POST['task_group_name'];
				$oGroup->vUpdateGroup();
			}catch(Exception $e){
				echo json_encode(array("errorMsg"=>$e->getMessage()));
				exit;
			}
			echo json_encode(array(	'errorMsg'=>'',
									'task_group_name'=>$oGroup->sName
									));
			exit;
		}
	}
	
	private function tGroupDelete(){
		$sTaskGroupUuid = $_GET['task_group_uuid'];
		$oGroup = CTaskGroup::oGetGroup($sTaskGroupUuid);
		try{
			$oGroup->vDeleteGroup();
		}catch(Exception $e){
			echo json_encode(array("errorMsg"=>$e->getMessage()));
			exit;
		}
		echo json_encode(array(	'errorMsg'=>''));
		exit;
	}
	
	private function tTaskAdd(){
		$Smarty = self::$Smarty;
		$oCurrentUser = self::$session->get('oCurrentUser');
		if(empty($_POST)){
			//return tpl
			if (date('Y-m-d', strtotime($_GET['start'])) == $_GET['start']) 
				$Smarty->assign('task_start_date',	$_GET['start']);
			else
				$Smarty->assign('task_start_date',date('Y-m-d'));	

			if (date('Y-m-d', strtotime($_GET['end'])) == $_GET['end']) 
				$Smarty->assign('task_end_date',	$_GET['end']);
			else
				$Smarty->assign('task_end_date',date('Y-m-d'));

			$Smarty->assign('oCurrentUser',self::$session->get('oCurrentUser'));
			$Smarty->assign('aEvent',CEvent::aAllEvent());
			$Smarty->assign('aAllUser',CUserIwant::aAllUser("galaxy_user.status='1'"));
			$aTaskGroup = CTaskGroup::aAllGroup("task_group_user_no='{$oCurrentUser->iUserNo}'",'ORDER BY created ASC');
			$Smarty->assign('aGroups',$aTaskGroup);
			echo $Smarty->fetch('./admin/'.get_class($this).'/task_add.html');
			exit;
		}else{

			self::vVaildPost($_POST);
			$sStartDate = $_POST['task_start_date'].' '.$_POST['task_start_time']; //datetime in string from user
			$sEndDate = $_POST['task_end_date'].' '.$_POST['task_end_time'];  //datetime in string from user
			$iStartDate = strtotime($sStartDate); 
			$iEndDate = strtotime($sEndDate); 
			$_POST['task_start_date'] = $iStartDate;	//overwrite in timestamp
			$_POST['task_end_date'] = $iEndDate;	//overwrite in timestamp

			if(count($_POST['task_user'])==0){
				echo json_encode(array("errorMsg"=>'no task user'));
				exit;
			}

			//get task info from client
			$oTask = new CTask($_POST,date('Y',$iStartDate),date('m',$iEndDate));
			$aTaskUser = $_POST['task_user'];
			try{
				$oTask->vAddTask($aTaskUser);	//add tasks for each task_user
			}catch(Exception $e){
				echo json_encode(array("errorMsg"=>$e->getMessage()));
				exit;
			}

			//user names for return
			$aUserName = array();
			foreach ($aTaskUser as $iUserNo) {
				$oUser = CUser::oGetUser($iUserNo);
				$aUserName[] = array(	'user_no'=>$iUserNo,
										'user_name'=>$oUser->sName
										);
			}

			$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
			$sPrefix = $aTempSpliter[0];

			echo json_encode(array(
				'id' => $sPrefix."_task_".$oTask->iTaskNo,
				'title' => $_POST['task_name'],
				'url' => $_POST['task_url'],
				'start' => $sStartDate,
				'end' => $sEndDate,
				'allDay' => (bool)$oTask->bAllDay,
				'editable'=> true,
				'description' => $_POST['task_desc'], //non-standard Task Object field, 描述
				'task_group_uuid' => $_POST['task_group_uuid'],//群組使用者uuid
				'task_user' => $aUserName,//所有參與人員
				'errorMsg'=>""
			));
			exit;
		}
	}
	
	private function tTaskEdit(){
		$Smarty = self::$Smarty;
		$oCurrentUser = self::$session->get('oCurrentUser');
		if(empty($_POST)){
			//return error in string
			if(empty($_GET['task_no'])){
				echo _LANG_MY_CALENDAR_NO_TASKNO;
				exit;
			}
			if(preg_match("/^[a-zA-Z]+_task_([0-9]+)$/i",$_GET['task_no'],$match)){
				 $iTaskNo = $match[1];
			}else{
				echo _LANG_MY_CALENDAR_NO_TASKNO;
				exit;
			}
			if (date('Y-m-d', strtotime($_GET['start'])) != $_GET['start']) {
				echo _LANG_MY_CALENDAR_VAILD_START_DATE;
				exit;
			}
			$oTask = CTask::oGetTask($iTaskNo,date('Y',strtotime($_GET['start'])),date('m',strtotime($_GET['start'])));

			$aAllUser = CUserIwant::aAllUser("galaxy_user.status='1'");
			foreach ($aAllUser as $oUser) {
				//if user_no = task_user, let it be selected
				if($oUser->iUserNo === $oTask->__iTaskUserNo){
					$oUser->bSelected = '1';
					continue;
				}
				//each family tasks, let the task_user_no be selected
				foreach ($oTask->aFamilies() as $oGroupTask) {
					if($oUser->iUserNo === $oGroupTask->__iTaskUserNo){
						$oUser->bSelected = '1';
						break;
					}
				}
			}

			$Smarty->assign('oTask',$oTask);
			$Smarty->assign('oCurrentUser',$oCurrentUser);
			$Smarty->assign('aEvent',CEvent::aAllEvent());
			$Smarty->assign('aAllUser',$aAllUser);
			$aTaskGroup = CTaskGroup::aAllGroup("task_group_user_no='{$oCurrentUser->iUserNo}'",'ORDER BY created ASC');
			$Smarty->assign('aGroups',$aTaskGroup);
			echo $Smarty->fetch('./admin/'.get_class($this).'/task_edit.html');
			exit;
		}else{
			//return error in json
			if(empty($_GET['task_no'])){
				echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_NO_TASKNO));
				exit;
			}
			if(preg_match("/^[a-zA-Z]+_task_([0-9]+)$/i",$_GET['task_no'],$match)){
				 $iTaskNo = $match[1];
			}else{
				echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_NO_TASKNO));
				exit;
			}
			if (date('Y-m-d', strtotime($_GET['start'])) != $_GET['start']) {
				echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
				exit;
			}

			$oTask = CTask::oGetTask($iTaskNo,date('Y',strtotime($_GET['start'])),date('m',strtotime($_GET['start'])));
			$sStartDate = $_POST['task_start_date'].' '.$_POST['task_start_time']; 
			$sEndDate = $_POST['task_end_date'].' '.$_POST['task_end_time']; 
			$iStartDate = strtotime($sStartDate); 
			$iEndDate = strtotime($sEndDate); 
			$oTask->iStartDate = $iStartDate;
			$oTask->iEndDate = $iEndDate;

			$oTask->sTaskGroupUuid = $_POST['task_group_uuid'];
			$oTask->iEventNo = $_POST['event_no'];
			$oTask->sName = $_POST['task_name'];
			$oTask->sDesc = $_POST['task_desc'];
			$oTask->sFunc = $_POST['task_func'];
			$oTask->sAction = $_POST['task_action'];
			$oTask->sTableUuid = $_POST['table_id'];
			$oTask->sUrl = $_POST['task_url'];
			$oTask->bAllDay = $_POST['is_allday'];
			$oTask->bStatus = $_POST['status'];

			$aTaskUser = $_POST['task_user'];

			try{
				$oTask->vUpdateTask($aTaskUser);
			}catch(Exception $e){
				echo json_encode(array("errorMsg"=>$e->getMessage()));
				exit;
			}

			//user names for return
			$aUserName = array();
			foreach ($aTaskUser as $iUserNo) {
				$oUser = CUser::oGetUser($iUserNo);
				$aUserName[] = array(	'user_no'=>$iUserNo,
										'user_name'=>$oUser->sName
										);
			}

			$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
			$sPrefix = $aTempSpliter[0];

			echo json_encode(array(
				'id' => $sPrefix."_task_".$iTaskNo,
				'title' => $_POST['task_name'],
				'url' => $_POST['task_url'],
				'start' => $sStartDate,
				'end' => $sEndDate,
				'allDay' => (bool)$_POST['is_allday'],
				'editable'=> true,
				'description' => $_POST['task_desc'], //non-standard Task Object field, 描述
				'task_group_uuid' => $_POST['task_group_uuid'],//群組使用者uuid
				'task_user' => $aUserName,//所有參與人員
				'errorMsg'=>""
			));
			exit;
		}
	}
	
	private function tTaskDelete(){
		if(empty($_GET['task_no'])){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_NO_TASKNO));
			exit;
		}
		if(preg_match("/^[a-zA-Z]+_task_([0-9]+)$/i",$_GET['task_no'],$match)){
			 $iTaskNo = $match[1];
		}else{
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_NO_TASKNO));
			exit;
		}
		if (date('Y-m-d', strtotime($_GET['start'])) != $_GET['start']) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
			exit;
		}

		$oTask = CTask::oGetTask($iTaskNo,date('Y',strtotime($_GET['start'])),date('m',strtotime($_GET['start'])));
		try{
			$oTask->vDeleteTask();
		}catch(Exception $e){
			echo json_encode(array("errorMsg"=>$e->getMessage()));
			exit;
		}
		$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
		$sPrefix = $aTempSpliter[0];

		echo json_encode(array(
			'id' => $sPrefix."_task_".$iTaskNo,
			'title' => $oTask->sName,
			'errorMsg'=>""
		));
		exit;
	}

	private function tTaskMove(){
		if(empty($_GET['task_no'])){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_NO_TASKNO));
			exit;
		}
		if(preg_match("/^[a-zA-Z]+_task_([0-9]+)$/i",$_GET['task_no'],$match)){
			 $iTaskNo = $match[1];
		}else{
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_NO_TASKNO));
			exit;
		}
		if (date('Y-m-d', strtotime($_GET['start'])) != $_GET['start']) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
			exit;
		}

		$oTask = CTask::oGetTask($iTaskNo,date('Y',strtotime($_GET['start'])),date('m',strtotime($_GET['start'])));
		$iTimeDiff = 0;
		if(is_numeric($_GET['day'])){
			$iTimeDiff = intval($_GET['day'])*24*60*60; 
		}	
		if(is_numeric($_GET['minute'])){
			$iTimeDiff = $iTimeDiff + intval($_GET['minute'])*60; 
		}

		//if is move , not resize, need to modify task_start_date
		if($_GET['action']==='move')
			$oTask->iStartDate = $oTask->iStartDate + $iTimeDiff;
		//both move and resiz need to modify task_end_date
		$oTask->iEndDate = $oTask->iEndDate + $iTimeDiff;

		//if task is move to all day task
		if($_GET['all_day']==='true')
			$oTask->bAllDay = '1';
		elseif ($_GET['all_day']==='false') {
			$oTask->bAllDay = '';
		}
		//update task
		try{
			$oTask->vUpdateTask();
		}catch(Exception $e){
			echo json_encode(array("errorMsg"=>$e->getMessage()));
			exit;
		}

		$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
		$sPrefix = $aTempSpliter[0];
		$aReturnTask = array();
		foreach ($oTask->aFamilies() as $oGroupTask) {
			$aReturnTask[] = array(	'id' => $sPrefix."_task_".$oGroupTask->iTaskNo,
									'start' => date("Y-m-d H:i",$oTask->iStartDate),
									'end' => date("Y-m-d H:i",$oTask->iEndDate),
									'allDay' => (bool)$oTask->bAllDay,
									);
		}
		echo json_encode(array(	"errorMsg"=>'',
								"tasks"=>$aReturnTask
								));
		exit;
	}
	
	/****************************************************************
	*	following functions are for ajax return with json_encode()	*
	*****************************************************************/

	/*
		get event of galaxy company
	*/
	public function aGetGalaxyEvent(){
		if(!is_numeric($_GET['start']) || !is_numeric($_GET['end'])){
			echo json_encode(array("errorMsg"=>_LANG_IWANT_CALENDAR_VAILD_START_DATE." or "._LANG_IWANT_CALENDAR_VAILD_END_DATE));
			exit;
		}
		if($_GET['end'] < $_GET['start']){
			echo json_encode(array("errorMsg"=>_LANG_IWANT_CALENDAR_VAILD_DATE_OVER));
			exit;
		}
		if( !checkdate(date('m',$_GET['start']),date('d',$_GET['start']),date('Y',$_GET['start'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_IWANT_CALENDAR_VAILD_START_DATE));
			exit;
		}
		if( !checkdate(date('m',$_GET['end']),date('d',$_GET['end']),date('Y',$_GET['end'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_IWANT_CALENDAR_VAILD_END_DATE));
			exit;
		}
		if(empty($_GET['editable']))
			$bEditable = false;
		else
			$bEditable = true;

		$aGalaxyEvent = CGalaxyEvent::aAllEvent("event_start_date >='".$_GET['start']."'  
			AND event_start_date <='".$_GET['end']."'");

		$aAllEvent = array();
		foreach ($aGalaxyEvent as $oEvent) {
			$aAllEvent[] = array(	'id' => "iwant_calendar_".$oEvent->iEventNo,
									'title' => $oEvent->sName,
									'url' => $oEvent->sUrl,
									'start' => date('Y-m-d H:i', $oEvent->iStartDate),
									'end' => date('Y-m-d H:i', strtotime("+1 minutes", $oEvent->iEndDate)),
									'allDay' => (bool)$oEvent->bAllDay,
									'backgroundColor' => ($oEvent->bHoliday==='1')?"#f06b6b":"",
									'editable'=> $bEditable,
									'description' => $oEvent->sDesc //non-standard Event Object field
									); 
		}
		return array("events"=>$aAllEvent,"errorMsg"=>"");
	}

	/*
		get group which are belong to target user , if user_no not given, target user = current_user
	*/
	public function aGetUserGroup(){
		if(empty($_GET['user_no']))
			$iUserNo = self::$session->get('oCurrentUser')->iUserNo;
		else
			$iUserNo = $_GET['user_no'];

		$aCTaskGroups = CTaskGroup::aAllGroup("task_group_user_no = '$iUserNo'","ORDER BY created ASC");
		$aMyGroups = array();
		foreach ($aCTaskGroups as $index => $oGroup) {
			$aMyGroups[] = array(	'task_group_uuid'=>$oGroup->sTaskGroupUuid,
									'task_group_name'=>$oGroup->sName
									);
		}
		return $aMyGroups;
	}

	/*
		get group which contains jobs of target user, but LastUser is not him/her,target user , if user_no not given, target user = current_user
	*/
	public function aGetElseGroup(){
		if(empty($_GET['user_no']))
			$iUserNo = self::$session->get('oCurrentUser')->iUserNo;
		else
			$iUserNo = $_GET['user_no'];

		//input filter
		if(!is_numeric($_GET['start']) || !is_numeric($_GET['end'])){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE." or "._LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		if($_GET['end'] < $_GET['start']){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_DATE_OVER));
			exit;
		}
		if( !checkdate(date('m',$_GET['start']),date('d',$_GET['start']),date('Y',$_GET['start'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
			exit;
		}
		if( !checkdate(date('m',$_GET['end']),date('d',$_GET['end']),date('Y',$_GET['end'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}

		//get info from client
		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];

		$iNextMonth = $iStart;
		//開始時間 結束時間 取得所在資料表
		$aElseGroups = array();
		while($iNextMonth < $iEnd){
			$aGroups = CTask::aGetGroups("task_user_no='$iUserNo' AND task_start_date >='$iStart'  
					AND task_start_date <='$iEnd'",date('Y',$iNextMonth),date('m',$iNextMonth));
			foreach ($aGroups as $oGroup) {
				if($oGroup->__iGroupUserNo === $iUserNo)
					continue;
				$aElseGroups[] = array(	'task_group_uuid'=>$oGroup->sTaskGroupUuid,
										'task_group_name'=>$oGroup->sName
										);
			}
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array("groups"=>$aElseGroups,"errorMsg"=>"");
	}

	/*
		get jobs of given task_group && user_no group by task group
	*/
	public function aGetTask(){
		if(empty($_GET['task_group_uuid']) || empty($_GET['user_no']))
			throw new Exception("group_uuid or user_no not given");
		$sTaskGroupUuid = $_GET['task_group_uuid'];
		$iUserNo = $_GET['user_no'];

		//input filter
		if(!is_numeric($_GET['start']) || !is_numeric($_GET['end'])){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE." or "._LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		if($_GET['end'] < $_GET['start']){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_DATE_OVER));
			exit;
		}
		if( !checkdate(date('m',$_GET['start']),date('d',$_GET['start']),date('Y',$_GET['start'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
			exit;
		}
		if( !checkdate(date('m',$_GET['end']),date('d',$_GET['end']),date('Y',$_GET['end'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		//editable
		if(empty($_GET['editable']))
			$bEditable = false;
		else
			$bEditable = true;
		//get info from client
		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];
		$iNextMonth = $iStart;
		//event filter
		$iEventNo = $_GET['event_no'];

		$aAllTask = array();
		while($iNextMonth < $iEnd){
			$sSearchSql = "task_group_uuid ='$sTaskGroupUuid' AND task_user_no='$iUserNo'";
			if(!empty($iEventNo))
				$sSearchSql .= " AND event_no='$iEventNo'";
			$sSearchSql .= " AND  task_start_date >='$iStart' AND task_start_date <='$iEnd'";
			$aTasks = CTask::aAllTask($sSearchSql,'',date('Y',$iNextMonth),date('m',$iNextMonth));

			foreach ($aTasks as $oTask) {
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

				$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
				$sPrefix = $aTempSpliter[0];

				$aAllTask[] = array(	'id' => $sPrefix."_task_".$oTask->iTaskNo,
										'title' => $oTask->sName,
										'url' => $oTask->sUrl,
										'start' => date('Y-m-d H:i', $oTask->iStartDate),
										'end' => date('Y-m-d H:i', $oTask->iEndDate),
										'allDay' => (bool)$oTask->bAllDay,
										'editable'=> $bEditable,
										'description' => $oTask->sDesc,
										'task_group_uuid' => $oTask->sTaskGroupUuid,
										'task_user' => $aAllTaskUser
										); 
			}
			
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array("events"=>$aAllTask,"errorMsg"=>"");
	}

	/*
		get all task of target user (in all task group)
	*/
	public function aGetUserTask(){
		if(empty($_GET['user_no']))
			throw new Exception("user_no not given");
		$iUserNo = $_GET['user_no'];

		//input filter
		if(!is_numeric($_GET['start']) || !is_numeric($_GET['end'])){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE." or "._LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		if($_GET['end'] < $_GET['start']){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_DATE_OVER));
			exit;
		}
		if( !checkdate(date('m',$_GET['start']),date('d',$_GET['start']),date('Y',$_GET['start'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
			exit;
		}
		if( !checkdate(date('m',$_GET['end']),date('d',$_GET['end']),date('Y',$_GET['end'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		//editable
		if(empty($_GET['editable']))
			$bEditable = false;
		else
			$bEditable = true;
		//get info from client
		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];
		$iNextMonth = $iStart;
		//event filter
		$iEventNo = $_GET['event_no'];

		$aAllTask = array();
		while($iNextMonth < $iEnd){
			$sSearchSql = "task_user_no='$iUserNo'";
			if(!empty($iEventNo))
				$sSearchSql .= " AND event_no='$iEventNo'";
			$sSearchSql .= " AND  task_start_date >='$iStart' AND task_start_date <='$iEnd'";
			$aTasks = CTask::aAllTask($sSearchSql,'',date('Y',$iNextMonth),date('m',$iNextMonth));

			foreach ($aTasks as $oTask) {
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

				$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
				$sPrefix = $aTempSpliter[0];

				$aAllTask[] = array(	'id' => $sPrefix."_task_".$oTask->iTaskNo,
										'title' => $oTask->sName,
										'url' => $oTask->sUrl,
										'start' => date('Y-m-d H:i', $oTask->iStartDate),
										'end' => date('Y-m-d H:i', $oTask->iEndDate),
										'allDay' => (bool)$oTask->bAllDay,
										'editable'=> $bEditable,
										'description' => $oTask->sDesc,
										'task_group_uuid' => $oTask->sTaskGroupUuid,
										'task_user' => $aAllTaskUser
										); 
			}
			
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array("events"=>$aAllTask,"errorMsg"=>"");
	}

	/*
		get all task of target group
	*/
	public function aGetGroupTask(){
		if(empty($_GET['task_group_uuid']))
			throw new Exception("group_uuid not given");
		$sTaskGroupUuid = $_GET['task_group_uuid'];

		//input filter
		if(!is_numeric($_GET['start']) || !is_numeric($_GET['end'])){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE." or "._LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		if($_GET['end'] < $_GET['start']){
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_DATE_OVER));
			exit;
		}
		if( !checkdate(date('m',$_GET['start']),date('d',$_GET['start']),date('Y',$_GET['start'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_START_DATE));
			exit;
		}
		if( !checkdate(date('m',$_GET['end']),date('d',$_GET['end']),date('Y',$_GET['end'])) ) {
			echo json_encode(array("errorMsg"=>_LANG_MY_CALENDAR_VAILD_END_DATE));
			exit;
		}
		//editable
		if(empty($_GET['editable']))
			$bEditable = false;
		else
			$bEditable = true;
		
		//get info from client
		$iStart = $_GET['start'];
		$iEnd = $_GET['end'];
		$iNextMonth = $iStart;
		//event filter
		$iEventNo = $_GET['event_no'];

		$aAllTask = array();
		while($iNextMonth < $iEnd){
			$sSearchSql = "task_group_uuid ='$sTaskGroupUuid'";
			if(!empty($iEventNo))
				$sSearchSql .= " AND event_no='$iEventNo'";
			$sSearchSql .= " AND  task_start_date >='$iStart' AND task_start_date <='$iEnd'";
			$aTasks = CTask::aAllTask($sSearchSql,'',date('Y',$iNextMonth),date('m',$iNextMonth));

			$aExistTaskUserGroup = array();
			foreach ($aTasks as $oTask) {

				//if this task has task_user_group
				if(!empty($oTask->sTaskUserGroupUuid)){
					//if same task_user_group is alrdy set, skip this task
					if(in_array($oTask->sTaskUserGroupUuid, $aExistTaskUserGroup))
						continue;
					else
						$aExistTaskUserGroup[] = $oTask->sTaskUserGroupUuid;	//add it into exist list
				}

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

				$aTempSpliter = explode('_', $oTask->sTaskGroupUuid);
				$sPrefix = $aTempSpliter[0];

				$aAllTask[] = array(	'id' => $sPrefix."_task_".$oTask->iTaskNo,
										'title' => $oTask->sName,
										'url' => $oTask->sUrl,
										'start' => date('Y-m-d H:i', $oTask->iStartDate),
										'end' => date('Y-m-d H:i', $oTask->iEndDate),
										'allDay' => (bool)$oTask->bAllDay,
										'editable'=> $bEditable,
										'description' => $oTask->sDesc,
										'task_group_uuid' => $oTask->sTaskGroupUuid,
										'task_user' => $aAllTaskUser
										); 
			}
			
			$iNextMonth = strtotime("+1 months", strtotime(date("Y-m-01", $iNextMonth)));
		}
		return array("events"=>$aAllTask,"errorMsg"=>"");
	}

	/*
		check if $_POST has all info we need
	*/
	static protected function vVaildPost($aPostData){
		$aErrorMsg = array();
		if(empty($aPostData['task_group_uuid'])){
			$aErrorMsg[]=_LANG_MY_CALENDAR_VAILD_GROUP;
		}
		if(empty($aPostData['event_no'])){
			$aErrorMsg[]=_LANG_MY_CALENDAR_VAILD_EVENT;
		}
		if(strlen(trim($aPostData['task_name']))==0){
			$aErrorMsg[]=_LANG_MY_CALENDAR_VAILD_NAME;
		}
		if (date('Y-m-d', strtotime($aPostData['task_start_date'])) != $aPostData['task_start_date']) {
			$aErrorMsg[]=_LANG_MY_CALENDAR_VAILD_START_DATE;
		}

		$iStartDate = strtotime($aPostData['task_start_date'].' '.$aPostData['task_start_time']); 
		$iEndDate = strtotime($aPostData['task_end_date'].' '.$aPostData['task_end_time']); 

		if($iStartDate>$iEndDate){
			$aErrorMsg[]=_LANG_MY_CALENDAR_VAILD_DATE_OVER;
		}

		$sErrorMsg = "";
		//form submit vaild data
		if(count($aErrorMsg) > 0){
			$sErrorMsg = implode("\n",$aErrorMsg);
			echo json_encode(array("errorMsg"=>$sErrorMsg));
			exit;
		}	
	}
}
?>