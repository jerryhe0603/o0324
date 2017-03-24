<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CBoard.php');

class CSite extends CGalaxyClass
{
	static public $aSiteType = array();

	private $iSiteNo;
	public $sTitle;
	public $sUrl;
	public $sHost;
	public $iTypeNo;
	
	public $bSiteProvideMail;
	public $sSiteMailDomain;
	public $iMemberSiteNo;
	public $bPhoneCertification;
	public $bEmailCertification;
	public $bRegisterVerification;
	public $bLoginVerification;
	public $bLoginView;
	public $bArticleOrder;
	public $bInviteCode;
	public $sMailAccountLimit;
	public $iProfilePhoto;
	public $iHtmlCodeSupport;
	public $iBodyEmbedImage;
	public $iBodyEmbedVedio;
	public $iBodyEmbedLink;
	public $bPostEdit;
	public $bPostDel;
	public $bReplyEdit;
	public $bReplyDel;
	public $bReplyTop;
	public $iSignFunction;
	public $iSignCodeSupport;
	public $bSignImage;
	public $iInviteFriend;

	private $__aCBoard;

	//database setting
	static protected $sDBName = 'GENESIS';

	//instance pool
	static private $aInstancePool = array();

	static public function oGetSite($iSiteNo){
		$oDB = self::oDB(self::$sDBName);
		//if already queryed
		if(!is_null(self::$aInstancePool[$iSiteNo]))
			return self::$aInstancePool[$iSiteNo];
		$sSql = "SELECT * FROM galaxy_site WHERE site_no = '$iSiteNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCSite = new CSite($aRow);
		self::$aInstancePool[$iSiteNo] = $oCSite;
		return self::$aInstancePool[$iSiteNo];
	}

	static public function aAllSite($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_site";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllSite = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['site_no']])){
				self::$aInstancePool[$aRow['site_no']] = new CSite($aRow);
			}
			$aAllSite[] = self::$aInstancePool[$aRow['site_no']];
		}
		return $aAllSite;
	}

	public static function aEmailSiteNos(){
		$aSites = self::aAllSite("`site_provide_mail`='1'");
		$aSiteNos = array();
		foreach ($aSites as $oSite) {
			$aSiteNos[] = $oSite->iSiteNo;
		}
		return $aSiteNos;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CSite: __construct failed, require an array");
		//initialize vital member
		$this->iSiteNo = $multiData['site_no'];
		$this->sTitle = $multiData['site_title'];
		$this->sUrl = $multiData['site_url'];
		$this->sHost = $multiData['site_host'];
		$this->iTypeNo = $multiData['site_type_no'];
		
		$this->bSiteProvideMail = $multiData['site_provide_mail'];
		$this->sSiteMailDomain = $multiData['site_mail_domain'];
		$this->iMemberSiteNo = $multiData['member_site_no'];
		$this->bPhoneCertification = $multiData['phone_certification'];
		$this->bEmailCertification = $multiData['email_certification'];
		$this->bRegisterVerification = $multiData['register_verification'];
		$this->bLoginVerification = $multiData['login_verification'];
		$this->bLoginView = $multiData['login_view'];
		$this->bArticleOrder = $multiData['article_order'];
		$this->bInviteCode = $multiData['invite_code'];
		$this->sMailAccountLimit = $multiData['mail_account_limit'];
		$this->iProfilePhoto = $multiData['profile_photo'];
		$this->iHtmlCodeSupport = $multiData['html_code_support'];
		$this->iBodyEmbedImage = $multiData['body_embed_image'];
		$this->iBodyEmbedVedio = $multiData['body_embed_vedio'];
		$this->iBodyEmbedLink = $multiData['body_embed_link'];
		$this->bPostEdit = $multiData['post_edit'];
		$this->bPostDel = $multiData['post_del'];
		$this->bReplyEdit = $multiData['reply_edit'];
		$this->bReplyDel = $multiData['reply_del'];
		$this->bReplyTop = $multiData['reply_top'];
		$this->iSignFunction = $multiData['sign_function'];
		$this->iSignCodeSupport = $multiData['sign_code_support'];
		$this->bSignImage = $multiData['sign_image'];
		$this->iInviteFriend = $multiData['invite_friend'];

		//galaxy class memeber
		$this->bStatus = $multiData['site_status'];
		$this->sCreateTime = $multiData['site_createtime'];
		$this->sModifiedTime = $multiData['site_modifiedtime'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }


    /*
    	get site type in string
    */
    public function sType(){
    	$oDB = self::oDB(self::$sDBName);
    	if(empty(self::$aSiteType)){
    		$sSql = "SELECT * FROM galaxy_site_type";
    		$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				self::$aSiteType[$aRow['site_type_no']] = $aRow['site_type_name'];
			}
    	}
    	return self::$aSiteType[$this->iTypeNo];
    }

	public function aBoard(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCBoard)){
			$this->__aCBoard = CBoard::aAllBoard("`site_no`={$this->iSiteNo}");
		}
		return $this->__aCBoard;
	}

	public function iAddSite(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'site_title'=>$this->sTitle,
							'site_url'=>$this->sUrl,
							'site_host'=>$this->sHost,
							'site_type_no'=>$this->iTypeNo,
							'site_provide_mail'=>$this->bSiteProvideMail,
							'site_mail_domain'=>$this->sSiteMailDomain,
							'member_site_no'=>$this->iMemberSiteNo,
							'phone_certification'=>$this->bPhoneCertification,
							'email_certification'=>$this->bEmailCertification,
							'register_verification'=>$this->bRegisterVerification,
							'login_verification'=>$this->bLoginVerification,
							'login_view'=>$this->bLoginView,
							'article_order'=>$this->bArticleOrder,
							'invite_code'=>$this->bInviteCode,
							'mail_account_limit'=>$this->sMailAccountLimit,
							'profile_photo'=>$this->iProfilePhoto,
							'html_code_support'=>$this->iHtmlCodeSupport,
							'body_embed_image'=>$this->iBodyEmbedImage,
							'body_embed_vedio'=>$this->iBodyEmbedVedio,
							'body_embed_link'=>$this->iBodyEmbedLink,
							'post_edit'=>$this->bPostEdit,
							'post_del'=>$this->bPostDel,
							'reply_edit'=>$this->bReplyEdit,
							'reply_del'=>$this->bReplyDel,
							'reply_top'=>$this->bReplyTop,
							'sign_function'=>$this->iSignFunction,
							'sign_code_support'=>$this->iSignCodeSupport,
							'sign_image'=>$this->bSignImage,
							'invite_friend'=>$this->iInviteFriend,
							'user_no'=>$oCurrentUser->iUserNo,
							'site_status'=>$this->bStatus,
							'site_createtime'=>date("Y-m-d H:i:s"),
							'site_modifiedtime'=>date("Y-m-d H:i:s"),
							);
		try{
			$oDB->sInsert('galaxy_site',array_keys($aValues),array_values($aValues));
			$this->iSiteNo = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog("galaxy_site",$this->iSiteNo,'site','add');
			return $this->iSiteNo;
		}catch (Exception $e){
			throw new Exception("CSite->iAddSite: ".$e->getMessage());
		}
	}

	public function vUpdateSite(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'site_title'=>$this->sTitle,
							'site_url'=>$this->sUrl,
							'site_host'=>$this->sHost,
							'site_type_no'=>$this->iTypeNo,
							'site_provide_mail'=>$this->bSiteProvideMail,
							'site_mail_domain'=>$this->sSiteMailDomain,
							'member_site_no'=>$this->iMemberSiteNo,
							'phone_certification'=>$this->bPhoneCertification,
							'email_certification'=>$this->bEmailCertification,
							'register_verification'=>$this->bRegisterVerification,
							'login_verification'=>$this->bLoginVerification,
							'login_view'=>$this->bLoginView,
							'article_order'=>$this->bArticleOrder,
							'invite_code'=>$this->bInviteCode,
							'mail_account_limit'=>$this->sMailAccountLimit,
							'profile_photo'=>$this->iProfilePhoto,
							'html_code_support'=>$this->iHtmlCodeSupport,
							'body_embed_image'=>$this->iBodyEmbedImage,
							'body_embed_vedio'=>$this->iBodyEmbedVedio,
							'body_embed_link'=>$this->iBodyEmbedLink,
							'post_edit'=>$this->bPostEdit,
							'post_del'=>$this->bPostDel,
							'reply_edit'=>$this->bReplyEdit,
							'reply_del'=>$this->bReplyDel,
							'reply_top'=>$this->bReplyTop,
							'sign_function'=>$this->iSignFunction,
							'sign_code_support'=>$this->iSignCodeSupport,
							'sign_image'=>$this->bSignImage,
							'invite_friend'=>$this->iInviteFriend,
							'site_status'=>$this->bStatus,
							'site_modifiedtime'=>date("Y-m-d H:i:s"),
							);
		try{
			$oDB->sUpdate("galaxy_site", array_keys($aValues), array_values($aValues), "`site_no`={$this->iSiteNo}");
			$oCurrentUser->vAddUserLog("galaxy_site",$this->iSiteNo,'site','update');
		}catch (Exception $e){
			throw new Exception("CSite->vUpdateSite: ".$e->getMessage());
		}
	}
}
?>