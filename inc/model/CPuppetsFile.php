<?php
/**
 * 身份 file
 */
include_once('../inc/model/CGalaxyClass.php');
class CPuppetsFile extends CGalaxyClass
{
	private $sPuppetsUuid;	//身份uuid
	private $sPuppetsFileUuid;	//檔案uuid
	public $sFileTitle;		//檔案備註
	public $sFileFile;		//檔案名稱
	public $sFileName;		//原始檔案名稱
	public $sFileType;		//檔案類型
	public $iFileSize;		//檔案大小
	public $bImageFile;		//image file

	//database setting
	static protected $sDBName = 'PUPPETS';

	public function __get($varName)
	{
		return $this->$varName;
	}
	/**
	* @param $iType 0 unix絕對路徑 1  網頁相對位置
	* @return 路徑 
	* @desc 取得儲存的路徑
	*/
	static public function sGetDataPath($iType=0) {
		if($iType) return "/data/puppets"; //網頁相對位置
		else { 
			$sPath=$_SERVER["DOCUMENT_ROOT"]; //unix絕對路徑
			return $sPath."/data/puppets";	
		}
	}

	static public function sGetFilePath($sFileNo = ""){
		if($sFileNo === "") return "";
		if(preg_match("/^([a-z0-9]{1})([a-z0-9]{1})([a-z0-9]{1})/i",strtoupper($sFileNo),$aMatch)){
			$sFolder = $aMatch[1];
			$sFolder2 = $aMatch[2];
			$sFolder3 = $aMatch[3];
			if($sFolder && $sFolder2 && $sFolder3)
				return "$sFolder/$fsFolder2/$sFolder3"; 
		}
		
		return "";
		
	}
	/*
		get $oCPuppetsFile by certain file_no($sPuppetsFileUuid)
	*/
	static public function oGetFile($sPuppetsFileUuid) {
		
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT * FROM galaxy_puppets_files WHERE file_no = '$sPuppetsFileUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCPuppetsFile = new CPuppetsFile($aRow);

		return $oCPuppetsFile;
	}


	/*
		get all file in an array
		if $sSearchSql is given, query only match files
		example: CPuppetsFile::aAllFileInfo('5','','ORDER BY createtime DESC LIMIT 0,10')
		CAUTION: this function may query lots of data from galaxy_puppets_files DB, make sure you need all of these files
	*/
	static public function aAllFile($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllfile = array();
		$sSql = "SELECT * FROM galaxy_puppets_files";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllfile[] = new CPuppetsFile($aRow);
		}
		return $aAllfile;
	}
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(file_no) as total FROM galaxy_puppets_files";
		
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
		constructor of $oCPuppetsFile
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData){
		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CPuppetsFile: __construct failed, require an array");
		//initialize vital member
		if(isset($multiData['file_no']))
			$this->sPuppetsFileUuid = $multiData['file_no'];
		else
			$this->sPuppetsFileUuid = CMisc::uuid_v1();
		


		if($multiData['puppets_no']==='')
			throw new Exception("CPuppetsFile: __construct failed, puppets_no  needed");

		//initialize optional member
		
		$this->sPuppetsUuid = $multiData['puppets_no'];
		$this->sFileTitle = $multiData['file_title'];
		$this->sFileFile = $multiData['file_file'];
		$this->sFileName = $multiData['file_name'];
		$this->sFileType = $multiData['file_type'];
		$this->iFileSize = $multiData['file_size'];
		//galaxy class memeber
		$this->bStatus = $multiData['file_status'];
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];

		$this->bImageFile = false;
		if(preg_match("/^image/i",$multiData['file_type'],$match)){
			$this->bImageFile = true;
		}

		
	}

	/*
		overwrite $this with another $oCPuppetsFile object, which has same sPuppetsUuid and some different value
	*/
	public function vOverwrite($oCPuppetsFile){
		//if not a CDoc object or uuid not match
		
		if(get_class($oCPuppetsFile)!=='CPuppetsFile' || $this->sPuppetsFileUuid!==$oCPuppetsFile->sPuppetsFileUuid)
			throw new Exception('CPuppetsFile->vOverwrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='sPuppetsFileUuid')
				continue;
			$this->$name = $oCPuppetsFile->$name;	//overwrite
		}
	}

	/*
		upload file
		if upload no file , return flase
		if upload any file success, return true
		throw exception if error
		
	*/
	private function vUploadFile($aFile){
		$oDB = self::oDB(self::$sDBName);
		if(!$aFile['file_file']['tmp_name'])
			return;

		if($aFile['file_file']['tmp_name']){
			$folder_no = "";
			
			if(preg_match("/^([a-z0-9]{1})([a-z0-9]{1})([a-z0-9]{1})/i",strtoupper($this->sPuppetsFileUuid),$match)){
				$folder_no = $match[1];
				$folder_no2 = $match[2];
				$folder_no3 = $match[3];
			}else{
				@unlink($aFile['file_file']['tmp_name']);   
				throw new Exception('CPuppetsFile->vUploadFile: file_no parse error');
			}

			$sDir = self::sGetDataPath();
		
			$sFileName = "";
			
			if(!is_dir($sDir))mkdir($sDir,0777);
			$sDir=$sDir."/$folder_no/"; 
			if(!is_dir($sDir))mkdir($sDir,0777);
			$sDir=$sDir."/$folder_no2/"; 
			if(!is_dir($sDir))mkdir($sDir,0777);
			$sDir=$sDir."/$folder_no3/"; 
			if(!is_dir($sDir))mkdir($sDir,0777);
			
			$aTmp = explode(".",$aFile['file_file']['name']);
			$c = count($aTmp);
			if($aTmp[$c-1])
				$sFileName = md5(uniqid(rand(), true)).".".$aTmp[$c-1];
			else
				$sFileName = md5(uniqid(rand(), true)).".".$aTmp[1];
			
           		if(copy($aFile['file_file']['tmp_name'], $sDir.$sFileName)) { 		
				chmod($sDir.$sFileName,0777);
				
				//insert file name to db
				$aValues = array(	"file_file"=>$sFileName,
									"file_name"=>$aFile['file_file']['name'],
									"file_type"=>$aFile['file_file']['type'],
									"file_size"=>$aFile['file_file']['size']
									);
				try{
					$oDB->sUpdate("galaxy_puppets_files",array_keys($aValues),array_values($aValues),"file_no='{$this->sPuppetsFileUuid}'");
					@unlink($aFile['file_file']['tmp_name']);
				}catch (Exception $e){
					@unlink($sDir.$sFileName);
					@unlink($aFile['file_file']['tmp_name']);
					throw new Exception('CPuppetsFile->vUploadFile: '.$e->getMessage());
				}
			}				
		}
		return;
	}

	/*
		add file to certain system
		return file_no(string of uuid)
		
		step1: new a $oCPuppetsFile object with all info
		step2: call this function
	*/
	public function sAddFile(){
		$oDB = self::oDB(self::$sDBName);
		
		try{
			$oDB->vBegin();
			//insert doc attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'file_no'=>$this->sPuppetsFileUuid,
						'puppets_no'=>$this->sPuppetsUuid,
						'file_title'=>$this->sFileTitle,
						'file_status'=>$this->bStatus,
						'user_no'=>$this->__iUserNo,
						'createtime'=>$sDate,
						'modifiedtime'=>$sDate
					);
			
			$oDB->sInsert("galaxy_puppets_files",array_keys($aValues),array_values($aValues));
			//insert all doc element

			//upload file
			$this->vUploadFile($_FILES);
			$oDB->vCommit();
			return $this->sPuppetsFileUuid;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('CPuppetsFile->sAddFile: '.$e->getMessage());
		}
	}

	/*
		update file data
		step1: $oCPuppetsFile = CPuppetsFile: oGetFile($sPuppetsFileUuid)  and create a CPuppetsFile with all info
		step2: $oCPuppetsFile->overwrite($oNewCPuppetsFile);
		step3: call this function
	*/
	public function vUpdateFile(){
		$oDB = self::oDB(self::$sDBName);
		
		try{
			$oDB->vBegin();
			//update doc attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'file_title'=>$this->sFileTitle,
						'file_status'=>$this->bStatus,
						'modifiedtime'=>$sDate
					);
			$oDB->sUpdate("galaxy_puppets_files",array_keys($aValues),array_values($aValues),"file_no='{$this->sPuppetsFileUuid}'");
			
			//upload file
			$this->vUploadFile($_FILES);
			if(!is_null($_FILES['file_file']['name'])){
				//刪除舊的檔案
				$sFilePath = self::sGetDataPath()."/".self::sGetFilePath($this->sPuppetsFileUuid)."/".$this->sFileFile;
				@unlink("$sFilePath");
			}
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CPuppetsFile->vUpdateDoc: '.$e->getMessage());
		}
	}

	public function vActiveFile($iFlag=0){
		$oDB = self::oDB(self::$sDBName);
		try{
			$oCUser = self::$session->get('oCurrentUser');
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'file_status'=>$iFlag,
						'user_no'=>$oCUser->__iUserNo,
						'modifiedtime'=>$sDate
					);
			$oDB->sUpdate("galaxy_puppets_files",array_keys($aValues),array_values($aValues),"file_no='{$this->sPuppetsFileUuid}'");
			
			$oCUser->oOwner()->vAddUserLog("galaxy_puppets_files",$this->sPuppetsFileUuid,$_GET['func'],$_GET['action']);
			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCPuppets->vActivePuppets: '.$e->getMessage());
		}
	}

}
?>