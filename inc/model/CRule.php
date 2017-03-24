<?php

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

include_once($sNowPath.'/inc/model/CGalaxyClass.php');
include_once($sNowPath.'/inc/model/CCategory.php');
class CRule extends CGalaxyClass
{
	//vital member
	private $iRuleNo;	//rule_no in galaxy_user.galaxy_rule, read only after construct
	//optional member
	public $sName;
	public $sDesc;
	public $sFunc;
	public $sAction;
	public $iCategoryNo;
	private $__oCCategory;
	//database setting
	static protected $sDBName = 'USER';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCRule by certain rule_no
	*/
	static public function oGetRule($iRuleNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_rule WHERE rule_no = '$iRuleNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCRule = new CRule($aRow);
		self::$aInstancePool[$iRuleNo] = $oCRule;

		return $oCRule;
	}

	/*
		get all rule in an array
		if $sSearchSql is given, query only match rules
		example1:$aCRules = CRule::aAllRule("category_no=5");
		example2:$aCRules = CRule::aAllRule("func='puppets'");
	*/
	static public function aAllRule($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllRule = array();
		$sSql = "SELECT * FROM galaxy_rule";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(!isset(self::$aInstancePool[$aRow['rule_no']])){
				self::$aInstancePool[$aRow['rule_no']] = new CRule($aRow);
			}
			$aAllRule[] = self::$aInstancePool[$aRow['rule_no']];
		}
		return $aAllRule;
	}

	/*
		get all rule sorted by category
	*/
	static public function aAllRuleInCategory(){
		$oDB = self::oDB(self::$sDBName);
		$aCategoryPool = CCategory::aAllCategory();
		//print_r($aCategoryPool);
		$aCategoryTree =array();
		foreach ($aCategoryPool as $iCategoryNo => $oCCategory) {
			if($oCCategory->iParentCategoryNo==='0'){
				$aCategoryTree[$iCategoryNo] = $oCCategory;
			}else{
				$oParentCategory = $aCategoryPool[$oCCategory->iParentCategoryNo];	//pointer to parent category object
				$oParentCategory->aChildCategory[$iCategoryNo] = $oCCategory;	//add child category pointer to parent's child category array
			}
		}
		$sSql = "SELECT * FROM galaxy_rule";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$oCRule = new CRule($aRow);
			$oCCategory = $aCategoryPool[$oCRule->iCategoryNo];	//pointer to that category object
			$oCCategory->aCRule[$oCRule->iRuleNo] = $oCRule;
		}
		return $aCategoryTree;
	}


	/*
		get count of rule which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(rule_no) as total FROM galaxy_rule";
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

	/*
		constructor of $oCRule
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CRule: __construct failed, require an array");
		//initialize vital member
		$this->iRuleNo = $multiData['rule_no'];
		$this->sName = $multiData['rule_name'];
		$this->sDesc = $multiData['rule_desc'];
		$this->sFunc = $multiData['func'];
		$this->sAction = $multiData['action'];
		$this->iCategoryNo = $multiData['category_no'];
		//galaxy class memeber
		$this->bStatus = $multiData['status'];
	}

	//php default function, let private member become read-only class member for others
    public function __get($varName)
    {
        return $this->$varName;
    }

    /*
		set & get category of this rule
    */
	public function oCategory(){
		if(empty($this->__oCCategory)){
			$this->__oCCategory = CCategory::oGetCategory($this->iCategoryNo);
		}
		return $this->__oCCategory;
	}

	/*
		update rule data in this CRule to galaxy_user DB
		if you want to update rule data in DB, get a CRule of that rule, change member value, and call this function
		$oCRule->iRuleNo is not changeable
	*/
	public function vUpdateRule(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'rule_name'=>$this->sName, 
							'rule_desc'=>$this->sDesc,
							'func'=>$this->sFunc,
							'action'=>$this->sAction,
							'category_no'=>$this->iCategoryNo,
							'status'=>$this->bStatus
							);
		try{
			$oDB->sUpdate("`galaxy_rule`", array_keys($aValues), array_values($aValues), "`rule_no` = {$this->iRuleNo}");
			$oCurrentUser->vAddUserLog("galaxy_rule",$this->iRuleNo,'rule','update');
		}catch (Exception $e){
			throw new Exception("CRule->vUpdateRule: ".$e->getMessage());
		}
	}

	/*
		add $oCRule data to galaxy_user DB
		if you want to create a new rule in DB, new a CRule and call this function
		return insert id(rule_no), user may find this new CRule by rule_no
	*/
	public function iAddRule(){
		$oDB = self::oDB(self::$sDBName);
		$oCurrentUser = self::$session->get('oCurrentUser');

		$aValues = array(	'rule_name'=>$this->sName, 
							'rule_desc'=>$this->sDesc,
							'func'=>$this->sFunc,
							'action'=>$this->sAction,
							'category_no'=>$this->iCategoryNo,
							'status'=>$this->bStatus
							);
		try{
			$oDB->sInsert("`galaxy_rule`", array_keys($aValues), array_values($aValues));
			$this->iRuleNo = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog("galaxy_rule",$this->iRuleNo,'rule','add');
			return $this->iRuleNo;
		}catch (Exception $e){
			throw new Exception("CRule->iAddRule: ".$e->getMessage());
		}
	}
}
?>