<?php
include_once('../inc/model/CProxy.php');
class CPuppetsField
{
	/**
	 * key is element mapping no, value is the function name
	 */	
	static public $_field = array(
				"gender"=>array(
					"element_mapping_no"=>"8",			//性別 element_mapping_no
					"option"=>array(
						"79" =>"1",		//男
						"80" =>"0"		//女
						
					)
					
				),
				"lastName"=>array(
					"element_mapping_no"=>"22"			//姓氏
					
				),
				"firstName"=>array(
					"element_mapping_no"=>"23"			//名字
				),
				"allName"=>array(
					"element_mapping_no"=>"10"			//姓+名
				),
				"pinyinAllName"=>array(
					"element_mapping_no"=>"75"			//拼音姓+拼音名
				),
				"pinyinLastName"=>array(
					"element_mapping_no"=>"71"			//拼音姓
				),
				"pinyinFirstName"=>array(
					"element_mapping_no"=>"72"			//拼音名
				),
				"englishName"=>array(
					"element_mapping_no"=>"27"			//英文名
				),
				"nickName"=>array(
					"element_mapping_no"=>"32"			//暱稱	
				),
				"ID"	=>array(
					"element_mapping_no"=>"81"			//身分證	
				),
				/*"year"=>array(
					"element_mapping_no"=>"58"			//出生年
				),*/
				"yearOption"=>array(
					"element_mapping_no"=>"6",			//出生年(下拉式選單)
					"option"=>array(
						"1" =>"1958",		
						"2" =>"1959",		
						"3" =>"1960",		
						"4" =>"1961",		
						"5" =>"1962",		
						"6" =>"1963",		
						"7" =>"1964",		
						"8" =>"1965",		
						"9" =>"1966",		
						"10" =>"1967",		
						"11" =>"1968",		
						"12" =>"1969",
						"13" =>"1970",	
						"14" =>"1971",	
						"15" =>"1972",	
						"16" =>"1973",	
						"17" =>"1974",	
						"18" =>"1975",	
						"19" =>"1976",	
						"20" =>"1977",	
						"21" =>"1978",	
						"22" =>"1979",	
						"23" =>"1980",
						"24" =>"1981",
						"25" =>"1982",
						"26" =>"1983",
						"27" =>"1984",
						"28" =>"1985",
						"29" =>"1986",
						"30" =>"1987"		
					)
				),
				
				"month"=>array(
					"element_mapping_no"=>"25",			//出生月
					"option"=>array(
						"66" =>"1",		
						"67" =>"2",		
						"68" =>"3",		
						"69" =>"4",		
						"70" =>"5",		
						"71" =>"6",		
						"72" =>"7",		
						"73" =>"8",		
						"74" =>"9",		
						"75" =>"10",		
						"76" =>"11",		
						"77" =>"12"
						
					)
				),
				/*"day"=>array(
					"element_mapping_no"=>"60"			//出生日
				),*/
				"dayOption"=>array(
					"element_mapping_no"=>"29",			//出生日(下拉式選單)
					"option"=>array(
						"35" =>"1",		
						"36" =>"2",		
						"37" =>"3",		
						"38" =>"4",		
						"39" =>"5",		
						"40" =>"6",		
						"41" =>"7",		
						"42" =>"8",		
						"43" =>"9",		
						"44" =>"10",		
						"45" =>"11",		
						"46" =>"12",
						"47" =>"13",	
						"48" =>"14",	
						"49" =>"15",	
						"50" =>"16",	
						"51" =>"17",	
						"52" =>"17",	
						"53" =>"19",	
						"54" =>"20",	
						"55" =>"21",	
						"56" =>"22",	
						"57" =>"23",
						"58" =>"24",
						"59" =>"25",
						"60" =>"26",
						"61" =>"27",
						"62" =>"28",
						"63" =>"29",
						"64" =>"30"	
					)
				),
				"country"=>array(
					"element_mapping_no"=>"86",			//國家
					"option"=>array(
						"158"=>"tw"	
						),
					"option_range"=>array(
						"158" => array(	//台灣
							"161"
						)
					)
				),
				"zip"=>array(
					"element_mapping_no"=>"28"			//郵遞區號
				),
				"counties"=>array(
					"element_mapping_no"=>"88",			//縣市
					"option"=>array(
						"161"=>"taipei"	
						),
					"option_range"=>array(
						"161" => array(	// 臺北市
							"160",
							"162",
							"163",
							"164",
							"165",
							"166",
							"167",
							"168",
							"169",
							"170",
							"171",
							"172"
						)		
						
					)
				),
				"town"=>array(
					"element_mapping_no"=>"87",			//鄉鎮市區
					"option"=>array(
						"160" =>"100",		//中正區 zip
						"162" =>"103",		//大同區
						"163" =>"104",		//中山區
						"164" =>"105",		//松山區
						"165" =>"106",		//大安區
						"166" =>"108",		//萬華區
						"167" =>"110",		//信義區
						"168" =>"111",		//士林區
						"169" =>"112",		//北投區
						"170" =>"114",		//內湖區
						"171" =>"115",		//南港區
						"172" =>"116"			//文山區
					)
				),
				"address"=>array(
					"element_mapping_no"=>"89"			//地址
				),
				"countryCode"=>array(
					"element_mapping_no"=>"35"			//國碼
				),
				"dayTele"=>array(
					"element_mapping_no"=>"92"			//日間聯絡電話
				),
				"phone"=>array(
					"element_mapping_no"=>"12"			//行動電話
				),
				"edu"=>array(
					"element_mapping_no"=>"13",			//學歷
					"option"=>array(
						"181" =>"1",			//國中
						"182" =>"2",			//高中（高職）
						"183" =>"3",			//大學
						"184" =>"4",			//研究所及以上
						"185" =>"5"			//專科
						
					)
				),
				"job"=>array(
					"element_mapping_no"=>"54",			//職業
					"option"=>array(
						"148" =>"1",			//上班族
						"149" =>"2",			//電子資訊 ╱軟體╱半導體相關業
						"150" =>"3",			//批發╱零售╱傳直銷業
						"173" =>"4",			//資訊及通訊業
						"174" =>"5",			//大眾傳播相關業
						"175" =>"6",			//金融投顧及保險業
						"176" =>"7",			//文教相關業
						"177" =>"8",			//服務業
						"178" =>"9",			//學生
						"179" =>"10",			//待業/無業
						"180" =>"11"			//家管
					)
				),
				"income"=>array(
					"element_mapping_no"=>"94",			//月收入
					"option"=>array(
						"186" =>"1",			//NT20,001~NT30,000
						"187" =>"2",			//NT30,001~NT40,000
						"188" =>"3",			//NT40,001~NT50,000
						"189" =>"4",			//NT50,001~NT60,000
						"190" =>"5",			//NT60,000以上
						"191" =>"6",			//無收入
						"192" =>"7",			//NT10,000以下
						"193" =>"8"			//NT10,001~NT20,000
						
					)
				),
				"marry"=>array(
					"element_mapping_no"=>"95",			//婚姻狀況
					"option"=>array(
						"194" =>"0",		//未婚
						"195" =>"1"		//已婚
						
					)
				)
				

    		);


	public function __construct() {
	}

	static public function checkPuppetFields() {
		

		$errorMsg = "";

		foreach(self::$_field AS $key => $val){

			$rs = CElementMapping::bIsExistElementMapping($val['element_mapping_no']);

			if(!$rs) 
				$errorMsg+='欄位關聯('.$key.') id 不存在';
			if($val['option']){
				foreach($val['option'] AS $key2 => $val2){

					$optionData = CElementMappingOption::oGetElementMappingOption($key2);
					if($optionData){
						$errorMsg+='欄位關聯('.$key.') 選項('.$key2.') 不存在';
					}

				}
			}	
			
		}
		if($errorMsg) throw new Exception('CPuppetsField->checkPuppetFields: '.$errorMsg);
	}

	/**
	 * 取得所有身份預設的欄位
	 */
	static public  function aGetDefaultPuppetFields() {
		
			
		
		foreach(self::$_field AS $key => $val){
			
			if($key==="country" || $key==="counties" || $key==="zip" || $key==="countryCode" || $key==="address" ) //這幾個欄位都是從town 的選項zipcode 取得 有關聯性,所以不需要設定
				continue;

			$element_mappnig['name']= $key;
			$element_mappnig['element'] = CElementMapping::oGetElementMapping($val['element_mapping_no']);
			

			if($element_mappnig['element']->sTagType === "checkbox" 
				|| $element_mappnig['element']->sTagType === "select" 
				|| $element_mappnig['element']->sTagType === "radio") {

				if($val['option']){
					foreach($val['option'] AS $key2 => $val2){

						if($element_mappnig['element']->sName==="town"){
							//鄉鎮市區 zipcode 的線路是否存在
							
							if(!CProxy::aGetProxyByZIP($val2)){
								//$key2  element_mapping_option_no
								unset($element_mappnig['element']->aOption[$key2]);
							}
							continue;
							

						}

						

					}
					
				}

			}

			$data[] = $element_mappnig;
		}

		return $data;
		
	
	}
	
	
	
	
	static public function aGetPuppetsFieldsSelector(){
		
			
		$aFieldsData[99] = array("name"=>_LANG_PUPPETS_FIELDS_SELECTOR_DEFAULT_TITLE,"element"=>array());
		$aPuppetFieldsData = self::aGetDefaultPuppetFields();
		
		if($aPuppetFieldsData){
		
			foreach($aPuppetFieldsData AS $key => $val){
				
				$val['set'] =1;
				
				$aFieldsData[99]["element"][] = $val;
			
			}
		}	
		
		$aElementMappingData = CElementMapping::aAllElementMappingGroupByTagtype();
		
		if($aElementMappingData){
		
			foreach($aElementMappingData AS $key => $val){
				
				
				$aFieldsData[$key]["name"] = $val["name"];
				
				for($i=0;$i<count($val['element']);$i++){
					$aTmp = array("fields_no"=>0,"fields_status"=>1,"set"=>0,"element"=>$val['element'][$i]);
					
					
					$aFieldsData[$key]["element"][] = $aTmp;
				
				}
				
			
			}
		}	
		return $aFieldsData;
	
	}

	
}


?>