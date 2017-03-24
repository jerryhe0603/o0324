<?php
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CScript.php');

class CPlan extends CGalaxyClass
{
	private $iPlanNo;
	public $sName;
	public $sDesc;
	public $iSiteNo;

	private $__aOrderScript;

	//database setting
	static protected $sDBName = 'GENESIS';

	static public function oGetPlan($iPlanNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_plan WHERE plan_no=$iPlanNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCPlan = new CPlan($aRow);
		return $oCPlan;
	}

	static public function aAllPlan($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_plan";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCPlan = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllCPlan[] = new CPlan($aRow);
		}
		return $aAllCPlan;
	}

	/*
		get count of plan which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(plan_no) as total FROM galaxy_plan";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow!==false)
			$iCount = (int)$aRow['total'];
		else
			$iCount = 0;
		return $iCount;
	}

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CPlan: __construct failed, require an array");
		//initialize vital member
		$this->iScriptNo = $multiData['plan_no'];
		$this->sName = $multiData['plan_name'];
		$this->sDesc = $multiData['plan_desc'];
		$this->iSiteNo = $multiData['site_no'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['status'];
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
	}

	public function __get($varName)
    {
        if(method_exists($this,$varName))
        	return $this->$varName();

        return $this->$varName;
    }

    /*******************************************
			class member controll function
    *******************************************/

    public function aOrderScript(){
    	$oDB = self::oDB(self::$sDBName);
    	if(is_null($this->__aOrderScript)){

    		//initialize empty array
    		$this->__aOrderScript = array();

    		//get script with order
    		$sSql = "SELECT * FROM galaxy_plan_script WHERE script_no=$iScriptNo ORDER BY order ASC";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aOrderScript[$aRow['order']] = CScript::oGetScript($aRow['script_no']);
			}

			//sort script with order
			ksort($this->__aOrderScript);
    	}
		return $this->__aOrderScript;
    }
    
    public function vSetOrderScript($aOrderScript){
    	//clear previous scripts
    	$this->__aOrderScript = array();

    	//set new script and order
    	foreach ($aOrderScript as $iOrder => $iScriptNo) {
    		$oScript = CScript::oGetScript($iScriptNo);
    		if(is_null($oScript))
    			continue;
    		$this->__aOrderScript[$iOrder] = $oScript;
    	}
    }

    /*******************************************
    			DB controll function
    *******************************************/

    public function iAddPlan(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//insert plan itself
			$aValues = array(	'plan_name'=>$this->sName,
								'plan_desc'=>$this->sDesc,
								'user_no'=>$oCurrentUser->iUserNo,
								'createtime'=>date("Y-m-d H:i:s"),
								'modifiedtime'=>date("Y-m-d H:i:s")
								);
			$oDB->sInsert("galaxy_plan", array_keys($aValues), array_values($aValues));
			$this->iPlanNo = $oDB->iGetInsertId();

			//insert script with order
			foreach ($this->aOrderScript() as $iOrder => $oScript) {
				$aScriptVal = array(	'plan_no' => $this->iPlanNo,
										'order' => $iOrder,
										'script_no' => $oScript->iScriptNo
										);
				$oDB->sInsert("galaxy_plan_script", array_keys($aScriptVal), array_values($aScriptVal));
			}

			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('galaxy_plan',$this->iPrincipleNo,$_GET['func'],$_GET['action']);
			return $this->iPlanNo;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CPlan->iAddPlan: ".$e->getMessage());
		}
    }

    public function vUpdatePlan(){
    	$oDB = self::oDB(self::$sDBName);
    	$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//update plan itself
			$aValues = array(	'plan_name'=>$this->sName,
								'plan_desc'=>$this->sDesc,
								'modifiedtime'=>date("Y-m-d H:i:s")
								);
			$oDB->sUpdate("galaxy_plan", array_keys($aValues), array_values($aValues), "`plan_no` = {$this->iPlanNo}");

			//delete and insert new script in order
			$oDB->vDelete('galaxy_plan_script',"`plan_no`='{$this->iPlanNo}'");
			foreach ($this->aOrderScript() as $iOrder => $oScript) {
				$aScriptVal = array(	'plan_no' => $this->iPlanNo,
										'order' => $iOrder,
										'script_no' => $oScript->iScriptNo
										);
				$oDB->sInsert("galaxy_plan_script", array_keys($aScriptVal), array_values($aScriptVal));
			}
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('galaxy_plan',$this->iPrincipleNo,$_GET['func'],$_GET['action']);
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CPlan->vUpdatePlan: ".$e->getMessage());
		}
    }

}

?>