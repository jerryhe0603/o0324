<?php

include_once('../inc/model/CGalaxyClass.php');


class CCompanyDept extends CGalaxyClass
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
			throw new Exception("CCompanyDept: __construct failed, require an array");
			
		$this->cd_id = $multData['cd_id'];
		$this->cd_name = $multData['cd_name'];
		$this->flag = $multData['flag'];
		$this->co_id = $multData['co_id'];
	
	}
	
	
	public static function aGetCompanyDept($co_id=0)
	{

		$aCompanyTel = array();
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT * FROM company_dept WHERE co_id = '$co_id'");
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aCompanyTel[] = new CCompanyDept($aRow);
		}
		return $aCompanyTel;
	
	}
	
	
	/**
	 *  @desc 取得某公司員工的部門
	 *  @return array
	 */
	public static function aGetCompanyDeptByCoIdUserNo($co_id=0,$user_no=0)
	{

		$aCompanyDept = array();
		$oDB = self::oDB(self::$sDBName);
		$iDbq = $oDB->iQuery("SELECT * FROM company_dept AS a INNER JOIN contact_dept_rel AS b ON a.cd_id=b.cd_id WHERE a.co_id = $co_id AND b.user_no=$user_no");
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aCompanyDept[] = new CCompanyDept($aRow);
		}
		return $aCompanyDept;
	
	}
	
	/**
	 *  @desc 取的某公司員工的部門
	 *  @return string
	 */
	public static function sGetCompanyDeptNameByCoIdUserNo($co_id=0,$user_no=0,$comma='<br>')
	{
		$aCdName = array();
		$aCompanyDept = self::aGetCompanyDeptByCoIdUserNo($co_id,$user_no);
		if (count($aCompanyDept))
		{
			foreach($aCompanyDept as $oCoDept)
			{
				if ($oCoDept->flag==0) continue;
				if (in_array($oCoDept->cd_name,$aCdName)) continue;
				array_push($aCdName,$oCoDept->cd_name);
			}
		}
		return implode($comma,$aCdName);
		
	
	}



}
?>