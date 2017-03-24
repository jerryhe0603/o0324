<?php
include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CBoard.php');
include_once('../inc/model/CGalaxyEvent.php');

include_once('../inc/model/beauty/CBeautyPrinciple.php');
include_once('../inc/model/beauty/CBeautyDoc.php');
include_once('../inc/model/beauty/CBeautyInterval.php');
include_once('../inc/model/beauty/CBeautySchedule.php');

class CBeautyProject extends CGalaxyClass
{

	private $sProjectUuid;
	public $sId;
	public $sName;
	public $iTypeNo;
	public $sDesc;
	public $sStartDate;
	public $sEndDate;
	public $iPromiseResRate;
	public $iReportInterval;
	public $iReportCount;
	public $iPostCount;
	public $iResponseCount;

	//set when function is called
	private $__aCBeautyPrinciple;
	private $__aCBeautyDoc;
	private $__aCBeautyInterval;
	private $__aCUser;
	private $__aCBoard;
	private $__aCSchedule;
	private $__aAntiword;
	private $__aKeyword;

	//database setting
	static protected $sDBName = 'SITE';

	//instance pool
	static public $aInstancePool = array();

	//project type
	static public $aType = array(	'0'=>'not selected',
									'1'=>'repretation',	//iPromiseResRate != 0
									'2'=>'customer',
									'3'=>'security',
									'4'=>'googleplus',
									'5'=>'dandelion'
									);
	static public $aTypeTW = array(	'0'=>'未選擇',
									'1'=>'口碑',
									'2'=>'消費者',
									'3'=>'保全',
									'4'=>'Google+',
									'5'=>'蒲公英'
									);

	/*
		get $oCBeautyProject by certain project_no(uuid)
	*/
	static public function oGetProject($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$sProjectUuid]))
			return self::$aInstancePool[$sProjectUuid];

		//query from beauty DB
		$sSql = "SELECT * FROM project WHERE project_no='$sProjectUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCProject = new CBeautyProject($aRow);
		self::$aInstancePool[$sProjectUuid] = $oCProject;

		return $oCProject;
	}

	/*
		get all beauty project in an array
		if $sSearchSql is given, query only match projects
	*/
	static public function aAllProject($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllProject = array();
		$sSql = "SELECT * FROM `project`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['project_no']])){
				self::$aInstancePool[$aRow['project_no']] = new CBeautyProject($aRow);
			}
			$aAllProject[] = self::$aInstancePool[$aRow['project_no']];
		}
		return $aAllProject;
	}

	/*
		get count of project which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(project_no) as total FROM project";
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
		get available project for current user in an array 
	*/
	static public function aAvailProject($iUserNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT project_no FROM project_user WHERE `manage_user_no`=$iUserNo";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aProjectUuid[] = $aRow['project_no'];
		}
		return $aProjectUuid;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CBeautyProject: __construct failed, require an array");
		if(!empty($multiData['project_no']))
			$this->sProjectUuid = $multiData['project_no'];
		else
			$this->sProjectUuid = 'beauty_'.CMisc::uuid_v1();

		$this->sId = $multiData['project_id'];
		$this->sName = $multiData['project_name'];
		$this->iTypeNo = $multiData['project_type_no'];
		$this->sDesc = $multiData['project_desc'];
		$this->sStartDate = $multiData['start_date'];
		$this->sEndDate = $multiData['end_date'];
		$this->iPromiseResRate = $multiData['promise_res_rate'];
		$this->iReportInterval = $multiData['report_interval'];
		$this->iReportCount = $multiData['report_count'];
		$this->iPostCount = $multiData['project_post_count'];
		$this->iResponseCount = $multiData['response_count'];
		//galaxy class member
		$this->bStatus = $multiData['project_status'];
		$this->sCreateTime = $multiData['create_time'];
		$this->sModifiedTime = $multiData['modify_time'];
	}

	public function __get($varName)
    {
        if(method_exists($this,$varName))
        	return $this->$varName();

        return $this->$varName;
    }
    
	/*
		overwrite $this with another $oCBeautyProject object, which has same sProjectUuid and some different value
	*/
	public function vOverwrite($oCBeautyProject){
		//if not a CDoc object or uuid not match
		if(get_class($oCBeautyProject)!=='CBeautyProject' || $this->sProjectUuid!==$oCBeautyProject->sProjectUuid)
			throw new Exception('CBeautyProject->vOverwrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='sProjectUuid' || is_null($oCBeautyProject->$name))
				continue;
			$this->$name = $oCBeautyProject->$name;	//overwrite
		}
	}

	public function sType(){
    	return self::$aTypeTW[$this->iTypeNo];
	}

	public function aAntiword(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aAntiword)){
			$sSql = "SELECT `antiword` FROM project_antiword WHERE project_no='{$this->sProjectUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aAntiword[] = $aRow['antiword'];
			}
		}
		return $this->__aAntiword;
	}

	public function iAntiwordCount(){
		return count($this->aAntiword());
	}

	public function aKeyword(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aKeyword)){
			$sSql = "SELECT `keyword` FROM project_keyword WHERE project_no='{$this->sProjectUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aKeyword[] = $aRow['keyword'];
			}
		}
		return $this->__aKeyword;
	}

	public function iKeywordCount(){
		return count($this->aKeyword());
	}

	/*
		set & get principle of this project
	*/
	public function aPrinciple(){
		if(empty($this->__aCBeautyPrinciple)){
			$this->__aCBeautyPrinciple = CBeautyPrinciple::aAllPrinciple("project_no='{$this->sProjectUuid}'");
		}
		return $this->__aCBeautyPrinciple;
	}

	public function iPrincipleCount(){
		return CBeautyPrinciple::iGetCount("project_no='{$this->sProjectUuid}'");
	}

	/*
		set & get doc of this project
	*/
	public function aDoc(){
		if(empty($this->__aCBeautyDoc)){
			$this->__aCBeautyDoc = CBeautyDoc::aAllDoc("project_no='{$this->sProjectUuid}'");
		}
		return $this->__aCBeautyDoc;
	}

	public function iDocCount(){
		return CBeautyDoc::iGetCount("project_no='{$this->sProjectUuid}'");
	}

	/*
		set & get interval of this project
	*/
	public function aInterval(){
		if(empty($this->__aCBeautyInterval)){
			$this->__aCBeautyInterval = CBeautyInterval::aAllInterval("project_no='{$this->sProjectUuid}'","ORDER BY `interval_order`");
		}
		return $this->__aCBeautyInterval;
	}

	public function iIntervalCount(){
		return CBeautyInterval::iGetCount("project_no='{$this->sProjectUuid}'");
	}

	/*
		set & get schedule of this project
	*/
	public function aSchedule(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCSchedule)){
			$this->__aCSchedule = CBeautySchedule::aAllSchedule("project_no='{$this->sProjectUuid}'");
		}
		return $this->__aCSchedule;
	}

	public function iScheduleCount(){
		return CBeautySchedule::iGetCount("project_no='{$this->sProjectUuid}'");
	}

	/*
		set & get user of this project
	*/
	public function aUser(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCUser)){
			$sSql = "SELECT manage_user_no FROM project_user WHERE project_no='{$this->sProjectUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aCUser[] = CUser::oGetUser($aRow['manage_user_no']);
			}
		}
		return $this->__aCUser;
	}

	/*
		count user of this project
	*/
	public function iUserCount(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(user_no) as total FROM project_user WHERE project_no='{$this->sProjectUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		return $aRow['total'];
	}

	/*
		set & get target boards of this project
	*/
	public function aBoard(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCBoard)){
			$sSql = "SELECT galaxy_board_no FROM project_board WHERE project_no='{$this->sProjectUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aCBoard[] = CBoard::oGetBoard($aRow['galaxy_board_no']);
			}
		}
		return $this->__aCBoard;
	}

	/*
		count target boards of this project
	*/
	public function iBoardCount(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(galaxy_board_no) as total FROM project_board WHERE project_no='{$this->sProjectUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		return $aRow['total'];
	}

	/*
		clear and overwrite user of this project
	*/
	public function vSetUser($aUserNos){
		$this->__aCUser = array();	//clear prev setting
		foreach ($aUserNos as $iUserNo) {
			if($iUserNo == 0)
				continue;
			$this->__aCUser[] = CUser::oGetUser($iUserNo);
		}
	}

	/*
		update user setting of this project to DB
		step1: $oProject = CBeautyProject::oGetProject(uuid)
		step2: $oProject->vSetUser(array_of_user_no)
		setp3: call this function
	*/
	public function vUpdateUser(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$oDB->vDelete('project_user',"`project_no`='{$this->sProjectUuid}'");
			foreach ($this->__aCUser as $oCUser) {
				$aValues = array(	'project_no'=>$this->sProjectUuid,
									'manage_user_no'=>$oCUser->iUserNo,
									'user_no'=>self::$session->get('oCurrentUser')->iUserNo,
									'create_time'=>date("Y-m-d H:i:s"),
									'modify_time'=>date("Y-m-d H:i:s")
									);
				$oDB->sInsert('project_user',array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_user',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CBeautyProject->vUpdateUser: '.$e->getMessage());
		}
	}

	/*
		clear and overwrite board of this project
	*/
	public function vSetBoard($aBoardNos){
		$this->__aCBoard = array();	//clear prev setting
		$aBoardNos = array_unique($aBoardNos);
		foreach ($aBoardNos as $iBoardNo) {
			if($iBoardNo==0)
				continue;
			$this->__aCBoard[] = new CBoard(array('board_no' => $iBoardNo));
		}
	}

	/*
		update board setting of this project to DB
		step1: $oProject = CBeautyProject::oGetProject(uuid)
		step2: $oProject->vSetBoard(array_of_board_no)
		setp3: call this function
	*/
	public function vUpdateBoard(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$oDB->vDelete('project_board',"`project_no`='{$this->sProjectUuid}'");
			foreach ($this->__aCBoard as $oCBoard) {
				$aValues = array(	'project_no'=>$this->sProjectUuid,
									'galaxy_board_no'=>$oCBoard->iBoardNo,
									'user_no'=>self::$session->get('oCurrentUser')->iUserNo,
									'create_time'=>date("Y-m-d H:i:s")
									);
				$oDB->sInsert('project_board',array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_board',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CBeautyProject->vUpdateBoard: '.$e->getMessage());
		}
	}

	public function vSetAntiword($aAntiword){
		$this->__aAntiword = empty($aAntiword)?array():$aAntiword;
	}

	public function vUpdateAntiword(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$oDB->vDelete('project_antiword',"`project_no`='{$this->sProjectUuid}'");
			foreach ($this->__aAntiword as $sAntiword) {
				$aValues = array(	"project_no"=>$this->sProjectUuid,
									"antiword"=>$sAntiword
									);
				$oDB->sInsert('project_antiword',array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_antiword',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyProject->vUpdateAntiword: ".$e->getMessage());
		}
	}

	public function vSetKeyword($aKeyword){
		$this->__aKeyword = empty($aKeyword)?array():$aKeyword;
	}

	public function vUpdateKeyword(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$oDB->vDelete('project_keyword',"`project_no`='{$this->sProjectUuid}'");
			foreach ($this->__aKeyword as $sKeyword) {
				$aValues = array(	"project_no"=>$this->sProjectUuid,
									"keyword"=>$sKeyword
									);
				$oDB->sInsert('project_keyword',array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_keyword',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyProject->vUpdateKeyword: ".$e->getMessage());
		}
	}

	/*
		add this project to DB
		step1: $oProject = new CBeautyProject($_POST);
		step2: $oProject->vAddProject();
	*/
	public function vAddProject(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();

			//calculate interval and insert
			$iOrder = 1;
			$iStart = strtotime($this->sStartDate);
			while($iOrder <= $this->iReportCount){
				//get available timerange & holidays
				$iCount = 2;
				$aHoliday = array();
				while($iCount<10){
					$iPossibleEnd = $iStart + $this->iReportInterval*24*60*60*$iCount;

					$aHoliday = CGalaxyEvent::aAllHoliday($iStart,$iPossibleEnd);	//find holidays between $iStart & $iPossibleEnd
					$iNonHoliday = $iCount*$this->iReportInterval - count($aHoliday);	//count of non holiday between $iStart & $iPossibleEnd
					if($iNonHoliday < $iReportInterval){	//count of non holiday must not lesser than $iReportInterval
						$iCount++;
					}else{
						break;
					}
				}

				//get interval range
				$iAccumulateDay = 1;
				$iEnd = $iStart;
				while($iAccumulateDay < $this->iReportInterval){
					if(!in_array(date("Y-m-d",$iEnd), $aHoliday)){
						$iAccumulateDay++;
					}
					$iEnd = $iEnd + 24*60*60;
				}

				//create interval and add
				$aInit = array(	"project_no"=>$this->sProjectUuid,
								"interval_order"=>$iOrder,
								"start_date"=>date("Y-m-d",$iStart),
								"end_date"=>date("Y-m-d",$iEnd),
								"status"=>1
								);
				$oInterval = new CBeautyInterval($aInit);
				$oInterval->iAdd();
				$this->__aCBeautyInterval[] = $oInterval;

				//if is last interval , set $this->sEndDate = date("Y-m-d",$iEnd)
				if($iOrder == $this->iReportCount)
					$this->sEndDate = date("Y-m-d",$iEnd);

				//next interval
				$iStart = $iEnd + 24*60*60;	//end+24*60*60 is next start
				$iOrder++;
			}
			//insert project itself
			$aValues = array(	'project_no'=>$this->sProjectUuid,
				 				'project_id'=>$this->sId,
								'project_name'=>$this->sName,
								'project_type_no'=>$this->iTypeNo,
								'project_desc'=>$this->sDesc,
								'start_date'=>$this->sStartDate,
								'end_date'=>$this->sEndDate,
								'promise_res_rate'=>$this->iPromiseResRate,
								'report_interval'=>$this->iReportInterval,
								'report_count'=>$this->iReportCount,
								'project_post_count'=>$this->iPostCount,
								'response_count'=>$this->iResponseCount,
								'user_no'=>$oCurrentUser->iUserNo,
								'project_status'=>$this->bStatus,
								'create_time'=>date("Y-m-d H:i:s"),
								'modify_time'=>date("Y-m-d H:i:s")
								);
			$oDB->sInsert("project", array_keys($aValues), array_values($aValues));
			$this->vSetUser(array(self::$session->get('oCurrentUser')->iUserNo));
			$this->vUpdateUser();

			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyProject->vAddProject: ".$e->getMessage());
		}
	}

	/*
		update this project to DB
		step1: $oProject = CBeautyProject::oGetProject(uuid);
		step2: $oProject->overwrite($oProjectFromPost);
		step3: $oProject->vUpdateProject();
	*/
	public function vUpdateProject(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$aValues = array(	'project_id'=>$this->sId,
								'project_name'=>$this->sName,
								'project_type_no'=>$this->iTypeNo,
								'project_desc'=>$this->sDesc,
								'start_date'=>$this->sStartDate,
								'end_date'=>$this->sEndDate,
								'promise_res_rate'=>$this->iPromiseResRate,
								'report_interval'=>$this->iReportInterval,
								'report_count'=>$this->iReportCount,
								'project_post_count'=>$this->iPostCount,
								'response_count'=>$this->iResponseCount,
								'user_no'=>$oCurrentUser->iUserNo,
								'project_status'=>$this->bStatus,
								'modify_time'=>date("Y-m-d H:i:s")
								);
			$oDB->sUpdate("project", array_keys($aValues), array_values($aValues), "`project_no` ='{$this->sProjectUuid}'");
			$oCurrentUser->vAddUserLog('project',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			throw new Exception("CBeautyProject->vUpdateProject: ".$e->getMessage());
		}
	}

	/*
		activate this oCBeautyProject
	*/
	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		if($this->bStatus==='1')
			$this->bStatus='0';
		else
			$this->bStatus='1';
		$aValues = array('project_status'=>$this->bStatus);
		try{
			$oDB->sUpdate("`project`", array_keys($aValues), array_values($aValues),"`project_no`='{$this->sProjectUuid}'");
			$oCurrentUser->vAddUserLog('project',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CBeautyProject->vActivate: ".$e->getMessage());
		}
	}
}
?>