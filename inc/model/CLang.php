<?php

include_once('../inc/model/CGalaxyClass.php');
class CLang extends CGalaxyClass
{

	static public $iBackendLang ; //現在是前台或後台
	private $iLangId;
	public $sFunc;
	public $sType;
	public $sName;
	public $sValue;
	public $sDesc;
	public $iBackend; //1只屬於後台 0前台後台共用

	static protected $sDBName = 'GENESIS';

	static public  function oGetLang($iLangId = 0){

		if($iLangId === 0 ) return null;
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $GenesisDbh->iQuery("SELECT * FROM lang
								WHERE lang_id='$iLangId'");

		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)==0)
			return null;

		$oCLang = new CLang($aRow);
		return $oCLang;
	}

	static public  function oGetLangByName($sLangName = ""){

		if($sLangName === "") return null;
		$oDB = self::oDB(self::$sDBName);

		$iDbq = $oDB->iQuery("SELECT * FROM lang
								WHERE lang_name='$sLangName'");


		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)==0)
			return null;

		$oCLang = new CLang($aRow);

		return $oCLang;
	}

	static public  function vDefineLang($sFunc="",$sType="",$iBackend=0,$sDesc="",$sName="",$sValue=""){
		global $GenesisDbh;


		if($sFunc==="" || $sType==="" || $sName==="" || $sValue==="")
			return;

		if($iBackend!=self::$iBackendLang &&  $iBackend) return; //iBackend =1只屬於後台,並且如果現在是iBackendLang =0 前台

		$oCLang = self::oGetLangByName($sName);

		if(!$oCLang){

			$oDB = self::oDB(self::$sDBName);
			try{

				$aValues = array(	'lang_func'=>$sFunc,
								'lang_type'=>$sType,
								'lang_name'=>$sName,
								'lang_value'=>$sValue,
								'lang_desc'=>$sDesc,
								'is_where'=>$iBackend
							);


				$oDB->sInsert("lang",array_keys($aValues),array_values($aValues));

				define($name,$value);
			}catch (Exception $e){

				throw new Exception('oCLang->vDefineLang: '.$e->getMessage());
			}
		}else{
			define($oCLang->sName,$oCLang->sValue);

		}

	}

	public function __construct ($multiData){

		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CLang: __construct failed, require an array");

		if(isset($multiData['lang_id']))
			$this->iLangId = $multiData['lang_id'];
		else
			$this->iLangId =0;

		//initialize optional member
		$this->sFunc = $multiData['lang_func'];
		$this->sType = $multiData['lang_type'];
		$this->sName = $multiData['lang_name'];
		$this->sValue = $multiData['lang_value'];
		$this->sDesc = $multiData['lang_desc'];
		$this->iBackend = $multiData['is_where'];
	}

	public function vOverwrite($oCLang){
		//if not a CPuppets object or uuid not match
		if(get_class($oCLang)!=='CLang' || $this->iLangId!==$oCLang->iLangId)
			throw new Exception('CLang->vOverwrite: fatal error');

		foreach ($this as $name => $value) {
			if($name==='iLangId')
				continue;
			$this->$name = $oCLang->$name;	//overwrite
		}
	}



	public function vUpdateLang(){
		$oDB = self::oDB(self::$sDBName);

		try{

			$aValues = array(	'lang_value'=>$this->sValue

					);
			$oDB->vUpdate("lang",array_keys($aValues),array_values($aValues),"lang_id='{$this->iLangId}'");

		}catch (Exception $e){

			throw new Exception('CLang->vUpdateLang: '.$e->getMessage());
		}
	}

}
?>