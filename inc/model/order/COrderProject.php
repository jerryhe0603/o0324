<?php

$sNowPath = realpath(dirname(dirname(dirname(dirname( __FILE__ )))));
include_once($sNowPath.'/inc/model/CGalaxyClass.php');
include_once($sNowPath.'/inc/CMisc.php');
include_once($sNowPath.'/inc/model/order/COrder.php');
include_once($sNowPath.'/inc/model/CCompanyBrand.php');
include_once($sNowPath.'/inc/model/CUserFront.php');

Class  COrderProject extends CGalaxyClass{
	
	static public $aService=array(
					//0 => "無",
					//1 => "圖文專案",
					//2 => "ICB",
					//3 => "台灣好東西",
					4 => "美女隊"
					);
	static public $aProjectNoPrefix=array(
					//1 => "1_",
					//2 => "2_",
					//3 => "goods_",
					4 => "beauty_"
					);

	private $sProjectUuid;
	private $sOrderUuid;
	public $iServiceId;
	public $iBrandId;
	public $iUserNo;
	public $bStatus;

	private $__oCompanyBrand;
	private $__oOrder;
	private $__aClient;

	static protected $sDBName = 'ORDER';


	public function __construct($multiData){
		parent::__construct($multiData);
		
		if(!is_array($multiData))
			throw new Exception("COrderProject: __construct failed, require an array");
		if(!empty($multiData['project_no']))
			$this->sProjectUuid = $multiData['project_no'];
		else
			throw new Exception("COrderProject: __construct failed, require project_no");
		
		if(is_null($multiData['user_no'])){
			$oCUser = self::$session->get('oCurrentUser');
			$multiData['user_no'] = $oCUser->iUserNo;
		}else{
			$this->iUserNo = $multiData['user_no'];
		}

		$this->sOrderUuid=$multiData['order_no'];
		$this->iServiceId=$multiData['service_id'];
		$this->iBrandId=$multiData['cb_id'];
		$this->bStatus=$multiData['status'];

		$this->sCreateTime=$multiData['create_time'];
		$this->sModifiedTime=$multiData['modify_time'];
	}

	public function __get($varName){
		   return $this->$varName;
	}


	static public function oGetOrderProject($sProjectUuid){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_project` WHERE `project_no` = '$sProjectUuid' ";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		// if($aRow===false||$oDB->iNumRows($iDBq)>1)
		if(!$aRow || $oDB->iNumRows($iDBq)>1)
			return null;
		$oCOrderProject = new COrderProject($aRow);
		return $oCOrderProject;
	}

	static public function aAllOrderProject($sSearchSql='', $sPostfix=''){
		$aAllData = array();
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT * FROM `order_project` ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql ";
		if($sPostfix!=='')
			$sSql.=$sPostfix;
		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllData[]=new COrderProject($aRow);
		}
		return $aAllData;
	}

	static public function iGetCount($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT count(project_no) as total FROM order_project ";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql ";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		if($aRow)
			$iCount=(int)$aRow['total'];
		else
			$iCount=0;
		return $iCount;
	}

	static public function aAllProjectJoinOrder($sSearchSql, $sPostfix){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT * FROM `order_project` AS op LEFT JOIN `order` AS o ON o.`order_no` = op.`order_no`";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql ";
		if($sPostfix!=='')
			$sSql.=$sPostfix;
		$iDBq=$oDB->iQuery($sSql);
		while($aRow=$oDB->aFetchAssoc($iDBq)){
			$aAllProjectJoinOrder[]=new COrderProject($aRow);
		}
		return $aAllProjectJoinOrder;
	}

	static public function iGetCountProjectJoinOrder($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql="SELECT count(op.`project_no`)as total FROM `order_project` AS op LEFT JOIN `order` AS o ON o.`order_no` = op.`order_no`";
		if($sSearchSql!=='')
			$sSql.=" WHERE $sSearchSql ";
		$iDBq=$oDB->iQuery($sSql);
		$aRow=$oDB->aFetchAssoc($iDBq);
		if($aRow)
			$iCount=(int)$aRow['total'];
		else
			$iCount=0;
		return $iCount;
	}

	public function sAdd(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{
			$oDB->vBegin();
			$aValues=array(
				'project_no'=>$this->sProjectUuid,
				'order_no'=>$this->sOrderUuid,
				'service_id'=>$this->iServiceId,
				'cb_id'=>$this->iBrandId,
				'user_no'=>$oCurrentUser->iUserNo,
				'create_time'=>$sDate,
				'modify_time'=>$sDate,
				'status'=>1
				);
			$oDB->sInsert('order_project', array_keys($aValues), array_values($aValues));
			$oCurrentUser->vAddUserLog('order_project',$this->sProjectUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
			return $this->sProjectUuid;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception('COrder->sAdd:'.$e->getMessage());
		}
	}

	public function vOverwrite($oCOrderProject){
		if(get_class($oCOrderProject)!=='COrderProject'||$this->sProjectUuid!==$oCOrderProject->sProjectUuid){
			throw new Exception ('COrderProject->vOverwrite: wrong class or id');
		}
		foreach($this as $key => $value){
			if($key==='sProjectUuid'|| $key==='sOrderUuid' || is_null($oCOrderProject->$key))
				continue;
			$this->$key=$oCOrderProject->$key;
		}
	}

	public function vUpdate(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$sDate = date("Y-m-d H:i:s");

		try{	
			$oDB->vBegin();
			$aValues=array(
				'service_id'=>$this->iServiceId,
				'cb_id'=>$this->iBrandId,
				'user_no'=>$oCurrentUser->iUserNo,
				'modify_time'=>$sDate
				);
			$oDB->sUpdate('order_project', array_keys($aValues), array_values($aValues), "`project_no`='{$this->sProjectUuid}'");
			$oCurrentUser->vAddUserLog('order_project',$this->sProjectUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("COrderProject->vUpdate: ".$e->getMessage());
		}
	}

	public function vDeactivate(){
		$oDB=self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		
		$aValues = array('status'=>0);
		try{
			$oDB->vBegin();
			$oDB->sUpdate("order_project", array_keys($aValues), array_values($aValues),"`project_no`='{$this->sProjectUuid}'");
			$oCurrentUser->vAddUserLog('order_project',$this->sProjectUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("COrderProject->vDeactivate: ".$e->getMessage());
		}
	}

	public function oCompanyBrand(){
		if(empty($this->__oCompanyBrand)){
			$this->__oCompanyBrand=CCompanyBrand::oGetCompanyBrand($this->iBrandId);
		}
		return $this->__oCompanyBrand;
	}

	public function oOrder(){
		if(empty($this->__oOrder)){
			$this->__oOrder=COrder::oGetOrder($this->sOrderUuid);
		}
		return $this->__oOrder;
	}

	public function aClient(){
		if(!is_null($this->__aClient))
			return $this->__aClient;

		$this->__aClient = CUserFront::aUserByProject($this->sProjectUuid);

		return $this->__aClient;
	}
    
    /**
     *  @desc 取得已執行篇數
     *  @created 2016/03/22
     */
    public function iGetRunArticleCount(){
        $sApiUrl = "http://".BEAUTY2_SERVER."/api/api.get_execute_article.php?sProjectUuid=".$this->sProjectUuid;
        $sUrl = $sApiUrl."&PHPSESSID=".session_id();
        $aOptions=array();
        $aResult=CMisc::aCurl($sUrl,$aOptions);
        
        if(!empty($aResult)){
            return $aResult;
        }
        return 0;
    }
    
    
}
?>