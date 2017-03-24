<?php

include_once('../inc/model/CGalaxyClass.php');

class CDocFolder extends CGalaxyClass
{
	private $iFolderNo;
	public $iParentFolderNo;
	public $sName;
	public $iTypeNo;

	//database setting
	static protected $sDBName = 'DOCS';

	//instance pool
	static public $aInstancePool = array();

	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CDocFolder: __construct failed, require an array");
		//initialize vital member
		$this->iFolderNo = $multiData['folder_no'];
		$this->iParentFolderNo = $multiData['parent_folder_no'];
		$this->sName = $multiData['folder_name'];
		$this->iTypeNo = $multiData['folder_type'];
		//galaxy class memeber
		$this->bStatus = $multiData['folder_status'];
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];
	}

	public function __get($varName) {
        return $this->$varName;
    }

	static public function oGetFolder($iFolderNo){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_docs_folder WHERE folder_no=$iFolderNo";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCDocFolder = new CDocFolder($aRow);
		self::$aInstancePool[$iFolderNo] = $oCDocFolder;

		return $oCDocFolder;
	}

	static public function aAllFolder($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_docs_folder";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllCDocFolder = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['folder_no']])){
				self::$aInstancePool[$aRow['folder_no']] = new CDocFolder($aRow);
			}
			$aAllCDocFolder[] = self::$aInstancePool[$aRow['folder_no']];
		}
		return $aAllCDocFolder;
	}

	static public function oGetFolderByName($sSystemName){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_docs_folder WHERE folder_name='$sSystemName'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if(!$aRow || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCDocFolder = new CDocFolder($aRow);
		return $oCDocFolder;
	}

    public function iAddFolder(){
    	$oDB = self::oDB(self::$sDBName);
    	$oCurrentUser = self::$session->get('oCurrentUser');
		$aValues = array(	'parent_folder_no'=>$this->iParentFolderNo,
					'folder_name'=>$this->sName,
					'folder_type'=>$this->iTypeNo,
					'folder_status'=>$this->bStatus,
					'createtime'=>date("Y-m-d H:i:s"),
					'modifiedtime'=>date("Y-m-d H:i:s")
		);
		try{
			$oDB->sInsert("`galaxy_docs_folder`", array_keys($aValues), array_values($aValues));
			$this->iFolderNo = $oDB->iGetInsertId();
			$oCurrentUser->vAddUserLog("galaxy_docs_folder",$this->iFolderNo,'docs_folder','add');
			return $this->iFolderNo;
		}catch (Exception $e){
			throw new Exception("CDocFolder->iAddFolder: ".$e->getMessage());
		}
    }
}
?>
