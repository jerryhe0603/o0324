<?php
include_once('../inc/model/CGalaxyClass.php');

class CPuppetsLog extends CGalaxyClass
{
	private $sQueueUuid;
	public $sPuppetsUuid;	//uuid
	public $iSiteNo;		//網站流水號
	public $sAccountsUuid;	//uuid
	public $iLogResult; //使用結果(0=失敗, 1=成功,99 準備使用)
	public $iLogType;//0 登入 1 註冊 2 po文
	public $sProxyName;
	public $sIpv4Address;
	public $iServiceNo;
	public $sProjectNo;
	public $iCompanyNo;
	public $iCompanyBrandNo;
	public $sQueueTime;


	//extra member not in DB
	private $sYear;
	private $sMonth;

	static protected $sDBName = 'PUPPETS';
	

	static public function oGetLog($sQueueUuid){
		
		$s = explode("_",$sQueueUuid);
		$s2 = explode("-",$s[0]); 
		$sYear = $s2[0];
		$sMonth = $s2[1];

		if(!ctype_digit(strval($sYear)) || !ctype_digit(strval($sYear))) 
			return null;

		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("galaxy_accounts_log_{$sYear}_{$sMonth}"))
			return null;
		$sSql = "SELECT * FROM galaxy_accounts_log_{$sYear}_{$sMonth} WHERE quuid='$sQueueUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow ===false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oLog = new CUserLog($aRow,$sYear,$sMonth);
		return $oLog;
	}
	

	static public function aAllLog($sSearchSql='',$sPostFix='',$sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
		if(!$oDB->bIsTableExist("galaxy_accounts_log_{$sYear}_{$sMonth}"))
			return array();
		$sSql = "SELECT * FROM galaxy_accounts_log_{$sYear}_{$sMonth}";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllLog = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllLog[] = new CPuppetsLog($aRow,$sYear,$sMonth);
		}
		return $aAllLog;
	}


	

	static public function vAddLogTable($sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
    		$aTableInfo = $oDB->aGetCreateTableInfo("galaxy_accounts_log");
		if(!empty($aTableInfo['Create Table'])){
			$aTableInfo['Create Table'] = preg_replace("/galaxy_accounts_log/i", "galaxy_accounts_log_{$sYear}_{$sMonth}", $aTableInfo['Create Table']);
			$oDB->iQuery($aTableInfo['Create Table'].";\n\n");
		}

		$oDB->iQuery($aTableInfo['Create Table'].";\n\n");

		


	}

	static public function vAddLockTable($sYear,$sMonth){
		$oDB = self::oDB(self::$sDBName);
    		
		//lock table
		$aTableInfo = $oDB->aGetCreateTableInfo("galaxy_accounts_lock_log");
		if(!empty($aTableInfo['Create Table'])){
			$aTableInfo['Create Table'] = preg_replace("/galaxy_accounts_lock_log/i", "galaxy_accounts_lock_log_{$sYear}_{$sMonth}", $aTableInfo['Create Table']);
			$oDB->iQuery($aTableInfo['Create Table'].";\n\n");

			
			//產生當月table ,必須將上個月lock expirytime 大於今天 是這個月的紀錄複製進去
			$y = date("Y", mktime(0, 0, 0, $sMonth-1,date("d"), $sYear));
			$m = date("m", mktime(0, 0, 0, $sMonth-1,date("d"), $sYear));
			if($oDB->bIsTableExist("galaxy_accounts_lock_log_{$y}_{$m}"))
				$oDB->iQuery("INSERT INTO galaxy_accounts_lock_log_{$sYear}_{$sMonth} SELECT * FROM galaxy_accounts_lock_log_{$y}_{$m} WHERE expirytime >= NOW()");
		}
		


	}
	public function __construct($multiData,$sYear='',$sMonth=''){
		parent::__construct($multiData);
		$this->sQueueUuid = $multiData['quuid'];
		$this->iSiteNo = $multiData['site_no'];
		$this->sPuppetsUuid = $multiData['puppets_no'];
		$this->sAccountsUuid = $multiData['accounts_site_no'];
		$this->iLogResult = $multiData['log_result'];
		$this->iLogType = $multiData['log_type'];
		$this->sProxyName = $multiData['proxy_name'];
		$this->sIpv4Address = $multiData['ipv4_address'];
		$this->iServiceNo = $multiData['service_no'];
		$this->sProjectNo = $multiData['project_no'];
		$this->iCompanyNo = $multiData['co_id'];
		$this->iCompanyBrandNo = $multiData['cb_id'];
		$this->sQueueTime = $multiData['queue_time'];

		//extra member
		if($sYear!=='')
			$this->sYear = $sYear;
		if($sMonth!=='')
			$this->sMonth = $sMonth;
		//galaxy class memeber
		$this->sCreateTime = $multiData['log_createtime'];
		$this->sModifiedTime = $multiData['log_modifiedtime'];
		
	}

	public function bAddLockLog(){
		$oDB = self::oDB(self::$sDBName);


		
		$aValues = array(	
					'site_no'=>$this->iSiteNo,
					'accounts_site_no'=>$this->sAccountsUuid,
					'puppets_no'=>$this->sPuppetsUuid,
					'project_no'=>$this->sProjectNo,
					'user_no'=>$this->__iUserNo,
					'log_createtime'=>date("Y-m-d H:i:s"),
					'expirytime'=>date("Y-m-d H:i:s", mktime(date("H"), date("i"), 0, date("m"),date("d")+7,  date("Y")))
					);
		

		
		try{
			$oDB->vBegin();
			if(!$oDB->bIsTableExist('galaxy_accounts_lock_log_'.date('Y').'_'.date('m'))){
				self::vAddLockTable(date('Y'),date('m'));
			}
			
			$sSql = "SELECT accounts_site_no FROM galaxy_accounts_lock_log_".date('Y')."_".date('m')." WHERE accounts_site_no='{$this->sAccountsUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			if($oDB->iNumRows($iDbq)>0){
				//update 原有的資料

				$oDB->sUpdate("galaxy_accounts_lock_log_".date('Y')."_".date('m'),array_keys($aValues),array_values($aValues),"accounts_site_no='{$this->sAccountsUuid}'");
		

			}else{
				//insert
				$oDB->sInsert("galaxy_accounts_lock_log_".date('Y')."_".date('m'),array_keys($aValues),array_values($aValues));

			}

			$oDB->vCommit();
			return true;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CPuppetsLog->bAddLockLog: ".$e->getMessage());
		}
	}
	public function bAddLog(){
		$oDB = self::oDB(self::$sDBName);


		$aValues = array(	'quuid'=>$this->sQueueUuid,
					'puppets_no' => $this->sPuppetsUuid,
					'site_no'=>$this->iSiteNo,
					'accounts_site_no'=>$this->sAccountsUuid,
					'log_result'=>99,
					'log_type'=>$this->iLogType,
					'proxy_name'=>$this->sProxyName,
					'service_no'=>$this->iServiceNo,
					'project_no'=>$this->sProjectNo,
					'co_id'=>$this->iCompanyNo,
					'cb_id'=>$this->iCompanyBrandNo,
					'queue_time'=>$this->sQueueTime,
					'user_no'=>$this->__iUserNo,
					'log_modifiedtime'=>date("Y-m-d H:i:s"),
					'log_createtime'=>date("Y-m-d H:i:s")
					);
		try{
			$oDB->vBegin();
			if(!$oDB->bIsTableExist('galaxy_accounts_log_'.date('Y').'_'.date('m'))){
				self::vAddLogTable(date('Y'),date('m'));
			}
			$oDB->sInsert('galaxy_accounts_log_'.date('Y').'_'.date('m'),array_keys($aValues),array_values($aValues));
			
			$oDB->vCommit();
			return true;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CPuppetsLog->bAddLog: ".$e->getMessage());
		}
	}
	public function vUpdateLog($iLogResult=0,$sIpv4Address){
		$oDB = self::oDB(self::$sDBName);
		try{
			
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'ipv4_address'=>$this->sIpv4Address,
						'log_result'=>$iLogResult,
						'log_modifiedtime'=>$sDate
					);
			
			$oDB->sUpdate("galaxy_accounts_log_{$this->sYear}_{$this->sMonth}",array_keys($aValues),array_values($aValues),"quuid='{$this->sQueueUuid}'");
			


			//最後發文紀錄


			if($iLogResult==1 && $this->iLogType==2){
				//最後發文,只紀錄使用此帳號針對哪個品牌的最後一次發文紀錄
				$sSql = "SELECT puppets_no FROM galaxy_accounts_lastpost_log WHERE accounts_site_no='{$this->sAccountsUuid}' AND cb_id={$this->iCompanyBrandNo}";
				$iDbq = $oDB->iQuery($sSql);
				$aValues = array('quuid'=>$this->sQueueUuid,
					'site_no'=>$this->iSiteNo,
					'puppets_no'=>$this->sPuppetsUuid,
					'accounts_site_no'=>$this->sAccountsUuid,
					'project_no'=>$this->sProjectNo,
					'co_id'=>$this->iCompanyNo,
					'cb_id'=>$this->iCompanyBrandNo,
					'ipv4_address'=>$this->sIpv4Address,
					'last_time'=>$sDate
					);
				if($oDB->iNumRows($iDbq)>1){
					//update 原有的資料

					$oDB->sUpdate("galaxy_accounts_lastpost_log",array_keys($aValues),array_values($aValues),"accounts_site_no='{$this->sAccountsUuid}' AND cb_id={$this->iCompanyBrandNo}'");
			

				}else{
					//insert
					$oDB->sInsert('galaxy_accounts_lastpost_log',array_keys($aValues),array_values($aValues));

				}	


				

			}


			//上站次數統計
			$sSql = "SELECT * FROM galaxy_accounts_login_count_log WHERE accounts_site_no='{$this->sAccountsUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			if($oDB->iNumRows($iDbq)>1){

				$sSql = "UPDATE   galaxy_accounts_login_count_log SET login_count=login_count+1 WHERE accounts_site_no='{$this->sAccountsUuid}'";
				$iDbq = $oDB->iQuery($sSql);
			}else{

				$aValues = array(	'quuid'=>$this->sQueueUuid,
						'site_no'=>$this->iSiteNo,
						'puppets_no'=>$this->sPuppetsUuid,
						'accounts_site_no'=>$this->sAccountsUuid,
						'project_no'=>$this->sProjectNo,
						'login_count'=>1,
						'last_time'=>$sDate
						);

				$oDB->sInsert('galaxy_accounts_login_count_log',array_keys($aValues),array_values($aValues));
			}
			
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppetsLog->vUpdateLog: '.$e->getMessage());
		}
	}
	

	public function bAddNewRegisterLog(){
		$oDB = self::oDB(self::$sDBName);
		if($this->iLogType!=1)
			throw new Exception("CPuppetsLog->bAddNewRegisterLog: type not register");
		$aValues = array(	'quuid'=>$this->sQueueUuid,
					'site_no'=>$this->iSiteNo,
					'accounts_site_no'=>$this->sAccountsUuid,
					'puppets_no'=>$this->sPuppetsUuid,					
					'project_no'=>$this->sProjectNo,
					'registered_time'=>date("Y-m-d H:i:s")
					);
		try{
			$oDB->vBegin();
			
			$oDB->sInsert('galaxy_accounts_new_log',array_keys($aValues),array_values($aValues));
			
			$oDB->vCommit();
			return true;
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CPuppetsLog->bAddNewRegisterLog: ".$e->getMessage());
		}
	}

}
?>