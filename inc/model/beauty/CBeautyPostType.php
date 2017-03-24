<?php
include_once('../inc/model/CGalaxyClass.php');

Class CBeautyPostType extends CGalaxyClass
{

	private $iPostTypeNo;
	public $sName;
	public $sDesc;
	public $iWordMin;
	public $iWordMax;
	public $iPicMin;
	public $iPicMax;
	public $fWeight;

	//database setting
	static protected $sDBName = 'SITE';

	//instance pool
	static public $aInstancePool = array();

	/*
		get $oCBeautyPostType certain post_type_no
	*/
	static public function oGetType($iPostTypeNo){
		$oDB = self::oDB(self::$sDBName);

		//if already queryed
		if(!is_null(self::$aInstancePool[$iPostTypeNo]))
			return self::$aInstancePool[$iPostTypeNo];

		//query from beauty DB
		$sSql = "SELECT * FROM post_type WHERE post_type_no='$iPostTypeNo'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oPostType = new CBeautyPostType($aRow);
		self::$aInstancePool[$iPostTypeNo] = $oPostType;

		return $oPostType;
	}

	/*
		get all beauty post type in an array
		if $sSearchSql is given, query only match types
	*/
	static public function aAllType($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllProject = array();
		$sSql = "SELECT * FROM `post_type`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['post_type_no']])){
				self::$aInstancePool[$aRow['post_type_no']] = new CBeautyPostType($aRow);
			}
			$aAllType[] = self::$aInstancePool[$aRow['post_type_no']];
		}
		return $aAllType;
	}

	/*
		get count of post_type which match query
	*/
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(post_type_no) as total FROM post_type";
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
			throw new Exception("CBeautyPostType: __construct failed, require an array");
		if(!empty($multiData['post_type_no']))
			$this->iPostTypeNo = $multiData['post_type_no'];
		$this->sName = $multiData['type_name'];
		$this->sDesc = $multiData['type_desc'];
		$this->iWordMin = $multiData['word_min'];
		$this->iWordMax = $multiData['word_max'];
		$this->iPicMin = $multiData['picture_min'];
		$this->iPicMax = $multiData['picture_max'];
		$this->fWeight = $multiData['weight'];
		//galaxy class member
		/*none*/
	}

	public function __get($varName)
    {
        return $this->$varName;
    }

    
}

?>