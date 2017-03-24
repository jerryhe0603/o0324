<?php
include_once('../inc/model/CGalaxyClass.php');

include_once('../inc/model/CBoard.php');
include_once('../inc/model/CScript.php');
include_once('../inc/model/CPuppets.php');

include_once('../inc/CErrorCode.php');

class CSchedule extends CGalaxyClass
{
	static public $aType = array(	'1'=>'post',
									'2'=>'catch',
									'3'=>'response',
									'4'=>'monitor',
									'5'=>'register',
									'6'=>'activate',
									'7'=>'catchList',
									'8'=>'phone'
									);
	static public $aTypeTW = array(	'1'=>'發文',
									'2'=>'抓文',
									'3'=>'回文',
									'4'=>'監控',
									'5'=>'註冊',
									'6'=>'啟用',
									'7'=>'抓列表頁',
									'8'=>'手機'
									);
	static public $aResultCode = array(	'default'=>'0', 
										'success'=>'1',
										'failed'=>'2',
										'multiple'=>'9'
										);

	protected $sScheduleUuid;

	public $iTypeNo;
	public $sProjectUuid;

	public $iSiteNo;
	private $__oCSite;

	public $iBoardNo;
	private $__oCBoard;

	public $sScriptUuid;
	private $__oCScript;

	public $sPuppetsNo;
	private $__oPuppet;

	public $iTimes;
	public $iResultStatus;

	public $sStartTime;
	public $sEndTime;

	public $sMainDocUuid = '';	//used by controller

	public $sUrl;

	protected $__aQueueStat = array();

	//database setting
	static protected $sDBName;	//defined by child class
	
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CSchedule: __construct failed, require an array");

		$this->sScheduleUuid = $multiData['project_schedule_uuid'];
		$this->iTypeNo = $multiData['schedule_type'];
		$this->sProjectUuid = $multiData['project_no'];
		$this->sUrl = $multiData['url'];

		if(empty($multiData['site_no'])){
			$oBoard = CBoard::oGetBoard($multiData['board_no']);
			if(!is_null($oBoard))
				$this->iSiteNo = $oBoard->iSiteNo;
		}else{
			$this->iSiteNo = $multiData['site_no'];
		}

		$this->iBoardNo = $multiData['board_no'];
		$this->sScriptUuid = $multiData['script_no'];
		$this->sPuppetsNo = $multiData['puppets_no'];
		$this->iResultStatus =  $multiData['result_status'];
		$this->iTimes =  $multiData['times'];
		$this->sStartTime = $multiData['schedule_start_time'];
		$this->sEndTime = $multiData['schedule_end_time'];

		//galaxy class member
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['create_time'];
		$this->sModifiedTime = $multiData['modify_time'];

	}
	
	public function __get($varName)
	{
		return $this->$varName;
	}

	public function oSite(){
		if(is_null($this->__oCSite))
			$this->__oCSite = CSite::oGetSite($this->iSiteNo);
		return $this->__oCSite;
	}

	public function oBoard(){
		if(is_null($this->__oCBoard))
			$this->__oCBoard = CBoard::oGetBoard($this->iBoardNo);
		return $this->__oCBoard;
	}

	public function oScript(){
		if(is_null($this->__oCScript))
			$this->__oCScript = CScript::oGetScript($this->sScriptUuid);
		return $this->__oCScript;
	}

	public function oPuppet(){
		if(is_null($this->__oPuppet))
			$this->__oPuppet = CPuppets::oGetPuppets($this->sPuppetsNo);
		return $this->__oPuppet;
	}

	//implement in child class
	public function aQueueStat(){

	}

	public function sLocalStartTime(){
		return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sStartTime)));
	}

	public function sLocalEndTime(){
		return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sEndTime)));
	}

	public function sLocalResultTime(){
		return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sResultTime)));
	}

	protected function sAddSchedule($aPostData){

	}

	//$aFileDocs query by child class
	protected function vAddPost($sApiUrl,$aExecUser,$sDocUuid='',$aFileDocs=''){
		//options for lock_account_api
		$aOptions = array();
		$puppets_url = 'http://'.PUPPETS_SERVER.'/api/api.CPuppets.php?action=lock_account&PHPSESSID='.$_COOKIE['PHPSESSID'];
		if($this->iSiteNo === '0' || is_null($this->iSiteNo))
			$this->iSiteNo = $this->oBoard()->iSiteNo;
		$aOptions = array(	'puppets_no'=>$this->sPuppetsNo,
							'site_no'=>$this->iSiteNo,
							'project_no'=>$this->sProjectUuid
							);
		$aLockRes = self::aCurl($puppets_url,$aOptions);
		if($aLockRes['status']!==true){
			throw new Exception("CSchedule->vAddPost:".$aLockRes['errorMsg']);
		}

		//options for add_queue_api
		$aOptions['queue']['project_no']		   = $this->sProjectUuid;
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']	   = $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']		   = $oPuppets->iProxyNo;
		}else if($iProxyNo!=0){
			$aOptions['queue']['proxy_no']		   = $iProxyNo;
		}else{
			//throw new Exception("both puppets_no and proxy_no are 0");
			$aOptions['queue']['proxy_no']		   = 0;
			$aOptions['queue']['puppets_no']	   = '';
			$aExecUser = array();
		}

		$aOptions['queue']['site_no']			   = $this->iSiteNo;
		$aOptions['queue']['docs_no']			   = $sDocUuid;
		$aOptions['queue']['board_no']			   = $this->iBoardNo;
		$aOptions['queue']['priority']             = 1;
		$aOptions['queue']['api_url']              = $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']            = $this->sScriptUuid;
		/*	正式版本
		if(strtotime($this->sStartTime) < (time()+12*60))
			$iStartTime = time()+12*60;
		else
			$iStartTime = strtotime($this->sStartTime);
		*/
		/*	測試版本	*/
		if(strtotime($this->sStartTime) < time()+30)
			$iStartTime = time()+30;
		else
			$iStartTime = strtotime($this->sStartTime);
		
		$aOptions['queue']['start']                = $iStartTime;	//開始時間
		$aOptions['queue']['end']                  = strtotime($this->sEndTime);	//結束時間
		$aOptions['queue']['user_no']              = $aExecUser;

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $aQueueRes['status']
		if($aQueueRes['status']===true){

			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
														"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
														));
		}else{
			//call cancel_queue_api for each queue just added
			throw new Exception("CSchedule->vAddPost:".$aQueueRes['msg']);
		}
		
	}

	protected function vAddCatch($sApiUrl,$aExecUser,$iStartTime,$iEndTime,$sTargetUrl,$iProxyNo=0){

		//options for add_queue_api
		$aOptions = array();
		$aOptions['queue']['project_no']		   = $this->sProjectUuid;
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']	   = $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']		   = $oPuppets->iProxyNo;
		}else if($iProxyNo!=0){
			$aOptions['queue']['puppets_no']	   = '';
			$aOptions['queue']['proxy_no']		   = $iProxyNo;
		}else{
			throw new Exception("puppets_no='' and proxy_no is 0");
		}
		
		if($this->iSiteNo === '0' || is_null($this->iSiteNo))
    		$this->iSiteNo = $this->oBoard()->iSiteNo;
		$aOptions['queue']['site_no']			   = $this->iSiteNo;
		$aOptions['queue']['docs_no']			   = '';
		$aOptions['queue']['board_no']			   = $this->iBoardNo;
		$aOptions['queue']['priority']             = 3;
		$aOptions['queue']['api_url']              = $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']            = $this->sScriptUuid;
		$aOptions['queue']['go_url']               = $sTargetUrl;
		if($iStartTime < (time()+12*60))
			$iStartTime = time()+12*60;
		$aOptions['queue']['start']                = $iStartTime;	//開始時間
		$aOptions['queue']['end']                  = $iEndTime;	//結束時間
		$aOptions['queue']['user_no']              = $aExecUser;	//specific users

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $aQueueRes['status']
		if($aQueueRes['status']===true){

			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
														"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
														));
		}else{
			//call cancel_queue_api for each queue just added
			throw new Exception("CSchedule->vAddCatch:".$aQueueRes['msg']);
		}
	}

	protected function vAddResponse($sApiUrl,$aExecUser,$sDocUuid='',$aFileDocs=''){
		//basic is same to vAddPost, but with additional attribute, start_url
		if(empty($this->sUrl))
			throw new Exception("CSchedule->vAddResponse: target url is empty");

		//options for lock_account_api
		$aOptions = array();
		$puppets_url = 'http://'.PUPPETS_SERVER.'/api/api.CPuppets.php?action=lock_account&PHPSESSID='.$_COOKIE['PHPSESSID'];
		if($this->iSiteNo === '0' || is_null($this->iSiteNo))
			$this->iSiteNo = $this->oBoard()->iSiteNo;
		$aOptions = array(	'puppets_no'=>$this->sPuppetsNo,
							'site_no'=>$this->iSiteNo,
							'project_no'=>$this->sProjectUuid
							);
		$aLockRes = self::aCurl($puppets_url,$aOptions);
		if($aLockRes['status']!==true){
			throw new Exception("CSchedule->vAddResponse:".$aLockRes['errorMsg']);
		}

			$aOptions['queue']['project_no']		   = $this->sProjectUuid;
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']	   = $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']		   = $oPuppets->iProxyNo;
		}else if($iProxyNo!=0){
			$aOptions['queue']['proxy_no']		   = $iProxyNo;
		}else{
			//throw new Exception("both puppets_no and proxy_no are 0");
			$aOptions['queue']['proxy_no']		   = 0;
			$aOptions['queue']['puppets_no']	   = '';
			$aExecUser = array();
		}
		$aOptions['queue']['site_no']			   = $this->iSiteNo;
		$aOptions['queue']['board_no']			   = $this->iBoardNo;
		$aOptions['queue']['docs_no']			   = $sDocUuid;
		$aOptions['queue']['priority']             = 1;
		$aOptions['queue']['api_url']              = $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']            = $this->sScriptUuid;
		$aOptions['queue']['go_url']               = $this->sUrl;	
		/*	正式版本
		if(strtotime($this->sStartTime) < (time()+12*60))
			$iStartTime = time()+12*60;
		else
			$iStartTime = strtotime($this->sStartTime);
		*/
		/*	測試版本	*/
		if(strtotime($this->sStartTime) < time()+30)
			$iStartTime = time()+30;
		else
			$iStartTime = strtotime($this->sStartTime);
		$aOptions['queue']['start']                = $iStartTime;	//開始時間
		$aOptions['queue']['end']                  = strtotime($this->sEndTime);	//結束時間
		$aOptions['queue']['user_no']              = $aExecUser;

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $aQueueRes['status']
		if($aQueueRes['status']===true){

			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
														"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
														));
		}else{
			//call cancel_queue_api for each queue just added
			throw new Exception("CSchedule->vAddResponse:".$aQueueRes['msg']);
		}
	}

	protected function vAddMonitor($sApiUrl){
		
	}

	protected function vAddCatchList($sApiUrl,$aExecUser,$iProxyNo=0){
		/*
	//options for get_script_api
	$aOptions = array();
	$aOptions['script']['script_no'] = $this->sScriptUuid;
	$aOptions['script']['puppets_no'] = $this->sPuppetsNo;
	$aOptions['script']['start'] = $iStartTime;
	$aOptions['script']['start_url'] = $sTargetUrl;

	$script_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=clone_script&PHPSESSID='.$_COOKIE['PHPSESSID'];

	//call get_script_api for cloned & modified script_no
	$aScriptRes = self::aCurl($script_url,$aOptions);
	if($aScriptRes['status']!==true){
		throw new Exception($aScriptRes['msg']);
	}
	$sScriptUuid = $aScriptRes['script_no'];

	//options for add_queue_api
	$aOptions = array();
	$aOptions['queue']['project_no']= $this->sProjectUuid;
	if(!empty($this->sPuppetsNo)){
		$aOptions['queue']['puppets_no']= $this->sPuppetsNo;
		$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
		$aOptions['queue']['proxy_no']= $oPuppets->iProxyNo;
	}else if($iProxyNo!=0){
		$aOptions['queue']['proxy_no']= $iProxyNo;
	}else{
		//throw new Exception("both puppets_no and proxy_no are 0");
		$aOptions['queue']['proxy_no']= 0;
		$aOptions['queue']['puppets_no']= '';
		$aExecUser = array();
	}
		
	$aOptions['queue']['site_no']= $this->iSiteNo;
	$aOptions['queue']['site_no']= $this->iSiteNo;
	$aOptions['queue']['priority']= 1;
	//$aOptions['queue']['service_no']= $this->sScheduleUuid;	//服務編號,suchedule uuid
	$aOptions['queue']['api_url']= $sApiUrl;	//執行完後 執行的api_url
	$aOptions['queue']['script_no']= $sScriptUuid;	//modified script
	$aOptions['queue']['script_type_no']= $aScriptRes['script_type_no'];
	$aOptions['queue']['galaxy_script_no']= $this->sScriptUuid;	//original script_no
	if(strtotime($iStartTime) < (time()+12*60))
		$iStartTime = time()+12*60;
	$aOptions['queue']['start']= $iStartTime;	//開始時間
	$aOptions['queue']['end']= strtotime($this->sEndTime);	//結束時間
	$aOptions['queue']['start']= $iStartTime;	//開始時間
	$aOptions['queue']['end']= $iEndTime;	//結束時間
	$aOptions['queue']['user_no']= $aExecUser;	//specific users

	$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

	//call add_queue_api and get response
	$aQueueRes = self::aCurl($queue_url,$aOptions);

	//different procedure depends on $aQueueRes['status']
	if($aQueueRes['status']===true){
		//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
										"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
									));
	}else{
		//call cancel_queue_api for each queue just added
		throw new Exception($aQueueRes['msg']);
	}
		*/
	//CMisc::vPrintR('CSchedule->vAddPost');
	//start_time, end_time, and time_interval controll(exec_times>1)
	$iWholeStart = strtotime($this->sStartTime);
	$iWholeEnd = strtotime($this->sEndTime);
	$iTotal = $this->iTimes;
	$iInterval = ($iWholeEnd-$iWholeStart)/$iTotal;
	if($this->iSiteNo === '0' || is_null($this->iSiteNo))
			$this->iSiteNo = $this->oBoard()->iSiteNo;
	
	//get exec_times from $aPostData, average time interval
	$iFailTimes = 0;
	for($iExecTimes=1; $iExecTimes<=$iTotal; $iExecTimes++){		

		//options for get_script_api
		$aOptions = array();
		$aOptions['script']['script_no'] = $this->sScriptUuid;
		if($this->sPuppetsNo !== '')
			$aOptions['script']['puppets_no'] = $this->sPuppetsNo;
		if($sDocUuid !== '')
			$aOptions['script']['docs_no'] = $sDocUuid;	//'' if not required
		if($aFileDocs !== '')
			$aOptions['script']['file_docs'] = $aFileDocs;	//empty array if not required
		$script_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=clone_script&PHPSESSID='.$_COOKIE['PHPSESSID'];
		//call get_script_api for cloned & modified script_no
		$aScriptRes = self::aCurl($script_url,$aOptions);
		
		if($aScriptRes['status']!==true){
			throw new Exception("CSchedule->vAddCatchList:".$aScriptRes['msg']);
		}
		$sScriptUuid = $aScriptRes['script_no'];	        
		$iAccountSiteNo = $aScriptRes['accounts_site_no'];

		//options for add_queue_api
		$iIntervalStart = $iWholeStart + ($iExecTimes-1)*$iInterval; 
		$iIntervalEnd = $iIntervalStart + $iInterval;

		$aOptions = array();
		$aOptions['queue']['project_no']= $this->sProjectUuid;
		/*
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']= $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']= $oPuppets->iProxyNo;
		}else{
			throw new Exception("puppets_no is empty");
		}
		*/
		$aOptions['queue']['proxy_no']=$iProxyNo;
		$aOptions['queue']['accounts_site_no']= $iAccountSiteNo;	//from api1
		$aOptions['queue']['site_no']= $this->iSiteNo;
		$aOptions['queue']['board_no']= $this->iBoardNo;
		$aOptions['queue']['priority']= 0;
		//$aOptions['queue']['service_no']= $this->sScheduleUuid;	//服務編號,suchedule uuid
		$aOptions['queue']['api_url']= $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']= $sScriptUuid;	//modified script
		$aOptions['queue']['script_type_no']= $aScriptRes['script_type_no'];
		$aOptions['queue']['galaxy_script_no']= $this->sScriptUuid;	//original script_no
		$aOptions['queue']['start']= $iIntervalStart;	//開始時間
		$aOptions['queue']['end']= $iIntervalEnd;	//結束時間
		$aOptions['queue']['user_no']= $aExecUser;

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];
			
		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $$aQueueRes['status']
		if($aQueueRes['status']===true){
			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
										"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
										));
		}else{
			//call cancel_queue_api for each queue just added
			
			throw new Exception("CSchedule->vAddCatchList:".$aQueueRes['msg']);
		}
	}
	
	}

	protected function vAddRegister($sApiUrl,$aExecUser){

		$aOptions['queue']['project_no']		   = $this->sProjectUuid;
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']	   = $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']		   = $oPuppets->iProxyNo;
		}else if($iProxyNo!=0){
			$aOptions['queue']['proxy_no']		   = $iProxyNo;
		}else{
			//throw new Exception("both puppets_no and proxy_no are 0");
			$aOptions['queue']['proxy_no']		   = 0;
			$aOptions['queue']['puppets_no']	   = '';
			$aExecUser = array();
		}

		$aOptions['queue']['site_no']			   = $this->iSiteNo;
		$aOptions['queue']['board_no']			   = $this->iBoardNo;
		$aOptions['queue']['priority']             = 1;
		$aOptions['queue']['api_url']              = $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']            = $this->sScriptUuid;	
		if(strtotime($this->sStartTime) < (time()+12*60))
			$iStartTime = time()+12*60;
		else
			$iStartTime = strtotime($this->sStartTime);
		$aOptions['queue']['start']                = $iStartTime;	//開始時間
		$aOptions['queue']['end']                  = strtotime($this->sEndTime);	//結束時間
		$aOptions['queue']['user_no']              = $aExecUser;

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $aQueueRes['status']
		if($aQueueRes['status']===true){

			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
									"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
								));
		}else{
			//call cancel_queue_api for each queue just added
			throw new Exception("CSchedule->vAddRegister:".$aQueueRes['msg']);
		}
	}

	protected function vAddActivate($sApiUrl,$aExecUser){
		$aOptions['queue']['project_no']		   = $this->sProjectUuid;
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']	   = $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']		   = $oPuppets->iProxyNo;
		}else if($iProxyNo!=0){
			$aOptions['queue']['proxy_no']		   = $iProxyNo;
		}else{
			//throw new Exception("both puppets_no and proxy_no are 0");
			$aOptions['queue']['proxy_no']		   = 0;
			$aOptions['queue']['puppets_no']	   = '';
			$aExecUser = array();
		}

		$aOptions['queue']['site_no']			   = $this->iSiteNo;
		$aOptions['queue']['board_no']			   = $this->iBoardNo;
		$aOptions['queue']['priority']             = 1;
		$aOptions['queue']['api_url']              = $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']            = $this->sScriptUuid;	//modified script
		if(strtotime($this->sStartTime) < (time()+12*60))
			$iStartTime = time()+12*60;
		else
			$iStartTime = strtotime($this->sStartTime);
		$aOptions['queue']['start']                = $iStartTime;	//開始時間
		$aOptions['queue']['end']                  = strtotime($this->sEndTime);	//結束時間
		$aOptions['queue']['user_no']              = $aExecUser;

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $aQueueRes['status']
		if($aQueueRes['status']===true){

			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
														"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
														));
		}else{
			//call cancel_queue_api for each queue just added
			throw new Exception("CSchedule->vAddActivate:".$aQueueRes['msg']);
		}
	}

	protected function vAddPhone($sApiUrl,$aExecUser){

		$aOptions['queue']['project_no']		   = $this->sProjectUuid;
		if(!empty($this->sPuppetsNo)){
			$aOptions['queue']['puppets_no']	   = $this->sPuppetsNo;
			$oPuppets = CPuppets::oGetPuppets($this->sPuppetsNo);
			$aOptions['queue']['proxy_no']		   = $oPuppets->iProxyNo;
		}else if($iProxyNo!=0){
			$aOptions['queue']['proxy_no']		   = $iProxyNo;
		}else{
			//throw new Exception("both puppets_no and proxy_no are 0");
			$aOptions['queue']['proxy_no']		   = 0;
			$aOptions['queue']['puppets_no']	   = '';
			$aExecUser = array();
		}

		$aOptions['queue']['site_no']			   = $this->iSiteNo;
		$aOptions['queue']['board_no']			   = $this->iBoardNo;
		$aOptions['queue']['priority']             = 1;
		$aOptions['queue']['api_url']              = $sApiUrl;	//執行完後 執行的api_url
		$aOptions['queue']['script_no']            = $this->sScriptUuid;	
		if(strtotime($this->sStartTime) < (time()+12*60))
			$iStartTime = time()+12*60;
		else
			$iStartTime = strtotime($this->sStartTime);
		$aOptions['queue']['start']                = $iStartTime;	//開始時間
		$aOptions['queue']['end']                  = strtotime($this->sEndTime);	//結束時間
		$aOptions['queue']['user_no']              = $aExecUser;

		$queue_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=seat&PHPSESSID='.$_COOKIE['PHPSESSID'];

		//call add_queue_api and get response
		$aQueueRes = self::aCurl($queue_url,$aOptions);

		//different procedure depends on $aQueueRes['status']
		if($aQueueRes['status']===true){

			//set queue_stat
			$this->__aQueueStat[] = new QueueStat(array("queue_uuid"=>$aQueueRes['quuid'],
									"queue_time"=>date("Y-m-d H:i:s",$aQueueRes['exectime'])	//utc datetime
								));
		}else{
			//call cancel_queue_api for each queue just added
			throw new Exception("CSchedule->vAddRegister:".$aQueueRes['msg']);
		}
	}
	protected function sCancelSchedule(){

		$aMsg = array();

		foreach ($this->__aQueueStat as $oStat) {
			if($oStat->sResultTime!='0000-00-00 00:00:00')
				continue;

			$sMsg = $oStat->sCancelQueue();

			if($sMsg!='')
				$aMsg[] = $sMsg;
		}

		return implode('\n', $aMsg);
	}

	protected function vQueueReturn($sQueueUuid,$iResultCode){
		
	}

	public static function aCurl($sUrl,$aOptions){
		//CMisc::vPrintR($sUrl);
		//CMisc::vPrintR($aOptions);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $sUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($aOptions));
		$jResult = curl_exec($curl);
		curl_close($curl);
		$aResult = json_decode($jResult,true);

		if(json_last_error()!=JSON_ERROR_NONE || empty($jResult)){
			$aResult = array(	"status"=>false,
								"msg"=>"json_decode failed"
								);
		}
		return $aResult;
	}

}
//sub class used only here
class QueueStat
{
	public $sQueueUuid;
	public $sQueueTime;
	public $sResultTime;
	public $iResultCode;
	public $sResultUrl;
	public $bParsed;
	public $iExecUserNo;

	public function __construct($multiData){
		$this->sQueueUuid = $multiData['queue_uuid'];
		$this->sQueueTime = $multiData['queue_time'];	//datetime
		$this->sResultTime = $multiData['result_time'];
		$this->iResultCode = $multiData['result_status_code'];
		$this->sResultUrl = $multiData['result_url'];
		$this->bParsed = $multiData['parsed'];
		$this->iExecUserNo = $multiData['exec_user_no'];
	}

	public function sLocalQueueTime(){
		return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sQueueTime)));
	}	

	public function sLocalResultTime(){
		return date('Y-m-d H:i:s',strtotime('+8hour',strtotime($this->sResultTime)));
	}

	public function sErrorMessage(){
		if($this->iResultCode!='0' && array_key_exists($this->iResultCode, CErrorCode::$_ERRORCODE))
			return CErrorCode::$_ERRORCODE[$this->iResultCode];
	}

	public function sCancelQueue(){
		//CMisc::vPrintR($this->sQueueUuid);
		$aCancelRes = array();

		//如果執行的queue 時間跟現在差距15分鐘以內, 就不能取消
		if(strtotime($this->sQueueTime) - time()  < 60*15){
			$aCancelRes['status'] = false;
			$aCancelRes['msg'] = '不能取消準備執行的queue';

			return $aCancelRes['msg'];
		}

		$sQueueDate = date('Y-m-d', strtotime($this->sQueueTime));

		//CMisc::vPrintR($this->sQueueUuid);

		$cancel_url = 'http://'.QUEUE_SERVER.'/api/queue.php?action=cancel&quuid='.$this->sQueueUuid.'&queue_date='.$sQueueDate.'&PHPSESSID='.$_COOKIE['PHPSESSID'];
		
		$aOptions = array();
		$aCancelRes = CSchedule::aCurl($cancel_url,$aOptions);

		//CMisc::vPrintR($aCancelRes);

		if($aCancelRes['status']===true){
			return '';
		}else{
			return $aCancelRes['msg'];
		}
	}
}
?>