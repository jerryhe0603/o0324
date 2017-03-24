<?php
/**
 * 線路
 */
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/CTor.php');
class CProxy extends CGalaxyClass
{

	
	private $iProxyNo;		//線路流水號
	public 	$sProxyName;		//線路名稱
	public 	$sProxyHost;		//線路host
	public 	$iProxyPort;		//線路port
	
	public 	$sProxyUser;		//線路帳號
	public 	$sProxyPassword;	//線路密碼
	public 	$iProxyType;		//線路型態 0 tor 1 proxy
	
	public 	$sProxyDesc;		//線路描述
	public $sISO;			//線路所在地 國家
	public $sZIP;			//線路所在地 zipcode
	public 	$sProxyInstallAddress;	//線路裝機地址
	public 	$sProxyCaretaker;		//線路聯絡人
	private $sProxyResetIPTime;	//線路最後reset ip time
	private $iQueueServerNo;		//queue server no
	private $sProxyPreviousIp;	//線路上一次的ip
	private $iProxyAvailable;	//線路是否可用 取得是否有傳回ip


	//database setting
	static protected $sDBName = 'GENESIS';

	static protected  $aGroupName = array("all","beauty","goods");

	public $aGroup = array();	//身份可使用群組

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CProxy: __construct failed, require an array");
		//initialize vital member
		if(isset($multiData['proxy_id']))
			$this->sPuppetsUuid = $multiData['proxy_id'];
		
		//initialize optional member
		$this->sProxyName = $multiData['proxy_name'];
		$this->sProxyHost = $multiData['proxy_host'];
		$this->iProxyPort = $multiData['proxy_port'];
		$this->sProxyUser = $multiData['proxy_user'];
		$this->sProxyPassword = $multiData['proxy_pass'];
		$this->iProxyType = $multiData['proxy_type'];
		$this->sProxyDesc = $multiData['proxy_desc'];
		$this->sISO = $multiData['ISO'];
		$this->sZIP = $multiData['ZIP'];
		$this->sProxyInstallAddress = $multiData['proxy_install_address'];
		$this->sProxyCaretaker = $multiData['proxy_caretaker'];


		$this->sProxyResetIPTime = $multiData['proxy_resetiptime'];
		$this->iQueueServerNo = $multiData['server_no'];
		$this->sProxyPreviousIp = $multiData['proxy_previous_ip'];
		$this->iProxyAvailable = $multiData['proxy_available'];
		
		
		//galaxy class memeber
		$this->bStatus = $multiData['proxy_status'];
		$this->sCreateTime = $multiData['modifiedtime'];
		$this->sModifiedTime = $multiData['createtime'];

		if(is_array($multiData['group']) )
			$this->aGroup = $multiData['group'];
		else
			$this->aGroup = array();
	}
	/**
	 * 取得線路資料
	 *	
	 * @param string $iProxyNo 線路流水號
	 */
	static public  function oGetProxy($iProxyNo=0) {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT * FROM galaxy_proxy WHERE proxy_id = $iProxyNo";
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$sSql = "SELECT group_no FROM galaxy_proxy_group WHERE proxy_id = '$iProxyNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aGroupNo = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aGroupNo[] = $aRow['group_no'];
		}
		$aRow['group'] = $aGroupNo;

		$oCProxy = new CProxy($aRow);
		
		return $oCProxy;

		
	}
	static public  function oGetProxyByName($sProxyName="") {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT * FROM galaxy_proxy WHERE proxy_name = '$sProxyName'";
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$sSql = "SELECT group_no FROM galaxy_proxy_group WHERE proxy_id = '$iProxyNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aGroupNo = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aGroupNo[] = $aRow['group_no'];
		}
		$aRow['group'] = $aGroupNo;

		$oCProxy = new CProxy($aRow);
		
		return $oCProxy;

		
	}

	/**
	 * 使用標籤取得線路
	 *
	 */
	static public  function oGetProxyByTag($sTagList="") {
		global $GenesisDbh, $CProxy;
		
		if($sTagList==="") return false;
		
		$oDB = self::oDB(self::$sDBName);
		$aAllProxys = array();
		$sSql = "SELECT table_no AS proxy_id FROM galaxy_tag_log 
					WHERE table_name = 'galaxy_proxy' 
						AND tag_no IN (".$sTagList.")";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllProxys[] = new CProxy($aRow);
		}
		return $aAllProxys;
	}
	static public function aGetProxyByZIP($zip="") {
		$oDB = self::oDB(self::$sDBName);
		$aAllProxys = array();
		
		$sql = "SELECT * FROM `galaxy_proxy` 
				WHERE `ZIP` = '$zip'
				AND `proxy_status` = '1'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllProxys[] = new CProxy($aRow);
		}
		return $aAllProxys;
	}
	/**
	 * 取得所有線路的資料
	 */
	static public function aAllProxys($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllProxys = array();
		$sSql = "SELECT * FROM galaxy_proxy";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllProxys[] = new CProxy($aRow);
		}
		return $aAllProxys;
	}

	/* 線路是否可用
	 * 
	 * author ben
	*/
	static public function bIsAvailible($sProxyName="") {
		global $GenesisDbh;
		
		if($sProxyName==="") return false;

		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT `proxy_available` 
					FROM `galaxy_proxy` 
					WHERE `proxy_name` = '$sProxyName'
					AND `proxy_available` = '1'";
	
		$iDbq = $oDB->iQuery($sSql);

		if($aRow === false || $oDB->iNumRows($iDbq)==0){
			return false;
		} else {
			return true;
		}
	}
	
	static public function iGetServer($iProxyNo=0) {
		

		$sSql = "SELECT `server_no` 
					FROM `galaxy_proxy` 
					WHERE `proxy_id` = $iProxyNo
					LIMIT 1";

		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)==0)
			return 0;


		return $aRow['server_no'];
	}
	/**
	 * 取得所有線路的筆數
	 */
	static public function iTotal($sSearchSql='') {
				
		$oDB = self::oDB(self::$sDBName);
		$aAllProxys = array();
		$sSql = "SELECT count(1) AS total FROM galaxy_proxy";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);

		if($aRow === false || $oDB->iNumRows($iDbq)==0)
			return 0;
			
		return $aRow['total'];
	}
	
	/**
	 * 更換IP後，更新線路資料
	 *
	 * @param int $ProxyData 線路資料
	 */
	static public function vUpdateResetIPInfo($ProxyData){
		/* 線路可用狀態，如果new_wan2為x，代表 */
		$proxy_available = 1;
		if($ProxyData['new_wan'] == 'x'){
			$proxy_available = 0;
		}
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			//insert proxy attr


			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'proxy_previous_ip'=>$ProxyData['wan2'],
						'proxy_available'=>$proxy_available,
						'proxy_resetiptime'=>$sDate,
						'modifiedtime'=>$sDate
						);
			$oDB->vUpdate("galaxy_puppets",array_keys($aValues),array_values($aValues),"proxy_name='".$ProxyData['name']."'");

			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCProxy->vUpdateProxy: '.$e->getMessage());
		}
		
	
	
	}
	
	/**
	 * 新增線路資料
	 *
	 */
	public function iAddProxy() {
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//insert proxy attr


			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'proxy_name'=>$this->sProxyName,
						'proxy_desc'=>$this->sProxyDesc,
						'proxy_host'=>$this->sProxyHost,
						'proxy_port'=>$this->iProxyPort,
						'proxy_user'=>$this->sProxyUser,
						'proxy_pass'=>$this->sProxyPassword,
						'proxy_type'=>$this->iProxyType,
						
						'ISO'=>$this->sISO,
						'ZIP'=>$this->sZIP,
						'proxy_install_address'=>$this->sProxyInstallAddress,
						'proxy_caretaker'=>$this->sProxyCaretaker,
						'proxy_status'=>$this->bStatus,
						
						'user_no'=>$oCurrentUser->iUserNo,
						'createtime'=>$sDate,
						'modifiedtime'=>$sDate
						);
			$oDB->sInsert("galaxy_puppets",array_keys($aValues),array_values($aValues));
			$this->iProxyNo = $oDB->iGetInsertId();


			

			for($i=0;$i<count($this->aGroup);$i++){
				$aValues = array(	'proxy_id'=>$this->iProxyNo,
							'group_no'=>$this->aGroup[$i]
						);
				$oDB->sInsert("galaxy_proxy_group",array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();
			return $this->iProxyNo;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCProxy->iAddProxy: '.$e->getMessage());
		}
		
	}
	
	/**
	 * 更新線路資料
	 *
	 */
	public function vUpdateProxy() {
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//insert proxy attr


			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'proxy_name'=>$this->sProxyName,
						'proxy_desc'=>$this->sProxyDesc,
						'proxy_host'=>$this->sProxyHost,
						'proxy_port'=>$this->iProxyPort,
						'proxy_user'=>$this->sProxyUser,
						'proxy_pass'=>$this->sProxyPassword,
						'proxy_type'=>$this->iProxyType,
						
						'ISO'=>$this->sISO,
						'ZIP'=>$this->sZIP,
						'proxy_install_address'=>$this->sProxyInstallAddress,
						'proxy_caretaker'=>$this->sProxyCaretaker,
						'proxy_status'=>$this->bStatus,
						
						'user_no'=>$oCurrentUser->iUserNo,
						'modifiedtime'=>$sDate
						);
			$oDB->vUpdate("galaxy_puppets",array_keys($aValues),array_values($aValues),"proxy_id={$this->iProxyNo}");

			$sSql = "DELETE FROM galaxy_proxy_group WHERE  proxy_id = {$this->iProxyNo}";
			$iRes = $oDB->iQuery($sSql);

			for($i=0;$i<count($this->aGroup);$i++){
				$aValues = array(	'proxy_id'=>$this->iProxyNo,
							'group_no'=>$this->aGroup[$i]
						);
				$oDB->sInsert("galaxy_proxy_group",array_keys($aValues),array_values($aValues));
			}
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCProxy->vUpdateProxy: '.$e->getMessage());
		}

	}
		
	
	static public function aGetProxyRouter($sProxyName=""){
		
		if(!$sProxyName) return array();
		$aRouterDesc = array();
		$CTor = new CTor();
		// tor dir server
		if($CTor->bConnect("172.16.2.46",9051,"cj/6vup4ru,6")){
			if($CTor->bAuthenticate()){
				$bDirServer = true;
				
				$aRouterInfo = $CTor->aGetRouterInfo($sProxyName);
				
				if(!$aRouterInfo){
					$CTor->close();
					throw new Exception('CProxy->aGetProxyRouter: '.$CTor->sGetErrorMsg());

				}
				
				$aRouterDesc = $CTor->aGetRouterDesc($sProxyName);
				if(!$aRouterDesc){
				
					$CTor->close();
					throw new Exception('CProxy->aGetProxyRouter: '.$CTor->sGetErrorMsg());
				}	
				if($aRouterInfo['published'] > $aRouterDesc['published']){
					$aRouterDesc['ip'] = $aRouterInfo['ip'];
					$aRouterDesc['published'] = $aRouterInfo['published'];
				}	
			}else{
				
				$CTor->close();
				throw new Exception('CProxy->aGetProxyRouter: '.$CTor->sGetErrorMsg());
			}	

			$CTor->close();
		}else{
			
			throw new Exception('CProxy->aGetProxyRouter: '.$CTor->sGetErrorMsg()." , 請檢查172.16.2.46:9051 是否正常防火牆是否有阻擋,tor是否有運作");
		}
		$aRouterDesc['traffic'] = array();
		$aRouterDesc['accounting'] = array();
		$aRouterDesc['wan'] = "x";
		$aRouterDesc['wan2'] = "x";
		
		if($aRouterDesc['ip']){
		
			if($CTor->bConnect($aRouterDesc['ip'],9051,"cj/6vup4ru,6")){
				if($CTor->bAuthenticate()){
					$aRouterDesc['traffic'] = $CTor->aGetTraffic();
					$aRouterDesc['accounting'] = $CTor->aGetAccounting();
					$aRouterDesc['wan'] = $CTor->sGetIP("wan");
					$aRouterDesc['wan2'] = $CTor->sGetIP("wan2");
				}else{
					$CTor->close();
					throw new Exception('CProxy->aGetProxyRouter: '.$CTor->sGetErrorMsg());
				}
				
			}else
				throw new Exception('CProxy->aGetProxyRouter: '.$CTor->sGetErrorMsg()." , 請檢查".$aRouterDesc['ip'].":9051 是否正常防火牆是否有阻擋,tor是否有運作");

			$CTor->close();	
		}
		
		return $aRouterDesc;
	
	}
	
	
	static public function aSetProxyRouterIP($sProxyName=""){
		
		if(!$sProxyName) return array();
		$aRouterDesc = array();
		// tor dir server
		$CTor = new CTor();
		if($CTor->bConnect("172.16.2.46",9051,"cj/6vup4ru,6")){
			if($CTor->bAuthenticate()){
				$bDirServer = true;
				$aRouterInfo = $CTor->aGetRouterInfo($sProxyName);
				
				if(!$aRouterInfo){
					
					$CTor->close();
					throw new Exception('CProxy->aSetProxyRouterIP: '.$CTor->sGetErrorMsg());
				}
				
				$aRouterDesc = $CTor->aGetRouterDesc($sProxyName);
				if(!$aRouterDesc){
					
					$CTor->close();
					
					throw new Exception('CProxy->aSetProxyRouterIP: '.$CTor->sGetErrorMsg());
				}	
				
				// if($aRouterInfo['published'] >= $aRouterDesc['published']){

					$aRouterDesc['ip'] = $aRouterInfo['ip'];
					$aRouterDesc['published'] = $aRouterInfo['published'];
				// }
								
			}else{
				
				$CTor->close();
				throw new Exception('CProxy->aSetProxyRouterIP: '.$CTor->sGetErrorMsg());
			}	

			$CTor->close();
		}else{
			throw new Exception('CProxy->aSetProxyRouterIP: '.$CTor->sGetErrorMsg()." , 請檢查172.16.2.46:9051 是否正常防火牆是否有阻擋,tor是否有運作");
			
		}
		$aRouterDesc['traffic'] = array();
		$aRouterDesc['accounting'] = array();
		$aRouterDesc['wan'] = "x";
		$aRouterDesc['wan2'] = "x";
		$aRouterDesc['new_wan'] = "x";
		$aRouterDesc['new_wan2'] = "x";
		if($aRouterDesc['ip']){
		
			if($CTor->bConnect($aRouterDesc['ip'],9051,"cj/6vup4ru,6")){
				if($CTor->bAuthenticate()){
					
					$aRouterDesc['traffic'] = $CTor->aGetTraffic();
					$aRouterDesc['accounting'] = $CTor->aGetAccounting();
					$aRouterDesc['wan'] = $CTor->sGetIP("wan");
					$aRouterDesc['wan2'] = $CTor->sGetIP("wan2");
					$aRouterDesc['new_wan'] = $aRouterDesc['wan'];
					$aRouterDesc['new_wan2'] = $aRouterDesc['wan2'];
					//sSetIP
					//520GU  reset wan
					if(preg_match("/520GU/i",$sProxyName)){
						$aRouterDesc['new_wan'] = $CTor->sSetIP("wan");
					}else
						$aRouterDesc['new_wan2'] = $CTor->sSetIP("wan2");
					$CTor->sSetGW("wan2");
					
					
				}else{
					$CTor->close();
					throw new Exception('CProxy->aSetProxyRouterIP: '.$CTor->sGetErrorMsg());
				}	

				$CTor->close();
			}else
				throw new Exception('CProxy->aSetProxyRouterIP: '.$CTor->sGetErrorMsg()." , 請檢查".$aRouterDesc['ip'].":9051 是否正常防火牆是否有阻擋,tor是否有運作");
		}
		
		return $aRouterDesc;
	
	}
	
	static public function aSetProxyRouterGW($sProxyName=""){
		global $CTor;
		if(!$sProxyName) return array();
		$aRouterDesc = array();
		$CTor = new CTor();
		// tor dir server
		if($CTor->bConnect("172.16.2.46",9051,"cj/6vup4ru,6")){
			if($CTor->bAuthenticate()){
				$bDirServer = true;
				$aRouterInfo = $CTor->aGetRouterInfo($sProxyName);
				
				if(!$aRouterInfo){
					
					$CTor->close();
					throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg());
				}
				
				$aRouterDesc = $CTor->aGetRouterDesc($sProxyName);
				if(!$aRouterDesc){
					
					$CTor->close();
					throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg());
				}	
				
				// if($aRouterInfo['published'] >= $aRouterDesc['published']){

					$aRouterDesc['ip'] = $aRouterInfo['ip'];
					$aRouterDesc['published'] = $aRouterInfo['published'];
				// }
								
			}else{
				
				$CTor->close();
				throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg());
			}	

			$CTor->close();
		}else{
			throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg()." , 請檢查172.16.2.46:9051 是否正常防火牆是否有阻擋,tor是否有運作");
		}
		$aRouterDesc['traffic'] = array();
		$aRouterDesc['accounting'] = array();
		$aRouterDesc['wan'] = "x";
		$aRouterDesc['wan2'] = "x";
		$aRouterDesc['new_wan'] = "x";
		$aRouterDesc['new_wan2'] = "x";
		if($aRouterDesc['ip']){
		
			if($CTor->bConnect($aRouterDesc['ip'],9051,"cj/6vup4ru,6")){
				if($CTor->bAuthenticate()){
					
					$aRouterDesc['traffic'] = $CTor->aGetTraffic();
					$aRouterDesc['accounting'] = $CTor->aGetAccounting();
					$aRouterDesc['wan'] = $CTor->sGetIP("wan");
					$aRouterDesc['wan2'] = $CTor->sGetIP("wan2");
					$aRouterDesc['new_wan'] = $aRouterDesc['wan'];
					$aRouterDesc['new_wan2'] = $aRouterDesc['wan2'];
					//sSetIP
					//520GU  reset wan
					// if(preg_match("/520GU/i",$sProxyName)){
						// $aRouterDesc['new_wan'] = $CTor->sSetIP("wan");
					// }else
						$aRouterDesc['new_wan2'] = $CTor->sSetGW("wan2");
					$CTor->close();
					
				
				}else{
					$CTor->close();
					throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg());
					
				}		

				
			}else
				throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg()." , 請檢查".$aRouterDesc['ip'].":9051 是否正常防火牆是否有阻擋,tor是否有運作");
		}
		
		return $aRouterDesc;
	
	}
	
	static public function vRebootProxyRouter($sProxyName=""){
		global $CTor;
		if(!$sProxyName) return;
		// tor dir server
		$CTor = new CTor();
		$aRouterDesc = array();
		if($CTor->bConnect("172.16.2.46",9051,"cj/6vup4ru,6")){
			if($CTor->bAuthenticate()){
				$bDirServer = true;
				
				$aRouterInfo = $CTor->aGetRouterInfo($sProxyName);
				
				if(!$aRouterInfo){
					
					$CTor->close();
					throw new Exception('CProxy->vRebootProxyRouter: '.$CTor->sGetErrorMsg());
				}
				
				$aRouterDesc = $CTor->aGetRouterDesc($sProxyName);
				if(!$aRouterDesc){
				
					$CTor->close();
					throw new Exception('CProxy->vRebootProxyRouter: '.$CTor->sGetErrorMsg());
				}	
				
				if($aRouterInfo['published'] > $aRouterDesc['published']){
					$aRouterDesc['ip'] = $aRouterInfo['ip'];
					$aRouterDesc['published'] = $aRouterInfo['published'];
				}	
			
			}else{
				
				$CTor->close();
				throw new Exception('CProxy->vRebootProxyRouter: '.$CTor->sGetErrorMsg());
			}	
			$CTor->close();
		}else{
			throw new Exception('CProxy->vRebootProxyRouter: '.$CTor->sGetErrorMsg()." , 請檢查172.16.2.46:9051 是否正常防火牆是否有阻擋,tor是否有運作");
			
		}
		
		if($aRouterDesc['ip']){
			if($CTor->bConnect($aRouterDesc['ip'],9051,"cj/6vup4ru,6")){
				if($CTor->bAuthenticate()){
					
					$CTor->reboot();
				
				}else{
					$CTor->close();
					throw new Exception('CProxy->vRebootProxyRouter: '.$CTor->sGetErrorMsg());
				}	
				$CTor->close();
			}else
				throw new Exception('CProxy->aSetProxyRouterGW: '.$CTor->sGetErrorMsg()." , 請檢查".$aRouterDesc['ip'].":9051 是否正常防火牆是否有阻擋,tor是否有運作");
		}
		
		return ;
	
	}
	

	
}
?>