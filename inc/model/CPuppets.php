<?php
/**
 * 身份
 */
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CPuppetsElement.php');
include_once('../inc/model/CPuppetsSource.php');
include_once('../inc/model/CAccounts.php');
include_once('../inc/pinyin.class.php');
class CPuppets extends CGalaxyClass
{

	static protected  $aGroupName = array("all","beauty","goods");
	private $sPuppetsUuid;	//uuid
	public $sPuppetsNote;	//描述
	public $iProxyNo;		//使用線路流水號
	public $aGroup = array();	//身份可使用群組
	//database setting
	static protected $sDBName = 'PUPPETS';
	static protected $sLogDBName = 'GENESIS_LOG';
	static protected $sCompanyDBName = 'COMPANY';
	static protected $sOrderDBName = 'ORDER';
	
	private $__aCPuppetsElement;	//CAUTION: this is a map of element_mapping_no to CPuppetsElement object
	static private $__aSearchTemp = array();//search tmp array
	private $__aCAccount;


	//php default function, let private member become read-only class member for others
	public function __get($varName)
	{
		return $this->$varName;
	}

	static public	function aGetGroupName(){
		return self::$aGroupName;
	}

	/**
	 * get accounts
	 *
	 * @access	public
	 * @param	none
	 * @return	array
	 * @author	benjamin
	 */
	public function aAccounts(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCAccount)){
			$this->__aCAccount = CAccounts::aAllAccounts("`puppets_no` = '$this->sPuppetsUuid' ");
		}
		return $this->__aCAccount;
	}

	public function vFillElementMapping($element_mapping_no) {

		switch ($element_mapping_no) {
			case 1:	
					$pinyin = $this->__aCPuppetsElement[75]->sValue;  // pinyin name
					$pinyin_arr = explode(" ", $pinyin);

					$english_name = $this->__aCPuppetsElement[27]->sValue;

					//CMisc::vPrintR($this->__aCPuppetsElement);

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

					$this->__aCPuppetsElement[1] = new stdClass();
					$this->__aCPuppetsElement[1]->sValue = strtolower($account);
					$this->__aCPuppetsElement[1]->aOption = array();
					$this->__aCPuppetsElement[1]->bStatus = 1;
					$this->__aCPuppetsElement[1]->iOrder = 0;
					//$pinyin = $this->__aCPuppetsElement[27]->sValue;
					//CMisc::vPrintR($this->__aCPuppetsElement[75]->sValue);
					
				break;
			case 2:
					switch (mt_rand(1,3)) {
						case 1:
							$passwd .= ucfirst(CPuppetsSource::sGetRandEnglishWord(8));
							$passwd .= CPuppetsSource::sGetRandNumber(2);
							break;	
						case 2:
							$passwd .= CPuppetsSource::sGetRandEnglishWord(4);
							$passwd .= CPuppetsSource::sGetRandNumber(2);
							$passwd .= strtoupper(CPuppetsSource::sGetRandEnglishWord(4));
							break;	
						case 3:
							$passwd .= CPuppetsSource::sGetRandEnglishWord(2);
							$passwd .= strtoupper(CPuppetsSource::sGetRandEnglishWord(4));
							$passwd .= CPuppetsSource::sGetRandNumber(4);
							break;	
					}

					$this->__aCPuppetsElement[2] = new stdClass();
					$this->__aCPuppetsElement[2]->sValue = strtolower($passwd);
					$this->__aCPuppetsElement[2]->aOption = array();
					$this->__aCPuppetsElement[2]->bStatus = 1;
					$this->__aCPuppetsElement[2]->iOrder = 0;
				break;	
			case 9:
				
				break;		
			
			default:
				# code...
				break;
		}

	}


	/**
	 * 取得身份資料
	 *	
	 * @param string $puppets_no 身份序號
	 */
	static public  function oGetPuppets($sPuppetsUuid) {
		$oDB = self::oDB(self::$sDBName);

		$sSql = "SELECT * FROM galaxy_puppets WHERE puppets_no = '$sPuppetsUuid'";
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$sSql = "SELECT group_no FROM galaxy_puppets_group WHERE puppets_no = '$sPuppetsUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aGroupNo = array();
		while($aRow2 = $oDB->aFetchAssoc($iDbq)){
			
			$aGroupNo[] = $aRow2['group_no'];
		}
		$aRow['puppets_group'] = $aGroupNo;

		$oCPuppets = new CPuppets($aRow);
		$oCPuppets->aPuppetsElement();
		//$oCPuppets->aAccounts();


		return $oCPuppets;

		
	}

	/*
		get all puppet in an array
		if $sSearchSql is given, query only match puppets
		example: CPuppets::aAllDoc(','ORDER BY createtime DESC LIMIT 0,10')
		CAUTION: this function may query lots of data from genesis_docs DB, make sure you need all of these docs
	*/
	static public function aAllPuppets($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllPuppets = array();
		$sSql = "SELECT * FROM galaxy_puppets AS p";
		
		if($sSearchSql!=='') {
			$sSql .= " LEFT JOIN galaxy_puppets_mapping AS m ON m.puppets_no=p.puppets_no";
			$sSql .= " LEFT JOIN galaxy_puppets_mapping_option AS o ON m.puppets_no=o.puppets_no";
			$sSql .= " WHERE $sSearchSql ";
			$sSql .= " GROUP BY p.puppets_no";
		}
		
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
	
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllPuppets[] = new CPuppets($aRow);
		}
		return $aAllPuppets;
	}
	/*
		get count of puppet which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(p.puppets_no) as total FROM galaxy_puppets AS p";
		

		if($sSearchSql!==''){
			$sSql .= " LEFT JOIN galaxy_puppets_mapping AS m ON m.puppets_no=p.puppets_no WHERE $sSearchSql GROUP BY p.puppets_no";

		}

		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}
	static public function aGetElementMappingUnion() {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT `element_mapping_no` 
					FROM `galaxy_puppets_mapping` 
					GROUP BY `element_mapping_no`";	
					
		$iRes = $oDB->iQuery($sSql);
		
		while($fe = $oDB->aFetchAssoc($iRes)){
			$aRow[] = $fe;
		}
		
		return $aRow;
	 }
	 static public function aGetElementMappingValueUnion($iElementMappingNo) {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT *, count(`element_mapping_value`) AS `total`
					FROM `galaxy_puppets_mapping` 
					WHERE `element_mapping_no` = $iElementMappingNo 
					GROUP BY `element_mapping_value`";	
					
		$iRes = $oDB->iQuery($sSql);
		
		while($fe = $oDB->aFetchAssoc($iRes)){
			$aRow[] = $fe;
		}
		
		return $aRow;
	}

	
	static public function aAllPuppetsTemp($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllPuppets = array();
		$sSql = "SELECT * FROM galaxy_puppets_tmp";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllPuppets[] = $aRow;
		}
		return $aAllPuppets;
	}
	static public function aGetPuppetsElementTemp($sPuppetsUuid,$iElementMappingNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_puppets_mapping_tmp WHERE `puppets_no`='$sPuppetsUuid' AND `element_mapping_no`=$iElementMappingNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		
		
		return $aRow;
	}
	static public function iGetPuppetsTempCount(){
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT count(puppets_no) as total FROM galaxy_puppets_tmp");
		
					
		$aRow=$oDB->aFetchArray($iDbq);
		
		if($aRow['total']){
			return $aRow['total'];
		}else
			return 0;

	} 
	static public function vClearMakePuppetsTmp(){
		$oDB = self::oDB(self::$sDBName);
		
		$oDB->iQuery("TRUNCATE TABLE  `galaxy_puppets_tmp`");
		$oDB->iQuery("TRUNCATE TABLE  `galaxy_puppets_mapping_tmp`");
		$oDB->iQuery("TRUNCATE TABLE  `galaxy_puppets_mapping_option_tmp`");

	} 
	static public function vMakePuppets($aPuppetDefaultFieldsData,$aPostData){
		


		$oDB = self::oDB(self::$sDBName);
		try{
			
			$oDB->vBegin();
			

			
			$tmp_count=0;
			//將身份有缺的欄位寫入暫存,優先補足有缺欄位的身份
			for($i = 0; $i < count($aPuppetDefaultFieldsData); $i++) {

				$element_mapping_no = $aPuppetDefaultFieldsData[$i]['element']->iElementMappingNo;
				//取得沒有此欄位的身份
				$iDbq = $oDB->iQuery("SELECT puppets_no,proxy_no FROM `galaxy_puppets` 
					WHERE puppets_status=1 AND puppets_no NOT IN(SELECT puppets_no FROM `galaxy_puppets_mapping` WHERE element_mapping_no=$element_mapping_no) 
					");
				

				while($aRow = $oDB->aFetchArray($iDbq)) {
					$iDbq_exist = $oDB->iQuery("SELECT puppets_no FROM galaxy_puppets_tmp WHERE puppets_no ='".$aRow['puppets_no']."'");	
					//不存在
					if(!$oDB->aFetchArray($iDbq_exist)){
						
						//將此身份資料寫到暫存
						$aFields=array("puppets_no","proxy_no","tmp_status");
						$aValues=array($aRow['puppets_no'],$aRow['proxy_no'],1);
						$sSql = $oDB->sInsert("galaxy_puppets_tmp",$aFields,$aValues);
						if(!$sSql){
							$oDB->vRollback();
							throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."01");
												
						}
						
						$tmp_count++;
						if($tmp_count>=$aPostData['make_nums']) break;
					}
					


				}
				

				//取得有此欄位但沒有值或選項的身份
				$iDbq = $oDB->iQuery("SELECT puppets_no,proxy_no FROM `galaxy_puppets` 
					WHERE puppets_status=1 AND puppets_no  IN(SELECT puppets_no FROM `galaxy_puppets_mapping` WHERE element_mapping_no=$element_mapping_no AND element_mapping_value='') 
					");

				while($aRow = $oDB->aFetchArray($iDbq)) {
					
					$iDbq_exist = $oDB->iQuery("SELECT element_mapping_option_no FROM galaxy_puppets_mapping_option WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no=$element_mapping_no LIMIT 0,1");	
					//不存在選項
					if(!$oDB->aFetchArray($iDbq_exist)){

						$iDbq_exist = $oDB->iQuery("SELECT puppets_no FROM galaxy_puppets_tmp WHERE puppets_no ='".$aRow['puppets_no']."'");	
						//不存在
						if(!$oDB->aFetchArray($iDbq_exist)){
							
							//將此身份資料寫到暫存
							$aFields=array("puppets_no","proxy_no","tmp_status");
							$aValues=array($aRow['puppets_no'],$aRow['proxy_no'],1);
							$sSql = $oDB->sInsert("galaxy_puppets_tmp",$aFields,$aValues);
							if(!$sSql){
								$oDB->vRollback();
								throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."01");
													
							}
							
							$tmp_count++;
							if($tmp_count>=$aPostData['make_nums']) break;	
						}
					}
					

				}
				
				$iDbq = $oDB->iQuery("SELECT puppets_no FROM `galaxy_puppets_tmp` WHERE tmp_status=1 ");
				while($aRow = $oDB->aFetchArray($iDbq)) {
						
					//身份原本欄位
					$iDbq2 = $oDB->iQuery("SELECT element_mapping_no,element_mapping_value FROM `galaxy_puppets_mapping` WHERE puppets_no ='".$aRow['puppets_no']."'");
					while($aRow2 = $oDB->aFetchArray($iDbq2)) {
						
						$iDbq_exist = $oDB->iQuery("SELECT element_mapping_no FROM galaxy_puppets_mapping_tmp WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);	
					
						//不存在
						if(!$oDB->aFetchArray($iDbq_exist)){

							$aFields=array("puppets_no","element_mapping_no","element_mapping_value","tmp_status");
							$aValues=array($aRow['puppets_no'],$aRow2['element_mapping_no'],$aRow2['element_mapping_value'],1);
							
							$sSql = $oDB->sInsert("galaxy_puppets_mapping_tmp",$aFields,$aValues);

							if(!$sSql){
								$oDB->vRollback();
								throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."02");	
							}
							//option
							$iDbq3 = $oDB->iQuery("SELECT element_mapping_option_no FROM `galaxy_puppets_mapping_option` WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no='".$aRow2['element_mapping_no']."'");

							while($aRow3 = $oDB->aFetchArray($iDbq3)) {
								$aFields=array("puppets_no","element_mapping_no","element_mapping_option_no","tmp_status");
								$aValues=array($aRow['puppets_no'],$aRow2['element_mapping_no'],$aRow3['element_mapping_option_no'],1);
					
								$sSql = $oDB->sInsert("galaxy_puppets_mapping_option_tmp",$aFields,$aValues);

								if(!$sSql){
									$oDB->vRollback();
									throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."03");	
								}							
							}
						}	
					}
					$iDbq_exist = $oDB->iQuery("SELECT element_mapping_no FROM galaxy_puppets_mapping_tmp WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no=$element_mapping_no");	
					
					//不存在
					if(!$oDB->aFetchArray($iDbq_exist)){
						//新欄位
						$aFields=array("puppets_no","element_mapping_no","element_mapping_value","tmp_status");
						$aValues=array($aRow['puppets_no'],$element_mapping_no,"",0);
						
						$sSql = $oDB->sInsert("galaxy_puppets_mapping_tmp",$aFields,$aValues);
						
						if(!$sSql){
							$oDB->vRollback();
							throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."04");	
						}
					}	

				}

				if($tmp_count>=$aPostData['make_nums']) break;


			}
			
			//統計暫存的數量
			$iDbq = $oDB->iQuery("SELECT count(puppets_no) as total FROM galaxy_puppets_tmp");
			
						
			$aRow=$oDB->aFetchArray($iDbq);
			//要產生的數量
			$make_total =$aPostData['make_nums'];
			if($aRow['total']){			
				$make_total = $make_total -$aRow['total'];
				//$make_total = $make_total -$aRow['total'];
			}
			
			//還需產生新身份暫存
			if($make_total>0){

				for($i=0;$i<$make_total;$i++){

					$sPuppetNo = CMisc::uuid_v1();
					$aFields=array("puppets_no","proxy_no","tmp_status");
					$aValues=array($sPuppetNo,0,0);
					$sSql = $oDB->sInsert("galaxy_puppets_tmp",$aFields,$aValues);
					if(!$sSql){
						$oDB->vRollback();
						throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."05");	
					}
					for($j = 0; $j < count($aPuppetDefaultFieldsData); $j++) {
						$element_mapping_no = $aPuppetDefaultFieldsData[$j]['element']->iElementMappingNo;
						//新欄位
						$aFields=array("puppets_no","element_mapping_no","element_mapping_value","tmp_status");
						$aValues=array($sPuppetNo,$element_mapping_no,"",0);
						
						$sSql = $oDB->sInsert("galaxy_puppets_mapping_tmp",$aFields,$aValues);

						if(!$sSql){
							$oDB->vRollback();
							throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."06");	
						}
					}	
				}


			}


			$oDB->vCommit();

			//產生每個欄位 option 的數量
			$make_option = array();
			
			for($i = 0; $i < count($aPuppetDefaultFieldsData); $i++) {
				$name = $aPuppetDefaultFieldsData[$i]['name'];
				$element_mapping_no = $aPuppetDefaultFieldsData[$i]['element']->iElementMappingNo;
				
				$make_total2 = $aPostData['make_nums'];
				
				if($aPuppetDefaultFieldsData[$i]['element']->aOption){
					
					// CMisc::vPrintR($aPostData);
					

					if($aPostData[$name.'_random']){//random
						
						$percentageTotal = 0;
						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){
							$aPostData[$name][$val->iElementOptionNo] = mt_rand(0,100);//百分比
							$percentageTotal=$percentageTotal+$aPostData[$name][$val->iElementOptionNo];//百分比
						}

						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){
							
							
							$percentage=$aPostData[$name][$val->iElementOptionNo];//百分比
							
							$amount=0;//數量
							if($percentage && $percentageTotal){

								$amount=floor($make_total*($percentage/$percentageTotal));
								if($amount==0) $amount = $make_total2;
								if($amount < 0) $amount =0;
							}
							$make_total2 = $make_total2 - $amount;
							$make_option[$name][]=array("element_mapping_no"=>$element_mapping_no,"element_mapping_option_no"=>$val->iElementOptionNo,"amount"=>$amount);
						}

					}else{
						
						//有可能沒有設定百分比而沒有要產生的選項
						//所以暫存的身份欄位可能就沒有值或沒有選項
			//CMisc::vPrintR($aPuppetDefaultFieldsData);			
			//CMisc::vPrintR($aPostData);			
						
						$percentageTotal = 0;
						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){

							$percentageTotal=$percentageTotal+$aPostData[$name][$val->iElementOptionNo];//百分比

						}	
						

						foreach($aPuppetDefaultFieldsData[$i]['element']->aOption AS $key => $val){

							$percentage=$aPostData[$name][$val->iElementOptionNo];//百分比
						
							$amount=0;//數量
							if($percentage){

								$amount=floor($aPostData['make_nums']*($percentage/$percentageTotal));
								// CMisc::vPrintR($aPostData['make_nums']);
								// CMisc::vPrintR($percentage);
								// CMisc::vPrintR($percentageTotal);
								//CMisc::vPrintR("amout: ".$amount);
								/// 
								if($amount==0) $amount = $make_total2;
								if($amount < 0) $amount =0;
								$make_total2 = $make_total2 - $amount;

							// CMisc::vPrintR($percentage);
							// CMisc::vPrintR($make_option);
								// CMisc::vPrintR(':');
								// CMisc::vPrintR($make_total2);
								// CMisc::vPrintR(':');
								// CMisc::vPrintR($val->iElementOptionNo);
								// CMisc::vPrintR('<br>');
								//exit;


								$make_option[$name][]=array("element_mapping_no"=>$element_mapping_no,"element_mapping_option_no"=>$val->iElementOptionNo,"amount"=>$amount);
							}
							
						}


					}

				}
			}
				// CMisc::vPrintR($make_option);
			//寫入暫存,因為有些欄位有從屬關係所以自訂產生

			//gender
			self::vGenerateOption("gender",$make_option['gender']);
			//lastName
			self::vGenerateValue("lastName",($aPostData['lastName_exist'])?true:false);
			//firstName
			self::vGenerateValue("firstName",($aPostData['firstName_exist'])?true:false);
			//allName
			self::vGenerateValue("allName",($aPostData['allName_exist'])?true:false);
			//pinyinAllName
			self::vGenerateValue("pinyinAllName",($aPostData['pinyinAllName_exist'])?true:false);
			//pinyinLastName
			self::vGenerateValue("pinyinLastName",($aPostData['pinyinLastName_exist'])?true:false);
			//pinyinFirstName
			self::vGenerateValue("pinyinFirstName",($aPostData['pinyinFirstName_exist'])?true:false);
			//englishName
			self::vGenerateValue("englishName",($aPostData['englishName_exist'])?true:false);
			//nickName
			self::vGenerateValue("nickName",($aPostData['nickName_exist'])?true:false);
			//ID
			self::vGenerateValue("ID",($aPostData['ID_exist'])?true:false);
			//yearOption
			self::vGenerateOption("yearOption",$make_option['yearOption']);
			//month
			self::vGenerateOption("month",$make_option['month']);
			//dayOption
			self::vGenerateOption("dayOption",$make_option['dayOption']);
			//country
			//counties
			//town
			self::vGenerateOption("town",$make_option['town']);
			//zip
			self::vGenerateValue("zip",false);
			//countryCode
			self::vGenerateValue("countryCode",false);
			//address
			self::vGenerateValue("address",false);
			//dayTele
			self::vGenerateValue("dayTele",($aPostData['dayTele_exist'])?true:false);
			//phone
			self::vGenerateValue("phone",($aPostData['phone_exist'])?true:false);
			//edu
			self::vGenerateOption("edu",$make_option['edu']);
			//job
			self::vGenerateOption("job",$make_option['job']);
			//income
			self::vGenerateOption("income",$make_option['income']);
			//marry
			self::vGenerateOption("marry",$make_option['marry']);





		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppets->vMakePuppets: '.$e->getMessage());
		}
	}

	//欄位option寫入身份 ,單選
	//array : element_mapping_no element_mapping_option_no amount
	//可能因為要產生的選項需要其他選項的判斷才能寫入,會導致某些身份沒有欄位選項可寫入
	static public function vGenerateOption($fieldName="",$optionData=array()) {

		$oDB = self::oDB(self::$sDBName);

		if(!$optionData || !$fieldName) {

			return;

		}
		$oDB->vBegin();


//CMisc::vPrintR($optionData);
		for($i=0;$i<count($optionData);$i++){
			$element_mapping_option_no = $optionData[$i]['element_mapping_option_no'];
			$element_mapping_no = $optionData[$i]['element_mapping_no'];
			$element_mapping_option_value = CPuppetsField::$_field[$fieldName]['option'][$element_mapping_option_no];
			
			//暫存身份中有此選項的數量
			$iDbq = $oDB->iQuery("SELECT count(puppets_no) AS total FROM `galaxy_puppets_mapping_option_tmp` WHERE element_mapping_option_no=$element_mapping_option_no");
			$aRow=$oDB->aFetchArray($iDbq);
			$amount = $optionData[$i]['amount']-$aRow['total'];
			
			if($amount<0){
				
				continue;//數量已有
			}	
			
			//取得沒有此欄位選項的身份
			//單選,選項資料中沒有任一屬此欄位
			$iDbq = $oDB->iQuery("SELECT puppets_no FROM `galaxy_puppets_tmp` 
			WHERE  puppets_no NOT IN(SELECT puppets_no FROM `galaxy_puppets_mapping_option_tmp` WHERE element_mapping_no=$element_mapping_no ) 
			ORDER BY RAND() ");

			
			
			$c=0;
			while($aRow = $oDB->aFetchArray($iDbq)) {

				$aPuppetTmpData=self::aGetMakePuppetsTmpData($aRow['puppets_no']);
				//option 有其他的條件需要判斷
				$bOk = true;
				switch($fieldName){
					case "dayOption":

						$y = key($aPuppetTmpData['yearOption']['option_mapping']);
						$m = key($aPuppetTmpData['month']['option_mapping']);

						if(!$y || !$m) $bOk = false;
						elseif(!checkdate ( $m,$element_mapping_option_value,$y)){
							$bOk = false;
						}

						break;
					case "edu":
						$age = date("Y") - key($aPuppetTmpData['yearOption']['option_mapping']);
						$bOk = false;
						switch($element_mapping_option_value){
							case "1"://國中
								if($age > 11 && $age < 15) $bOk = true;
								break;
							case "2"://高中（高職）
								 if($age > 14 && $age < 20) $bOk = true;
								 break;
							case "3"://大學
								if($age > 19 && $age < 25) $bOk = true;
								break;
							default:
								if($age) $bOk = true;
								break;			
						}
						break;
					case "job":
						$age = date("Y") - key($aPuppetTmpData['yearOption']['option_mapping']);
						switch($element_mapping_option_value){
							case "9"://學生
								if( $age > 25)	$bOk = false;
								break;							
							default:
								if( $age < 25)	$bOk = false;
								break;			
						}
						break;
					case "income":
						$age = date("Y") - key($aPuppetTmpData['yearOption']['option_mapping']);
						switch($element_mapping_option_value){
							case "6"://無收入
								break;
							case "7"://NT10,000以下
								if($age < 14) $bOk = false;
								break;
							case "8"://NT10,001~NT20,000
								if($age < 19) $bOk = false;
								break;
							default:
								if($age < 25) $bOk = false;
								break;			
						}
						break;	
					case "marry":
						$age = date("Y") - key($aPuppetTmpData['yearOption']['option_mapping']);
						switch($element_mapping_option_value){
							case "0"://未婚
								break;
							case "1"://已婚
								if($age < 25) $bOk = false;
								break;
							
						}
						break;		
							
				}
				if(!$bOk) continue;//條件必須符合才能寫入選項
				
				if(!self::bInsertMakePuppetsOptionTmp($aRow['puppets_no'],$element_mapping_no,$element_mapping_option_no,0)){
				
					$oDB->vRollback();
					self::vClearMakePuppetsTmp();
					throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."07");	
				}	
				
				//相關欄位寫入
				if($fieldName=="town"){
					
					

					//counties
					$counties_element_mapping_option_no = 0;
					if(CPuppetsField::$_field['counties']['option_range']){
						foreach(CPuppetsField::$_field['counties']['option_range'] AS $key => $val){
							if(in_array($element_mapping_option_no, $val)){
								$counties_element_mapping_option_no = $key;
					
								if(!self::bInsertMakePuppetsOptionTmp($aRow['puppets_no'],CPuppetsField::$_field['counties']['element_mapping_no'],$counties_element_mapping_option_no,0)){
					
									$oDB->vRollback();
									self::vClearMakePuppetsTmp();
									throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."07");	
									
								}

								break;
							}
						}	
					}	

					//country
					$country_element_mapping_option_no=0;
					if($counties_element_mapping_option_no && CPuppetsField::$_field['country']['option_range']){
						foreach(CPuppetsField::$_field['country']['option_range'] AS $key => $val){
							if(in_array($counties_element_mapping_option_no, $val)){
								$country_element_mapping_option_no = $key;
								if(!self::bInsertMakePuppetsOptionTmp($aRow['puppets_no'],CPuppetsField::$_field['counties']['element_mapping_no'],$country_element_mapping_option_no,0)){
					
									$oDB->vRollback();
									self::vClearMakePuppetsTmp();
									throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."07");	
								}

								break;
							}
						}	
					}
					//proxy
					if(!$aPuppetTmpData['proxy_no']){

						$proxy = CProxy::aGetProxyByZIP($element_mapping_option_value);
						

						$aFields=array("proxy_no","tmp_status");
						$aValues=array($proxy['proxy_id'],($aPuppetTmpData['tmp_status'])?2:0);//原本狀態是1 屬於舊身份欄位只是沒有值 所以改寫狀態為2 update, 0 新欄位

						$sSql = $oDB->sUpdate("galaxy_puppets_tmp",$aFields,$aValues,"puppets_no='".$aRow['puppets_no']."'");

						if(!$sSql){
							$oDB->vRollback();
							self::vClearMakePuppetsTmp();
							throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."07");	
						}
					}

				}

				$c++;
				if($c>=$amount) break;


				
			}	
		}





		$oDB->vCommit();

	}

	static public function getTwID($gender) {  

	    $city = array(
	                'A'=>1,'I'=>39,'O'=>48,'B'=>10,'C'=>19,'D'=>28,
	                'E'=>37,'F'=>46,'G'=>55,'H'=>64,'J'=>73,'K'=>82,
	                'L'=>2,'M'=>11,'N'=>20,'P'=>29,'Q'=>38,'R'=>47,
	                'S'=>56,'T'=>65,'U'=>74,'V'=>83,'W'=>21,'X'=>3,
	                'Y'=>12,'Z'=>30
	            );

	    //建立隨機身份證碼
	    if($gender == 1) 
	    	$gender = 1;
	    else 
	    	$gender = 2;

	    $id = chr(65).$gender.substr(array_pop(explode('.',uniqid(rand(),true))),0,7);  
	 
	    //計算總分  
	    $total = $city[$id[0]]; 

	    for($i=1;$i<=8;$i++) 
	        $total += $id[$i]*(9-$i);   

	    //補上最後檢查碼  
	    return $id.substr((10-substr($total, -1)),-1);  
	}

	//產生欄位的value
	static public function vGenerateValue($fieldName="",$isExist=false){
		$oDB = self::oDB(self::$sDBName);
		if(!$fieldName) {

			return;

		}
		$element_mapping_no = CPuppetsField::$_field[$fieldName]['element_mapping_no'];


		if(!$element_mapping_no){
			return;
		}


		$prefix = array("0932","0933","0934","0937","0921",
						"0928","0910","0911","0912","0963",
						"0919","0972","0988","0930","0931",
						"0926","0916","0917","0954","0955",
						"0935","0936","0939","0920","0922",
						"0914","0918","0952","0953","0958",
						"0961","0970","0987","0982");

		$oDB->vBegin();
		$iDbq = $oDB->iQuery("SELECT puppets_no FROM `galaxy_puppets_tmp` 
					WHERE  puppets_no  IN(SELECT puppets_no FROM `galaxy_puppets_mapping_tmp` WHERE element_mapping_no=$element_mapping_no AND element_mapping_value='' ) 
					");
		while($aRow = $oDB->aFetchArray($iDbq)) {
			$puppets_no = $aRow['puppets_no'];
			$aPuppetTmpData=self::aGetMakePuppetsTmpData($puppets_no);
			
			$value="";	
			switch($fieldName){

				case "lastName":
					$value=CPuppetsSource::getLastName();

					break;
				case "firstName":
					
					//if($aPuppetTmpData['gender']['option_mapping']['1'] || $aPuppetTmpData['gender']['option_mapping']['0']){
						$gender = ($aPuppetTmpData['gender']['option_mapping']['1'])?1:0; //1 男 0 女
						$value=CPuppetsSource::getFirstName($gender);
					//}

					
					break;
				case "allName":
					if($aPuppetTmpData['lastName']['element_mapping_value'] && $aPuppetTmpData['firstName']['element_mapping_value'] )
						$value=$aPuppetTmpData['lastName']['element_mapping_value'].$aPuppetTmpData['firstName']['element_mapping_value'] ;
						
					break;
				case "pinyinAllName":
					if($aPuppetTmpData['allName']['element_mapping_value']){
						$str = iconv('UTF-8', 'GBK//IGNORE', $aPuppetTmpData['allName']['element_mapping_value']);
						$res = pinyin::convstr($str, pinyin::PY_SPACE);
						$res = iconv('GBK', 'UTF-8//IGNORE', $res);
						if($res)
							$value=$res;
											
					}
					break;
				case "pinyinLastName":
					if($aPuppetTmpData['pinyinAllName']['element_mapping_value']){
						$tmp = explode(" ", $aPuppetTmpData['pinyinAllName']['element_mapping_value']);
						$value = $tmp[0];
					}	
					break;
				case "pinyinFirstName":
					if($aPuppetTmpData['pinyinAllName']['element_mapping_value']){
						$tmp = explode(" ", $aPuppetTmpData['pinyinAllName']['element_mapping_value']);
						$value = $tmp[1]." ".$tmp[2];
					}	
					break;
				case "englishName":
					if($aPuppetTmpData['gender']['option_mapping']['1'] || $aPuppetTmpData['gender']['option_mapping']['0']){
						$gender = ($aPuppetTmpData['gender']['option_mapping']['1'])?1:0; //1 男 0 女
						$value=CPuppetsSource::getEnglishName($gender);
					}
					break;
				case "nickName":
					if($aPuppetTmpData['gender']['option_mapping']['1'] || $aPuppetTmpData['gender']['option_mapping']['0']){
						$gender = ($aPuppetTmpData['gender']['option_mapping']['1'])?1:0; //1 男 0 女
						$value=CPuppetsSource::getNickName(mt_rand(2,5),$gender);
					}
					break;
				case "ID":
					if($aPuppetTmpData['gender']['option_mapping']['1'] || $aPuppetTmpData['gender']['option_mapping']['0']){
						$gender = ($aPuppetTmpData['gender']['option_mapping']['1'])?1:0; //1 男 0 女
						$value=self::getTwID($gender);
					}
					break;	
				case "zip":
					$zipCode = key($aPuppetTmpData['town']['option_mapping']);
					if($zipCode){
						
						$value=$zipCode;
					}
					break;		
				case "countryCode":
					$countryCode="";
					if($aPuppetTmpData['country']['option_mapping']['tw']){
						$countryCode ="+886";
					}	
					if($countryCode){
						
						$value=$countryCode;
					}
					break;		
				case "address":
					$zipCode = key($aPuppetTmpData['town']['option_mapping']);
					if($zipCode){
						
						$value=CPuppetsSource::getRoad($zipCode);
					}
					break;
				case "dayTele":
					
					if($aPuppetTmpData['country']['option_mapping']['tw']){
						$zipCode = key($aPuppetTmpData['town']['option_mapping']);
						$counties = key($aPuppetTmpData['counties']['option_mapping']);
						$Tele_area_map=array(
							"taipei"=>"02"
							);	
						$Tele_map = array(
							"100" => "23",
							"103" => "25",
							"104" => "25",
							"105" => "27",
							"106" => "27",
							"108" => "23",
							"110" => "27",
							"111" => "28",
							"112" => "28",
							"114" => "27",
							"115" => "26",
							"116" => "29",
							);	
						$value = "(".$Tele_area_map[$counties].")".$Tele_map[$zipCode].mt_rand(131242,999999);
					}
					
					break;
				case "phone":
				
					
		
					$prefix_phone_number = $prefix[mt_rand(0, count($prefix)-1)];
					$number = array();	
					for($i = 0; $i < 6; $i++) {
						$number[] = mt_rand(0,9);
					}	
					
					array_unshift($number,$prefix_phone_number);
					$value = implode("",$number);
					unset($number);

					bereak;	

				

			}	


			if(!$value) continue;
			if($isExist){//判斷重複性
				$iDbq_exist = $oDB->iQuery("SELECT puppets_no FROM galaxy_puppets_mapping_tmp WHERE puppets_no!='$puppets_no' AND element_mapping_no=$element_mapping_no AND element_mapping_value='$value'");	
				//存在
				if($oDB->aFetchArray($iDbq_exist)) continue;
			}		

			

			if(!self::bInsertMakePuppetsValueTmp($puppets_no,$element_mapping_no,$value)){
				$oDB->vRollback();
				self::vClearMakePuppetsTmp();
				throw new Exception(_LANG_PUPPETS_MAKE_FAILURE."08");	
			}	
				

		}
		$oDB->vCommit();



	}

	static public function vMakePuppetsSave(){
		$oDB = self::oDB(self::$sDBName);

		$iDbq = $oDB->iQuery("SELECT count(puppets_no) AS total FROM `galaxy_puppets_tmp`");
		$aRow=$oDB->aFetchArray($iDbq);
		
		if($aRow['total']==0) {

			throw new Exception('CPuppets::vMakePuppetsSave:empty');

		}

		$oDB->iQuery("LOCK TABLE galaxy_puppets_tmp WRITE,galaxy_puppets_mapping_tmp WRITE,galaxy_puppets_mapping_option_tmp WRITE");


		$iDbq = $oDB->iQuery("SELECT puppets_no,proxy_no,tmp_status FROM `galaxy_puppets_tmp`");
		while($aRow = $oDB->aFetchArray($iDbq)) {
			$oDB->vBegin();
			$bOk = true;
			
			$iDbq_exist = $oDB->iQuery("SELECT puppets_no FROM galaxy_puppets WHERE puppets_no='".$aRow['puppets_no']."'");	
			
			if($oDB->iNumRows($iDbq_exist)){

				$aFields=array("modifiedtime");
				$aValues=array(date('Y-m-d H:i:s'));	

				if($aRow['tmp_status'] ==2){ //屬於舊身份欄位只是沒有值 所以狀態為2
				
					array_push($aFields, 'proxy_no');
					array_push($aValues, $aRow["proxy_no"]);

				}	

				$sSql = $oDB->sUpdate("galaxy_puppets",$aFields,$aValues,"puppets_no='".$aRow['puppets_no']."'");

				if(!$sSql){
					$bOk = false;					
				}

			}else{
				$sDate = date("Y-m-d H:i:s");	
				//insert puppet
				 $oCUser = self::$session->get('oCurrentUser');

				$aFields=array("puppets_no","proxy_no","user_no","createtime","modifiedtime");
				$aValues=array($aRow['puppets_no'],$aRow['proxy_no'],$oCUser->iUserNo,$sDate,$sDate);
			
				$sSql = $oDB->sInsert("galaxy_puppets",$aFields,$aValues);

				if(!$sSql){
					$bOk = false;
					
				}

			}


			if($bOk){
				$iDbq2 = $oDB->iQuery("SELECT element_mapping_no,element_mapping_value,tmp_status FROM `galaxy_puppets_mapping_tmp` WHERE puppets_no ='".$aRow['puppets_no']."'");
				while($aRow2 = $oDB->aFetchArray($iDbq2)) {
					

					$iDbq_exist = $oDB->iQuery("SELECT puppets_no FROM galaxy_puppets_mapping WHERE puppets_no='".$aRow['puppets_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);	
			
					if($oDB->iNumRows($iDbq_exist)){
						
						if($aRow2['tmp_status'] ==2){ //屬於舊身份欄位只是沒有值 所以狀態為2
							$aFields=array("element_mapping_value");
							$aValues=array($aRow2['element_mapping_value']);//原本狀態是1 屬於舊身份欄位只是沒有值 所以改寫狀態為2 update, 0 新欄位
							
							$sSql = $oDB->sUpdate("galaxy_puppets_mapping",$aFields,$aValues,"puppets_no='".$aRow['puppets_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);

							if(!$sSql){
								$bOk = false;
								break;
							}
						}	
					}else{
						$aFields=array("puppets_no","element_mapping_no","element_mapping_value");
						$aValues=array($aRow['puppets_no'],$aRow2['element_mapping_no'],$aRow2['element_mapping_value']);
				
						$sSql = $oDB->sInsert("galaxy_puppets_mapping",$aFields,$aValues);

						if(!$sSql){
							$bOk = false;
							break;
						}

					}
					

					//option
					$iDbq3 = $oDB->iQuery("SELECT element_mapping_option_no,tmp_status FROM `galaxy_puppets_mapping_option_tmp` WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);

					while($aRow3 = $oDB->aFetchArray($iDbq3)) {
						

						$iDbq_exist = $oDB->iQuery("SELECT puppets_no FROM galaxy_puppets_mapping_option WHERE puppets_no='".$aRow['puppets_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']." AND element_mapping_option_no=".$aRow3['element_mapping_option_no']);	
						
						if($oDB->iNumRows($iDbq_exist)==0){

							//insert
							$aFields=array("puppets_no","element_mapping_no","element_mapping_option_no");
							$aValues=array($aRow['puppets_no'],$aRow2['element_mapping_no'],$aRow3['element_mapping_option_no']);
							$sSql = $oDB->sInsert("galaxy_puppets_mapping_option",$aFields,$aValues);

							if(!$sSql){
								$bOk = false;
								break;
							}

						}	

					}	

				}

			}

			$oDB->iQuery("DELETE FROM galaxy_puppets_tmp WHERE puppets_no='".$aRow['puppets_no']."'");
			$oDB->iQuery("DELETE FROM galaxy_puppets_mapping_tmp WHERE puppets_no='".$aRow['puppets_no']."'");	
			$oDB->iQuery("DELETE FROM galaxy_puppets_mapping_option_tmp WHERE puppets_no='".$aRow['puppets_no']."'");	

			if(!$bOk){
				$oDB->vRollback();
				continue;
			}

			$oDB->vCommit();

		}
		

		$oDB->iQuery("UNLOCK TABLES");


	}

	//取得暫存中的身份資料,將資料轉成 CPuppetsField::_field 格式
	static public function aGetMakePuppetsTmpData($puppets_no){
		$oDB = self::oDB(self::$sDBName);
		$aPuppetData = array();
		
		$iDbq = $oDB->iQuery("SELECT puppets_no,proxy_no,tmp_status FROM `galaxy_puppets_tmp` WHERE puppets_no='$puppets_no'");
		$aRow = $oDB->aFetchArray($iDbq);
		if(!$aRow) return $aPuppetData;


		$aPuppetData['proxy_no'] = $aRow['proxy_no'];
		$aPuppetData['tmp_status'] = $aRow['tmp_status'];

		$iDbq = $oDB->iQuery("SELECT element_mapping_no,element_mapping_value,tmp_status FROM `galaxy_puppets_mapping_tmp` WHERE puppets_no ='$puppets_no'");
		while($aRow = $oDB->aFetchArray($iDbq)) {
			//將所有欄位轉成預設欄位的格式
			foreach(CPuppetsField::$_field AS $key => $val){
				
				if($aRow['element_mapping_no'] == $val['element_mapping_no']){
					
					$aPuppetData[$key]['element_mapping_no']=$aRow['element_mapping_no'];

					$aPuppetData[$key]['element_mapping_value']=$aRow['element_mapping_value'];
					$aPuppetData[$key]['tmp_status']=$aRow['tmp_status'];
					//option
					$iDbq2 = $oDB->iQuery("SELECT element_mapping_option_no,tmp_status FROM `galaxy_puppets_mapping_option_tmp` WHERE puppets_no ='".$puppets_no."' AND element_mapping_no='".$aRow['element_mapping_no']."'");

					
					while($aRow2 = $oDB->aFetchArray($iDbq2)) {

						
						$element_mapping_option_no = $aRow2['element_mapping_option_no'];
						
						if($val['option']){ //這個只是要讓後續的code 不用去判斷哪個 element_mapping_option_no 代表哪個選項 EX. $aPuppetData['gender']['option_mapping'][1] 就是選項男  
							$m = $val['option'][$element_mapping_option_no];
							$aPuppetData[$key]['option_mapping'][$m]=$element_mapping_option_no;
						}	
						//讓後續方便寫入資料庫
						$aPuppetData[$key]['option'][] = array("element_mapping_option_no"=>$element_mapping_option_no,"tmp_status"=>$aRow2['tmp_status']);

					}	

					break;
				}	
			}	

		}
		return $aPuppetData;
	}

	
	//寫入暫存身份選項
	static public function bInsertMakePuppetsOptionTmp($puppets_no,$element_mapping_no,$element_mapping_option_no,$tmp_status){
		$oDB = self::oDB(self::$sDBName);
		
		$iDbq = $oDB->iQuery("SELECT puppets_no  FROM galaxy_puppets_mapping_option_tmp WHERE puppets_no='$puppets_no' AND element_mapping_no=$element_mapping_no AND element_mapping_option_no=$element_mapping_option_no");	
		
		$aRow =$oDB->aFetchArray($iDbq);
		if(!$aRow){
			$aFields=array("puppets_no","element_mapping_no","element_mapping_option_no","tmp_status");
			$aValues=array($puppets_no,$element_mapping_no,$element_mapping_option_no,0);

			$sSql = $oDB->sInsert("galaxy_puppets_mapping_option_tmp",$aFields,$aValues);

			if(!$sSql){
				return false;
			}
		}	
		return true;

	}
	static public function bInsertMakePuppetsValueTmp($puppets_no,$element_mapping_no,$element_mapping_value){
		
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT puppets_no,tmp_status FROM galaxy_puppets_mapping_tmp WHERE puppets_no='$puppets_no' AND element_mapping_no=$element_mapping_no");	
		
		$aRow =$oDB->aFetchArray($iDbq);
		if($aRow){
			$aFields=array("element_mapping_value","tmp_status");
			$aValues=array($element_mapping_value,($aRow['tmp_status'])?2:0);//原本狀態是1 屬於舊身份欄位只是沒有值 所以改寫狀態為2 update, 0 新欄位

			$sSql = $oDB->sUpdate("galaxy_puppets_mapping_tmp",$aFields,$aValues,"puppets_no='$puppets_no' AND element_mapping_no=$element_mapping_no");

			if(!$sSql){
				return false;
			}
		}
		return true;

	}


	static public function vInsertSearchSqlTmp($sPuppetsUuid,$iSelectCount,$sProjectUuid){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');

		try{
			$iDbq = $oDB->iQuery("SELECT * FROM `galaxy_puppets` WHERE puppets_no ='".$sPuppetsUuid."'");
			$aRow = $oDB->aFetchArray($iDbq2);
			if(!$aRow){
				
				throw new Exception("insert galaxy_puppets  $sPuppetsUuid not exist");	
			}

			$oDB->vBegin();
			$iDbq_exist = $oDB->iQuery("SELECT * FROM galaxy_puppets_search_tmp WHERE puppets_no ='".$sPuppetsUuid."' AND user_no={$oCUser->iUserNo} AND select_count = ".($iSelectCount+1));	
			//不存在
			if(!$oDB->aFetchArray($iDbq_exist)){
				
				$aValues = array(	'puppets_no'=>$sPuppetsUuid,
							'puppets_note'=>$aRow['puppets_note'],
							'createtime'=>$aRow['createtime'],
							'modifiedtime'=>$aRow['modifiedtime'],
							'proxy_no'=>$aRow['proxy_no'],
							'os_no'=>$aRow['os_no'],
							'user_no'=>$aRow['user_no'],
							'puppets_status'=>$aRow['puppets_status'],

							'select_count'=>$iSelectCount+1,
							'project_no'=>$sProjectUuid,
							'search_user_no'=>$oCUser->iUserNo,
							'expirytime'=>date("Y-m-d H:i:s", time() + $session->_session_lifetime ) 
						);
				$sSql = $oDB->sInsert("galaxy_puppets_search_tmp",array_keys($aValues),array_values($aValues));
				if(!$sSql){
					$oDB->vRollback();
					throw new Exception("insert galaxy_puppets_search_tmp error");	
				}
				//身份原本欄位
				$iDbq2 = $oDB->iQuery("SELECT * FROM `galaxy_puppets_mapping` WHERE puppets_no ='".$aRow['puppets_no']."'");
				while($aRow2 = $oDB->aFetchArray($iDbq2)) {
					
					$iDbq_exist = $oDB->iQuery("SELECT element_mapping_no FROM galaxy_puppets_mapping_tmp WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no=".$aRow2['element_mapping_no']);	
				
					//不存在
					if(!$oDB->aFetchArray($iDbq_exist)){

						
						$aValues = array('puppets_no'=>$sPuppetsUuid,
								'element_mapping_no'=>$aRow2['element_mapping_no'],
								'element_mapping_value'=>$aRow2['element_mapping_value'],
								'fields_status'=>$aRow2['fields_status'],
								'fields_order'=>$aRow2['fields_order'],
								'select_count'=>$iSelectCount+1,
								'search_user_no'=>$oCUser->iUserNo,
								'expirytime'=>date("Y-m-d H:i:s", time() + $session->_session_lifetime ) 
						);
						$sSql = $oDB->sInsert("galaxy_puppets_mapping_search_tmp",array_keys($aValues),array_values($aValues));

						if(!$sSql){
							$oDB->vRollback();
							throw new Exception("insert galaxy_puppets_mapping_search_tmp error");	
						}
						//option
						$iDbq3 = $oDB->iQuery("SELECT element_mapping_option_no FROM `galaxy_puppets_mapping_option` WHERE puppets_no ='".$aRow['puppets_no']."' AND element_mapping_no='".$aRow2['element_mapping_no']."'");

						while($aRow3 = $oDB->aFetchArray($iDbq3)) {
							
							$aValues = array('puppets_no'=>$sPuppetsUuid,
									'element_mapping_no'=>$aRow2['element_mapping_no'],
									'element_mapping_option_no'=>$aRow3['element_mapping_option_no'],
									'select_count'=>$iSelectCount+1,
									'search_user_no'=>$oCUser->iUserNo,
									'expirytime'=>date("Y-m-d H:i:s", time() + $session->_session_lifetime ) 
							);
							$sSql = $oDB->sInsert("galaxy_puppets_mapping_option_search_tmp",array_keys($aValues),array_values($aValues));

							if(!$sSql){
								$oDB->vRollback();
								throw new Exception("insert galaxy_puppets_mapping_option_search_tmp error");
							}							
						}
					}	
				}

			}else{
				$oDB->vRollback();
				throw new Exception(" puppets_no=$sPuppetsUuid  selectcount=$iSelectCount  exist");	
			}

			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppets->vInsertSearchSqlTmp: '.$e->getMessage());
		}	


	}

	static public function vMakeSearchSession($aPost){

		if(count($aPost)){
			$session = self::$session;
			$oCUser = self::$session->get('oCurrentUser');
			$sKey = trim($aPost['s_key']);
			$sTerms = trim($aPost['s_terms']);
			$session->set("s_puppet_key",$sKey);
			$session->set("s_puppet_terms",$sTerms);
			$oDB = self::oDB(self::$sDBName);
			
			//刪除暫存
			$oDB->iQuery("DELETE FROM galaxy_puppets_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
			$oDB->iQuery("DELETE FROM galaxy_puppets_mapping_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
			$oDB->iQuery("DELETE FROM galaxy_puppets_mapping_option_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
			
		}

	}	

	static public  function vMakeSearchSql($iSelectCount=0){
		$session = self::$session;

		$oDB = self::oDB(self::$sDBName);
		$oCUser = self::$session->get('oCurrentUser');
		
		

		$sKey = $session->get("s_puppet_key");
		$sTerms =  $session->get("s_puppet_terms");
		
		$sSql = "";
		
		if(!$sKey) {
			$session->set("s_puppet_key","");
			$session->set("s_puppet_terms","");
			throw new Exception('oCPuppets->vMakeSearchSql: no terms');
		}
		

		$iStart=$iSelectCount*10;
		
		switch($sTerms){
			
			default :
				$sSql = $sSql." (m.element_mapping_no=$sTerms AND m.element_mapping_value LIKE '%$sKey%') ";
				break;
		}		

		try{
			
			
			
							
			$sSql = "SELECT p.puppets_no FROM galaxy_puppets AS p  LEFT JOIN galaxy_puppets_mapping AS m ON m.puppets_no=p.puppets_no WHERE $sSql GROUP BY p.puppets_no LIMIT $iStart,10";

			//寫入
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				self::$__aSearchTemp[] = $aRow['puppets_no'];
			}	
				
			

		}catch (Exception $e){
			
			throw new Exception('oCPuppets->vMakeSearchSql: '.$e->getMessage());
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

			$oDB->iQuery( "SELECT select_count FROM galaxy_puppets_search_tmp  WHERE search_user_no ={$oCUser->iUserNo}  ORDER BY select_count DESC LIMIT 0,1");
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
			
			throw new Exception('oCPuppets->vMakeSearchSqlTmp: '.$e->getMessage());
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
				$oDB->iQuery("DELETE FROM galaxy_puppets_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
				$oDB->iQuery("DELETE FROM galaxy_puppets_mapping_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
				$oDB->iQuery("DELETE FROM galaxy_puppets_mapping_option_search_tmp WHERE search_user_no ={$oCUser->iUserNo} OR expirytime  < '" . date("Y-m-d H:i:s", time()) . "'");
				
				
				$aPuppetLimit['new_limit'] = $aPost['new_limit'];
				$aPuppetLimit['brand_limit'] = $aPost['brand_limit'];
				

				$aPuppetLimit['noregister_limit'] = $aPost['noregister_limit'];
				$aPuppetLimit['create_date_limit'] = $aPost['create_date_limit'];



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
				$aPuppetFields = $session->get("s_puppet_advanced_key");
				$aPuppetLimit = $session->get("s_puppet_advanced_limit_key");
				$aTags = $session->get("s_puppet_advanced_tag");
			}	
			$sSql = "";
			$sSql2 = "";
			

			if(!$aPuppetLimit && !$aPuppetFields && !$aTags) {
				$session->set("s_puppet_advanced_key","");
				$session->set("s_puppet_advanced_limit_key","");
				$session->set("s_puppet_advanced_tag","");
				$oDB->vRollback();
				throw new Exception("no search terms");	
			}
			
			/*if(count($aTags) < 1 &&  count($aPuppetFields) < 2 ){

				$oDB->vRollback();
				throw new Exception("too less terms");	

			}*/

			$session->set("s_puppet_advanced_key",$aPuppetFields);
			$session->set("s_puppet_advanced_limit_key",$aPuppetLimit);
			$session->set("s_puppet_advanced_tag",$aTags);
			

			
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppets->vMakeAdvancedSearchSession: '.$e->getMessage());
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
			
			$aPuppetFields = $session->get("s_puppet_advanced_key");
			$aPuppetLimit = $session->get("s_puppet_advanced_limit_key");
			$aTags = $session->get("s_puppet_advanced_tag");
			$aPuppetSelector = $session->get("puppets_selector");

			$sSql = "";
			$sSql2 = "";
			

			if(!$aPuppetLimit && !$aPuppetFields && !$aTags) {
				$session->set("s_puppet_advanced_key","");
				$session->set("s_puppet_advanced_limit_key","");
				$session->set("s_puppet_advanced_tag","");
				$oDB->vRollback();
				throw new Exception("no search terms");	
			}
			/*
			if(count($aTags) < 1 &&  count($aPuppetFields) < 2 ){

				
				throw new Exception("too less terms");	

			}*/

			$session->set("s_puppet_advanced_key",$aPuppetFields);
			$session->set("s_puppet_advanced_limit_key",$aPuppetLimit);
			$session->set("s_puppet_advanced_tag",$aTags);

			//serialize site_no array
			$sSiteSql = "";
			


			if(is_array($aPuppetSelector['site_no']))
				$sSiteSql=" site_no IN(".implode(",",$aPuppetSelector['site_no']).")";	


			for($i=0;$i<count($aPuppetFields);$i++){
				
				$sSql = "";//身份欄位
				$sSql2 = "";//身份欄位選項
				$sCreateTimeSql = "";//身份建立時間
				


				$sTerms = $aPuppetFields[$i]['terms'];
				$sKey = $aPuppetFields[$i]['key'];
				
				if(count($aPuppetFields[$i]['option_key'])==0){
					$sSql = " (m.element_mapping_no=$sTerms AND m.element_mapping_value LIKE '%$sKey%') ";
				}else{
					
					for($j=0;$j<count($aPuppetFields[$i]['option_key']);$j++){
						$sKey = $aPuppetFields[$i]['option_key'][$j];
						if($sSql2)$sSql2 = $sSql2." AND "; //選項是複選
						$sSql2 = $sSql2." (element_mapping_no=$sTerms AND element_mapping_option_no ='$sKey')";
					}
					
				}

				if($aPuppetFields[$i]['create_date_limit'])  $sSql3="AND  createtime >='".$aPuppetFields[$i]['create_date_limit']."'";

				//沒有搜尋結果
				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){
					

					if($sSql2){
						$sSql = "SELECT p.puppets_no FROM galaxy_puppets AS p WHERE p.puppets_no IN ( SELECT puppets_no FROM galaxy_puppets_mapping_option WHERE $sSql2 LIMIT $iStart,10 ) $sCreateTimeSql";
					}else					
						$sSql = "SELECT p.puppets_no FROM galaxy_puppets AS p  LEFT JOIN galaxy_puppets_mapping AS m ON m.puppets_no=p.puppets_no WHERE $sSql $sCreateTimeSql  GROUP BY p.puppets_no LIMIT $iStart,10";


					$iDbq = $oDB->iQuery($sSql);
					while($aRow = $oDB->aFetchAssoc($iDbq)){
						
						//搜尋的是身份所以必須用此身份,去搜尋是否有網站帳號
						if($sSiteSql ){//必須要判斷網站
							
							if($aPuppetLimit['noregister_limit'])//未註冊
								$iDbq2 = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site WHERE puppets_no='".$aRow['puppets_no']."' AND registeredtime = '0000-00-00 00:00:00' AND $sSiteSql");
							else
								$iDbq2 = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site WHERE puppets_no='".$aRow['puppets_no']."' AND registeredtime != '0000-00-00 00:00:00' AND $sSiteSql");
							$aRow2=$oDB->aFetchArray($iDbq2);
							if($aRow2['total'] == count($aPuppetSelector['site_no']) )
								self::$__aSearchTemp[] = $aRow['puppets_no'];
								
						}else	
							self::$__aSearchTemp[] = $aRow['puppets_no'];

							

					}	
					
				}else{//已經有上一次搜尋結果
					
					$aSearchTemp = self::$__aSearchTemp;
					for($j=0;$j<count(self::$__aSearchTemp);$j++){

						if($sSql2){
							$sSql3 = " SELECT puppets_no FROM galaxy_puppets_mapping_option WHERE $sSql2  AND puppets_no='".$aSearchTemp[$j]."'";
						}else 					
							$sSql3 = "SELECT p.puppets_no FROM galaxy_puppets AS p  LEFT JOIN galaxy_puppets_mapping AS m ON m.puppets_no=p.puppets_no WHERE $sSql  AND p.puppets_no='".$aSearchTemp[$j]."'";
						$iDbq = $oDB->iQuery($sSql3);
						if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
							array_splice(self::$__aSearchTemp,$j);
						}	
					}	



				}

				//其中一個欄位結果不符導致暫存變為空,不需要在搜尋
				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp)==0)
					throw new Exception('oCPuppets->vMakeAdvancedSearchSql: no result for $i field search');

			}
			
			
			if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0)
					throw new Exception('oCPuppets->vMakeAdvancedSearchSql: no result for fields'.count(self::$__aSearchTemp));


			if(count($aTags)>0) {
				
				$sSql = "";
				for($i=0;$i<count($aTags);$i++){
					$sKey = $aTags[$i];
					
					$sSql = $sSql." tag_no ='$sKey'";
					
				}

				//沒有搜尋結果
				if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){
					$sSql = "SELECT table_no FROM galaxy_tag_log WHERE table_name='galaxy_puppets' AND $sSql LIMIT $iStart,10";
					$iDbq = $oDB->iQuery($sSql);
					while($aRow = $oDB->aFetchAssoc($iDbq)){
						//搜尋的是身份所以必須用此身份,去搜尋是否有網站帳號
						if($sSiteSql){//上一次沒有搜尋結果所以必須要判斷網站
							if($aPuppetLimit['noregister_limit'])//未註冊
								$iDbq2 = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site WHERE  puppets_no='".$aRow['table_no']."' AND  AND registeredtime  = '0000-00-00 00:00:00' AND  $sSiteSql");
							else
								$iDbq2 = $oDB->iQuery("SELECT count(accounts_site_no) as total FROM galaxy_accounts_site WHERE  puppets_no='".$aRow['table_no']."' AND  AND registeredtime != '0000-00-00 00:00:00' AND  $sSiteSql");	
							$aRow2=$oDB->aFetchArray($iDbq2);
							if($aRow2['total'] == count($aPuppetSelector['site_no']))
								self::$__aSearchTemp[] = $aRow['table_no'];
						}else		
							self::$__aSearchTemp[] = $aRow['table_no'];
					}	

				}else{

					$sSql = "SELECT table_no FROM galaxy_tag_log WHERE table_name='galaxy_puppets' AND $sSql";
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
						$sSql = "SELECT puppets_no FROM galaxy_accounts_new_log  WHERE $sSiteSql    ORDER BY registered_time  DESC  LIMIT $iStart,10 ";
					else 	$sSql = "SELECT puppets_no FROM galaxy_accounts_new_log  GROUP BY puppets_no   ORDER BY registered_time  DESC  LIMIT $iStart,10 ";
					
					$iDbq = $oDB->iQuery($sSql);
					while($aRow = $oDB->aFetchAssoc($iDbq)){
						self::$__aSearchTemp[] = $aRow['puppets_no'];
					}	

				}else{
					$sSql = "SELECT table_no FROM galaxy_tag_log WHERE table_name='galaxy_puppets' AND $sSql";
					$aSearchTemp = self::$__aSearchTemp;
					for($j=0;$j<count(self::$__aSearchTemp);$j++){

						
						$sSql = "SELECT puppets_no FROM galaxy_accounts_new_log  WHERE    puppets_no='".$aSearchTemp[$j]."'";
						
						$iDbq = $oDB->iQuery($sSql);
						if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
							array_splice(self::$__aSearchTemp,$j);
						}	
					}

				}	


				

			}else{
				//品牌互斥
				if($aPuppetLimit['brand_limit'] && $aPuppetSelector['project_no']){

					$oCompanyDB = self::oDB(self::$sCompanyDBName);
					$oOrderDB = self::oDB(self::$sOrderDBName);
					//此專案的品牌群組
					$sSql = "SELECT cb_id FROM order_sub    WHERE project_no = '".$aPuppetSelector['project_no']."'";
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
							$sSql = "SELECT puppets_no,cb_id FROM galaxy_accounts_lastpost_log    WHERE   project_no != '".$aPuppetSelector['project_no']."' AND last_time >= '".$aPuppetLimit['last_use_date_limit']."'";
						else
							$sSql = "SELECT puppets_no,cb_id FROM galaxy_accounts_lastpost_log    WHERE   project_no != '".$aPuppetSelector['project_no']."' AND last_time <= '".$aPuppetLimit['last_use_date_limit']."'";	
					
					}else{
						$sSql = "SELECT puppets_no,cb_id FROM galaxy_accounts_lastpost_log    WHERE project_no != '".$aPuppetSelector['project_no']."'";
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

								self::$__aSearchTemp[] = $aRow['puppets_no'];

							}

							
						}

					}else{

						$aSearchTemp = self::$__aSearchTemp;
						for($j=0;$j<count(self::$__aSearchTemp);$j++){

							
							$sSql = $sSql." AND  puppets_no='".$aSearchTemp[$j]."'";
							
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
						$sAfterSql = "SELECT puppets_no FROM galaxy_accounts_lastpost_log    WHERE  last_time >= '".$aPuppetLimit['last_use_date_limit']."' ";	
					else
						$sAfterSql2 = "SELECT puppets_no FROM galaxy_accounts_lastpost_log    WHERE puppets_no NOT IN( SELECT puppets_no FROM galaxy_accounts_lastpost_log   WHERE  last_time >= '".$aPuppetLimit['last_use_date_limit']."')";	

					if($aPuppetSelector['project_no'])
						$sProjectSql = " AND project_no != '".$aPuppetSelector['project_no']."'";

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

							self::$__aSearchTemp[] = $aRow['puppets_no'];
						}

					}else{
						$aSearchTemp = self::$__aSearchTemp;
						for($j=0;$j<count(self::$__aSearchTemp);$j++){

							$sSql = "SELECT puppets_no FROM galaxy_accounts_lastpost_log    WHERE  last_time >= '".$aPuppetLimit['last_use_date_limit']."' $sProjectSql AND  puppets_no='".$aSearchTemp[$j]."'";	
						
							
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
					$sSql = "SELECT puppets_no FROM galaxy_accounts_login_count_log WHERE  project_no != '".$aPuppetSelector['project_no']."' AND  login_count > ".$aPuppetLimit['login_count_limit']." ";				
					


					if(is_array(self::$__aSearchTemp)  && count(self::$__aSearchTemp) == 0){

						if($sSiteSql)
							$sSql = $sSql."  AND $sSiteSql  ORDER BY last_time  DESC LIMIT $iStart,10";
						else
							$sSql = $sSql." ORDER BY last_time  DESC LIMIT $iStart,10";
						$iDbq = $oDB->iQuery($sSql);
						while($aRow = $oDB->aFetchAssoc($iDbq)){

							self::$__aSearchTemp[] = $aRow['puppets_no'];
						}

					}else{
						$aSearchTemp = self::$__aSearchTemp;
						for($j=0;$j<count(self::$__aSearchTemp);$j++){

							$sSql = $sSql." AND  puppets_no='".$aSearchTemp[$j]."'";
							
							$iDbq = $oDB->iQuery($sSql);
							if(!$oDB->aFetchAssoc($iDbq)){//條件不符移除
								array_splice(self::$__aSearchTemp,$j);
							}	
						}

					}

				}

			}

		}catch (Exception $e){
			
			throw new Exception('oCPuppets->vMakeAdvancedSearchSql: '.$e->getMessage());
		}	




	}

	static public function vMakeAdvancedSearchSqlTmp($aPost=array()){
		$session = self::$session;
		$oDB = self::oDB(self::$sDBName);
	
		
		$oCUser = self::$session->get('oCurrentUser');
		$aPuppetSelector = $session->get("puppets_selector");
		
		try{

			if(count($aPost)){

				self::vMakeAdvancedSearchSession($aPost);

			}

			$oDB->iQuery( "SELECT select_count FROM galaxy_puppets_search_tmp  WHERE search_user_no ={$oCUser->iUserNo}  ORDER BY select_count DESC LIMIT 0,1");
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
					
					self::vInsertSearchSqlTmp(self::$__aSearchTemp[$i],$iSelectCount,$aPuppetSelector['project_no']);
				}	
			}else
				throw new Exception('  over 10 times search ');	


		}catch (Exception $e){
			
			throw new Exception('oCPuppets->vMakeAdvancedSearchSqlTmp: '.$e->getMessage());
		}	


	}
	static public function iGetCountSearchTmp($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(p.puppets_no) as total FROM galaxy_puppets_search_tmp AS p";
		
		if($sSearchSql!==''){
			$sSql .= " WHERE $sSearchSql ";

		}
		
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);

		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}
	static public function aAllPuppetsSearchTmp($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllPuppets = array();
		$sSql = "SELECT p.* FROM galaxy_puppets_search_tmp AS p";
		if($sSearchSql!=='')
			$sSql .= " LEFT JOIN galaxy_puppets_mapping_search_tmp AS m ON m.puppets_no=p.puppets_no WHERE $sSearchSql  GROUP BY p.puppets_no";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			
			$aAllPuppets[] = new CPuppets($aRow);
		}
		return $aAllPuppets;
	}
	/*
		constructor of $oCPuppets
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		
		parent::__construct($multiData);
		
		if(!is_array($multiData))
			throw new Exception("CPuppets: __construct failed, require an array");
		//initialize vital member
		if(isset($multiData['puppets_no']))
			$this->sPuppetsUuid = $multiData['puppets_no'];
		else
			$this->sPuppetsUuid = CMisc::uuid_v1();	
		
		//initialize optional member
		$this->sPuppetsNote = $multiData['puppets_note'];
		if($multiData['proxy_no'] >=0 )
		$this->iProxyNo = $multiData['proxy_no'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['puppets_status'];
		
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
		//if got $multiData from $_POST, set puppets elements
		$aPuppetsFields = array();
		//get all element_mapping_no that needs to be set
		foreach($multiData AS $key => $val){
			if(preg_match("/^element_mapping_option_no_([0-9]+)$/i",$key,$match)){	
				$aPuppetsFields[] = $match[1];					
			}elseif(preg_match("/^element_mapping_no_([0-9]+)$/i",$key,$match)){
				
				$aPuppetsFields[] = $match[1];
			}

		}
		$iOrder = 0;
		foreach ($aPuppetsFields as $iEleMapNo) {
			$iOrder++;
			
			$aEleInit =array(	'puppets_no'=>$this->sPuppetsUuid,
								'element_mapping_no'=>$iEleMapNo,
								'element_mapping_value'=>$multiData['element_mapping_no_'.$iEleMapNo],
								'fields_order'=>$iOrder,
								'fields_status'=>($multiData['fields_status_'.$iEleMapNo])?'1':'0'
								);
			$oCPuppetsElement = new CPuppetsElement($aEleInit);
			if(isset($multiData['element_mapping_option_no_'.$iEleMapNo])){

				$oCPuppetsElement->vSetOption($multiData['element_mapping_option_no_'.$iEleMapNo]);
			}
			$this->vSetPuppetsElement($oCPuppetsElement);
		}

		if(is_array($multiData['puppets_group']) )
			$this->aGroup = $multiData['puppets_group'];
		else
			$this->aGroup = array();

		

	}

	

	/*
		set & get doc element
	*/
	public function aPuppetsElement(){
		$oDB = self::oDB(self::$sDBName);
		if(empty($this->__aCPuppetsElement)){
			$this->__aCPuppetsElement = CPuppetsElement::aAllPuppetsElement($this->sPuppetsUuid);
		}
		return $this->__aCPuppetsElement;
	}

	/*
		overwrite $this with another $oCPuppets object, which has same sPuppetsUuid and some different value
	*/
	public function vOverWrite($oCPuppets){
		//if not a CPuppets object or uuid not match
		if(get_class($oCPuppets)!=='CPuppets' || $this->sPuppetsUuid!==$oCPuppets->sPuppetsUuid)
			throw new Exception('CPuppets->vOverWrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='sPuppetsUuid')
				continue;
			$this->$name = $oCPuppets->$name;	//overwrite
		}
	}


	/*
		update puppet data
		step1: $oCPuppets = CPuppets:oGetPuppets($sPuppetsUuid) and create a CPuppets with all info
		step2: $oCPuppets>overwrite($oNewCPuppets);
		step3: call this function
	*/
	public function vUpdatePuppets(){
		$oDB = self::oDB(self::$sDBName);
	
		try{
			$oCUser = self::$session->get('oCurrentUser');
			$oDB->vBegin();
			//update puppets attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'puppets_note'=>$this->sPuppetsNote,
						'proxy_no'=>$this->iProxyNo,
						'puppets_status'=>$this->bStatus,
						'user_no'=>$oCUser->__iUserNo,
						'modifiedtime'=>$sDate
					);
			$oDB->sUpdate("galaxy_puppets",array_keys($aValues),array_values($aValues),"puppets_no='{$this->sPuppetsUuid}'");


			if($this->__aCPuppetsElement) {

				//delete all before insert all puppets element
				$sSql = "DELETE FROM galaxy_puppets_mapping WHERE  puppets_no = '{$this->sPuppetsUuid}'";
				$iRes = $oDB->iQuery($sSql);
				$sSql = "DELETE FROM galaxy_puppets_mapping_option WHERE  puppets_no = '{$this->sPuppetsUuid}'";
				$iRes = $oDB->iQuery($sSql);

				foreach ($this->__aCPuppetsElement as $oCPuppetsElement) {
					$oCPuppetsElement->sAddPuppetsElement();
				}				
			}

			

			$sSql = "DELETE FROM galaxy_puppets_group WHERE  puppets_no = '{$this->sPuppetsUuid}'";
			$iRes = $oDB->iQuery($sSql);

			for($i=0;$i<count($this->aGroup);$i++){
				$aValues = array(	'puppets_no'=>$this->sPuppetsUuid,
							'group_no'=>$this->aGroup[$i]
						);
				$oDB->sInsert("galaxy_puppets_group",array_keys($aValues),array_values($aValues));
			}
			$oCUser->vAddUserLog("galaxy_puppets",$this->sPuppetsUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CPuppets->vUpdatePuppets: '.$e->getMessage());
		}
	}

	/*
		add puppet to certain system
		return puppets_no(string of uuid)
		step1: new a $oCPuppets object with all info
		step2: call this function
	*/
	public function sAddPuppets(){
		$oDB = self::oDB(self::$sDBName);
		try{
			
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'puppets_no'=>$this->sPuppetsUuid,
								'puppets_note'=>$this->sPuppetsNote,
								'puppets_status'=>$this->bStatus,
								'user_no'=>$this->__iUserNo,
								'createtime'=>$sDate,
								'modifiedtime'=>$sDate
							);
			$oDB->sInsert("galaxy_puppets",array_keys($aValues),array_values($aValues));
			//insert all puppet element
			foreach ($this->__aCPuppetsElement as $oCPuppetsElement) {
				$oCPuppetsElement->sAddPuppetsElement();
			}


			for($i=0;$i<count($this->aGroup);$i++){
				$aValues = array(	'puppets_no'=>$this->sPuppetsUuid,
							'group_no'=>$this->aGroup[$i]
						);
				$oDB->sInsert("galaxy_puppets_group",array_keys($aValues),array_values($aValues));
			}

			$this->oLastUser()->vAddUserLog("galaxy_puppets",$this->sProjectUuid,$_GET['func'],$_GET['action']);

			$oDB->vCommit();



			return $this->sPuppetsUuid;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppets->sAddPuppets: '.$e->getMessage());
		}
	}

	


	public function vActivePuppets($iFlag=0){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oCUser = self::$session->get('oCurrentUser');
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'puppets_status'=>$iFlag,
						'user_no'=>$oCUser->__iUserNo,
						'modifiedtime'=>$sDate
					);
			$oDB->sUpdate("galaxy_puppets",array_keys($aValues),array_values($aValues),"puppets_no='{$this->sPuppetsUuid}'");
			
			$oCUser->vAddUserLog("galaxy_puppets",$this->sPuppetsUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppets->vActivePuppets: '.$e->getMessage());
		}
	}

	/*
		add CPuppetsElement
		if same element_mapping_no exist in this doc, this function will overwrite it
	*/
	public function vSetPuppetsElement($oCPuppetsElement){
		$this->__aCPuppetsElement[$oCPuppetsElement->iElementMappingNo] = $oCPuppetsElement;
	}

	/*
		add galaxy_puppets_group
	*/
	public static function vAddPuppetsGroup($aPuppetsNos, $iGroup) {
		$oDB = self::oDB(self::$sDBName);
		try{
			$oDB->vBegin();
			
			foreach ($aPuppetsNos as $sPuppetsNo) {
				
				$sSql = "SELECT * FROM `galaxy_puppets_group` WHERE puppets_no='$sPuppetsNo'";
				$iDbq_exist = $oDB->iQuery($sSql);
				if(!$oDB->aFetchArray($iDbq_exist)){
					$aValues = array(	'puppets_no'=>$sPuppetsNo,
								'group_no'=>$iGroup,
							);
					$oDB->sInsert('galaxy_puppets_group',array_keys($aValues),array_values($aValues));
				}	
			}
			$oDB->vCommit();
			
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CPuppets->vAddPuppetsGroup: '.$e->getMessage());
		}
	}
	/*
	function bAddPuppetsLog($log_data){
		global $PuppetsLogDbh;

		$sLogTables = "galaxy_puppets_log_" . substr($log_data['puppets_no'], 0, 2) . "_" . date("Y");	//用身份UUID前兩碼加入資料表名稱 

		if(!$PuppetsLogDbh->bIsTableExist($sLogTables)){
			$sql = "CREATE TABLE " . $sLogTables . " LIKE galaxy_puppets_log";	// 從原始資料表複製
			$iRes = $PuppetsLogDbh->iQuery($sql);
			if(!$iRes) return false;
		}
		
		$aField = array_keys($log_data);
		$aValue = array_values($log_data);
		
		$sql = $PuppetsLogDbh->sInsert($sLogTables, $aField, $aValue);

		return false;
	}
	
	
	function bAddSiteLog($log_data){
		global $PuppetsLogDbh;

		$sLogTables = "galaxy_site_log_" . date("Y") . "_" . date("m");	// 用年、月切割資料表 

		if(!$PuppetsLogDbh->bIsTableExist($sLogTables)){
			$sql = "CREATE TABLE " . $sLogTables . " LIKE galaxy_site_log";	// 從原始資料表複製 
			$iRes = $PuppetsLogDbh->iQuery($sql);
			if(!$iRes) return false;
		}
		
		$aField = array_keys($log_data);
		$aValue = array_values($log_data);
		
		$sql = $PuppetsLogDbh->sInsert($sLogTables, $aField, $aValue);

		return false;
	}
	
	 
	 
	
	

	function IsPuppetElementMappingNo($puppet_no, $element_mapping_no) {
		global $oDB;
		
		if($element_mapping_no==0) return false;
		
		$sql = "SELECT `puppets_no`
					FROM `galaxy_puppets_mapping` 
					WHERE `element_mapping_no` = $element_mapping_no";

		$iRes = $oDB->iQuery($sql);
		$fe = $oDB->aFetchAssoc($iRes);
		if(!$fe) return false;
		return true;
	}	

	function getMappingValue($puppets_no, $element_mapping_no) {
		global $oDB;

		$sql = "SELECT *
					FROM `galaxy_puppets_mapping` 
					WHERE `puppets_no` = '$puppets_no'
					AND `element_mapping_no` = $element_mapping_no
					AND `fields_status` = 1";
			
		$iRes = $oDB->iQuery($sql);			
		$fe   = $oDB->aFetchAssoc($iRes);

		if(!$fe['element_mapping_value']) 
			return $this->getOptionNo($puppets_no, $element_mapping_no);
	 	else
			return $fe['element_mapping_value'];
	} 

	function getMappingVal($puppets_no, $element_mapping_no) {
		global $oDB;

		$sql = "SELECT `element_mapping_value`
				FROM `galaxy_puppets_mapping` 
				WHERE `puppets_no` = '$puppets_no'
				AND `element_mapping_no` = $element_mapping_no
				AND `fields_status` = 1";
				
		$iRes = $oDB->iQuery($sql);			
		$fe   = $oDB->aFetchAssoc($iRes);

		if(!$fe) return false;

		return $fe['element_mapping_value'];
	}

	function getOptionNo($puppets_no, $element_mapping_no) {
		global $oDB;

		$sql = "SELECT `element_mapping_option_no`
					FROM `galaxy_puppets_mapping_option` 
					WHERE `puppets_no` = '$puppets_no'
					AND `element_mapping_no` = $element_mapping_no";

		$iRes = $oDB->iQuery($sql);			
		$fe  = $oDB->aFetchAssoc($iRes);	

		if(!$fe) return false;

		return $fe['element_mapping_option_no'];
	}

	function getOptionName($puppets_no, $element_mapping_no) {
		global $oDB, $CElementMappingOption;

		$sql = "SELECT `element_mapping_option_no`
					FROM `galaxy_puppets_mapping_option` 
					WHERE `puppets_no` = '$puppets_no'
					AND `element_mapping_no` = $element_mapping_no";

		$iRes = $oDB->iQuery($sql);			
		$fe  = $oDB->aFetchAssoc($iRes);	

		if(!$fe) return false;

		return $CElementMappingOption->getName($fe['element_mapping_option_no']);
	}

	function getMappingValueInDeep($puppets_no, $element_mapping_no) {
		global $oDB, $CAccounts;

		$sql = "SELECT *
					FROM `galaxy_puppets_mapping` 
					WHERE `puppets_no` = '$puppets_no'
					AND `element_mapping_no` = $element_mapping_no
					AND `fields_status` = 1";
		
		$iRes = $oDB->iQuery($sql);			
		$fe   = $oDB->aFetchAssoc($iRes);

		if(!$fe['element_mapping_value']) {
			
				$option_no = $this->getOptionNo($puppets_no, $element_mapping_no);

				if($option_no) 
					return $option_no;
				else 
					return $CAccounts->AccountsValue($puppets_no, $element_mapping_no); 	

	 	} else {
			return $fe['element_mapping_value'];
		}	
	} 

	function updatePuppets($puppets_no, $data) {
		global $oDB;

		$aField = array_keys($data);
		$aValue = array_values($data);
			
		$sql = $oDB->sUpdate("`galaxy_puppets`", $aField, $aValue, "`puppets_no` = '$puppets_no'");	

		if($sql)
			return true;
		else 
			trigger_error("update puppets proxy no fail", E_USER_ERROR);
	}

	function addMappingValue($puppets_no, $element_mapping_no, $value) {
		global $oDB;

		$data['puppets_no'] = $puppets_no;
		$data['element_mapping_no'] = $element_mapping_no;
		$data['element_mapping_value'] = $value;

		$aField = array_keys($data);
		$aValue = array_values($data);

		$sql = $oDB->sInsert("`galaxy_puppets_mapping`", $aField, $aValue);	

		if($sql)
			return true;
		else 
			trigger_error("insert galaxy puppets mapping fail", E_USER_ERROR);			
	}

	function addMappingOption($puppets_no, $element_mapping_no, $option_no) {
		global $oDB;

		$rs = $this->addMappingValue($puppets_no, $element_mapping_no, "");

		$data['puppets_no'] = $puppets_no;
		$data['element_mapping_no'] = $element_mapping_no;
		$data['element_mapping_option_no'] = $option_no;

		$aField = array_keys($data);
		$aValue = array_values($data);

		$sql = $oDB->sInsert("`galaxy_puppets_mapping_option`", $aField, $aValue);	

		if($sql)
			return true;
		else 
			trigger_error("insert galaxy puppets mapping option fail", E_USER_ERROR);	

	}

	function addPuppets($data) {
		global $oDB;

		$aField = array_keys($data);
		$aValue = array_values($data);

		$sql = $oDB->sInsert("`galaxy_puppets`", $aField, $aValue);	

		if($sql)
			return true;
		else 
			trigger_error("insert galaxy puppets fail", E_USER_ERROR);	
	}
	
	function deletePuppets($puppets_no) {
		global $oDB;

		$sql = "DELETE
					FROM `galaxy_puppets` 
					WHERE `puppets_no` = '$puppets_no'";

		$iRes = $oDB->iQuery($sql);			
	}

	function deletePuppetsMapping($puppets_no) {
		global $oDB;

		$sql = "DELETE
					FROM `galaxy_puppets_mapping` 
					WHERE `puppets_no` = '$puppets_no'";

		$iRes = $oDB->iQuery($sql);			
	}

	function deletePuppetsMappingOption($puppets_no) {
		global $oDB;

		$sql = "DELETE
					FROM `galaxy_puppets_mapping_option` 
					WHERE `puppets_no` = '$puppets_no'";

		$iRes = $oDB->iQuery($sql);			
	}

	function getAllElementMappingNo($puppets_no) {
		global $oDB;	

		$sql = "SELECT `element_mapping_no` 
					FROM `galaxy_puppets_mapping` 
					WHERE `puppets_no` = '$puppets_no'";	
					
		$iRes = $oDB->iQuery($sql);
		
		while($fe = $oDB->aFetchAssoc($iRes)){
			$aRow[] = $fe['element_mapping_no'];
		}

		return $aRow;
	}
	*/
} 

?>