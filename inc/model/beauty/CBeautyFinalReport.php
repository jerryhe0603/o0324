<?php
include_once('../inc/model/CGalaxyClass.php');

include_once('../inc/model/beauty/CBeautyProject.php');
include_once('../inc/model/beauty/CBeautyDoc.php');

class CBeautyFinalReport extends CGalaxyClass
{
	private $sProjectUuid;
	public $iProjectTotalPost;
	public $iProjectGalaxyPost;
	public $iProjectResponse;
	public $iResponseRateDivisor;
	public $iAnalysisPositive;
	public $iAnalysisNegative;
	public $iAnalysisComplex;
	public $iAnalysisGeneral;
	public $iAnalysisSum;
	public $aDocPostGoal;
	public $aWritingPositive;
	public $aWritingNegative;
	public $aPost;
	public $aNickName;
	public $aKeyWord;
	public $aHot;
	public $aBoardChart;
	public $aPostType;



	//database setting
	static protected $sDBName = 'SITE';

	//instance pool
	static public $aInstancePool = array();

	/*
		get CBeautyFinalReport by certain project_no
	*/
	static public function oGetReport($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$sProjectUuid]))
			return self::$aInstancePool[$sProjectUuid];

		//query from beauty DB
		$sSql = "SELECT * FROM project_final_report WHERE project_no='$sProjectUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oReport = new CBeautyFinalReport($aRow);
		self::$aInstancePool[$sProjectUuid] = $oReport;

		return $oReport;
	}

	/*
		get all beauty final report in an array
		if $sSearchSql is given, query only match reports
	*/
	static public function aAllReport($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllReport = array();
		$sSql = "SELECT * FROM `project_final_report`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['project_no']])){
				self::$aInstancePool[$aRow['project_no']] = new CBeautyFinalReport($aRow);
			}
			$aAllReport[] = self::$aInstancePool[$aRow['project_no']];
		}
		return $aAllReport;
	}

	/*
		get count of final report which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(project_no) as total FROM project_final_report";
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
		calculate a project's final report and insert to database
	*/
	static public function oCalculateReport($sProjectUuid){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		//get project
		$oProject = CBeautyProject::oGetProject($sProjectUuid);
		if(is_null($oProject))
			throw new Exception('CBeautyBeautyFinalReport::vCalculateReport: project not found');

		//calculate start
		$iDocCount=0;
		$aBeautyDocInfo=array();
		$aBeautyDocComment=array();
		$aBeautyDocAccumulate=array();
		$aBeautyDocPostType=array();
		$aBeautyDoc=CBeautyDoc:: aAllDoc("`project_no`='$sProjectUuid' AND `post_type_no`!='9' AND `parent_doc_no` ='0' AND `comment_check`!='0' ");
		$aUniqueNickname=array();
		$aTotalDoc = array();
		foreach($aBeautyDoc as $oBeautyDoc){
			$bHasResult=true;
			if(!$bHasResult) continue;
			$aTotalDoc[] =$oBeautyDoc;
			/*
			// set title
			$aBeautyDocInfo[$iDocCount]['title']=$oBeautyDoc->sName;				
			//init site board title
			$aSiteBoardTitle=array();
			$aBoard=$oBeautyDoc->aBoard();
			foreach($aBoard as $oBoard){
				$aSiteBoardTitle[]=$oBoard->oSite()->sTitle.' '.$oBoard->sTitle;
			}
			$aBeautyDocInfo[$iDocCount]['site_board']=$aSiteBoardTitle;
			*/
			$aBeautyDocInfo[$iDocCount]['doc_no']=$oBeautyDoc->iBeautyDocNo;
			$oBeautyDoc->vGetAnalysis();
			// put parent doc and child doc into a single array
			$aBeautyDocParentAndChild=array();
			$aBeautyDocParentAndChild[]=$oBeautyDoc;
			$aChildDoc=$oBeautyDoc->aChildRecursive();
			foreach($aChildDoc as $oChildDoc){
				$aBeautyDocParentAndChild[]=$oChildDoc;
				$aTotalDoc[] =$oChildDoc;
			}
			//init unique nickname for this doc string
			$aUniqueNickname[$iDocCount]=array();
			//get and set positve comment,  negative comment, and unique nickname
			$aBeautyDocComment[$iDocCount]['positive_comment']=0;
			$aBeautyDocComment[$iDocCount]['negative_comment']=0;
			$aBeautyDocComment[$iDocCount]['general_comment']=0;
			$aBeautyDocComment[$iDocCount]['complex_comment']=0;
			$aBeautyDocAccumulate[$iDocCount]['response']=0;
			$aBeautyDocAccumulate[$iDocCount]['galaxy']=0;
			$aBeautyDocAccumulate[$iDocCount]['positive_comment']=0;
			foreach($aBeautyDocParentAndChild as $oBeautyDoc){
				$aComment=array('iPriceComment', 'iServiceComment', 'iMainComment', 'iElseComment');
				foreach($aComment as $sComment){
					switch($oBeautyDoc->$sComment){
						case 8:
						case 1:
							$aBeautyDocComment[$iDocCount]['positive_comment']++;
							$aBeautyDocAccumulate[$iDocCount]['positive_comment']++;
							break;
						case 9:
						case 4:
						case 3:
							$aBeautyDocComment[$iDocCount]['negative_comment']++;
							break;
						case 6:
						case 10:
							$aBeautyDocComment[$iDocCount]['general_comment']++;
							break;
						case 5:
							$aBeautyDocComment[$iDocCount]['complex_comment']++;
							break;
						default: break;
					}
				}
				// skip attachment
				//if(!empty($oBeautyDoc->oCDoc()->sFileFile)) continue;	//use comment_check to filter out
				if(!$oBeautyDoc->bGalaxy){
					// insert nickname
					if(!$oBeautyDoc->bGalaxy){
						$sDocNickname=$oBeautyDoc->oCDoc()->aDocElement()[32]->sValue;
						if(!empty($sDocNickname)&&
							!in_array($sDocNickname, $aUniqueNickname[$iDocCount])){
							$aUniqueNickname[$iDocCount][]=$sDocNickname;
						}
					}
					$aBeautyDocAccumulate[$iDocCount]['response']++;
				}else{
					$aBeautyDocAccumulate[$iDocCount]['galaxy']++;
					$oPostType=$oBeautyDoc->oPostType();
					if(!empty($oPostType)){
						$sName=$oPostType->sName;
						$fWeight=$oPostType->fWeight;
						if(empty($aBeautyDocPostType[$sName]['weight']))
							$aBeautyDocPostType[$sName]['weight']=$fWeight;
						$aBeautyDocPostType[$sName]['count']++;
						$aBeautyDocPostType['sum']+=$fWeight;
					}
				}
				$aBeautyDocAccumulate[$iDocCount]['sum']=$aBeautyDocAccumulate[$iDocCount]['galaxy']+$aBeautyDocAccumulate[$iDocCount]['response'];
				/*
				foreach($aComment as $sComment){
					switch($oBeautyDoc->$sComment){
						case 8:
						case 1:
							$aBeautyDocAccumulate[$iDocCount]['positive_comment']++;
							break;
						default: break;
					}
				}
				*/
			}
			$iDocCount++;
		}
		// count number of unique nickname
		$aUniqueNicknameCount=array();
		foreach($aUniqueNickname as $key => $value){
			$aUniqueNicknameCount[$key]=count($value);
		}
		foreach($aBeautyDocPostType as $key =>$value){
			if($key!='sum'){
				if(empty($aBeautyDocPostType[$key]['weight'])) $aBeautyDocPostType[$key]['weight']=0;
				if(empty($aBeautyDocPostType[$key]['count'])) $aBeautyDocPostType[$key]['count']=0;
			}else{
				if(empty($aBeautyDocPostType['sum'])) $aBeautyDocPostType['sum']=0;
			}
		}					
		$aSiteBoardTitle=array();
		$aTotalDocPostByBoard=array();
		//count number of galaxy doc posts by board
		foreach($aTotalDoc as $oBeautyDoc){	
			// draft for schedule and queue test is as above in this for loop
			//pseudo loop assuming the doc is posted to all of its boards
			$aBoard=$oBeautyDoc->aBoard();
			foreach($aBoard as $oBoard){
				$sSiteBoardTitle=$oBoard->oSite()->sTitle.' '.$oBoard->sTitle;
				if(!in_array($sSiteBoardTitle, $aSiteBoardTitle))
					$aSiteBoardTitle[]=$sSiteBoardTitle;
				$iBoardIndex=array_search($sSiteBoardTitle, $aSiteBoardTitle);
				$aTotalDocPostByBoard[$iBoardIndex]++;
			}				
		}			
		// project doc post: galaxy, response, and sum 
		//$aProjectDocPost=array();
		$iProjectGalaxyPost=0;
		$iProjectTotalPost=0;
		$iProjectGalayPost=0;
		$iResponseRateDivisor=0;
		foreach($aTotalDoc as $oBeautyDoc){
			//if(!empty($oBeautyDoc->oCDoc()->sFileFile)) continue;	//use comment_check to filter out
			$aBoard=$oBeautyDoc->aBoard();
			foreach($aBoard as $oBoard){
				if($oBeautyDoc->bGalaxy){
					$iProjectGalaxyPost++;
					$iProjectTotalPost++;
					$bIsDivisor=false;
					$oPostType=$oBeautyDoc->oPostType();
					if(!empty($oPostType)){
						if($oPostType->sName=="開題文"||$oPostType->sName=="主回文"){
							$bIsDivisor=true;
						}
					}
					if($bIsDivisor)$iResponseRateDivisor++;
				}else{
					$iProjectGalayPost++;
					$iProjectTotalPost++;
				}
			}
		}
		//set doc post goal
		$aDocPostGoal=array();
		$aDocPostGoal['galaxy']=$oProject->iPostCount;
		$aDocPostGoal['response']=$oProject->iResponseCount;
		$aDocPostGoal['sum']=$aDocPostGoal['galaxy']+$aDocPostGoal['response'];
		
		//get Hot Posts' title and board
		$aHotParentDoc=array();
		$aHotParentDocNo=array();
		$aHotGalaxyDocPost=array();
		foreach($aTotalDoc as $oBeautyDoc){
			//if(!empty($oBeautyDoc->oCDoc()->sFileFile)) continue;
			if(!$oBeautyDoc->bGalaxy) {
				$iDocNo=$oBeautyDoc->iParentDocNo==0?$oBeautyDoc->iBeautyDocNo:$oBeautyDoc->iParentDocNo;
				$aHotParentDocNo[$iDocNo]++;
			}
			if($oBeautyDoc->iParentDocNo==0)
				$aHotParentDoc[$oBeautyDoc->iBeautyDocNo] = $oBeautyDoc;
		}
		arsort($aHotParentDocNo);
		$iDocCount=1;
		foreach($aHotParentDocNo as $iHotParentDocNo => $count){
			$oBeautyDoc = $aHotParentDoc[$iHotParentDocNo];		//CBeautyDoc::oGetDoc($iHotParentDocNo);
			$aHotGalaxyDocPost[$iDocCount]=$oBeautyDoc->sName.'(';
			$aBoard=$oBeautyDoc->aBoard();
			foreach($aBoard as $oBoard){
				$aHotGalaxyDocPost[$iDocCount].=$oBoard->oSite()->sTitle.' '.$oBoard->sTitle;
			}
			$aHotGalaxyDocPost[$iDocCount].=')';
			if($iDocCount==3) break;
			$iDocCount++;
		}				
		//$aProjectDocPostComment=array();
		$aProjectDocPostWriting=array();
		$iAnalysisPositive=0;
		$iAnalysisNegative=0;
		$iAnalysisGeneral=0;
		$iAnalysisComplex=0;
		foreach($aTotalDoc as $oBeautyDoc){
			$oBeautyDoc->vGetAnalysis();
			$aComment=array('iPriceComment', 'iServiceComment', 'iMainComment', 'iElseComment');
			foreach($aComment as $sComment){
				switch($oBeautyDoc->$sComment){
					case 8:
					case 1:
						$iAnalysisPositive++;
						break;
					case 9:
					case 4:
					case 3:
						$iAnalysisNegative++;
						break;
					case 6:
					case 10:
						$iAnalysisGeneral++;
						break;
					case 5:
						$iAnalysisComplex++;
						break;
					default: break;
				}
			}
			$oBeautyDoc->vGetWriting();
			$sPositiveSummary=$oBeautyDoc->sPositiveSummary;
			if(!empty($sPositiveSummary))
				$aProjectDocPostWriting['positive'][]=$sPositiveSummary;
			$sNegativeSummary=$oBeautyDoc->sNegativeSummary;
			if(!empty($sNegativeSummary))
				$aProjectDocPostWriting['negative'][]=$sNegativeSummary;
		}
		// get project keyword and initi count array
		$aKeyword=$oProject->aKeyword();
		if(!empty($aKeyword)){
			$aKeywordCount=array();
			foreach($aTotalDoc as $oBeautyDoc){
				//get galaxy doc content
				$sContent=$oBeautyDoc->oCDoc()->aDocElement()[5]->sValue;
				$iIndex=0;
				if(empty($sContent)) continue;
				//count keyword in the content
				foreach($aKeyword as $sKeyword){
					$aKeywordCount[$iIndex]+=substr_count( $sContent, $sKeyword);
					$iIndex++;
				}
			}
		}
		$aValues=array();
		$aValues['project_no']=$sProjectUuid;
		$aValues['project_total_post']=$iProjectTotalPost;
		$aValues['project_galaxy_post']=$iProjectGalaxyPost;
		$aValues['project_response']=$iProjectGalayPost;
		$aValues['response_rate_divisor']=$iResponseRateDivisor;
		$aValues['analysis_positive']=$iAnalysisPositive;
		$aValues['analysis_negative']=$iAnalysisNegative;
		$aValues['analysis_complex']=$iAnalysisComplex;
		$aValues['analysis_general']=$iAnalysisGeneral;

		$aValues['doc_post_goal']=json_encode($aDocPostGoal);
		if(!empty($aProjectDocPostWriting['positive'])){
			$aValues['writing_positive']=array();
			foreach($aProjectDocPostWriting['positive'] as $sWriting){
				$aValues['writing_positive'][]=$sWriting;
			}
			$aValues['writing_positive']=json_encode($aValues['writing_positive']);
		}			
		if(!empty($aProjectDocPostWriting['negative'])){
			$aValues['writing_negative']=array();
			foreach($aProjectDocPostWriting['negative'] as $sWriting){
				$aValues['writing_negative'][]=$sWriting;
			}
			$aValues['writing_negative']=json_encode($aValues['writing_negative']);
		}			
		$aValues['post']=array();
		foreach($aBeautyDocComment as $key => $aComment){
			$aValues['post'][$key]['doc_no']=$aBeautyDocInfo[$key]['doc_no'];
			$aValues['post'][$key]['galaxy']=$aBeautyDocAccumulate[$key]['galaxy'];
			$aValues['post'][$key]['response']=$aBeautyDocAccumulate[$key]['response'];
			$aValues['post'][$key]['sum']=$aBeautyDocAccumulate[$key]['sum'];
			$aValues['post'][$key]['accumulate_positive_comment']=$aBeautyDocAccumulate[$key]['positive_comment'];
			$aValues['post'][$key]['positive_comment']=$aComment['positive_comment'];
			$aValues['post'][$key]['negative_comment']=$aComment['negative_comment'];
			$aValues['post'][$key]['general_comment']=$aComment['general_comment'];
			$aValues['post'][$key]['complex_comment']=$aComment['complex_comment'];
		}
		$aValues['post']=json_encode($aValues['post']);
		$aValues['nickname']=array();
		foreach($aUniqueNicknameCount as $key => $iCount){
			$aValues['nickname'][$key]['doc_no']=$aBeautyDocInfo[$key]['doc_no'];
			$aValues['nickname'][$key]['count']=$iCount;
		}
		$aValues['nickname']=json_encode($aValues['nickname']);
		if(!empty($aKeyword)){
			$aValues['keyword']=array();
			foreach($aKeywordCount as $key=> $iCount){
				$aValues['keyword'][$key]['keyword']=$aKeyword[$key];
				$aValues['keyword'][$key]['count']=$iCount;
			}
		}
		$aValues['keyword']=json_encode($aValues['keyword']);
		$aValues['hot']=array();
		foreach($aHotGalaxyDocPost as $key => $post){
			$aValues['hot'][$key]=$post;
		}
		$aValues['hot']=json_encode($aValues['hot']);
		$aValues['board_chart']=array();
		foreach($aTotalDocPostByBoard as $key => $count){
			$aValues['board_chart'][$key]['title']=$aSiteBoardTitle[$key];
			$aValues['board_chart'][$key]['count']=$count;
		}
		$aValues['board_chart']=json_encode($aValues['board_chart']);
		$aValues['post_type']=array();
		$aValues['post_type']['sum']=$aBeautyDocPostType['sum'];
		foreach($aBeautyDocPostType as $key => $aPostType){
			if($key=='sum') continue;
				$aValues['post_type'][$key]['name']=$key;
				$aValues['post_type'][$key]['count']=$aPostType['count'];
				$aValues['post_type'][$key]['weight']=$aPostType['weight'];
		}
		$aValues['post_type']=json_encode($aValues['post_type']);

		$iFinalCount = self::iGetCount("`project_no` = '$sProjectUuid'");
		if($iFinalCount===0){
			$aValues['create_time'] = date("Y-m-d H:i:s");
		}
		$aValues['modify_time'] = date("Y-m-d H:i:s");

		$oReport=new CBeautyFinalReport($aValues);

		foreach($aValues as $index => $dataset){
			$aValues[$index]=mysql_real_escape_string($aValues[$index]);
		}

		try{
			if($iFinalCount===0){
				//final report data not exist, insert
				$oDB->sInsert("project_final_report",array_keys($aValues),array_values($aValues));	
				
			}else{
				//final report data already exist, update
				$oDB->sUpdate("project_final_report",array_keys($aValues),array_values($aValues),"`project_no`='$sProjectUuid'");

			}

			//if call by controller, add log
			if(!empty($_GET['func']) || !empty($_GET['action']))
				$oCurrentUser->vAddUserLog('project_final_report',$sProjectUuid,$_GET['func'],$_GET['action']);

		}catch (Exception $e){
			throw new Exception('CBeautyBeautyFinalReport::vCalculateReport:'.$e->getMessage());
		}

		return  $oReport;
		
	}


	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CBeautyBeautyFinalReport: __construct failed, require an array");

		$this->sProjectUuid = $multiData['project_no'];
		$this->iProjectTotalPost=$multiData['project_total_post'];
		$this->iProjectGalaxyPost=$multiData['project_galaxy_post'];
		$this->iProjectResponse=$multiData['project_response'];
		$this->iResponseRateDivisor=$multiData['response_rate_divisor'];
		$this->iAnalysisPositive=$multiData['analysis_positive'];
		$this->iAnalysisNegative=$multiData['analysis_negative'];
		$this->iAnalysisComplex=$multiData['analysis_complex'];
		$this->iAnalysisGeneral=$multiData['analysis_general'];
		
		$this->iAnalysisSum=$multiData['analysis_positive'];
		$this->iAnalysisSum+=$multiData['analysis_negative'];
		$this->iAnalysisSum+=$multiData['analysis_complex'];
		$this->iAnalysisSum+=$multiData['analysis_general'];

		$this->aDocPostGoal=json_decode($multiData['doc_post_goal'], true);
		$this->aWritingPositive=json_decode($multiData['writing_positive'], true);
		$this->aWritingNegative=json_decode($multiData['writing_negative'], true);

		$aPost=json_decode($multiData['post'], true);
		foreach($aPost as $key => $post){
			$aPost[$key]['doc_object']=CBeautyDoc::oGetDoc($post['doc_no']);
			unset($aPost[$key]['doc_no']);
		}
		$this->aPost=$aPost;

		$aNickName=json_decode($multiData['nickname'] ,true);
		foreach($aNickName as $key => $nickname){
			$aNickName[$key]['doc_object']=CBeautyDoc::oGetDoc($nickname['doc_no']);
			unset($aNickName[$key]['doc_no']);
		}
		$this->aNickName=$aNickName;
		
		$this->aKeyWord=json_decode($multiData['keyword'], true);
		$this->aHot=json_decode($multiData['hot'], true);
		$this->aBoardChart=json_decode($multiData['board_chart'], true);
		$this->aPostType=json_decode($multiData['post_type'], true);

		//galaxy class member
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['createdtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];

	}

	public function __get($varName)
    {
        return $this->$varName;
    }
/*
	public function vAdd(){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			$aValues = array(	'project_no'=>$this->sProjectUuid,
								'status'=>$this->bStatus,
								'createdtime'=>date("Y-m-d H:i:s"),
								'modifiedtime'=>date("Y-m-d H:i:s")
								);
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('project_final_report',$this->sProjectUuid,$_GET['func'],$_GET['action']);
			return $this->iIntervalNo;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CBeautyInterval->vAdd: ".$e->getMessage());
		}

	}
*/

	public function vUpdate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$aValues = array(	'modifiedtime'=>date("Y-m-d H:i:s")
								);
			$oDB->sUpdate("project_final_report", array_keys($aValues), array_values($aValues),"`project_no`='{$this->iIntervalNo}'");
			$oCurrentUser->vAddUserLog('project_final_report',$this->sProjectUuid,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			throw new Exception("CBeautyInterval->vUpdate: ".$e->getMessage());
		}
	}

}
?>