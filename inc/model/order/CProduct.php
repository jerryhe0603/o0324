<?php

/**
 *  @desc 產品
 *  @creatd 2015/10/23
 */

include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');

Class CProduct extends CGalaxyClass {
	
	static protected $sDBName = 'ORDER';	
	
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CProduct: __construct failed: require an array.");
		
		$this->product_no = $multiData['product_no'];
		$this->product_name = $multiData['product_name'];
		$this->flag = $multiData['flag'];
		$this->created = $multiData['created'];
		$this->modified = $multiData['modified'];
		$this->user_no = $multiData['user_no'];
		$this->edit_user_no = $multiData['edit_user_no'];
		$this->product_order = $multiData['product_order'];
	}

	
	public function __get($varName){
		return $this->$varName;
	}
	

	static public function oGetProduct($product_no){
		$oDB=self::oDB(self::$sDBName);
		$iDBq = $oDB->iQuery("SELECT * FROM `product` WHERE product_no='$product_no'");
		$aRow = $oDB->aFetchAssoc($iDBq);
		if($aRow===false||$oDB->iNumRows($iDBq)>1)
			return null;
		$oCProduct = new CProduct($aRow);
		return $oCProduct;
	}
	
	

	static public function aAllProduct($sSearchSql='', $sPostfix=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT * FROM `product` ";
		if ($sSearchSql!=='') $sSql.=" WHERE $sSearchSql";
		if ($sPostfix!=='') $sSql.=" $sPostfix ";
		$aAllData = array();
		$iDBq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDBq)){
			$aAllData[] = new CProduct($aRow);
		}
		return $aAllData;
	}
	

	static public function iGetCount($sSearchSql=''){
		$oDB=self::oDB(self::$sDBName);
		$sSql=" SELECT count(`product_no`) as total FROM `product`";
		if ($sSearchSql!=='') $sSql.=" WHERE $sSearchSql ";
		$iDBq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDBq);
		if ($aRow) $iCount=(int)$aRow['total'];
		else $iCount=0;
		return $iCount;
	}
	
	
	/**
	 *  @desc 新增
	 *  @created 2015/10/23
	 */
	public function vAddProduct(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		try{
			$oDB->vBegin();
			//insert 
			$aValues = array(
				'product_name' => $this->product_name,
				'flag' => $this->flag,
				'user_no' => $this->user_no,
				'product_order' => $this->product_order,
				'edit_user_no' => $this->edit_user_no,
				'modified' => date("Y-m-d H:i:s"),
				'created' => date("Y-m-d H:i:s"),
			);
			$oDB->sInsert("product", array_keys($aValues), array_values($aValues));
			$this->product_no = $oDB->iGetInsertId();
			//add post count depart by type
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog('product',$this->product_no,$_GET['func'],$_GET['action']);
			return $this->product_no;
		}catch(Exception $e){
			$oDB->vRollback();
			throw new Exception("CProduct->vAddProduct: ".$e->getMessage());
		}
	}
		

	/**
	 *  @desc 更新
	 *  @created 2015/10/23
	 */
	public function vUpdateProduct(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(
			'product_name' => $this->product_name,
			'flag' => $this->flag,
			'product_order' => $this->product_order,
			'edit_user_no' => $this->edit_user_no,
			'modified' => date("Y-m-d H:i:s")
		);
		try{
			$oDB->vBegin();
			$oDB->sUpdate("product", array_keys($aValues), array_values($aValues), "`product_no` = {$this->product_no}");
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("product",$this->product_no,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CProduct->vUpdateProduct: ".$e->getMessage());
		}
	}
	
	
	/**
	 *  @desc overwrite $this with another $oCProduct object, which has same stage_no and some different value
	 */
	public function vOverwrite($oCProduct){
		//if not a CDoc object or uuid not match
		if(get_class($oCProduct)!=='CProduct' || $this->product_no!==$oCProduct->product_no)
			throw new Exception('CProduct->vOverwrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='product_no' || is_null($oCProduct->$name))
				continue;
			$this->$name = $oCProduct->$name;	//overwrite
		}
	}	
	

	/**
	 *  @desc 更新狀態
	 *  @created 2015/10/23
	 */
	public function vActivate(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		if($this->flag==='1')
			$this->flag='0';
		else
			$this->flag='1';
		$aValues = array('flag'=>$this->flag);
		try{
			$oDB->sUpdate("product", array_keys($aValues), array_values($aValues),"product_no='{$this->product_no}'");
			$oCurrentUser->vAddUserLog('product',$this->product_no,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CProduct->vActivate: ".$e->getMessage());
		}
	}
		
	
	/**
	 *  @desc 刪除
	 *  @created 2015/10/23
	 */
	public function vDelete(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');
		$this->flag='9';
		$aValues = array('flag'=>$this->flag);
		try{
			$oDB->sUpdate("product", array_keys($aValues), array_values($aValues),"product_no='{$this->product_no}'");
			$oCurrentUser->vAddUserLog('product',$this->product_no,$_GET['func'],$_GET['action']);
		}catch (Exception $e){
			throw new Exception("CProduct->vDelete: ".$e->getMessage());
		}
	}
	
	
	/**
	 *  @desc 建立者
	 *  @created 2015/10/23
	 */
	public function sGetAddUser(){
		//return "建立者";
		if (!$this->user_no) return '';
		$oUser = CUser::oGetUser($this->user_no);
		return $oUser->sName;
	}
		
		
	/**
	 *  @desc 編修者
	 *  @created 2015/10/23
	 */
	public function sGetEditUser(){
		if (!$this->edit_user_no) return '';
		$oUser = CUser::oGetUser($this->edit_user_no);
		return $oUser->sName;
	}
	
	
}
/* End of File */