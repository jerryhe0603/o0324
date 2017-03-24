<?php
include_once('../inc/model/CDoc.php');
include_once('../inc/model/CBoard.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CElementMapping.php');
include_once('../inc/model/beauty/CBeautyPostType.php');
include_once('../inc/model/beauty/CBeautySchedule.php');

Class CBeautyDoc extends CGalaxyClass
{
	static public $aComment = array(	'0'=>"NULL",
										/*
										'1'=>"GREAT",
										'2'=>"GOOD",
										'3'=>"FINE",
										'4'=>"SOSO",
										'5'=>"BAD",
										'10'  => '無關評價 (完全無關)',		//一般 -> 議題討論
										'6'  => '△ 一般評價 (普通,回覆)',		//一般 -> 產品
										'8'  => 'Ｏ 好評 (有親身接觸)',			//正向
										'1'  => 'Ｏ 好感 (未親身接觸)',			//正向
										'5'  => 'ＯＸ 複合評價 (好壞皆有)',		//複合
										'9'  => 'Ｘ 壞感 (未親身接觸)',			//負向
										'4'  => 'Ｘ 負評 (有親身接觸)',			//負向
										'3'  => 'Ｘ 情緒謾罵 (過度反應)',		//負向
										*/
										'11' => '議題討論',	//update 10 to 11
										'12' => '產品討論',	//update 6 to 12
										'13' => '正向討論',	//update 1 & 8 to 13
										'14' => '負向討論',	//update 3 & 4 & 9 to 14
										'15' => '複合討論'	//update 5 to 15
										);

	//fix beauty system member, fix after construct
	private $iBeautyDocNo;
	private $sProjectUuid;
	private $iPrincipleNo;
	private $sName;
	public $sDesc;
	private $iParentDocNo;	//beauty doc
	private $sDocUuid;	//doc uuid in galaxy_docs
	public $iPostType;
	public $bGalaxy;
	public $bCommentCheck;
	public $bClosed;

	private $__iOwnerNo;
	private $__oOwner;
	
	private $__iWriteUserNo;
	private $__oWriter;
	
	//set below members only when certain funcion is called
	//analysis member; $oCBeautyDoc->vGetAnalysis();
	public $iPriceComment;
	public $iServiceComment;
	public $iMainComment;
	public $iPathComment;
	public $iElseComment;
	public $bGreat;
	public $bBestAnswer;
	public $bMDoc;
	public $bPicExp;
	public $bOpenBoxExp;
	public $bCompete;
	//writings; $oCBeautyDoc->vGetWriting();
	public $sCommunication;
	public $sPositiveSummary;
	public $sNegativeSummary;
	public $sCompeteSummary;

	//oCDoc
	public $__oCDoc;
	private $__aChildDoc =array();
	private $__aBoard = array();	//array of CBoard object

	//about schedule
	private $__oNonVerifiedSchedule;
	private $__aSchedule;

	//store for schedule, only for beauty2
	public $iScriptNo;
	public $sPuppetsNo;
	public $sStartTime;
	public $sEndTime;

	//database setting
	static protected $sDBName = 'SITE';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCBeautyDoc by certain principle_doc_no
	*/
	static public function oGetDoc($iBeautyDocNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iBeautyDocNo]))
			return self::$aInstancePool[$iBeautyDocNo];

		//query from beauty DB
		$sSql = "SELECT * FROM principle_doc WHERE principle_doc_no='$iBeautyDocNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCBeautyDoc = new CBeautyDoc($aRow);
		self::$aInstancePool[$iBeautyDocNo] = $oCBeautyDoc;

		return $oCBeautyDoc;
	}

	static public function iGetDocNoByUuid($sDocUuid){
		$oDB = self::oDB(self::$sDBName);

		//query from beauty DB
		$sSql = "SELECT principle_doc_no FROM principle_doc WHERE galaxy_doc_no='$sDocUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return 0;

		return $aRow['principle_doc_no'];
	}

	/*
		get all beauty doc in an array
		if $sSearchSql is given, query only match docs
		CAUTION: this function WON'T return a full $oCBeautyDoc object with info in doc DB, but only info in galaxy_beauty2
	*/
	static public function aAllDoc($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllDoc = array();
		$sSql = "SELECT * FROM principle_doc";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['principle_doc_no']])){
				self::$aInstancePool[$aRow['principle_doc_no']] = new CBeautyDoc($aRow);
			}
			$aAllDoc[] = self::$aInstancePool[$aRow['principle_doc_no']];
		}
		return $aAllDoc;
	}

	/*
		get count of doc which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(principle_doc_no) as total FROM principle_doc";
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
		get default element_mapping for this kind of document
	*/
	static public function aDefaultFields(){
		$oDB = self::oDB(self::$sDBName);
		$aEleMap = array();
		$sSql = "SELECT * FROM doc_default_fields";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oEleMap = CElementMapping::oGetElementMapping($aRow['element_mapping_no']);
			$oEleMap->bFieldStatus = $aRow['fields_status'];	//extra member
			$aEleMap[] = $oEleMap;
		}
		return $aEleMap;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CBeautyDoc: __construct failed, require an array");
		//initialize member
		$this->iBeautyDocNo = $multiData['principle_doc_no'];
		$this->sProjectUuid = $multiData['project_no'];
		$this->sStartTime = $multiData['start_time'];
		$this->sEndTime = $multiData['end_time'];

		if(is_null($multiData['owner_no'])){
            $oCUser = self::$session->get('oCurrentUser');
            $multiData['owner_no'] = $oCUser->iUserNo;
        }else{
        	$this->__iOwnerNo = $multiData['owner_no'];
        }

		$this->__iWriteUserNo = $multiData['write_user_no'];
		$this->iPrincipleNo = $multiData['principle_no'];
		$this->sName = $multiData['doc_name'];
		$this->sDesc = $multiData['doc_desc'];
		$this->iParentDocNo = $multiData['parent_doc_no'];
		$this->sDocUuid = $multiData['galaxy_doc_no'];
		$this->bGalaxy = $multiData['galaxy_doc'];
		$this->iPostType = $multiData['post_type_no'];
		$this->bClosed = $multiData['closed'];
		$this->bCommentCheck = $multiData['comment_check'];
		$this->iScriptNo = $multiData['script_no'];
		$this->sPuppetsNo = $multiData['puppets_no'];
		//galaxy class member
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['create_time'];
		$this->sModifiedTime = $multiData['modify_time'];
	}
	
	public function __get($varName)
    {
        return $this->$varName;
    }

    public function oOwner(){
		if(is_null($this->__oOwner)){
            $this->__oOwner = CUser::oGetUser($this->__iOwnerNo);
        }
        return $this->__oOwner;
	}
 
	/*
		get $oWriter  by certain write_user_no
	*/
	public function oWriter(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__oWriter)){
			$sSql = "SELECT write_user_no FROM principle_doc WHERE principle_doc_no='$this->iBeautyDocNo'";
			$iDbq = $oDB->iQuery($sSql);
			$aRow = $oDB->aFetchAssoc($iDbq);
			$this->__oWriter = CUser::oGetUser($aRow['write_user_no']);
		}
		return $this->__oWriter;
	}

	/*
		set & get doc(galaxy_doc) content
	*/
	public function oCDoc(){
		if(empty($this->__oCDoc))
			$this->__oCDoc = CDoc::oGetDoc($this->sDocUuid);
		return $this->__oCDoc;
	}

	public function oPostType(){
		if(empty($this->__oPostType))
			$this->__oPostType = CBeautyPostType::oGetType($this->iPostType);
		return $this->__oPostType;
	}

	public function aBoard(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aBoard)){
			$sSql = "SELECT * FROM principle_doc_board WHERE principle_doc_no='{$this->iBeautyDocNo}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aBoard[] = CBoard::oGetBoard($aRow['galaxy_board_no']);
			}
		}
		return $this->__aBoard;
	}

	public function oNonVerifiedSchedule(){
		if(empty($this->__oNonVerifiedSchedule)){
			$aSchedule = CBeautySchedule::aAllSchedule("`main_doc_no`='{$this->iBeautyDocNo}' AND `verified`!=3 AND `verified`!=2");
			if(count($aSchedule)!==1 && count($aSchedule)!==0)
				throw new Exception("CBeautySchedule: fatal error; non verified schedule should always be 0 or 1. Contact with system developer");
			$this->__oNonVerifiedSchedule = $aSchedule[0];
		}
		
		return $this->__oNonVerifiedSchedule;
	}

	public function aSchedule(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aSchedule)){
			/*
			$sSql = "SELECT * FROM project_schedule_doc WHERE principle_doc_no='{$this->iBeautyDocNo}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aSchedule[$aRow['project_schedule_uuid']] = CBeautySchedule::oGetSchedule($aRow['project_schedule_uuid']);
			}
			*/
			$this->__aSchedule = CBeautySchedule::aAllSchedule("`main_doc_no`='{$this->iBeautyDocNo}'");
		}
		return $this->__aSchedule;
	}

	/*
		set & get child doc recursive
	*/
	public function aChildRecursive(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aChildDoc)){
			$sSql = "SELECT * FROM principle_doc WHERE parent_doc_no='{$this->iBeautyDocNo}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$oChildDoc = new CBeautyDoc($aRow);
				$oChildDoc->oCDoc = CDoc::oGetDoc($oChildDoc->sDocUuid);
				$oChildDoc->vGetAnalysis();
				$oChildDoc->vGetWriting();
				$oChildDoc->aChildRecursive();
				$this->__aChildDoc[] = $oChildDoc;
			}
		}
		return $this->__aChildDoc;
	}

	/*
		doc family count
	*/
	public function iFamilyCount(){
		return self::iGetCount("principle_doc_no='{$this->iBeautyDocNo}' OR parent_doc_no='{$this->iBeautyDocNo}'");
	}

	public function vOverWrite($oCBeautyDoc){
		//if not a CBeautyDoc object or uuid not match
		if(get_class($oCBeautyDoc)!=='CBeautyDoc' || $this->iBeautyDocNo!==$oCBeautyDoc->iBeautyDocNo)
			throw new Exception('CBeautyDoc->vOverwrite: fatal error ');

		$this->bGalaxy = $oCBeautyDoc->bGalaxy;
		$this->sStartTime = $oCBeautyDoc->sStartTime;
		$this->sEndTime = $oCBeautyDoc->sEndTime;
		//$this->__iWriteUserNo = $oCBeautyDoc->__iWriteUserNo;
		$this->iScriptNo = $oCBeautyDoc->iScriptNo;
		$this->sPuppetsNo = $oCBeautyDoc->sPuppetsNo;
		$this->iPostType = $oCBeautyDoc->iPostType;
		$this->sDesc = $oCBeautyDoc->sDesc;
		$this->iVerifyCheck = $oCBeautyDoc->iVerifyCheck;
		$this->bCommentCheck = $oCBeautyDoc->bCommentCheck;
		$this->iPriceComment = $oCBeautyDoc->iPriceComment;
		$this->iServiceComment = $oCBeautyDoc->iServiceComment;
		$this->iMainComment = $oCBeautyDoc->iMainComment;
		$this->iPathComment = $oCBeautyDoc->iPathComment;
		$this->iElseComment = $oCBeautyDoc->iElseComment;
		$this->bGreat = $oCBeautyDoc->bGreat;
		$this->bBestAnswer = $oCBeautyDoc->bBestAnswer;
		$this->bMDoc = $oCBeautyDoc->bMDoc;
		$this->bPicExp = $oCBeautyDoc->bPicExp;
		$this->bOpenBoxExp = $oCBeautyDoc->bOpenBoxExp;
		$this->bCompete = $oCBeautyDoc->bCompete;
		$this->sCommunication = $oCBeautyDoc->sCommunication;
		$this->sPositiveSummary = $oCBeautyDoc->sPositiveSummary;
		$this->sNegativeSummary = $oCBeautyDoc->sNegativeSummary;
		$this->sCompeteSummary = $oCBeautyDoc->sCompeteSummary;

	}

	/*
		update beauty doc
	*/
	public function vUpdateDoc(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$sDate = date("Y-m-d H:i:s");
			if(!empty($this->__oCDoc))
				$this->__oCDoc->vUpdateDoc();
			//update doc attr
			$aValues = array(	'galaxy_doc'=>$this->bGalaxy,
								'start_time' => $this->sStartTime,
								'end_time' => $this->sEndTime,
								'user_no'=>$oCurrentUser->iUserNo,
								'write_user_no' => $this ->__iWriteUserNo,
								'modify_time'=>$sDate,
								'comment_check'=>$this->bCommentCheck,
								'post_type_no'=>$this->iPostType,
								'doc_desc'=>$this->sDesc,
								'status'=>$this->bStatus,
								'script_no'=>$this->iScriptNo,
								'puppets_no'=>$this->sPuppetsNo
								);
			$oDB->sUpdate("principle_doc",array_keys($aValues),array_values($aValues),"principle_doc_no='{$this->iBeautyDocNo}'");
			//update doc_anaylsis attr
			$aAnaValues = array(	'price_comment'=>$this->iPriceComment,
									'service_comment'=>$this->iServiceComment,
									'main_comment'=>$this->iMainComment,
									'path_comment'=>$this->iPathComment,
									'else_comment'=>$this->iElseComment,
									'great_doc'=>$this->bGreat,
									'best_answer'=>$this->bBestAnswer,
									'M_doc'=>$this->bMDoc,
									'pic_exp'=>$this->bPicExp,
									'openbox_exp'=>$this->bOpenBoxExp,
									'compete_doc'=>$this->bCompete
								);
			$oDB->sUpdate("principle_doc_analysis",array_keys($aAnaValues),array_values($aAnaValues),"principle_doc_no='{$this->iBeautyDocNo}'");
			//update doc_writing attr
			$aWriValues = array(	'communication'=>$this->sCommunication,
									'positive_summary'=>$this->sPositiveSummary,
									'negative_summary'=>$this->sNegativeSummary,
									'compete_summary'=>$this->sCompeteSummary
								);
			$oDB->sUpdate("principle_doc_writings",array_keys($aWriValues),array_values($aWriValues),"principle_doc_no='{$this->iBeautyDocNo}'");
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('principle_doc',$this->iBeautyDocNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CBeautyDoc->vUpdateDoc:'.$e->getMessage());
		}
	}

	public function vReassignDoc($oCBeautyDoc){
		$this->__iWriteUserNo = $oCBeautyDoc->__iWriteUserNo;
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			$aValues = array(
				'user_no'=>$oCurrentUser->iUserNo,
				'write_user_no' => $this ->__iWriteUserNo,
				'modify_time'=>$sDate
				);
			$oDB->sUpdate("principle_doc",array_keys($aValues),array_values($aValues),"principle_doc_no='{$this->iBeautyDocNo}'");
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('principle_doc',$this->iBeautyDocNo,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CBeautyDoc->vReassignDoc:'.$e->getMessage());
		}
	}

	/*
		return principle_doc_no
	*/
	public function iAddDoc(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//insert CDoc info and get doc_uuid
			$this->sDocUuid = $this->__oCDoc->sAddDoc();	//CAUTION: use different DB, so if this function failed, delete that insert data manually
			//insert doc attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'project_no'=>$this->sProjectUuid,
								'principle_no'=>$this->iPrincipleNo,
								'doc_name'=>$this->sName,
								'doc_desc'=>$this->sDesc,
								'status'=>$this->bStatus,
								'start_time'=>$this->sStartTime,
								'end_time'=>$this->sEndTime,
								'write_user_no'=>$this->__iWriteUserNo,
								'parent_doc_no'=>$this->iParentDocNo,
								'galaxy_doc_no'=>$this->sDocUuid,
								'galaxy_doc'=>$this->bGalaxy,
								'user_no'=>$oCurrentUser->iUserNo,
								'create_time'=>(empty($this->sCreateTime)?$sDate:$this->sCreateTime),
								'modify_time'=>$sDate,
								'closed'=>false,
								'post_type_no'=>$this->iPostType,
								'comment_check'=>$this->bCommentCheck,
								'script_no'=>$this->iScriptNo,
								'puppets_no'=>$this->sPuppetsNo
							);
			$oDB->sInsert("principle_doc",array_keys($aValues),array_values($aValues));
			$this->iBeautyDocNo = $oDB->iGetInsertId();
			
			//insert doc_anaylsis attr
			$aAnaValues = array(	'principle_doc_no'=>$this->iBeautyDocNo,
									'price_comment'=>$this->iPriceComment,
									'service_comment'=>$this->iServiceComment,
									'main_comment'=>$this->iMainComment,
									'path_comment'=>$this->iPathComment,
									'else_comment'=>$this->iElseComment,
									'great_doc'=>$this->bGreat,
									'best_answer'=>$this->bBestAnswer,
									'M_doc'=>$this->bMDoc,
									'pic_exp'=>$this->bPicExp,
									'openbox_exp'=>$this->bOpenBoxExp,
									'compete_doc'=>$this->bCompete
								);

			$oDB->sInsert("principle_doc_analysis",array_keys($aAnaValues),array_values($aAnaValues));
			//insert doc_writing attr
			$aWriValues = array(	'principle_doc_no'=>$this->iBeautyDocNo,
									'communication'=>$this->sCommunication,
									'positive_summary'=>$this->sPositiveSummary,
									'negative_summary'=>$this->sNegativeSummary,
									'compete_summary'=>$this->sCompeteSummary
								);
			$oDB->sInsert("principle_doc_writings",array_keys($aWriValues),array_values($aWriValues));
			//insert doc_board attr
			foreach ($this->__aBoard as $oCBoard) {
				$aBoardValues = array(	'principle_doc_no'=>$this->iBeautyDocNo,
										'galaxy_board_no'=>$oCBoard->iBoardNo,
										'user_no'=>$oCurrentUser->iUserNo,
										'create_time'=>$sDate,
										'modify_time'=>$sDate
										);
				$oDB->sInsert("principle_doc_board",array_keys($aBoardValues),array_values($aBoardValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('principle_doc',$this->iBeautyDocNo,$_GET['func'],$_GET['action']);
			return $this->iBeautyDocNo;
		}catch (Exception $e){
			$oDB->vRollback();
			$this->__oCDoc->vDelete();
			throw new Exception('CBeautyDoc->sAddDoc:'.$e->getMessage());
		}
	}

	/*
		set & get analyse members
	*/
	public function vGetAnalysis(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM principle_doc_analysis WHERE principle_doc_no='{$this->iBeautyDocNo}'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return;
		$this->iPriceComment = $aRow['price_comment'];
		$this->iServiceComment = $aRow['service_comment'];
		$this->iMainComment = $aRow['main_comment'];
		$this->iPathComment = $aRow['path_comment'];
		$this->iElseComment = $aRow['else_comment'];

		$this->bGreat = $aRow['great_doc'];
		$this->bBestAnswer = $aRow['best_answer'];
		$this->bMDoc = $aRow['M_doc'];
		$this->bPicExp = $aRow['pic_exp'];
		$this->bOpenBoxExp = $aRow['openbox_exp'];
		$this->bCompete = $aRow['compete_doc'];
	}

	/*
		set & get writing members
	*/
	public function vGetWriting(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM principle_doc_writings WHERE principle_doc_no='{$this->iBeautyDocNo}'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return;
		$this->sCommunication = $aRow['communication'];
		$this->sPositiveSummary = $aRow['positive_summary'];
		$this->sNegativeSummary = $aRow['negative_summary'];
		$this->sCompeteSummary = $aRow['compete_summary'];
	}

	/*
		set boards, use before update or add doc
	*/
	public function vSetBoards($aBoardNos){
		$this->__aBoard = array();
		foreach ($aBoardNos as $iBoardNo) {
			if($iBoardNo==0) 
				continue;
			$this->__aBoard[] = new CBoard(array('board_no'=>$iBoardNo));
		}
	}

	public function vSetAnalysis($aRow){

		$this->iPriceComment = $aRow['price_comment'];
		$this->iServiceComment = $aRow['service_comment'];
		$this->iMainComment = $aRow['main_comment'];
		$this->iPathComment = $aRow['path_comment'];
		$this->iElseComment = $aRow['else_comment'];

		$this->bGreat = $aRow['great_doc'];
		$this->bEssence = $aRow['essence_doc'];
		$this->bBestAnswer = $aRow['best_answer'];
		$this->bMDoc = $aRow['M_doc'];
		$this->bTop = $aRow['top_doc'];
		$this->bRecommend = $aRow['recommend_doc'];
		$this->bPicExp = $aRow['pic_exp'];
		$this->bOpenBoxExp = $aRow['openbox_exp'];
		$this->bCompete = $aRow['compete_doc'];
	}

	/*
		set writing members
	*/
	public function vSetWriting($aRow){
		$this->sCommunication = $aRow['communication'];
		$this->sPositiveSummary = $aRow['positive_summary'];
		$this->sNegativeSummary = $aRow['negative_summary'];
		$this->sCompeteSummary = $aRow['compete_summary'];
	}

	/*
		close this doc, if $this->bTerminate == true, then it should not be modified anymore
	*/
	public function vClose(){	
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$this->bClosed = true;
		$aValues = array(	'user_no'=>$oCurrentUser->iUserNo,
							'modify_time'=>date("Y-m-d H:i:s"),
							'closed'=>$this->bClosed
							);
		$oDB->sUpdate("principle_doc",array_keys($aValues),array_values($aValues),"principle_doc_no='{$this->iBeautyDocNo}'");
		$oCurrentUser->vAddUserLog('principle_doc',$this->iBeautyDocNo,$_GET['func'],$_GET['action']);
		return;
	}
}
?>