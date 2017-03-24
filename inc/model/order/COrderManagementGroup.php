<?php

include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CUserFront.php');
include_once("../inc/nusoap/lib/nusoap.php");

Class COrderManagementGroup extends CGalaxyClass{

	private $iManagementNo;
	private $__aUser;
	static protected $sDBName = 'ORDER';

	public function __get($varName){
		   return $this->$varName;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("COrderManagementGroup: __construct failed: require an array.");

		$this->iManagementNo=$multiData['management_no'];
	}

	/*
		return oOrderManagementGroup if any user can be found under the management
		or return null
	*/
	public static function oGetGroup($iManagementNo){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_management_user_rel` WHERE `management_no` = $iManagementNo group by `management_no`";
		$iDbq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$COrderManagementGroup= new COrderManagementGroup($aRow);

		$aManagement=self::aGetManagement();
		foreach($aManagement as $aManagementData){
			if($COrderManagementGroup->iManagementNo==$aManagementData['mm_id']){
				$COrderManagementGroup->sName=$aManagementData['mm_name'];
				break;
			}
		}

		return $COrderManagementGroup;
	}

	//get all order management group
	public static function aAllGroup($sSearchSql='', $sPostfix=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_management_user_rel` group by `management_no`";
		if($sSearchSql!==''){
			$sSql.=" WHERE $sSearchSql ";
		}
		if($sPostfix!==''){
			$sSql.=" $sPostfix ";
		}
		$iDbq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDbq)){
			$aAllGroup[]=new COrderManagementGroup($aRow);
		}
		return $aAllGroup;
	}

	//get all order management group by user
	public static function aAllManagementByUser($iUserNo){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_management_user_rel` WHERE `group_user_no`= $iUserNo";
		$iDbq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDbq)){
			$aAllManagement[]=new COrderManagementGroup($aRow);
		}

		$aReturn = array();
		$aManagement=self::aGetManagement();
		

		if(!empty($aAllManagement)){
			foreach($aAllManagement as $oManagement){
				foreach($aManagement as $aManagementData){
					if($oManagement->iManagementNo==$aManagementData['mm_id']){
						$oManagement->sName=$aManagementData['mm_name'];
						$iOrder = $aManagementData['mm_order'];
						$aReturn[$iOrder] = $oManagement;
						break;
					}
				}
			}
		}
		ksort($aReturn);
		return $aReturn;
	}

	//get management info from old cat
	public static function aGetManagement(){
		// Create the Client instance
		$client = new nusoap_client('http://www.oldcat.com.tw/api/webservice/api.oldcat.php'); 
		$client->soap_defencoding = 'utf-8';
		$client->xml_encoding = 'utf-8';
		$client->decode_utf8 = false;
		$result = $client->call(
			'aGetManagement',
			array()
		);
		if ($client->fault) {
			$fault="Fault: $result";
			CJavaScript::vAlertRedirect($fault, $_SERVER['PHP_SELF']);
		} else {
			$err = $client->getError();
			if ($err) {
				CJavaScript::vAlertRedirect($err, $_SERVER['PHP_SELF']);
			} else {
				return $result;
			}
		}
	}	


	//set user array to the order management group
	public function vSetUser($aUser){
		$this->__aUser=array();
		if(!empty($aUser)){
			foreach($aUser as $oUser){
				$this->__aUser[]=$oUser;
			}
		}
	}

	//update the user
	public function vUpdateUser(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//delete the old user no of this group and insert the new
			$oDB->vDelete("order_management_user_rel", "`management_no`={$this->iManagementNo}");
			$sDate = date("Y-m-d H:i:s");
			foreach($this->__aUser as $oUser){
				$aValues=array(
					'management_no' => $this->iManagementNo,
					'group_user_no' => $oUser->iUserNo,
					'create_time'   => $sDate
				);
				$oDB->sInsert("order_management_user_rel", array_keys($aValues), array_values($aValues));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('order_management_user_rel', $this->iManagementNo, $_GET['func'], $_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception('COrderManagementGroup->vUpdateUser:'.$e->getMessage());
		}
	}

	//return all the array of user object of the management group
	public function aUser(){
		if(empty($this->__aUser)){
			$oDB=self::oDB(self::$sDBName);	
			$sSql="SELECT * FROM `order_management_user_rel` WHERE `management_no` = {$this->iManagementNo}";
			$iDbq=$oDB->iQuery($sSql);
			while($aRow=$oDB->aFetchAssoc($iDbq)){
				$this->__aUser[]=CUserFront::oGetUser($aRow['group_user_no']);
			}
		}
		return $this->__aUser;
	}
	
}

?>