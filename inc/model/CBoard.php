<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CSite.php');

class CBoard extends CGalaxyClass
{
	private $iBoardNo;
	public $sTitle;
	public $sUrl;

	public $iSiteNo;
	private $__oCSite;

	public $sMemo;

	public $bBrowseRestrict;
	public $iLevel;
	public $bUpdateTop;
	public $sRuleUrl;
	public $iRuleRestrict;
	public $bFormatRestrict;
	public $iCopy;
	public $bMarkForm;
	public $bPostMax;
	public $bAttachPicture;
	public $bAttachVideo;
	public $bAttachHref;
	public $bConnectOutside;
	public $bFileDownload;
	public $bPostAuthRestrict;
	public $bReplyAuthRestrict;
	public $bCrackStatus;

	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static public $aInstancePool = array();

	static public function oGetBoard($iBoardNo){
		$oDB = self::oDB(self::$sDBName);
		//if already queryed
		if(!is_null(self::$aInstancePool[$iBoardNo]))
			return self::$aInstancePool[$iBoardNo];

		$sSql = "SELECT * FROM galaxy_board WHERE board_no = '$iBoardNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCBoard = new CBoard($aRow);
		self::$aInstancePool[$iBoardNo] = $oCBoard;

		return self::$aInstancePool[$iBoardNo];
	}

	static public function aAllBoard($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_board";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllBoard = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['board_no']])){
				self::$aInstancePool[$aRow['board_no']] = new CBoard($aRow);
			}
			$aAllBoard[] = self::$aInstancePool[$aRow['board_no']];
		}
		return $aAllBoard;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CBoard: __construct failed, require an array");
		//initialize vital member
		$this->iBoardNo = $multiData['board_no'];
		$this->iSiteNo = $multiData['site_no'];
		$this->sTitle = $multiData['board_title'];
		$this->sUrl = $multiData['board_url'];
		$this->sMemo = $multiData['memo'];

		$this->bBrowseRestrict = $multiData['browse_restrict'];
		$this->iLevel = $multiData['level'];
		$this->bUpdateTop = $multiData['update_top'];
		$this->sRuleUrl = $multiData['rule_url'];
		$this->iRuleRestrict = $multiData['rule_restrict'];
		$this->bFormatRestrict = $multiData['format_restrict'];
		$this->iCopy = $multiData['copy'];
		$this->bMarkForm = $multiData['mark_form'];
		$this->bPostMax = $multiData['post_max'];
		$this->bAttachPicture = $multiData['attach_picture'];
		$this->bAttachVideo = $multiData['attach_video'];
		$this->bAttachHref = $multiData['attach_href'];
		$this->bConnectOutside = $multiData['connect_outside'];
		$this->bFileDownload = $multiData['file_download'];
		$this->bPostAuthRestrict = $multiData['post_auth_restrict'];
		$this->bReplyAuthRestrict = $multiData['reply_auth_restrict'];
		$this->bCrackStatus = $multiData['board_crack_status'];

		//galaxy class memeber
		$this->bStatus = $multiData['board_status'];
		$this->sCreateTime = $multiData['board_createtime'];
		$this->sModifiedTime = $multiData['board_modifiedtime'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }

    //return full title with site_title
    public function sFullTitle(){
    	$sFullTitle = $this->oSite()->sTitle;
    	$sFullTitle .= ' : ';
    	$sFullTitle .= $this->sTitle;
    	return $sFullTitle;
    }

    /*
    	set & get CSite
    */
    public function oSite(){
    	if(is_null($this->__oCSite)){
            $this->__oCSite = CSite::oGetSite($this->iSiteNo);
        }
        return $this->__oCSite;
    }

    public function iAddBoard(){
    	$oDB = self::oDB(self::$sDBName);
    	$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'board_title'=>$this->sTitle,
							'site_no'=>$this->iSiteNo,
							'board_url'=>$this->sUrl,
							'memo'=>$this->sMemo,
							'browse_restrict'=>$this->bBrowseRestrict,
							'level'=>$this->iLevel,
							'update_top'=>$this->bUpdateTop,
							'rule_url'=>$this->sRuleUrl,
							'rule_restrict'=>$this->iRuleRestrict,
							'format_restrict'=>$this->bFormatRestrict,
							'copy'=>$this->iCopy,
							'mark_form'=>$this->bMarkForm,
							'post_max'=>$this->bPostMax,
							'attach_picture'=>$this->bAttachPicture,
							'attach_video'=>$this->bAttachVideo,
							'attach_href'=>$this->bAttachHref,
							'connect_outside'=>$this->bConnectOutside,
							'file_download'=>$this->bFileDownload,
							'post_auth_restrict'=>$this->bPostAuthRestrict,
							'reply_auth_restrict'=>$this->bReplyAuthRestrict,
							'board_crack_status'=>$this->bCrackStatus,
							'user_no'=>$oCurrentUser->iUserNo,
							'board_status'=>$this->bStatus,
							'board_createtime'=>date("Y-m-d H:i:s"),
							'board_modifiedtime'=>date("Y-m-d H:i:s"),
							);
		try{
			$oDB->sInsert('galaxy_board',array_keys($aValues),array_values($aValues));
			$this->iBoardNo = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog("galaxy_board",$this->iBoardNo,'board','add');
			return $this->iBoardNo;
		}catch (Exception $e){
			throw new Exception("CBoard->iAddBoard: ".$e->getMessage());
		}
	}

	public function vUpdateBoard(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'board_title'=>$this->sTitle,
							'site_no'=>$this->iSiteNo,
							'board_url'=>$this->sUrl,
							'memo'=>$this->sMemo,
							'browse_restrict'=>$this->bBrowseRestrict,
							'level'=>$this->iLevel,
							'update_top'=>$this->bUpdateTop,
							'rule_url'=>$this->sRuleUrl,
							'rule_restrict'=>$this->iRuleRestrict,
							'format_restrict'=>$this->bFormatRestrict,
							'copy'=>$this->iCopy,
							'mark_form'=>$this->bMarkForm,
							'post_max'=>$this->bPostMax,
							'attach_picture'=>$this->bAttachPicture,
							'attach_video'=>$this->bAttachVideo,
							'attach_href'=>$this->bAttachHref,
							'connect_outside'=>$this->bConnectOutside,
							'file_download'=>$this->bFileDownload,
							'post_auth_restrict'=>$this->bPostAuthRestrict,
							'reply_auth_restrict'=>$this->bReplyAuthRestrict,
							'board_crack_status'=>$this->bCrackStatus,
							'board_status'=>$this->bStatus,
							'board_modifiedtime'=>date("Y-m-d H:i:s"),
							);
		try{
			$oDB->sUpdate("galaxy_board", array_keys($aValues), array_values($aValues), "`board_no`={$this->iBoardNo}");
			$oCurrentUser->vAddUserLog("galaxy_board",$this->iBoardNo,'board','edit');
		}catch (Exception $e){
			throw new Exception("CBoard->vUpdateBoard: ".$e->getMessage());
		}
	}
}
?>