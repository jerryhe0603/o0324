<?php
/**
 * 帳號
 */
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CAccountsElement.php');
include_once('../inc/pinyin.class.php');
class CAccounts extends CGalaxyClass
{

	private $sAccountsUuid;	//uuid
	private $sPuppetsUuid;	//puppet uuid
	public $sEmailAccountsSiteNo;	//用哪個email收認證信
	public $sLoginAccountsSiteNo;	//使用哪個帳號登入
	public $sAccountsNote;	//描述
	public $iProxyNo;		//使用線路流水號
	public $iSiteNo;		//網站流水號
	public $sRegisteredTime;	//註冊時間
	public $sLastUseTime;	//最後使用時間
	public $sLockTime;	   //鎖定時間, 避免在註冊前, 被重複領走

	//database setting
	static protected $sDBName = 'PUPPETS';

	private $__aCAccountsElement;	//CAUTION: this is a map of element_mapping_no to CAccountsElement object
	static private $__aSearchTemp = array();//search tmp array
	static private $__aMakeSearchTemp = array();// make search tmp array
	public function __get($varName)
	{
		return $this->$varName;
	}

	/**
	 * 取得網站帳號其他資料
	 *	
	 * @param string $accounts_site_no 帳號序號
	 */

	static public  function oGetAccounts($sAccountsUuid) {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT * FROM galaxy_accounts_site WHERE accounts_site_no = '$sAccountsUuid'";
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		

		$oCAccounts = new CAccounts($aRow);
		$oCAccounts->aAccountsElement();
		
		return $oCAccounts;		
	}

	/**
	 * 取得網站帳號其他資料
	 *	
	 * @param string $sPostFix where 條件
	 */

	static public  function oGetAccountsWithWhere($sPostFix='') {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT * FROM `galaxy_accounts_site`";
		if($sPostFix!=='')
			$sSql .= " WHERE  $sPostFix";
		$iDbq = $oDB->iQuery($sSql);

		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)==0)
			return null;
		$oCAccounts = new CAccounts($aRow);	

		return $oCAccounts;		
	}
	
	static public function aAllAccounts($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllAccounts = array();
		$sSql = "SELECT * FROM galaxy_accounts_site AS a";
		if($sSearchSql!=='')
			$sSql .= " LEFT JOIN galaxy_accounts_site_mapping AS m ON m.accounts_site_no=a.accounts_site_no WHERE $sSearchSql GROUP BY a.accounts_site_no";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllAccounts[] = new CAccounts($aRow);
		}
		return $aAllAccounts;
	}
	/*
		no join galaxy_accounts_site_mapping
	*/
	static public function aAllAccounts2($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllAccounts = array();
		$sSql = "SELECT * FROM galaxy_accounts_site AS a";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";

		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllAccounts[] = new CAccounts($aRow);
		}
		return $aAllAccounts;
	}
	
	
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(a.accounts_site_no) as total FROM galaxy_accounts_site AS a";
		
		if($sSearchSql!==''){
			$sSql .= " LEFT JOIN galaxy_accounts_site_mapping AS m ON m.accounts_site_no=a.accounts_site_no WHERE $sSearchSql GROUP BY a.accounts_site_no";

		}

		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}
	
	static public function bIsAccountExist($sAccountsUuid=0,$iSiteNo=0,$iElementMappingNo=0,$sValue=""){
		$oDB = self::oDB(self::$sDBName);
		
		if($iElementMappingNo==0) return false;
		
		$sSql = "SELECT accounts_site_no FROM galaxy_accounts_site_mapping AS m
				LEFT JOIN galaxy_accounts_site ON s
				WHERE AND s.site_no=$site_no AND m.element_mapping_no='$iElementMappingNo' AND m.element_mapping_value='$sValue' ";
		$iRes = $oDB->iQuery2($sSql);
		$fe = $oDB->aFetchAssoc($iRes);
		if(!$fe) return false;
		if($iSiteNo == $fe['accounts_site_no']) return false;
		return true;

	}
	
	
	/**
	 * 取得所有網站帳號的欄位
	 */
	static public function aGetAccountFields($sAccountsUuid,$bStatus=false){
		$oDB = self::oDB(self::$sDBName);
		if(!$bStatus)
			$sSql = "SELECT * FROM galaxy_accounts_site_mapping WHERE accounts_site_no='$sAccountsUuid'";
		else
			$sSql = "SELECT * FROM galaxy_accounts_site_mapping WHERE accounts_site_no='$sAccountsUuid' AND fields_status=1";
		$iRes = $oDB->iQuery2($sSql);
		
		while( $fe = $oDB->aFetchAssoc($iRes) ){
			
			//mapping element
			$fe['element'] = CElementMapping::oGetElementMapping($fe['element_mapping_no']);
			
			if($fe['element']->sTagType === "checkbox" 
				|| $fe['element']->sTagType === "select" 
				|| $fe['element']->sTagType === "radio") {

				//帳號資料是否有選取option,只有checkbox 的element 因為他有是複選否則一律存在galaxy_accounts_mapping table 的element_mapping_value 欄位
				$sSql = "SELECT * FROM  galaxy_accounts_site_mapping_option WHERE accounts_site_no='$accounts_site_no' AND element_mapping_no=".$fe['element_mapping_no'];
				$iRes2 = $oDB->iQuery2($sSql);
				
				while( $fe2 = $oDB->aFetchAssoc($iRes2) ){
					
					$fe['puppet_option'][] =  $fe2['element_mapping_option_no'];
					
				}
				//將element option 設定是否有選取如果帳號資料有選取
				

				if($fe['element']->aOption){
					foreach($fe['element']->aOption AS $key => $val){ // key iElementOptionNo, val oCOption
						//所選的值 
						
						$val->check = 0;
						for($i=0;$i<count($fe['puppet_option']);$i++){
							if($fe['puppet_option'][$i] == $key){
								$val->check = 1;
								break;
							}	
						}
						

					}	

				}
				
			}	
	
			$aRow[] = $fe;
		}

		return $aRow;
	
	}


	

	static public function aAllAccountsTemp($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllAccounts = array();
		$sSql = "SELECT * FROM galaxy_accounts_site_tmp";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllAccounts[] = $aRow;
		}
		return $aAllAccounts;
	}
	static public function aGetAccountsElementTemp($sAccountsUuid,$iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_accounts_site_mapping_tmp WHERE `accounts_site_no`='$sAccountsUuid' AND `element_mapping_no`=$iElementMappingNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		
		return $aRow;
	}
	static public function iGetAccountsTempCount(){
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site_tmp");
		
		$aRow=$oDB->aFetchArray($iDbq);
		
		if($aRow['total']){
			return $aRow['total'];
		}else
			return 0;

	} 
	static public function vClearMakeAccountsTmp(){
		$oDB = self::oDB(self::$sDBName);
		
		$oDB->iQuery("TRUNCATE TABLE  `galaxy_accounts_site_tmp`");
		$oDB->iQuery("TRUNCATE TABLE  `galaxy_accounts_site_mapping_tmp`");
		$oDB->iQuery("TRUNCATE TABLE  `galaxy_accounts_site_mapping_option_tmp`");
	}

	static public function bIsExistAccountsSite($sSearchSql='',$sPostFix='') {
		$oDB = self::oDB(self::$sDBName);
		$aAllAccounts = array();
		$sSql = "SELECT * FROM galaxy_accounts_site";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		
		if($oDB->iNumRows($iDbq) > 0) {
			return true;
		}	

		return false;
	}

	//取得某身份在某網站的帳號資料
	static public function oGetSameAccountAndSite($sPuppetsUuid, $iSiteNo) {
		$oDB = self::oDB(self::$sDBName);

		if(!$sPuppetsUuid || !$iSiteNo)
			return false;

		$sql = "SELECT *
					FROM `galaxy_accounts_site` 
					WHERE `puppets_no` = '$sPuppetsUuid'
					AND `site_no` = $iSiteNo
					AND `accounts_site_status` = 1
					LIMIT 1";

		$iRes = $oDB->iQuery($sql);	
		$fe   = $oDB->aFetchAssoc($iRes);

		if(!$fe) return false;

		$oCAccounts = new CAccounts($fe);

		return $oCAccounts;		
	}
	//取得暫存中的身份資料,將資料轉成 CPuppetsField::_field 格式
	static public function aGetMakeAccountsTmpData($accounts_site_no){
		$oDB = self::oDB(self::$sDBName);
		$aAccountData = array();
		
		$iDbq = $oDB->iQuery("SELECT accounts_site_no,proxy_no,tmp_status FROM `galaxy_accounts_site_tmp` WHERE accounts_site_no='$accounts_site_no'");
		$aRow = $oDB->aFetchArray($iDbq);
		if(!$aRow) return $aAccountData;


		$aAccountData['proxy_no'] = $aRow['proxy_no'];
		$aAccountData['tmp_status'] = $aRow['tmp_status'];

		$iDbq = $oDB->iQuery("SELECT element_mapping_no,element_mapping_value,tmp_status FROM `galaxy_accounts_site_mapping_tmp` WHERE accounts_site_no ='$accounts_site_no'");
		while($aRow = $oDB->aFetchArray($iDbq)) {
			//將所有欄位轉成預設欄位的格式
			foreach(CPuppetsField::$_field AS $key => $val){
				
				if($aRow['element_mapping_no'] == $val['element_mapping_no']){
					
					$aAccountData[$key]['element_mapping_no']=$aRow['element_mapping_no'];

					$aAccountData[$key]['element_mapping_value']=$aRow['element_mapping_value'];
					$aAccountData[$key]['tmp_status']=$aRow['tmp_status'];
					//option
					$iDbq2 = $oDB->iQuery("SELECT element_mapping_option_no,tmp_status FROM `galaxy_accounts_site_mapping_option_tmp` WHERE accounts_site_no ='".$accounts_site_no."' AND element_mapping_no='".$aRow['element_mapping_no']."'");

					
					while($aRow2 = $oDB->aFetchArray($iDbq2)) {

						
						$element_mapping_option_no = $aRow2['element_mapping_option_no'];
						
						if($val['option']){ //這個只是要讓後續的code 不用去判斷哪個 element_mapping_option_no 代表哪個選項 EX. $aPuppetData['gender']['option_mapping'][1] 就是選項男  
							$m = $val['option'][$element_mapping_option_no];
							$aAccountData[$key]['option_mapping'][$m]=$element_mapping_option_no;
						}	
						//讓後續方便寫入資料庫
						$aAccountData[$key]['option'][] = array("element_mapping_option_no"=>$element_mapping_option_no,"tmp_status"=>$aRow2['tmp_status']);

					}	

					break;
				}	
			}	

		}
		return $aAccountData;
	}

	static public function vMakeAccounts($aPuppetDefaultFieldsData,$aPostData){
		$oDB = self::oDB(self::$sDBName);
		try{
			
			$oDB->vBegin();

			//產生每個欄位 option 的數量
			$make_option = array();
		
			for($i = 0; $i < count($aPuppetDefaultFieldsData); $i++) {
				$name = $aPuppetDefaultFieldsData[$i]['name'];
				$element_mapping_no = $aPuppetDefaultFieldsData[$i]['element']->iElementMappingNo;
	
				$make_total = $aPostData['make_nums'];
				
				if($aPuppetDefaultFieldsData[$i]['element']->aOption){
					
					/*if($aPostData[$name.'_random']){//random
						$num = count($aPuppetDefaultFieldsData[$i]['element']->aOption);
						$percentageTotal = 0;
						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){
							$aPostData[$name][$val->iElementOptionNo] = mt_rand(0,100);//百分比
							$percentageTotal=$percentageTotal+$aPostData[$name][$val->iElementOptionNo];//百分比
						}	

						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){
							
							$percentage=$aPostData[$name][$val->iElementOptionNo];//百分比
							
							$amount=0;//數量
							if($percentage){

								$amount=floor($make_total*($percentage/$percentageTotal));
								if($amount==0) $amount = $make_total;
								if($amount < 0) $amount =0;
							}
							$make_total = $make_total - $amount;
							$make_option[$element_mapping_no][]=array("element_mapping_no"=>$element_mapping_no,"element_mapping_option_no"=>$val->iElementOptionNo,"amount"=>$amount);
						}

					}else{*/

						//有可能沒有設定百分比而沒有要產生的選項
						//所以暫存的身份欄位可能就沒有值或沒有選項
						$percentageTotal = 0;
						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){

							$percentageTotal=$percentageTotal+$aPostData[$name][$val->iElementOptionNo];//百分比

						}
						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){
							$percentage=$aPostData[$name][$val->iElementOptionNo];//百分比
							$amount=0;//數量
							if($percentage && $percentageTotal){

								//$amount=floor($make_total*($percentage/$percentageTotal));
								$amount=floor($aPostData['make_nums']*($percentage/$percentageTotal));
								if($amount==0) $amount = $make_total;
								if($amount < 0) $amount =0;
								$make_total = $make_total - $amount;
								$make_option[$element_mapping_no][]=array("element_mapping_no"=>$element_mapping_no,"element_mapping_option_no"=>$val->iElementOptionNo,"amount"=>$amount);
							}
							
						}


					//}

				}
			}


			$element_mapping_option_no = array();

			foreach($make_option AS $key => $val){
				$sWhereSql  = "";
				foreach($val AS $key2 => $val2){

					if($sWhereSql)$sWhereSql = $sWhereSql." OR ";
						$sWhereSql = $sWhereSql." (o.element_mapping_no=".$val2['element_mapping_no']." AND o.element_mapping_option_no ='".$val2['element_mapping_option_no']."')";
				}
				if(!$sWhereSql) continue;
				


				//依選項逐次搜尋至暫存
				if(is_array(self::$__aMakeSearchTemp)  && count(self::$__aMakeSearchTemp) == 0){
					//暫存是空的則只寫入1000筆結果
					$sSql = "SELECT p.puppets_no,p.proxy_no FROM galaxy_puppets_mapping_option AS o LEFT JOIN galaxy_puppets AS p ON o.puppets_no=p.puppets_no WHERE $sWhereSql AND p.puppets_status=1 GROUP BY p.puppets_no LIMIT 0,1000 ";
					$iDbq = $oDB->iQuery($sSql);

					while($aRow = $oDB->aFetchAssoc($iDbq)){
						self::$__aMakeSearchTemp[] = $aRow;
					}	

					//已經有上一次選項的搜尋結果
					$aSearchTemp = self::$__aMakeSearchTemp;
					for($j=0;$j<count(self::$__aMakeSearchTemp);$j++){

						$sSql = " SELECT o.puppets_no FROM galaxy_puppets_mapping_option AS o WHERE $sWhereSql AND o.puppets_no='".$aSearchTemp[$j]['puppets_no']."'";
						$iDbq = $oDB->iQuery($sSql);
						if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
							array_splice(self::$__aMakeSearchTemp,$j);
						}	

					}
				}	

				//其中一個欄位結果不符導致暫存變為空,不需要在搜尋
				if(is_array(self::$__aMakeSearchTemp)  && count(self::$__aMakeSearchTemp)==0)
					throw new Exception("oCAccounts->vMakeAccounts: no result for $i field search");

			}
			

			$aSiteCount = array();
			for($i=0;$i<count($aPostData['site']);$i++){
				$site_no = $aPostData['site'][$i];
				$aSiteCount[$site_no] = 0;
			}	

			for($i=0;$i<count(self::$__aMakeSearchTemp);$i++){
				$puppets_no =self::$__aMakeSearchTemp[$i]['puppets_no'];
				$proxy_no = self::$__aMakeSearchTemp[$i]['proxy_no'];
				//依序選取的網站
				
				if(!$puppets_no) continue;

				for($j=0;$j<count($aPostData['site']);$j++){

					$site_no = $aPostData['site'][$j];

					//達到產生數量
					if($aSiteCount[$site_no] >= $aPostData['make_nums']) {							
						continue;
					}

					$oAccountSiteData = CAccounts::oGetSameAccountAndSite($puppets_no, $site_no);

					if(!$oAccountSiteData){	

						$iDbq_exist = $oDB->iQuery("SELECT accounts_site_no FROM galaxy_accounts_site_tmp WHERE puppets_no ='$puppets_no' AND site_no=$site_no");	
						//不存在
						if(!$oDB->aFetchArray($iDbq_exist)){
							
							$accounts_site_no = CMisc::uuid_v1();
							//將此身份資料寫到暫存
							$aFields=array("accounts_site_no","puppets_no","site_no","proxy_no","tmp_status");
							$aValues=array($accounts_site_no,$puppets_no,$site_no,$proxy_no,0);
							$sSql = $oDB->sInsert("galaxy_accounts_site_tmp",$aFields,$aValues);
							if(!$sSql){
								$oDB->vRollback();
								throw new Exception(_LANG_ACCOUNTS_SITE_MAKE_FAILURE."01");							
							}

							$bMatch = true; //欄位選項是否是所選的條件數量比例是否符合
							//身份原本欄位
							$iDbq2 = $oDB->iQuery("SELECT element_mapping_no,element_mapping_value FROM `galaxy_puppets_mapping` WHERE puppets_no ='$puppets_no'");
							while($aRow2 = $oDB->aFetchArray($iDbq2)) {
									$element_mapping_no = $aRow2['element_mapping_no'];
									$aFields=array("accounts_site_no","element_mapping_no","element_mapping_value","tmp_status");
									$aValues=array($accounts_site_no,$element_mapping_no,$aRow2['element_mapping_value'],0);
									
									$sSql = $oDB->sInsert("galaxy_accounts_site_mapping_tmp",$aFields,$aValues);

									if(!$sSql){
										$oDB->vRollback();
										throw new Exception(_LANG_ACCOUNTS_SITE_MAKE_FAILURE."02");	
									}
									//option
									
									$iDbq3 = $oDB->iQuery("SELECT element_mapping_option_no FROM `galaxy_puppets_mapping_option` WHERE puppets_no ='".$puppets_no."' AND element_mapping_no='".$aRow2['element_mapping_no']."'");

									while($aRow3 = $oDB->aFetchArray($iDbq3)) {
										
										//選項是否是所選取的條件
										for($j=0;$j<count($make_option[$element_mapping_no]);$j++){
											if($make_option[$element_mapping_no][$j]['element_mapping_option_no']==$aRow3['element_mapping_option_no']){
												$make_option[$element_mapping_no][$j]['amount']--;//減少數量
												if($make_option[$element_mapping_no][$j]['amount']<=0){

													//數量超過
													$bMatch = false;
													
												}
												$bMatch = true;//選項符合
												break;
											}
											$bMatch = false;
										}		

										$aFields=array("accounts_site_no","element_mapping_no","element_mapping_option_no","tmp_status");
										$aValues=array($accounts_site_no,$aRow2['element_mapping_no'],$aRow3['element_mapping_option_no'],0);
							
										$sSql = $oDB->sInsert("galaxy_accounts_site_mapping_option_tmp",$aFields,$aValues);

										if(!$sSql){
											$oDB->vRollback();
											throw new Exception(_LANG_ACCOUNTS_SITE_MAKE_FAILURE."03");	
										}							
									}

							}

							if($bMatch){
								
								//如果每個網站有特殊欄位則在此增加例外
								
								$aSiteCount[$site_no]++;
							}else{
								
								//條件不符刪除暫存
								$sSql = "DELETE FROM galaxy_accounts_site_tmp WHERE  accounts_site_no = '$accounts_site_no'";
								$iRes = $oDB->iQuery($sSql);
								$sSql = "DELETE FROM galaxy_accounts_site_mapping_tmp WHERE  accounts_site_no = '$accounts_site_no'";
								$iRes = $oDB->iQuery($sSql);
								$sSql = "DELETE FROM galaxy_accounts_site_mapping_option_tmp WHERE  accounts_site_no = '$accounts_site_no'";
								$iRes = $oDB->iQuery($sSql);

							}		
	
						}


					}
					

				}
					
				

			}	

			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCAccounts->vMakeAccounts: '.$e->getMessage());
		}
		//exit;
	}

	static public function vMakeAccountsSave(){
		$oDB = self::oDB(self::$sDBName);

		$iDbq = $oDB->iQuery("SELECT count(accounts_site_no) AS total FROM `galaxy_accounts_site_tmp`");
		$aRow=$oDB->aFetchArray($iDbq);
		
		if($aRow['total']==0) {
			throw new Exception('CAccounts::vMakeAccountsSave:empty');
		}

		$oDB->iQuery("LOCK TABLE galaxy_accounts_site_tmp WRITE,galaxy_accounts_site_mapping_tmp WRITE,galaxy_accounts_site_mapping_option_tmp WRITE");

		$iDbq = $oDB->iQuery("SELECT accounts_site_no,puppets_no,site_no,proxy_no,tmp_status FROM `galaxy_accounts_site_tmp`");
		while($aRow = $oDB->aFetchArray($iDbq)) {

			$iDbq_exist = $oDB->iQuery("SELECT accounts_site_no FROM galaxy_accounts_site WHERE puppets_no='".$aRow['puppets_no']."' AND site_no=".$aRow['site_no']);	
			//$iDbq_exist = $oDB->iQuery("SELECT accounts_site_no FROM galaxy_accounts_site WHERE puppets_no='".$aRow['puppets_no']."' AND site_no=".$aRow['site_no']);	
			if($oDB->iNumRows($iDbq_exist)){
				continue;
			}	

			$oDB->vBegin();
			$bOk = true;
				
			if($oDB->iNumRows($iDbq_exist)){
				
				if($aRow['tmp_status'] ==2){ //屬於舊身份欄位只是沒有值 所以狀態為2
					$aFields=array("proxy_no");
					$aValues=array($aRow['proxy_no']);

					$sSql = $oDB->sUpdate("galaxy_accounts_site",$aFields,$aValues,"accounts_site_no='".$aRow['accounts_site_no']."'");

					if(!$sSql){
						$bOk = false;
						
					}
				}	
			}else{
				$sDate = date("Y-m-d H:i:s");	
				//insert puppet
				 $oCUser = self::$session->get('oCurrentUser');

				$aFields=array("accounts_site_no","puppets_no","site_no","proxy_no","user_no","createtime","modifiedtime");
				$aValues=array($aRow['accounts_site_no'],$aRow['puppets_no'],$aRow['site_no'],$aRow['proxy_no'],$oCUser->iUserNo,$sDate,$sDate);
		
				$sSql = $oDB->sInsert("galaxy_accounts_site",$aFields,$aValues);

				if(!$sSql){
					$bOk = false;
					
				}

			}


			if($bOk){
				
				$iDbq2 = $oDB->iQuery("SELECT element_mapping_no,element_mapping_value,tmp_status FROM `galaxy_accounts_site_mapping_tmp` WHERE accounts_site_no ='".$aRow['accounts_site_no']."'");
				while($aRow2 = $oDB->aFetchArray($iDbq2)) {
					
					


					$iDbq_exist = $oDB->iQuery("SELECT accounts_site_no FROM galaxy_accounts_site_mapping  WHERE accounts_site_no='".$aRow['accounts_site_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);	
			
					if($oDB->iNumRows($iDbq_exist)){
						
						if($aRow2['tmp_status'] ==2){ //屬於舊身份欄位只是沒有值 所以狀態為2
							$aFields=array("element_mapping_value");
							$aValues=array($aRow2['element_mapping_value']);//原本狀態是1 屬於舊身份欄位只是沒有值 所以改寫狀態為2 update, 0 新欄位

							$sSql = $oDB->sUpdate("galaxy_accounts_site_mapping",$aFields,$aValues,"accounts_site_no='".$aRow['accounts_site_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);

							if(!$sSql){
								$bOk = false;
								break;
							}
						}	
					}else{

						$aFields=array("accounts_site_no","element_mapping_no","element_mapping_value");
						$aValues=array($aRow['accounts_site_no'],$aRow2['element_mapping_no'],$aRow2['element_mapping_value']);
						$sSql = $oDB->sInsert("galaxy_accounts_site_mapping",$aFields,$aValues);

						if(!$sSql){
							$bOk = false;
							break;
						}

					}
					

					//option
					$iDbq3 = $oDB->iQuery("SELECT element_mapping_option_no,tmp_status FROM `galaxy_accounts_site_mapping_option_tmp` WHERE accounts_site_no ='".$aRow['accounts_site_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);

					while($aRow3 = $oDB->aFetchArray($iDbq3)) {
						

						$iDbq_exist = $oDB->iQuery("SELECT accounts_site_no FROM galaxy_accounts_site_mapping_option WHERE accounts_site_no='".$aRow['accounts_site_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']." AND element_mapping_option_no=".$aRow3['element_mapping_option_no']);	
						
						if($oDB->iNumRows($iDbq_exist)==0){

							//insert
							$aFields=array("accounts_site_no","element_mapping_no","element_mapping_option_no");
							$aValues=array($aRow['accounts_site_no'],$aRow2['element_mapping_no'],$aRow3['element_mapping_option_no']);
							$sSql = $oDB->sInsert("galaxy_accounts_site_mapping_option",$aFields,$aValues);

							if(!$sSql){
								$bOk = false;
								break;
							}

						}	

					}	

				}

			}

			$oDB->iQuery("DELETE FROM galaxy_accounts_site_tmp WHERE accounts_site_no='".$aRow['accounts_site_no']."'");
			$oDB->iQuery("DELETE FROM galaxy_accounts_site_mapping_tmp WHERE accounts_site_no='".$aRow['accounts_site_no']."'");	
			$oDB->iQuery("DELETE FROM galaxy_accounts_site_mapping_option_tmp WHERE accounts_site_no='".$aRow['accounts_site_no']."'");	

			if(!$bOk){
				$oDB->vRollback();
				continue;
			}

			$oDB->vCommit();
		}
			

		$oDB->iQuery("UNLOCK TABLES");
	}

	static public function vInsertSearchSqlTmp($sAccountsUuid,$iSelectCount){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');

		try{
			$iDbq = $oDB->iQuery("SELECT * FROM `galaxy_accounts_site` WHERE accounts_site_no ='".$sAccountsUuid."'");
			$aRow = $oDB->aFetchArray($iDbq2);
			if(!$aRow){
				
				throw new Exception("insert galaxy_accounts_site  $sAccountsUuid not exist");	
			}

			$oDB->vBegin();
			$iDbq_exist = $oDB->iQuery("SELECT * FROM galaxy_accounts_site_search_tmp WHERE accounts_site_no ='".$sAccountsUuid."' AND user_no={$oCUser->iUserNo} AND select_count = ".($iSelectCount+1));	
			//不存在
			if(!$oDB->aFetchArray($iDbq_exist)){
				
				$aValues = array(	'accounts_site_no'=>$sAccountsUuid,
							'puppets_no'=>$aRow['puppets_no'],
							'site_no'=>$aRow['site_no'],
							'accounts_site_note'=>$aRow['accounts_site_note'],
							
							'createtime'=>$aRow['createtime'],
							'modifiedtime'=>$aRow['modifiedtime'],
							'proxy_no'=>$aRow['proxy_no'],
							
							'user_no'=>$aRow['user_no'],
							'accounts_site_status'=>$aRow['accounts_site_status'],
							'registeredtime'=>$aRow['registeredtime'],
							'lastusetime'=>$aRow['lastusetime'],
							'locktime'=>$aRow['locktime'],
							'select_count'=>$iSelectCount+1,
							'search_user_no'=>$oCUser->iUserNo,
							'expirytime'=>date("Y-m-d H:i:s", time() + $session->_session_lifetime ) 
						);
				$sSql = $oDB->sInsert("galaxy_accounts_site_search_tmp",array_keys($aValues),array_values($aValues));
				if(!$sSql){
					$oDB->vRollback();
					throw new Exception("insert galaxy_accounts_site_search_tmp error");	
				}
				
				//身份原本欄位
				$iDbq2 = $oDB->iQuery("SELECT * FROM `galaxy_accounts_site_mapping` WHERE accounts_site_no ='".$aRow['accounts_site_no']."'");
				while($aRow2 = $oDB->aFetchArray($iDbq2)) {
					
					$iDbq_exist = $oDB->iQuery("SELECT element_mapping_no FROM galaxy_accounts_site_mapping_tmp WHERE accounts_site_no ='".$aRow['accounts_site_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);	
				
					//不存在
					if(!$oDB->aFetchArray($iDbq_exist)){

						
						$aValues = array('accounts_site_no'=>$sAccountsUuid,
								'element_mapping_no'=>$aRow2['element_mapping_no'],
								'element_mapping_value'=>$aRow2['element_mapping_value'],
								'fields_status'=>$aRow2['fields_status'],
								'fields_order'=>$aRow2['fields_order'],
								'select_count'=>$iSelectCount+1,
								'search_user_no'=>$oCUser->iUserNo,
								'expirytime'=>date("Y-m-d H:i:s", time() + $session->_session_lifetime ) 
						);
						$sSql = $oDB->sInsert("galaxy_accounts_site_mapping_search_tmp",array_keys($aValues),array_values($aValues));
						
						if(!$sSql){
							$oDB->vRollback();
							throw new Exception("insert galaxy_accounts_site_mapping_search_tmp error");	
						}
						//option
						$iDbq3 = $oDB->iQuery("SELECT element_mapping_option_no FROM `galaxy_accounts_site_mapping_option` WHERE accounts_site_no ='".$aRow['accounts_site_no']."' AND element_mapping_no='".$aRow2['element_mapping_no']."'");
						while($aRow3 = $oDB->aFetchArray($iDbq3)) {
							
							$aValues = array('accounts_site_no'=>$sAccountsUuid,
									'element_mapping_no'=>$aRow2['element_mapping_no'],
									'element_mapping_option_no'=>$aRow3['element_mapping_option_no'],
									'select_count'=>$iSelectCount+1,
									'search_user_no'=>$oCUser->iUserNo,
									'expirytime'=>date("Y-m-d H:i:s", time() + $session->_session_lifetime ) 
							);
							$sSql = $oDB->sInsert("galaxy_accounts_site_mapping_option_search_tmp",array_keys($aValues),array_values($aValues));

							if(!$sSql){
								$oDB->vRollback();
								throw new Exception("insert galaxy_accounts_site_mapping_option_search_tmp error");
							}							
						}
					}	
				}

			}else{
				$oDB->vRollback();
				throw new Exception(" accounts_site_no=$sAccountsUuid  selectcount=$iSelectCount  exist");	
			}

			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCAccounts->vInsertSearchSqlTmp: '.$e->getMessage());
		}	


	}

	static public function vMakeSearchSession($aPost){

		if(count($aPost)){
			$session = self::$session;
			$oCUser = self::$session->get('oCurrentUser');
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
			$session->set("s_account_site_key",$sKey);
			$session->set("s_account_site_terms",$sTerms);

			$oDB = self::oDB(self::$sDBName);
			
			//刪除暫存
			$oDB->iQuery("DELETE FROM galaxy_accounts_site_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
			$oDB->iQuery("DELETE FROM galaxy_accounts_site_mapping_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
			$oDB->iQuery("DELETE FROM galaxy_accounts_site_mapping_option_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
			
		}

	}	

	static public  function vMakeSearchSql($iSelectCount=0){
		$session = self::$session;

		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');

		$sKey = $session->get("s_account_site_key");
		$sTerms =  $session->get("s_account_site_terms");
		
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_account_site_key","");
			$session->set("s_account_site_terms","");
			throw new Exception('oCAccounts->vMakeSearchSql: no terms key');
		}
		
		$iStart=$iSelectCount*10;
		
		switch($sTerms){
			default :
				$sSql = $sSql." (m.element_mapping_no=$sTerms AND m.element_mapping_value LIKE '%$sKey%') ";
				break;
		}		

		try{
			$sSql = "SELECT a.accounts_site_no FROM galaxy_accounts_site AS a  LEFT JOIN galaxy_accounts_site_mapping AS m ON m.accounts_site_no=a.accounts_site_no WHERE $sSql GROUP BY a.accounts_site_no LIMIT $iStart,10";

			//寫入
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				self::$__aSearchTemp[] = $aRow['accounts_site_no'];
			}	

		}catch (Exception $e){
			
			throw new Exception('oCAccounts->vMakeSearchSql: '.$e->getMessage());
		}	

	}

	static public function vMakeSearchSqlTmp($aPost=array()){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
	
		$oCUser = self::$session->get('oCurrentUser');
		
		try{
			if(count($aPost)){
				self::vMakeSearchSession($aPost);
			}

			$oDB->iQuery( "SELECT select_count FROM galaxy_accounts_site_search_tmp  WHERE search_user_no ={$oCUser->iUserNo}  ORDER BY select_count DESC LIMIT 0,1");
			if($aRow = $oDB->aFetchAssoc($iDbq))
				$iSelectCount = $aRow['select_count']+1;
			else
				$iSelectCount=0;
			$iCount = 0;
			while(count(self::$__aSearchTemp)<10){//搜尋結果不足10筆則繼續搜尋,超過10次則退出

				self::vMakeSearchSql($iSelectCount);
				if($iCount>10) break;
				$iCount++;
				$iSelectCount++;
			}

			if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) > 0){
				for($i=0;$i<count(self::$__aSearchTemp);$i++){
					self::vInsertSearchSqlTmp(self::$__aSearchTemp[$i],$iSelectCount,0);
				}	
			}else
				throw new Exception('  over 10 times search ');	


		}catch (Exception $e){
			
			throw new Exception('oCAccounts->vMakeSearchSqlTmp: '.$e->getMessage());
		}	
	}


	static public function vMakeAdvancedSearchSession($aPost){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
		
		$oCUser = self::$session->get('oCurrentUser');
		$aPuppetFields = array();
		$aTags = array();
		
		try{

			$oDB->vBegin();

			$aPuppetFields = array();
			$aPuppetLimit = array();
			$aTags = array();

			if(count($aPost)){
				
				//刪除暫存
				$oDB->iQuery("DELETE FROM galaxy_accounts_site_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
				$oDB->iQuery("DELETE FROM galaxy_accounts_site_mapping_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
				$oDB->iQuery("DELETE FROM galaxy_accounts_site_mapping_option_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
				
				$aPuppetLimit['new_limit'] = $aPost['new_limit'];
				$aPuppetLimit['brand_limit'] = $aPost['brand_limit'];
				$aPuppetLimit['project_no'] = $aPost['project_no'];

				$aPuppetLimit['last_use_date_limit_after'] = $aPost['last_use_date_limit_after'];
				$aPuppetLimit['last_use_date_limit'] = $aPost['last_use_date_limit'];
				$aPuppetLimit['login_count_limit'] = $aPost['login_count_limit'];
				
				foreach($aPost AS $key => $val){
					if(preg_match("/^puppets_element_mapping_no_([0-9]+)$/i",$key,$match)){
						if($aPost['puppets_element_mapping_no_'.$match[1]])
						$aPuppetFields[] = array("terms"=>$match[1],"key"=>$aPost['puppets_element_mapping_no_'.$match[1]],"option_key"=>array());
						
					}elseif(preg_match("/^puppets_element_mapping_option_no_([0-9]+)$/i",$key,$match)){	
						$aOption = array();
						if(count($aPost['puppets_element_mapping_option_no_'.$match[1]])){
							for($j=0;$j<count($aPost['puppets_element_mapping_option_no_'.$match[1]]);$j++){
								if($aPost['puppets_element_mapping_option_no_'.$match[1]][$j]){
									$aOption[] = $aPost['puppets_element_mapping_option_no_'.$match[1]][$j];
								}
							}			
						}
						if($aOption)
						$aPuppetFields[] = array("terms"=>$match[1],"key"=>"","option_key"=>$aOption);
						
						
					}
				}
				
				$aTags = $aPost['tag'];


			}else{
				$aPuppetFields = $session->get("s_account_advanced_key");
				$aPuppetLimit = $session->get("s_account_advanced_limit_key");
				$aTags = $session->get("s_account_advanced_tag");
			}	
			$sSql = "";
			$sSql2 = "";
			

			if(/*!$aPuppetLimit &&*/!$aPuppetFields && !$aTags) {
				$session->set("s_account_advanced_key","");
				$session->set("s_account_advanced_limit_key","");
				$session->set("s_account_advanced_tag","");
				$oDB->vRollback();
				throw new Exception("no search terms");	
			}
			
			/*if(count($aTags) < 1 &&  count($aPuppetFields) < 2 ){

				$oDB->vRollback();
				throw new Exception("too less terms");	

			}*/

			$session->set("s_account_advanced_key",$aPuppetFields);
			$session->set("s_account_advanced_limit_key",$aPuppetLimit);
			$session->set("s_account_advanced_tag",$aTags);
			

			
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCAccounts->vMakeAdvancedSearchSession: '.$e->getMessage());
		}	

	}	

	static public function vMakeAdvancedSearchSql($iSelectCount=0){
	
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);


		$oCUser = self::$session->get('oCurrentUser');
		$aPuppetFields = array();
		$aTags = array();

		$iStart=$iSelectCount*10;
		
		try{

			$aPuppetFields = array();
			$aPuppetLimit = array();
			$aTags = array();

			$aPuppetFields = $session->get("s_account_advanced_key");
			$aPuppetLimit = $session->get("s_account_advanced_limit_key");
			$aTags = $session->get("s_account_advanced_tag");

			$sSql = "";
			$sSql2 = "";
			

			if(/*!$aPuppetLimit &&*/ !$aPuppetFields && !$aTags) {
				$session->set("s_account_advanced_key","");
				$session->set("s_account_advanced_limit_key","");
				$session->set("s_account_advanced_tag","");
				$oDB->vRollback();
				throw new Exception("no search terms");	
			}
			/*
			if(count($aTags) < 1 &&  count($aPuppetFields) < 2 ){

				
				throw new Exception("too less terms");	

			}*/

			$session->set("s_account_advanced_key",$aPuppetFields);
			$session->set("s_account_advanced_limit_key",$aPuppetLimit);
			$session->set("s_account_advanced_tag",$aTags);

			//serialize site_no array
			$sSiteSql = "";
			$aPuppetLimit['site_no'] = unserialize(base64_decode($aPost['site_no']));
			if(is_array($aPuppetLimit['site_no']))
				$sSiteSql=" site_no IN(".implode(",",$aPuppetLimit['site_no']).")";	


			for($i=0;$i<count($aPuppetFields);$i++){
				
				$sSql = "";//身份欄位
				$sSql2 = "";//身份欄位選項
				

				$sTerms = $aPuppetFields[$i]['terms'];
				$sKey = $aPuppetFields[$i]['key'];
				
				if(count($aPuppetFields[$i]['option_key'])==0){
					$sSql = " (m.element_mapping_no=$sTerms AND m.element_mapping_value LIKE '%$sKey%') ";
				}else{
					
					for($j=0;$j<count($aPuppetFields[$i]['option_key']);$j++){
						$sKey = $aPuppetFields[$i]['option_key'][$j];
						if($sSql2)$sSql2 = $sSql2." AND ";
						$sSql2 = $sSql2." (element_mapping_no=$sTerms AND element_mapping_option_no ='$sKey')";
					}
					
				}

				//沒有搜尋結果
				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){
					
					if($sSql2){
						$sSql = "SELECT a.accounts_site_no FROM galaxy_accounts_site AS a WHERE a.accounts_site_no IN ( SELECT accounts_site_no FROM galaxy_accounts_site_mapping_option WHERE $sSql2 LIMIT $iStart,10 )";
					}else					
						$sSql = "SELECT a.accounts_site_no FROM galaxy_accounts_site AS a  LEFT JOIN galaxy_accounts_site_mapping AS m ON m.accounts_site_no=a.accounts_site_no WHERE $sSql  GROUP BY a.accounts_site_no LIMIT $iStart,10";


					$iDbq = $oDB->iQuery($sSql);
					while($aRow = $oDB->aFetchAssoc($iDbq)){

						//搜尋的是身份所以必須用此身份,去搜尋是否有網站帳號
						if($sSiteSql ){//必須要判斷網站
							
							$iDbq2 = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site WHERE accounts_site_no='".$aRow['accounts_site_no']."' AND $sSiteSql");
							$aRow2=$oDB->aFetchArray($iDbq2);
							if($aRow2['total']>0)
								self::$__aSearchTemp[] = $aRow['accounts_site_no'];
								
						}else		
							self::$__aSearchTemp[] = $aRow['accounts_site_no'];

						
					}	
					
				}else{//已經有上一次搜尋結果
					$aSearchTemp = self::$__aSearchTemp;
					for($j=0;$j<count(self::$__aSearchTemp);$j++){

						if($sSql2){
							$sSql3 = " SELECT accounts_site_no FROM galaxy_accounts_site_mapping_option WHERE $sSql2 AND accounts_site_no='".$aSearchTemp[$j]."'";
						}else 					
							$sSql3 = "SELECT a.accounts_site_no FROM galaxy_accounts_site AS a  LEFT JOIN galaxy_accounts_site_mapping AS m ON m.accounts_site_no=a.accounts_site_no WHERE $sSql  AND a.accounts_site_no='".$aSearchTemp[$j]."'";
						$iDbq = $oDB->iQuery($sSql3);
						if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
							array_splice(self::$__aSearchTemp,$j);
						}	
					}	

				}

				//其中一個欄位結果不符導致暫存變為空,不需要在搜尋
				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp)==0)
					throw new Exception('oCAccounts->vMakeAdvancedSearchSql: no result for $i field search');
			}
			
			if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0)
					throw new Exception('oCAccounts->vMakeAdvancedSearchSql: no result for fields'.count(self::$__aSearchTemp));

			if(count($aTags)>0) {
				
				$sSql = "";
				for($i=0;$i<count($aTags);$i++){
					$sKey = $aTags[$i];
					
					$sSql = $sSql." tag_no ='$sKey'";
				}

				//沒有搜尋結果
				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){
					$sSql = "SELECT table_no FROM galaxy_tag_log WHERE table_name='galaxy_accounts_site' AND $sSql LIMIT $iStart,10";
					$iDbq = $oDB->iQuery($sSql);
					while($aRow = $oDB->aFetchAssoc($iDbq)){
						//搜尋的是身份所以必須用此身份,去搜尋是否有網站帳號
						if($sSiteSql){//上一次沒有搜尋結果所以必須要判斷網站
							
							$iDbq2 = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site WHERE accounts_site_no='".$aRow['table_no']."' AND $sSiteSql");
							$aRow2=$oDB->aFetchArray($iDbq2);
							if($aRow2['total'])
								self::$__aSearchTemp[] = $aRow['table_no'];
						}else		
							self::$__aSearchTemp[] = $aRow['table_no'];
					}	

				}else{

					$sSql = "SELECT table_no FROM galaxy_tag_log WHERE table_name='galaxy_accounts_site' AND $sSql";
					$aSearchTemp = self::$__aSearchTemp;
					for($j=0;$j<count(self::$__aSearchTemp);$j++){

						$sSql2 = "$sSql AND table_no='".$aSearchTemp[$j]."'";
						
						$iDbq = $oDB->iQuery($sSql2);
						if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
							array_splice(self::$__aSearchTemp,$j);
						}	
					}
				}

				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0)
					throw new Exception('oCPuppets->vMakeAdvancedSearchSql: no result for tags');

			}	


			//使用紀錄搜尋

			//新註冊. 
			if($aPuppetLimit['new_limit'] ){

				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){
					if($sSiteSql)
						$sSql = "SELECT accounts_site_no FROM galaxy_accounts_new_log  WHERE $sSiteSql    ORDER BY registered_time  DESC  LIMIT $iStart,10 ";
					else 	$sSql = "SELECT accounts_site_no FROM galaxy_accounts_new_log  GROUP BY accounts_site_no   ORDER BY registered_time  DESC  LIMIT $iStart,10 ";
					
					$iDbq = $oDB->iQuery($sSql);
					while($aRow = $oDB->aFetchAssoc($iDbq)){
						self::$__aSearchTemp[] = $aRow['accounts_site_no'];
					}	

				}else{
					$sSql = "SELECT table_no FROM galaxy_tag_log WHERE table_name='galaxy_accounts_site' AND $sSql";
					$aSearchTemp = self::$__aSearchTemp;
					for($j=0;$j<count(self::$__aSearchTemp);$j++){

						
						$sSql = "SELECT accounts_site_no FROM galaxy_accounts_new_log  WHERE    accounts_site_no='".$aSearchTemp[$j]."'";
						
						$iDbq = $oDB->iQuery($sSql);
						if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
							array_splice(self::$__aSearchTemp,$j);
						}	
					}

				}	

			}else{
				//品牌互斥
				if($aPuppetLimit['brand_limit'] && $aPuppetLimit['project_no']){

					$oCompanyDB = self::oDB(self::$sCompanyDBName);
					$oOrderDB = self::oDB(self::$sOrderDBName);
					//此專案的品牌群組
					$sSql = "SELECT cb_id FROM order_sub    WHERE project_no = '".$aPuppetLimit['project_no']."'";
					$iDbq = $oOrderDB->iQuery($sSql);
					$aProjectBrand = array();
					if($aRow = $oOrderDB->aFetchAssoc($iDbq)){
						$sSql = "SELECT cbg_id FROM company_brand_group_rel    WHERE cb_id = '".$aRow['cb_id']."'";
						$iDbq = $oCompanyDB->iQuery($sSql);
						while($aRow = $oCompanyDB->aFetchAssoc($iDbq)){
							$aProjectBrand[] = $aRow['cbg_id'];
						}
					}
					
					//從最後發文紀錄,並且不同專案
					if (date('Y-m-d', strtotime($aPuppetLimit['last_use_date_limit'])) == $aPuppetLimit['last_use_date_limit']) {
						
						if($aPuppetLimit['last_use_date_limit_after'])
							$sSql = "SELECT accounts_site_no,cb_id FROM galaxy_accounts_lastpost_log    WHERE   project_no != '".$aPuppetLimit['project_no']."' AND last_time >= '".$aPuppetLimit['last_use_date_limit']."'";
						else
							$sSql = "SELECT accounts_site_no,cb_id FROM galaxy_accounts_lastpost_log    WHERE   project_no != '".$aPuppetLimit['project_no']."' AND last_time <= '".$aPuppetLimit['last_use_date_limit']."'";	
					
					}else{
						$sSql = "SELECT accounts_site_no,cb_id FROM galaxy_accounts_lastpost_log    WHERE project_no != '".$aPuppetLimit['project_no']."'";
					}


					if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){

						if($sSiteSql)
							$sSql = $sSql."  AND $sSiteSql  LIMIT $iStart,10";
						else
							$sSql = $sSql." LIMIT $iStart,10";
						$iDbq = $oDB->iQuery($sSql);
						while($aRow = $oDB->aFetchAssoc($iDbq)){

							$bIsMutex = false;
							$sSql = "SELECT cbg_id FROM company_brand_group_rel    WHERE cb_id = '".$aRow['cb_id']."'";
							$iDbq2 = $oCompanyDB->iQuery($sSql);
							while($aRow2 = $oCompanyDB->aFetchAssoc($iDbq2)){
								//品牌群組有在專案品牌群組陣列內則表示互斥
								if(in_array($aRow2['cbg_id'],$aProjectBrand)){
									$bIsMutex = true;
									break;
								}	
							}

							if(!$bIsMutex){
								self::$__aSearchTemp[] = $aRow['accounts_site_no'];
							}
						}

					}else{

						$aSearchTemp = self::$__aSearchTemp;
						for($j=0;$j<count(self::$__aSearchTemp);$j++){

							
							$sSql = $sSql." AND  accounts_site_no='".$aSearchTemp[$j]."'";
							
							$iDbq = $oDB->iQuery($sSql);
							
							if($aRow = $oDB->aFetchAssoc($iDbq)){//取得發文紀錄
								
								if($aRow['cb_id']){//有品牌發文紀錄	
									$bIsMutex = false;
									$sSql = "SELECT cbg_id FROM company_brand_group_rel    WHERE cb_id = '".$aRow['cb_id']."'";
									$iDbq2 = $oCompanyDB->iQuery($sSql);
									while($aRow2 = $oCompanyDB->aFetchAssoc($iDbq2)){
										//品牌群組有在專案品牌群組陣列內則表示互斥
										if(in_array($aRow2['cbg_id'],$aProjectBrand)){
											$bIsMutex = true;
											break;
										}	
									}

									if($bIsMutex){//互斥

										array_splice(self::$__aSearchTemp,$j);

									}
								}
							}
						}

					}	

				}else if (date('Y-m-d', strtotime($aPuppetLimit['last_use_date_limit'])) == $aPuppetLimit['last_use_date_limit']) {
					//多久時間內有發文
					
					if($aPuppetLimit['last_use_date_limit_after'])
						$sAfterSql = "SELECT accounts_site_no FROM galaxy_accounts_lastpost_log    WHERE  last_time >= '".$aPuppetLimit['last_use_date_limit']."' ";	
					else
						$sAfterSql2 = "SELECT accounts_site_no FROM galaxy_accounts_lastpost_log    WHERE accounts_site_no NOT IN( SELECT accounts_site_no FROM galaxy_accounts_lastpost_log   WHERE  last_time >= '".$aPuppetLimit['last_use_date_limit']."')";	

					if($aPuppetLimit['project_no'])
						$sProjectSql = " AND project_no != '".$aPuppetLimit['project_no']."'";

					if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){

						
						if($aPuppetLimit['last_use_date_limit_after'])
							$sSql = $sAfterSql.$sProjectSql;
						else
							$sSql = $sAfterSql2.$sProjectSql;
						
						if($sSiteSql)
							$sSql = $sSql."  AND $sSiteSql  ORDER BY last_time  DESC LIMIT $iStart,10";
						else
							$sSql = $sSql." ORDER BY last_time  DESC LIMIT $iStart,10";
						
						$iDbq = $oDB->iQuery($sSql);
						while($aRow = $oDB->aFetchAssoc($iDbq)){

							self::$__aSearchTemp[] = $aRow['accounts_site_no'];
						}

					}else{
						$aSearchTemp = self::$__aSearchTemp;
						for($j=0;$j<count(self::$__aSearchTemp);$j++){

							$sSql = "SELECT accounts_site_no FROM galaxy_accounts_lastpost_log    WHERE  last_time >= '".$aPuppetLimit['last_use_date_limit']."' $sProjectSql AND  accounts_site_no='".$aSearchTemp[$j]."'";	
						
							
							$iDbq = $oDB->iQuery($sSql);

							//有發文
							if($aPuppetLimit['last_use_date_limit_after']){

								if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除,期間內沒發文紀錄
									array_splice(self::$__aSearchTemp,$j);
								}	
							}else{//未發文

								if($oDB->aFetchAssoc($iDbq)){//條件不符移除,期間內有發文紀錄
									array_splice(self::$__aSearchTemp,$j);
								}

							}
						}

					}		
				}else if($aPuppetLimit['login_count_limit']>0){
					//上站次數
					$sSql = "SELECT accounts_site_no FROM galaxy_accounts_login_count_log WHERE  project_no != '".$aPuppetLimit['project_no']."' AND  login_count > ".$aPuppetLimit['login_count_limit']." ";				
					
					if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){

						if($sSiteSql)
							$sSql = $sSql."  AND $sSiteSql  ORDER BY last_time  DESC LIMIT $iStart,10";
						else
							$sSql = $sSql." ORDER BY last_time  DESC LIMIT $iStart,10";
						$iDbq = $oDB->iQuery($sSql);
						while($aRow = $oDB->aFetchAssoc($iDbq)){

							self::$__aSearchTemp[] = $aRow['accounts_site_no'];
						}

					}else{
						$aSearchTemp = self::$__aSearchTemp;
						for($j=0;$j<count(self::$__aSearchTemp);$j++){

							$sSql = $sSql." AND  accounts_site_no='".$aSearchTemp[$j]."'";
							
							$iDbq = $oDB->iQuery($sSql);
							if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
								array_splice(self::$__aSearchTemp,$j);
							}	
						}

					}

				}

			}

		}catch (Exception $e){
			
			throw new Exception('oCAccounts->vMakeAdvancedSearchSql: '.$e->getMessage());
		}	

	}

	static public function vMakeAdvancedSearchSqlTmp($aPost=array()){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
		
		$oCUser = self::$session->get('oCurrentUser');
		
		try{

			if(count($aPost)){
				self::vMakeAdvancedSearchSession($aPost);
			}

			$oDB->iQuery( "SELECT select_count FROM galaxy_accounts_site_search_tmp  WHERE search_user_no ={$oCUser->iUserNo}  ORDER BY select_count DESC LIMIT 0,1");
			if($aRow = $oDB->aFetchAssoc($iDbq))
				$iSelectCount = $aRow['select_count']+1;
			else
				$iSelectCount=0;
			$iCount = 0;
			while(count(self::$__aSearchTemp)<10){//搜尋結果不足10筆則繼續搜尋,超過10次則退出

				self::vMakeAdvancedSearchSql($iSelectCount);
				if($iCount>10) break;
				$iCount++;
				$iSelectCount++;
			}

			if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) > 0){
				for($i=0;$i<count(self::$__aSearchTemp);$i++){
					
					self::vInsertSearchSqlTmp(self::$__aSearchTemp[$i],$iSelectCount,$aPost['project_no']);
				}	
			}else
				throw new Exception('  over 10 times search ');	


		}catch (Exception $e){
			
			throw new Exception('oCAccounts->vMakeAdvancedSearchSqlTmp: '.$e->getMessage());
		}	

	}

	
	static public function iGetCountSearchTmp(){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(accounts_site_no) as total FROM galaxy_accounts_site_search_tmp ";
		
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);

		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}

	static public function aAllAccountsSearchTmp($sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllAccounts = array();
		$sSql = "SELECT a.* FROM galaxy_accounts_site_search_tmp AS a";
		if($sSearchSql!=='')
			$sSql .= " LEFT JOIN galaxy_accounts_site_mapping_search_tmp AS m ON m.accounts_site_no=a.accounts_site_no GROUP BY a.accounts_site_no";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllAccounts[] = new CAccounts($aRow);
		}
		return $aAllAccounts;
	}

	public function __construct($multiData){
		
		parent::__construct($multiData);
		
		if(!is_array($multiData))
			throw new Exception("CAccounts: __construct failed, require an array");
		//initialize vital member
		if(!isset($multiData['puppets_no']))
			throw new Exception("CAccounts: __construct failed, require an puppets_no");

		if(!isset($multiData['site_no']))
			throw new Exception("CAccounts: __construct failed, require an site_no");

		if(isset($multiData['accounts_site_no']))
			$this->sAccountsUuid = $multiData['accounts_site_no'];
		else
			$this->sAccountsUuid = CMisc::uuid_v1();	
		

		$this->sPuppetsUuid = $multiData['puppets_no'];
		$this->iSiteNo = $multiData['site_no'];

		//initialize optional member
		$this->sAccountsNote = $multiData['accounts_site_note'];
		if($multiData['proxy_no'] >=0 )
		$this->iProxyNo = $multiData['proxy_no'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['accounts_site_status'];

		$this->sCreateTime     = $multiData['createtime'];
		$this->sModifiedTime   = $multiData['modifiedtime'];
		$this->sRegisteredTime = $multiData['registeredtime'];
		$this->sLastUseTime    = $multiData['lastusetime'];
		$this->sLockTime       = $multiData['locktime'];

		$this->sEmailAccountsSiteNo = $multiData['email_accounts_site_no'];
		$this->sLoginAccountsSiteNo = $multiData['login_accounts_site_no'];
		
		
		//if got $multiData from $_POST, set puppets elements
		$aAccountsFields = array();
		//get all element_mapping_no that needs to be set
		foreach($multiData AS $key => $val){
			if(preg_match("/^element_mapping_option_no_([0-9]+)$/i",$key,$match)){	
				$aAccountsFields[] = $match[1];					
			}elseif(preg_match("/^element_mapping_no_([0-9]+)$/i",$key,$match)){
				
				$aAccountsFields[] = $match[1];
			}

		}
		$iOrder = 0;

		foreach ($aAccountsFields as $iEleMapNo) {
			$iOrder++;
			
			$aEleInit =array(	'accounts_site_no'=>$this->sAccountsUuid,
								'element_mapping_no'=>$iEleMapNo,
								'element_mapping_value'=>$multiData['element_mapping_no_'.$iEleMapNo],
								'fields_order'=>$iOrder,
								'fields_status'=>($multiData['fields_status_'.$iEleMapNo])?'1':'0'
								);
			$oCAccountsElement = new CAccountsElement($aEleInit);
			if(isset($multiData['element_mapping_option_no_'.$iEleMapNo])){

				$oCAccountsElement->vSetOption($multiData['element_mapping_option_no_'.$iEleMapNo]);
			}
			$this->vSetAccountsElement($oCAccountsElement);
		}
	}

	public function sAddAccounts(){
		$oDB = self::oDB(self::$sDBName);
		try{
			
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'accounts_site_no'=>$this->sAccountsUuid,
						'puppets_no'=>$this->sPuppetsUuid,
						'site_no'=>$this->iSiteNo,
						'accounts_site_note'=>$this->sAccountsNote,
						'accounts_site_status'=>$this->bStatus,
						'user_no'=>$this->__iUserNo,
						'createtime'=>$sDate,
						'modifiedtime'=>$sDate
					);
			$oDB->sInsert("galaxy_accounts_site",array_keys($aValues),array_values($aValues));
			//insert all puppet element
			foreach ($this->__aCAccountsElement as $oCAccountsElement) {
				$oCAccountsElement->sAddAccountsElement();
			}

			$this->iBlogCatalogGroupNo()->vAddUserLog("galaxy_accounts_site",$this->sProjectUuid,$_GET['func'],$_GET['action']);

			$oDB->vCommit();



			return $this->sPuppetsUuid;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCAccounts->sAddAccounts: '.$e->getMessage());
		}
	}

	/*
		update account data
		step1: $oCAccounts = CAccounts:oGetAccounts($sAccountsUuid) and create a CAccounts with all info
		step2: $oCAccounts>overwrite($oNewCAccounts);
		step3: call this function
	*/
	public function vUpdateAccounts(){
		$oDB = self::oDB(self::$sDBName);
		
		try{
			$oCUser = self::$session->get('oCurrentUser');
			$oDB->vBegin();
			//update puppets attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'accounts_site_note'=>$this->sAccountsNote,
						'proxy_no'=>$this->iProxyNo,
						'email_accounts_site_no' => $this->sEmailAccountsSiteNo,
						'login_accounts_site_no' => $this->sLoginAccountsSiteNo,
						'accounts_site_status'=>$this->bStatus,
						'user_no'=>$oCUser->__iUserNo,
						'modifiedtime'=>$sDate,
						'registeredtime'=>$this->sRegisteredTime,
					);
			$oDB->sUpdate("galaxy_accounts_site",array_keys($aValues),array_values($aValues),"accounts_site_no='{$this->sAccountsUuid}'");
			
			if($this->__aCAccountsElement) {
				//delete all before insert all puppets element
				$sSql = "DELETE FROM galaxy_accounts_site_mapping WHERE  accounts_site_no = '{$this->sAccountsUuid}'";
				$iRes = $oDB->iQuery($sSql);
				$sSql = "DELETE FROM galaxy_accounts_site_mapping_option WHERE  accounts_site_no = '{$this->sAccountsUuid}'";
				$iRes = $oDB->iQuery($sSql);
		
				foreach ($this->__aCAccountsElement as $oCAccountsElement) {
					$oCAccountsElement->sAddAccountsElement();
				}
			}	

			$oCUser->iBlogCatalogGroupNo()->vAddUserLog("galaxy_accounts_site",$this->sPuppetsUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CAccounts->vUpdateAccounts: '.$e->getMessage());
		}
	}


	/*
		add CAccountsElement
		if same element_mapping_no exist in this doc, this function will overwrite it
	*/
	public function vSetAccountsElement($oCAccountsElement){
		$this->__aCAccountsElement[$oCAccountsElement->iElementMappingNo] = $oCAccountsElement;
	}

	/*
		set & get doc element
	*/
	public function aAccountsElement(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCAccountsElement)){
			$this->__aCAccountsElement = CAccountsElement::aAllAccountsElement($this->sAccountsUuid);
		}
		return $this->__aCAccountsElement;
	}
	public function vOverWrite($oCAccounts){
		//if not a CAccounts object or uuid not match
		if(get_class($oCAccounts)!=='CAccounts' || $this->sAccountsUuid!==$oCAccounts->sAccountsUuid)
			throw new Exception('CAccounts->vOverWrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='sPuppetsUuid')
				continue;
			$this->$name = $oCAccounts->$name;	//overwrite
		}
	}
	public function vActiveAccounts($iFlag=0){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oCUser = self::$session->get('oCurrentUser');
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'accounts_site_status'=>$iFlag,
						'user_no'=>$oCUser->__iUserNo,
						'modifiedtime'=>$sDate
					);
			$oDB->sUpdate("galaxy_accounts_site",array_keys($aValues),array_values($aValues),"accounts_site_no='{$this->sAccountsUuid}'");
			
			$oCUser->vAddUserLog("galaxy_accounts_site",$this->sAccountsUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCAccounts->vActiveAccounts: '.$e->getMessage());
		}
	}

	/*	
		用pinyin（75）跟 englisg name(27) 生成帳號跟密碼, 有缺才補
	*/
	public function vResetAccountAndPassword() {

		$this->aAccountsElement();
			
		$pinyin = $this->__aCAccountsElement[75]->sValue;  // pinyin name

		if(!$pinyin) {
			throw new Exception('oCAccounts->vResetAccountAndPassword: no pinyin data');
		} 

		$pinyin_arr = explode(" ", $pinyin);

		$english_name = $this->__aCAccountsElement[27]->sValue;

		if(!$english_name) {
			throw new Exception('oCAccounts->vResetAccountAndPassword: no english_name data');
		} 
		
		if( !$this->__aCAccountsElement[1] ){
			$account = '';

			switch (mt_rand(1,10)) {
				case 1:
					$account  = $pinyin_arr[0];
					$account .= CPuppetsSource::sGetRandEnglishWord(8-strlen($pinyin_arr[0]));
					$account .= CPuppetsSource::sGetRandNumber(2);
					break;
				case 2:
					$account  = $pinyin_arr[1];
					$account .= CPuppetsSource::sGetRandNumber(2);
					$account .= CPuppetsSource::sGetRandEnglishWord(8-strlen($pinyin_arr[1]));	
					break;
				case 3:
					$account  = $pinyin_arr[2];			
					$account .= CPuppetsSource::sGetRandEnglishWord(8-strlen($pinyin_arr[2]));
					$account .= CPuppetsSource::sGetRandNumber(2);
					break;
				case 4:
					$account  = $pinyin_arr[2];			
					$account .= CPuppetsSource::sGetRandEnglishWord(6-strlen($pinyin_arr[2]));
					$account .= CPuppetsSource::sGetRandNumber(4);
					break;	
				case 5:
					$account  = $pinyin_arr[2];			
					$account .= CPuppetsSource::sGetRandEnglishWord(4-strlen($pinyin_arr[2]));
					$account .= CPuppetsSource::sGetRandNumber(4);
					break;	
				case 6:
					$account  = $pinyin_arr[2];			
					$account .= CPuppetsSource::sGetRandEnglishWord(6-strlen($pinyin_arr[2]));
					$account .= CPuppetsSource::sGetRandNumber(2);
					break;		
				case 7:
					$account  = $pinyin_arr[2];			
					$account .= CPuppetsSource::sGetRandEnglishWord(7-strlen($pinyin_arr[2]));
					$account .= CPuppetsSource::sGetRandNumber(1);
					break;		
				case 8:
					$account  = $pinyin_arr[2];
					$account .= CPuppetsSource::sGetRandNumber(2);
					$account .= CPuppetsSource::sGetRandEnglishWord(8-strlen($pinyin_arr[2]));
					break;	
				case 9:
					$account  = $english_name;
					$account .= CPuppetsSource::sGetRandNumber(2);
					$account .= CPuppetsSource::sGetRandEnglishWord(8-strlen($english_name));
					break;		
				case 10:
					$account  = $english_name;
					$account .= CPuppetsSource::sGetRandEnglishWord(9-strlen($english_name));
					$account .= CPuppetsSource::sGetRandNumber(1);
					break;		
			}

			$aEleInit =array(	'accounts_site_no'=>$this->sAccountsUuid,
						'element_mapping_no'=>1,
						'element_mapping_value'=>strtolower($account),
						'fields_order'=>0,
						'fields_status'=>1
						);
			$oCAccountsElement = new CAccountsElement($aEleInit);
			$this->__aCAccountsElement[1] = $oCAccountsElement;

		}
	

		if( !$this->__aCAccountsElement[2] ){
			switch (mt_rand(1,3)) {
				case 1:
					$passwd .= ucfirst(CPuppetsSource::sGetRandEnglishWord(8));
					$passwd .= CPuppetsSource::sGetRandNumber(2);
					break;	
				case 2:
					$passwd .= strtolower(CPuppetsSource::sGetRandEnglishWord(4));
					$passwd .= CPuppetsSource::sGetRandNumber(2);
					$passwd .= strtoupper(CPuppetsSource::sGetRandEnglishWord(4));
					break;	
				case 3:
					$passwd .= strtolower(CPuppetsSource::sGetRandEnglishWord(2));
					$passwd .= strtoupper(CPuppetsSource::sGetRandEnglishWord(4));
					$passwd .= CPuppetsSource::sGetRandNumber(4);
					break;	
			}
			$aEleInit =array(	'accounts_site_no'=>$this->sAccountsUuid,
						'element_mapping_no'=>2,
						'element_mapping_value'=>$passwd,
						'fields_order'=>0,
						'fields_status'=>1
						);
			$oCAccountsElement = new CAccountsElement($aEleInit);
			$this->__aCAccountsElement[2] = $oCAccountsElement;
		}

	}

	public function vResetEmail($sEmailAddress) {

		if($this->__aCAccountsElement[9])
			return;

		$aEleInit =array(	'accounts_site_no'=>$this->sAccountsUuid,
					'element_mapping_no'=>9,
					'element_mapping_value'=>$sEmailAddress,
					'fields_order'=>0,
					'fields_status'=>1
					);
		$oCAccountsElement = new CAccountsElement($aEleInit);
		$this->__aCAccountsElement[9] = $oCAccountsElement;
	}

	/*
	// 
	// 
	// 
	// 取得N個還沒被註冊的網站帳號
	//
	function aGetUnRegisterAccountsSite($num = 1, $site_no, $gender_option_no, $ZIP) {
		global $PuppetsDbh;
		
		$now = date("Y-m-d H:i:s");
					
		$sql = "SELECT * 
					FROM `galaxy_accounts_site_mapping`
					WHERE `accounts_site_no` IN 
					(SELECT `accounts_site_no` 
						FROM  `galaxy_accounts_site_mapping_option` 
						WHERE `accounts_site_no` IN 
							(SELECT `accounts_site_no` 
								FROM `galaxy_accounts_site`
								WHERE  `galaxy_accounts_site`.`site_no` = $site_no
								AND `galaxy_accounts_site`.`registeredtime` = '0000-00-00 00:00:00'					
								AND '$now' > `locktime`
							) 
						AND `element_mapping_no` = 8
						AND `element_mapping_option_no` = $gender_option_no
					)
				AND `element_mapping_value` = '$ZIP'
				AND `element_mapping_no` = 28
				LIMIT $num";
	
		$iRes = $PuppetsDbh->iQuery($sql);
		
		while($fe = $PuppetsDbh->aFetchAssoc($iRes)) {			
			$aRow[] = $fe;			
		}

		if(!$aRow) return null;
			
		return $aRow;
	}
	

	function aChooseUnRegAccountSite($site_no, $email_site_no) {
		global $PuppetsDbh;

		$now = date("Y-m-d H:i:s");

		$sql = "SELECT *
					FROM `galaxy_accounts_site` 
					WHERE `site_no` = $site_no
					AND `registeredtime` = '0000-00-00 00:00:00'
					AND '$now' > `locktime`
					AND `accounts_no` 
					IN (select `accounts_no` FROM `galaxy_accounts_site` WHERE `registeredtime` != '0000-00-00 00:00:00' AND `site_no` = $email_site_no)
					LIMIT 1";
 
		$iRes = $PuppetsDbh->iQuery($sql);	
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);
			
		return ($fe)?$fe:null;	
	}

	function aNotRegisterEmailAccountsSite($site_no) {
		global $PuppetsDbh;

		$now = date("Y-m-d H:i:s");

		$sql = "SELECT *
					FROM `galaxy_accounts_site` 
					WHERE `site_no` = $site_no
					AND `registeredtime` = '0000-00-00 00:00:00'
					AND '$now' > `locktime`
					LIMIT 1";

		$iRes = $PuppetsDbh->iQuery($sql);	
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);
			
		return ($fe)?$fe:null;	
	}

	function aNotRegisterAccountsSite($site_no, $proxy_no, $register=false) {
		global $PuppetsDbh;

		$now = date("Y-m-d H:i:s");

		$sql = "SELECT * 
					FROM `galaxy_accounts_site`
					WHERE `proxy_no` = $proxy_no
					AND `site_no` = $site_no
					AND '$now' > `locktime`";

		if($register) 
			$sql .= "AND `registeredtime` != '0000-00-00 00:00:00'";
		else 
			$sql .= "AND `registeredtime` = '0000-00-00 00:00:00'";

		$sql .= "LIMIT 1";

		$iRes = $PuppetsDbh->iQuery($sql);	
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);


		if(!$fe) return null;
			
		return $fe;	
	}

	

	
	
	

	
	// 
	//  鎖定被安排註冊的帳號
	 
	function bLockAccountsSite($accounts_site_no, $locktime) {
		global $PuppetsDbh;
			
		$data = array();

		$data['locktime'] = $locktime;
				
		$aField = array_keys($data);
		
		$aValue = array_values($data);
						
		$sql = $PuppetsDbh->sUpdate("galaxy_accounts_site", $aField, $aValue, "`accounts_site_no` = '$accounts_site_no'");

		if(!$sql) 	
			return false;
		return true;
		
	}
	

	


	function aGetAccountsSiteValue($accounts_site_no, $element_mapping_no) {
		global $PuppetsDbh;

		$sql = "SELECT *
					FROM `galaxy_accounts_site_mapping` 
					WHERE `accounts_site_no` = '$accounts_site_no'
					AND `element_mapping_no` = $element_mapping_no
					AND `fields_status` = 1";
	
		$iRes = $PuppetsDbh->iQuery($sql);			
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);

		if(!fe)	
			return false;
		else 
			return $fe;
	}

	function aGetSiteOptionNo($accounts_site_no, $element_mapping_no) {
		global $PuppetsDbh;

		$sql = "SELECT `element_mapping_option_no`
					FROM `galaxy_accounts_site_mapping_option` 
					WHERE `accounts_site_no` = '$accounts_site_no'
					AND `element_mapping_no` = $element_mapping_no";

		$iRes = $PuppetsDbh->iQuery($sql);			
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);	

		if(!$fe) return null;

		return $fe['element_mapping_option_no'];
	}

	function aGetSiteOptionName($accounts_site_no, $element_mapping_no) {
		global $PuppetsDbh , $CElementMappingOption;

		$sql = "SELECT `element_mapping_option_name`
					FROM `galaxy_accounts_site_mapping_option` 
					WHERE `accounts_site_no` = '$accounts_site_no'
					AND `element_mapping_no` = $element_mapping_no";

		$iRes = $PuppetsDbh->iQuery($sql);			
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);	

		if(!$fe) return null;

		return $fe['element_mapping_option_name'];
	}



	function sGetMappingValue($accounts_site_no, $element_mapping_no) {
		global $PuppetsDbh, $CPuppets;

		//$element_mapping_no =100;
		//echo $element_mapping_no;

		$accounts_site = $this->aGetAccountsSiteValue($accounts_site_no, $element_mapping_no);
		if($accounts_site['element_mapping_value']) return $accounts_site['element_mapping_value'];

		$option_no = $this->aGetSiteOptionNo($accounts_site_no, $element_mapping_no);
		if($option_no) return array($option_no);
		
	
		
		
		$puppets_no = $this->sGetPuppetsByAccountSite($accounts_site_no);

		$puppets_value = $CPuppets->getMappingVal($puppets_no, $element_mapping_no);
		if($puppets_value) return $puppets_value;

		$puppets_option_no = $CPuppets->getOptionNo($puppets_no, $element_mapping_no);
		if($puppets_option_no) return array($puppets_option_no);

		return "";
	}

	//
	// 取得身份資料 當取出來的是選項時候return name 出去
	//
	//	
	function sGetMappingValueName($accounts_site_no, $element_mapping_no) {
		global $PuppetsDbh, $CPuppets;

		$accounts_site = $this->aGetAccountsSiteValue($accounts_site_no, $element_mapping_no);
		if($accounts_site['element_mapping_value']) return $accounts_site['element_mapping_value'];

		$option_name = $this->aGetSiteOptionName($accounts_site_no, $element_mapping_no);
		if($option_name) return $option_name;
		
		

		$puppets_no = $this->sGetPuppetsByAccountSite($accounts_site_no);

		$puppets_value = $CPuppets->getMappingVal($puppets_no, $element_mapping_no);
		if($puppets_value) return $puppets_value;

		$puppets_option_name = $CPuppets->getOptionName($puppets_no, $element_mapping_no);
		if($puppets_option_name) return $puppets_option_name;

		return "";
	}

	
	function sGetPuppetsByAccountSite($accounts_site_no) {
		global $PuppetsDbh;

		$sql = "SELECT `puppets_no`
					FROM `galaxy_accounts_site` 
					WHERE `accounts_site_no` = '$accounts_site_no'";

		$iRes = $PuppetsDbh->iQuery($sql);	
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);

		return $fe['puppets_no'];		
	}


	

	
	function bIsExistMapping($accounts_site_no, $element_mapping_no) {
		global $PuppetsDbh;

		$sql = "SELECT 1 
					FROM `galaxy_accounts_site_mapping`
					WHERE `accounts_site_no` = '$accounts_site_no'
					AND `element_mapping_no` = $element_mapping_no";

		$iRes = $PuppetsDbh->iQuery($sql);	
		$fe   = $PuppetsDbh->aFetchAssoc($iRes);

		if(!$fe) return false;

		return true;		
	}*/
}

?>