<?php
include_once('../inc/model/CSchedule.php');

include_once('../inc/model/beauty/CBeautyDoc.php');
include_once('../inc/model/CElement.php');

class CBeautySchedule extends CSchedule
{

	public $iMainDocNo;
	public $iVerified;
	public $iExamUserNo;
	private $sRejectReason;
	private $__aExecUser;
	private $__oMainDoc;	//beauty doc
	private $__aCBeautyDoc;

	private $__iOwnerNo;
	private $__oOwner;

	static public $aBotUserNos = array();	//specific bot's user_no

	//chained schedule in order
	private $__aScheduleChain;

	//database setting
	static protected $sDBName = 'SITE';

	//instance pool
	static private $aInstantcePool = array();

	static public function oGetSchedule($sScheduleUuid){
		$oDB = self::oDB(self::$sDBName);
		if(array_key_exists($sScheduleUuid, self::$aInstantcePool))
			return self::$aInstantcePool[$sScheduleUuid];

		$sSql = "SELECT * FROM project_schedule WHERE `project_schedule_uuid`='$sScheduleUuid'";
		//$sSql = "SELECT *,a.project_schedule_uuid FROM `project_schedule` AS a LEFT JOIN `project_schedule_result` AS b ON a.project_schedule_uuid=b.project_schedule_uuid WHERE a.project_schedule_uuid='$sScheduleUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCSchedule = new CBeautySchedule($aRow);
		self::$aInstantcePool[$sScheduleUuid] = $oCSchedule;
		return $oCSchedule;
	}

	//get Schedule By queue_uuid
	static public function oGetScheduleByQuuid($sQuuid){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT `project_schedule_uuid` FROM project_schedule_queue WHERE `queue_uuid`='$sQuuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$sScheduleUuid = $aRow['project_schedule_uuid'];
		return self::oGetSchedule($sScheduleUuid);
	}

	/*
		get all beauty schedule in an array
		if $sSearchSql is given, query only match users
		example: $aSchedules = CBeautySchedule::aAllSchedule('schedule_type=1')
		caution: this function may query lots of data from galaxy_beauty2 DB, make sure you need all of these schedules
	*/
	static public function aAllSchedule($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM project_schedule";
		//$sSql = "SELECT *,project_schedule.project_schedule_uuid FROM `project_schedule` LEFT JOIN `project_schedule_result` ON project_schedule.project_schedule_uuid=project_schedule_result.project_schedule_uuid";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllSchedule = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$sScheduleUuid = $aRow['project_schedule_uuid'];
			if(is_null(self::$aInstantcePool[$sScheduleUuid])){
				self::$aInstantcePool[$sScheduleUuid] = new CBeautySchedule($aRow);
			}
			$aAllSchedule[] = self::$aInstantcePool[$sScheduleUuid];
		}
		return $aAllSchedule;
	}

	static public function aNotParsedQueue(){
		$oDB = self::oDB(self::$sDBName);

		$sSql = "SELECT `queue_uuid` FROM project_schedule_queue WHERE `result_time`!='0000-00-00 00:00:00' AND `parsed`='0'";
		$iDbq = $oDB->iQuery($sSql);
		$aTargetUuids = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aTargetUuids[] = $aRow['queue_uuid'];
		}
		return $aTargetUuids;
	}

	/*
		get count of schedule which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(project_schedule_uuid) as total FROM project_schedule";
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
		get timeout queue and update them
	*/
	static public function vUpdateTimeoutQueue(){
		$oDB = self::oDB(self::$sDBName);

		$sTimeout = date('Y-m-d H:i:s',time()-15*60);

		$sSql = "SELECT `project_schedule_uuid`,`queue_uuid` FROM project_schedule_queue WHERE `queue_time`<'$sTimeout' AND `result_status_code`='0'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$sQueueUuid = $aRow['queue_uuid'];
			$sScheduleUuid = $aRow['project_schedule_uuid'];
			CMisc::vPrintR($aRow);
			try{
				//use schedule_uuid get BeautySchedule object
				$oSchedule = CBeautySchedule::oGetSchedule($sScheduleUuid);
				//use queue_uuid and failed code run vQueueReturn($sQueueUuid, $iFailed)
				$oSchedule->vQueueReturn($sQueueUuid,2);
				//if it's a monitor schedule
				if($oSchedule->iTypeNo == 4){
					//get last url and add monitor(catch) queue again
					$sTargetUrl = '';
					foreach ($oSchedule->aQueueStat() as $oQueueStat) {
						if($oQueueStat->sResultUrl != '')
							$sTargetUrl = $oQueueStat->sResultUrl;
					}
					$oSchedule->vAddMonitor($sTargetUrl);
				}

			}catch(exception $e){
				//CMisc::vPrintR($e->getMessage());
				continue;
			}
		}
	}

	/*
		link schedules, become a schedule chain
		$aTargetSchedules should in form below
		array(	1=>$oSchedule1,
				2=>$oSchedule2,
				3=>$oSchedule3
				)
	*/
	static public function vLinkSchedule($aTargetSchedules){
		if(!is_array($aTargetSchedules))
			throw new Exception("CBeautySchedule::vLinkSchedule() require array or CBeautySchedule");

		$oDB = self::oDB(self::$sDBName);

		try{
			$oDB->vBegin();

			for($iOrder=1; $iOrder<=count($aTargetSchedules); $iOrder++){

				$oPrevSchedule = $aTargetSchedules[$iOrder];
				$oNextSchedule = $aTargetSchedules[$iOrder+1];

				if(get_class($oPrevSchedule)!=='CBeautySchedule' || get_class($oNextSchedule)!=='CBeautySchedule')
					throw new Exception("error in order; handling object should be CBeautySchedule");

				$aLinker = array(	'prev_schedule_uuid'=>$oPrevSchedule->sScheduleUuid,
									'next_schedule_uuid'=>$oNextSchedule->sScheduleUuid
								);
				
				$oDB->sInsert('project_schedule_chain',array_keys($aLinker),array_values($aLinker));

			}
			$oDB->vCommit();
		}catch(exception $e){
			$oDB->vRollBack();
			throw new Exception("CBeautySchedule::vLinkSchedule:".$e->getMessage());
		}
	}

	static public function vParseDone($sQueueUuid){
		$oDB = self::oDB(self::$sDBName);
		$aValues = array(	"parsed" => true
							);
		try{
			$oDB->vUpdate("project_schedule_queue",array_keys($aValues),array_values($aValues),"`queue_uuid`='$sQueueUuid'");
		}catch(exception $e){
			throw new Exception("CBeautySchedule::vParseDone: failed update");
		}
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(empty($this->sScheduleUuid))
			$this->sScheduleUuid = 'beauty_'.CMisc::uuid_v1();

		$this->iMainDocNo = $multiData['main_doc_no'];
		$this->iVerified = $multiData['verified'];

		if(is_null($multiData['owner_no'])){
            $oCUser = self::$session->get('oCurrentUser');
            $multiData['owner_no'] = $oCUser->iUserNo;
        }else{
        	$this->__iOwnerNo = $multiData['owner_no'];
        }

		$this->iExamUserNo = $multiData['exam_user_no'];
		$this->sRejectReason = $multiData['reject_reason'];
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    /*******************************************
			class member controll function
    *******************************************/

	public function oOwner(){
		if(is_null($this->__oOwner)){
            $this->__oOwner = CUser::oGetUser($this->__iOwnerNo);
        }
        return $this->__oOwner;
	}

	public function aExecUser(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aExecUser)){
			$this->__aExecUser = array();
			$sSql = "SELECT * FROM project_schedule_user WHERE `project_schedule_uuid`='{$this->sScheduleUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aExecUser[] = CUser::oGetUser($aRow['user_no']);
			}
		}
		return $this->__aExecUser;
	}

	public function aDoc(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCBeautyDoc)){
			$this->__aCBeautyDoc = array();
			$sSql = "SELECT * FROM project_schedule_doc WHERE `project_schedule_uuid`='{$this->sScheduleUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$oBeautyDoc = CBeautyDoc::oGetDoc($aRow['principle_doc_no']);
				$oBeautyDoc->iElementNo = $aRow['element_no'];

				$oElement = CElement::oGetElement($aRow['element_no']);	//map to doc_element_no in script
				if($oElement->iElementMappingNo !== '0')
					$oBeautyDoc->sElementName = $oElement->oElementMapping()->sName;
				$this->__aCBeautyDoc[] = $oBeautyDoc;
			}
		}
		return $this->__aCBeautyDoc;
	}

	public function oMainDoc(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__oMainDoc)){
			$this->__oMainDoc = CBeautyDoc::oGetDoc($this->iMainDocNo);
		}
		return $this->__oMainDoc;	
	}

	public function iDocCount(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(principle_doc_no) AS total FROM project_schedule_doc WHERE `project_schedule_uuid`='{$this->sScheduleUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		return (int)$aRow['total'];
	}

	public function aQueueStat(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aQueueStat)){
			$sSql = "SELECT * FROM project_schedule_queue WHERE `project_schedule_uuid`='{$this->sScheduleUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aQueueStat[] = new QueueStat($aRow);
			}
		}
		return $this->__aQueueStat;
	}

	public function aScheduleChain(){
		$oDB = self::oDB(self::$sDBName);
		if(is_null($this->__aScheduleChain)){

			//initialize an empty array
			$this->__aScheduleChain = array();

			//get first child
			$sSql = "SELECT * project_schedule_chain WHERE `prev_schedule_uuid`={$this->sScheduleUuid}";
			$iDbq = $oDB->iQuery($sSql);
			$aRow = $oDB->aFetchAssoc($iDbq);
			if($aRow === false || $oDB->iNumRows($iDbq)>1)	//prev & next should always be unique
				return $this->__aScheduleChain;	//return empty array
			$oNextSchedule = CBeautySchedule::oGetSchedule($aRow['next_schedule_uuid']);
			$this->__aScheduleChain[0] = $oNextSchedule;

			//if first child also has child, append them
			$aChildChain = $oNextSchedule->aScheduleChain();
			if(!empty($aChildChain)){
				$iChainCount = 1;
				foreach ($aChildChain as $oSchedule) {
					$this->__aScheduleChain[$iChainCount] = $oSchedule;
				}
			}
		}

		return $this->__aScheduleChain;
	}

	/*******************************************
    			DB controll function
    *******************************************/
	public function sAddSchedule($aPostData){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			parent::sAddSchedule($aPostData);

			//insert project_schedule
			$aValues = array(	"project_schedule_uuid"=>$this->sScheduleUuid,
								"schedule_type"=>$this->iTypeNo,
								"main_doc_no"=>$this->iMainDocNo,
								"url"=>$this->sUrl,
								"project_no"=>$this->sProjectUuid,
								"site_no"=>$this->iSiteNo,
								"board_no"=>$this->iBoardNo,
								'script_no'=>$this->iScriptNo,
								'puppets_no'=>$this->sPuppetsNo,
								'verified'=>$this->iVerified,
								'owner_no'=>$this->__iOwnerNo,
								'exam_user_no'=>is_null($this->oMainDoc())?0:($this->oMainDoc()->__iOwnerNo),
								'times'=>$this->iTimes,
								'status'=>true,
								'user_no'=>$oCurrentUser->iUserNo,
								'schedule_start_time'=>$this->sStartTime,
								'schedule_end_time'=>$this->sEndTime,
								'create_time'=>date("Y-m-d H:i:s"),
								'modify_time'=>date("Y-m-d H:i:s")
								);
			$oDB->sInsert("project_schedule",array_keys($aValues),array_values($aValues));

			//insert project_schedule_docs
			foreach($aPostData AS $key => $val){
                if(preg_match("/^file_docs_([0-9]+)_beauty$/i",$key,$match)){
                	if(empty($aPostData['file_docs_'.$match[1].'_element']))
                		continue;
                    $iBeautyDocNo = $val;
                    $aDocValue = array(	"project_schedule_uuid"=>$this->sScheduleUuid,
                    					"principle_doc_no"=>$iBeautyDocNo,
                    					"doc_element_no"=>$aPostData['file_docs_'.$match[1].'_element'],	//the element_mapping_no of that file_doc given by user
                    					"galaxy_doc_no"=>$aPostData['file_docs_'.$match[1]]
                    					);
                    $oDB->sInsert("project_schedule_doc",array_keys($aDocValue),array_values($aDocValue));
                }
            }

            //insert project_schedule_user
            foreach($aPostData['exec_user'] AS $iUserNo){
            	$aUserVal = array(	'project_schedule_uuid'=>$this->sScheduleUuid,
            						'user_no'=>$iUserNo
            						);
            	$oDB->sInsert("project_schedule_user",array_keys($aUserVal),array_values($aUserVal));
            }

			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollBack();
			throw new Exception('CBeautySchedule->sAddSchedule: '.$e->getMessage());
		}

    	return $this->sScheduleUuid;
    }

    public function vUpdateSchedule($aPostData){
    	$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			//insert project_schedule
			$aValues = array(	"url"=>$this->sUrl,
								"site_no"=>$this->iSiteNo,
								"board_no"=>$this->iBoardNo,
								'script_no'=>$this->iScriptNo,
								'puppets_no'=>$this->sPuppetsNo,
								'puppets_no'=>$this->sPuppetsNo,
								'verified'=>$this->iVerified,
								'times'=>$this->iTimes,
								'user_no'=>$oCurrentUser->iUserNo,
								'status'=>true,
								'schedule_start_time'=>$this->sStartTime,
								'schedule_end_time'=>$this->sEndTime,
								'modify_time'=>date("Y-m-d H:i:s")
								);
			$oDB->sUpdate("project_schedule",array_keys($aValues),array_values($aValues),"`project_schedule_uuid`='{$this->sScheduleUuid}'");

			//insert project_schedule_docs
			$oDB->vDelete("project_schedule_doc","`project_schedule_uuid`='{$this->sScheduleUuid}'");
			foreach($aPostData AS $key => $val){
                if(preg_match("/^file_docs_([0-9]+)_beauty$/i",$key,$match)){
                	if(empty($aPostData['file_docs_'.$match[1].'_element']))
                		continue;
                    $iBeautyDocNo = $val;
                    $aDocValue = array(	"project_schedule_uuid"=>$this->sScheduleUuid,
                    					"principle_doc_no"=>$iBeautyDocNo,
                    					"doc_element_no"=>$aPostData['file_docs_'.$match[1].'_element'],	//the element_mapping_no of that file_doc given by user
                    					"galaxy_doc_no"=>$aPostData['file_docs_'.$match[1]]
                    					);
                    $oDB->sInsert("project_schedule_doc",array_keys($aDocValue),array_values($aDocValue));
                }
            }

            //insert project_schedule_user
            $oDB->vDelete("project_schedule_user","`project_schedule_uuid`='{$this->sScheduleUuid}'");
            foreach($aPostData['exec_user'] AS $iUserNo){
            	$aUserVal = array(	'project_schedule_uuid'=>$this->sScheduleUuid,
            						'user_no'=>$iUserNo
            						);
            	$oDB->sInsert("project_schedule_user",array_keys($aUserVal),array_values($aUserVal));
            }

			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollBack();
			throw new Exception('CBeautySchedule->vUpdateSchedule: '.$e->getMessage());
		}
    }

    public function vExam(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		if($this->iVerified !== '0')
			return;

		$this->iVerified = '1';

		$aValues = array(	'user_no'=>$oCurrentUser->iUserNo,
							'modify_time'=>date("Y-m-d H:i:s"),
							'verified'=>$this->iVerified
							);
		$oDB->sUpdate("project_schedule",array_keys($aValues),array_values($aValues),"project_schedule_uuid='{$this->sScheduleUuid}'");
		$oCurrentUser->vAddUserLog('project_schedule',$this->sScheduleUuid,$_GET['func'],$_GET['action']);
		return;
	}

	public function vVerify($iLevel){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		if($this->iTypeNo != array_search('post', self::$aType) && $this->iTypeNo != array_search('response', self::$aType))
			throw new Exception("this schedule need not be verified");
		
		if($this->iVerified == '3')
			return;
		else
			$this->iVerified = $iLevel;

		$aValues = array(	'user_no'=>$oCurrentUser->iUserNo,
							'modify_time'=>date("Y-m-d H:i:s"),
							'verified'=>$this->iVerified,
							'reject_reason'=>''
							);
		$oDB->sUpdate("project_schedule",array_keys($aValues),array_values($aValues),"project_schedule_uuid='{$this->sScheduleUuid}'");
		$oCurrentUser->vAddUserLog('project_schedule',$this->sScheduleUuid,$_GET['func'],$_GET['action']);
		return;
	}

	public function vReject($sRejectReason){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		
		$this->iVerified = '0';
		if(empty($this->sRejectReason))
			$this->sRejectReason = $sRejectReason;
		else
			$this->sRejectReason .= "\n$sRejectReason";
		
		$aValues = array(	'user_no'=>$oCurrentUser->iUserNo,
							'modify_time'=>date("Y-m-d H:i:s"),
							'verified'=>$this->iVerified,
							'reject_reason'=>$this->sRejectReason
							);
		$oDB->sUpdate("project_schedule",array_keys($aValues),array_values($aValues),"project_schedule_uuid='{$this->sScheduleUuid}'");
		$oCurrentUser->vAddUserLog('project_schedule',$this->sScheduleUuid,$_GET['func'],$_GET['action']);
		return;
	}

    public function vAddPost(){
    	$oDB = self::oDB(self::$sDBName);
    	if($this->iTypeNo != array_search('post',self::$aType))
    		throw new Exception("this schedule is not a post schedule");

    	try{
    		//set api_url, depends on subsystem
			$sApiUrl = "http://".$_SERVER['SERVER_ADDR']."/api/api.CBeautySchedule.php?action=queue_return&schedule_uuid={$this->sScheduleUuid}";

			//get file_docs' galaxy_doc_no with map doc_element
			$aFileDocs = array();
			$sSql = "SELECT * FROM project_schedule_doc WHERE project_schedule_uuid='{$this->sScheduleUuid}'";
			$oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc){
				$aFileDocs[$aRow['doc_element_no']] = $aRow['galaxy_doc_no'];
			}
			//get exec_user
			$aExecUser = array();
			$sSql = "SELECT `user_no` FROM project_schedule_user WHERE `project_schedule_uuid`='{$this->sScheduleUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$aExecUser[] = $aRow['user_no'];
			}
			
			//if this schedule has main_doc or not
			if(is_null($this->oMainDoc()))
				parent::vAddPost($sApiUrl,$aExecUser);
			else
				parent::vAddPost($sApiUrl,$aExecUser,$this->oMainDoc()->sDocUuid,$aFileDocs);
			$oDB->vBegin();
    		//add queue_stat into sub system DB
			foreach ($this->__aQueueStat as $oStat) {
				$aStatValue = array(	"project_schedule_uuid"=>$this->sScheduleUuid,
										"queue_uuid"=>$oStat->sQueueUuid,
										"queue_time"=>$oStat->sQueueTime
										);
				$oDB->sInsert("project_schedule_queue",array_keys($aStatValue),array_values($aStatValue));
			}
			$oDB->vCommit();
    	}catch(Exception $e){
    		$oDB->vRollBack();
    		//reject this schedule with error message
			$this->vReject($e->getMessage());
			
    		parent::vCancelQueue();
    		throw new Exception("CBeautySchedule->vAddPost:".$e->getMessage());
    	}
    }

    public function vAddCatch($iStartTime,$sTargetUrl){
    	if($this->iTypeNo != array_search('catch',self::$aType))
    		throw new Exception("this schedule is not a catch schedule");
    	$this->iVerified = 3;

    	try{
    		//set api_url, depends on subsystem
			$sApiUrl = "http://".$_SERVER['SERVER_ADDR']."/api/api.CBeautySchedule.php?action=queue_return&schedule_uuid={$this->sScheduleUuid}";


		}catch(Exception $e){
    		$oDB->vRollBack();
    		parent::vCancelQueue();
    		throw new Exception("CBeautySchedule->vAddCatch:".$e->getMessage());
    	}

    }

    public function vAddResponse(){
    	if($this->iTypeNo != array_search('response',self::$aType))
    		throw new Exception("this schedule is not a response schedule");

    	try{
    		//set api_url, depends on subsystem
			$sApiUrl = "http://".$_SERVER['SERVER_ADDR']."/api/api.CBeautySchedule.php?action=queue_return&schedule_uuid={$this->sScheduleUuid}";

			//if we want to response, target url should be parent doc's schedule result url
			if($this->oMainDoc()->iParentDocNo === '0')
				throw new Exception("response should not use a doc without parent");

			//if this schedule has main_doc or not
			if(is_null($this->oMainDoc()))
				parent::vAddPost($sApiUrl,$aExecUser);
			else
				parent::vAddPost($sApiUrl,$aExecUser,$this->oMainDoc()->sDocUuid,$aFileDocs);

			
		}catch(Exception $e){
    		$oDB->vRollBack();
    		parent::vCancelQueue();
    		throw new Exception("CBeautySchedule->vAddResponse:".$e->getMessage());
    	}

    }

    public function vAddMonitor($sTargetUrl){
    	if($this->iTypeNo != array_search('monitor',self::$aType))
    		throw new Exception("this schedule is not a monitor schedule");

    	//set api_url, depends on subsystem
	  	$sApiUrl = "http://".$_SERVER['SERVER_ADDR']."/api/api.CBeautySchedule.php?action=queue_return&schedule_uuid={$this->sScheduleUuid}";

    	$iStart = time() + 4*60*60;
    	$iEnd = time() + 8*60*60;

    	//get exec_user
		$aExecUser = array();
		$sSql = "SELECT `user_no` FROM project_schedule_user WHERE `project_schedule_uuid`='{$this->sScheduleUuid}'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aExecUser[] = $aRow['user_no'];
		}

    	try{
    		if(empty($this->sPuppetsNo)){
		  		$iProxyNo;	//random in some special proxy

		  		/*
					MIGHT BE QUERIED BY PROXY_GROUP ASSIGN TO BEAUTY2
		  		*/

		  		parent::vAddCatch($sApiUrl,$aExecUser,$iStart,$iEnd,$sTargetUrl,$iProxyNo);	
		  	}else{
		  		parent::vAddCatch($sApiUrl,$aExecUser,$iStart,$iEnd,$sTargetUrl);
		  	}
			
    		//add queue_stat into sub system DB
    		$oStat = $this->__aQueueStat[count($this->__aQueueStat)-1];
			$aStatValue = array(	"project_schedule_uuid"=>$this->sScheduleUuid,
									"queue_uuid"=>$oStat->sQueueUuid,
									"queue_time"=>$oStat->sQueueTime
									);
			$oDB->sInsert("project_schedule_queue",array_keys($aStatValue),array_values($aStatValue));
			
		}catch(Exception $e){
    		$oDB->vRollBack();
    		parent::vCancelQueue();
    		throw new Exception("CBeautySchedule->vAddMonitor:".$e->getMessage());
    	}
    }

    public function vCancelSchedule(){
    	$oDB = self::oDB(self::$sDBName);
    	try{
    		$this->aQueueStat();	//get QueueStat
			parent::vCancelSchedule();
    		//sub system record controll code
    		$aValues = array(	'verified'=>3,
    							"status"=>0,
    							"result_status"=>self::$aResultCode['failed']
    							);
			$oDB->sUpdate("project_schedule",array_keys($aValues),array_values($aValues),"project_schedule_uuid='{$this->sScheduleUuid}'");
		}catch(Exception $e){
			throw new Exception('CBeautySchedule->vCancelSchedule: '.$e->getMessage());
		}
    }

    public function vQueueReturn($sQueueUuid,$iResultCode,$sResultUrl=''){
    	$oDB = self::oDB(self::$sDBName);

    	if(count($this->aQueueStat())>1){
    		$iScheduleResult = self::$aResultCode['multiple'];
    	}else if($iResultCode == 2101){	
    		$iScheduleResult = self::$aResultCode['success'];	//translate to self::$aResultCode
    	}else{
    		$iScheduleResult = self::$aResultCode['failed'];
    	}

    	try{
    		parent::vQueueReturn($sQueueUuid,$iResultCode);
    		$oDB->vBegin();
    		//update schedule's result_status_code
    		$aValues = array("result_status"=>$iScheduleResult);
			$oDB->sUpdate("project_schedule",array_keys($aValues),array_values($aValues),"project_schedule_uuid='{$this->sScheduleUuid}'");
			//update schedule_queue's result_time & result_status_code
			$aStatValue = array("result_time"=>date("Y-m-d H:i:s"),
								"result_status_code"=>$iResultCode,
								"result_url"=>$sResultUrl
								);
			$oDB->sUpdate("project_schedule_queue",array_keys($aStatValue),array_values($aStatValue),"queue_uuid='$sQueueUuid'");

			//if failed, send task to user who create task


			$oDB->vCommit();
    	}catch(Exception $e){
    		$oDB->vRollBack();
    		throw new Exception("CBeautySchedule->vQueueReturn:".$e->getMessage());
    	}
    }
}
?>