<?php

include_once('../inc/model/CGalaxyClass.php');



class CContactTel  extends CGalaxyClass 
{


	protected static $sDBName = 'COMPANY';


	public function __get($varName)
	{
	
		return $this->varName;
	
	}
	
	
	
	
	public function __construct($multData)
	{
		parent::__construct($multData);
		
		if(!is_array($multData))
			throw new Exception("CContactTel: __construct failed, require an array");
			
		$this->user_no = $multData['user_no'];
		$this->ct_tel = $multData['ct_tel'];
		$this->type = $multData['type'];
	
	}
	

	
	public static function aGetContactTel($user_no)
	{

		$aContactTel = array();
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM contact_tel WHERE user_no = '$user_no'";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aContactTel[] = new CContactTel($aRow);
		}
		return $aContactTel;
	
	}
	
	
	/**
	* @desc 抓取公司聯絡人電話
	* @param $co_id int 公司序號
	* @param $type int 0:全部 1:電話 2:手機 3:傳真
	* @return string 公司電話
	* @created 2014/04/03
	*/
	public static function sGetContactTel($user_no=0,$type=0,$comma='<br>')
	{
		$str = '';
		if (!$user_no) return '';
		
		$aContactTel = self::aGetContactTel($user_no);
		
		$aCtTel = array();
		
		foreach($aContactTel as $oTel)
		{
			if ($type!=0 AND $oTel->type!=$type) continue;
			if (in_array($oTel->ct_tel,$aCtTel)) continue;
			array_push($aCtTel,$oTel->ct_tel);
		}
		return implode($comma,$aCtTel);
	}		
	
	
		





}
?>