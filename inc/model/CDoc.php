<?php
include_once('../inc/CMisc.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/CDocElement.php');
include_once('../inc/model/CDocDBConfig.php');

class CDoc extends CGalaxyClass
{
	
	private $sDocUuid;
	private $sEncode;
	public $sDocsNote;
	public $sFileFile;
	public $sFileName;
	public $sFileType;
	public $iFileSize;
	public $bStatus;

	//database setting
	//static protected $sDBName = 'DOCS';
	private $sSystem;

	//instance pool
	static public $aInstancePool = array();

	private $__aUrl = array();
	private $__aCDocElement = array();	//CAUTION: this is a map of element_mapping_no to CDocElement object

	/*
		get $oCDoc by certain docs_no($sDocUuid) from certain doc database
	*/
	static public function oGetDoc($sDocUuid){
		$aTempData = explode('_', $sDocUuid);
		$sSystem = $aTempData[0];
		$iFolderNo = $aTempData[1];

		$oDB = self::oDB($sSystem);

		$sSql = "SELECT * FROM galaxy_docs_$iFolderNo WHERE `docs_no`='$sDocUuid'";
		$iDbq = $oDB->iQuery($sSql);
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;
		$oCDoc = new CDoc($aRow,$sSystem);
		$oCDoc->aDocElement();
		self::$aInstancePool[$sDocUuid] = $oCDoc;

		return $oCDoc;
	}

	/*
		get all doc in an array
		if $sSearchSql is given, query only match docs
		example: CDoc::aAllDoc('beauty','5','','ORDER BY createtime DESC LIMIT 0,10')
		CAUTION: this function may query lots of data from genesis_docs DB, make sure you need all of these docs
	*/
	static public function aAllDoc($sSystem,$iFolderNo,$sSearchSql='',$sPostFix=''){
		$oDB = self::oDB($sSystem);
		$sSql = "SELECT * FROM galaxy_docs_$iFolderNo";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		$aAllDoc = array();
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			if(is_null(self::$aInstancePool[$aRow['docs_no']])){
				self::$aInstancePool[$aRow['docs_no']] = new CDoc($aRow,$sSystem);
			}
			$aAllDoc[] = self::$aInstancePool[$aRow['docs_no']];
		}
		return $aAllDoc;
	}

	/*
		check if any doc which has same pattern exists, if any doc does, return it's uuid, else return empty string
	*/
	static function sMd5Exist($sEncode,$sSystem){
		$oDB = self::oDB($sSystem);
		$aFolder = CDocFolder::aAllFolder($sSystem);
		foreach ($aFolder as $oFolder) {
			$iFolderNo = $oFolder->iFolderNo;
			$sSql = "SELECT `docs_no` FROM galaxy_docs_$iFolderNo WHERE `docs_encode`='$sEncode'";
			$iDbq = $oDB->iQuery($sSql);
			$aRow = $oDB->aFetchAssoc($iDbq);
			if(!empty($aRow['docs_no'])){
				return $aRow['docs_no'];
			}
		}

		return '';
	}

	/*
		constructor of $oCDoc
		some class member are essential, must be initialized, or throw exception
		some class member are optional, may not be initialized
	*/
	public function __construct($multiData,$sSystem='',$iFolderNo=''){
		if($sSystem==='')
			throw new Exception("CDoc: __construct failed, require system target");

		parent::__construct($multiData);
		if(!is_array($multiData))
			throw new Exception("CDoc: __construct failed, require an array");
		//initialize vital member
		if(isset($multiData['docs_no']))
			$this->sDocUuid = $multiData['docs_no'];
		elseif($iFolderNo!=='' && $sSystem!='')
			$this->sDocUuid = $sSystem."_".$iFolderNo."_".CMisc::uuid_v1();	//$iFolderNo is diff by system and year
		else
			throw new Exception("CDoc: __construct failed, docs_no or folder_no needed");
		//initialize optional member
		$this->sEncode = $multiData['docs_encode'];
		$this->sDocsNote = $multiData['docs_note'];
		$this->sFileFile = $multiData['file_file'];
		$this->sFileName = $multiData['file_name'];
		$this->sFileType = $multiData['file_type'];
		$this->iFileSize = $multiData['file_size'];
		//galaxy class memeber
		$this->bStatus = $multiData['docs_status'];
		$this->sCreateTime = $multiData['createtime'];
		$this->sModifiedTime = $multiData['modifiedtime'];

		//doc database setting
		$this->sSystem = $sSystem;

		//if got $multiData from $_POST, set doc elements
		$aDocFields = array();
		//get all element_mapping_no that needs to be set
		foreach($multiData AS $key => $val){
			if(preg_match("/^element_mapping_no_([0-9]+)$/i",$key,$match)){
				$aDocFields[] = $match[1];
			}
		}
		$iOrder = 0;
		foreach ($aDocFields as $iEleMapNo) {
			$iOrder++;
			$aEleInit =array(	'docs_no'=>$this->sDocUuid,
								'element_mapping_no'=>$iEleMapNo,
								'element_mapping_value'=>$multiData['element_mapping_no_'.$iEleMapNo],
								'fields_order'=>$iOrder,
								'fields_status'=>($multiData['fields_status_'.$iEleMapNo])?'1':'0'
								);
			$oCDocElement = new CDocElement($aEleInit,$this->sSystem);
			if(isset($multiData['element_mapping_option_no_'.$iEleMapNo])){
				$oCDocElement->vSetOption($multiData['element_mapping_option_no_'.$iEleMapNo]);
			}
			$this->vSetDocElement($oCDocElement);
		}
	}

	public function __get($varName)
    {
        if(method_exists($this,$varName))
        	return $this->$varName();

        return $this->$varName;
    }

    public function aUrl(){
    	if(empty($this->__aUrl)){
    		$aTempData = explode('_', $this->sDocUuid);
			$sSystem = $aTempData[0];
			$iFolderNo = $aTempData[1];

			$oDB = self::oDB($sSystem);	//equal to $oDB = self::oDB($this->sSystem);

			$sSql = "SELECT * FROM galaxy_docs_url_$iFolderNo WHERE `docs_no`='{$this->sDocUuid}'";
			$iDbq = $oDB->iQuery($sSql);
			while($aRow = $oDB->aFetchAssoc($iDbq)){
				$this->__aUrl[] = $aRow['url'];
			}
    	}
    	return $this->__aUrl;
    }

    public function vSetUrl($aUrl){
    	$aTempData = explode('_', $this->sDocUuid);
		$sSystem = $aTempData[0];
		$iFolderNo = $aTempData[1];

		$oDB = self::oDB($sSystem);	//equal to $oDB = self::oDB($this->sSystem);

    	$this->__aUrl = $aUrl;
    	//clear and insert assoc url
    	try{
   		
    		$oDB->vBegin();
			$oDB->vDelete("galaxy_docs_url_$iFolderNo","`docs_no`='{$this->sDocUuid}'");
			foreach ($this->__aUrl as $sUrl) {
				$aUrlVal = array(	"docs_no"=>$this->sDocUuid,
									"url"=>$sUrl
									);
				$oDB->sInsert("galaxy_docs_url_$iFolderNo",array_keys($aUrlVal),array_values($aUrlVal));
			}
			$oDB->vCommit();
    	}catch(exception $e){
    		$oDB->vRollback();
    		throw new Exception("CDoc->vUpdateUrl failed");
    	}
    }

    public function sDownloadPath(){
    	return 'http://'.GENESIS_SERVER."/api/api.CDoc.php?action=download_file&docs_no=".$this->sDocUuid;
    }

    public function bIsImage(){
    	if(preg_match("/^image/i",$this->sFileType,$match)){
			return true;
		}else{
			return false;
		}
    }

	/*
		set & get doc element
	*/
	public function aDocElement(){
		$oDB = self::oDB($this->sSystem);
		if(empty($this->__aCDocElement)){
			$this->__aCDocElement = CDocElement::aAllDocElement($this->sDocUuid,$this->sSystem);
		}
		return $this->__aCDocElement;
	}

	/*
		overwrite $this with another $oCDoc object, which has same sDocUuid and some different value
	*/
	public function vOverwrite($oCDoc){
		//if not a CDoc object or uuid not match
		if(get_class($oCDoc)!=='CDoc' || $this->sDocUuid!==$oCDoc->sDocUuid)
			throw new Exception('CDoc->vOverwrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='sDocUuid' || is_null($oCDoc->$name))
				continue;
			$this->$name = $oCDoc->$name;	//overwrite
		}
	}

	/*
		update doc data
		step1: $oCDoc = CDoc:oGetDoc($sDocUuid) and create a CDoc with all info
		step2: $oCDoc->overwrite($oNewCDoc);
		step3: call this function
	*/
	public function vUpdateDoc(){
		$aTempData = explode('_', $this->sDocUuid);
		$sSystem = $aTempData[0];
		$iFolderNo = $aTempData[1];

		$oDB = self::oDB($sSystem);	//equal to $oDB = self::oDB($this->sSystem);
		$oCurrentUser = self::$session->get('oCurrentUser');

		try{
			$oDB->vBegin();

			$aEncodeContent = array();
			//update all doc element
			foreach ($this->__aCDocElement as $oCDocElement) {
				$oCDocElement->vUpdate();
				/*
				if(!empty($oCDocElement->aOption))
					$aEncodeContent['element_mapping_no_'.$oCDocElement->iElementMappingNo] =  $oCDocElement->aOption;
				else
					$aEncodeContent['element_mapping_no_'.$oCDocElement->iElementMappingNo] =  $oCDocElement->sValue;
				*/
				if($oCDocElement->iElementMappingNo == 5 || $oCDocElement->iElementMappingNo==32)
					$aEncodeContent['element_mapping_no_'.$oCDocElement->iElementMappingNo] =  $oCDocElement->sValue;
			}
			$this->sEncode = md5(json_encode($aEncodeContent));

			//update doc attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'docs_encode'=>$this->sEncode,
								'docs_note'=>$this->sDocsNote,
								'docs_status'=>$this->bStatus,
								'modifiedtime'=>$sDate
							);
			$oDB->sUpdate("galaxy_docs_$iFolderNo",array_keys($aValues),array_values($aValues),"docs_no='{$this->sDocUuid}'");
			
			//upload file
			$this->vUploadFile($_FILES);
			
			$oDB->vCommit();
			$oCurrentUser->vAddUserLog("galaxy_docs_".$iFolderNo,$this->sDocUuid,'docs','edit');
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CDoc->vUpdateDoc: '.$e->getMessage());
		}
	}

	/*
		add doc to certain system
		return docs_no(string of uuid)
		$iFolderNo decided by system, given by controller
		step1: new a $oCDoc object with all info
		step2: call this function
	*/
	public function sAddDoc(){
		$aTempData = explode('_', $this->sDocUuid);
		$sSystem = $aTempData[0];
		$iFolderNo = $aTempData[1];

		$oDB = self::oDB($sSystem);	//equal to $oDB = self::oDB($this->sSystem);
		$oCurrentUser = self::$session->get('oCurrentUser');

		if(is_null($oCurrentUser))
			$this->__iUserNo = 0;
		else
			$this->__iUserNo = $oCurrentUser->iUserNo;

		try{
			$oDB->vBegin();

			$aEncodeContent = array();
			//insert all doc element
			foreach ($this->__aCDocElement as $oCDocElement) {
				$oCDocElement->vAdd();
				/*
				if(!empty($oCDocElement->aOption))
					$aEncodeContent['element_mapping_no_'.$oCDocElement->iElementMappingNo] =  $oCDocElement->aOption;
				else
					$aEncodeContent['element_mapping_no_'.$oCDocElement->iElementMappingNo] =  $oCDocElement->sValue;
				*/
				if($oCDocElement->iElementMappingNo == 5 || $oCDocElement->iElementMappingNo==32)
					$aEncodeContent['element_mapping_no_'.$oCDocElement->iElementMappingNo] =  $oCDocElement->sValue;
			}
			$this->sEncode = md5(json_encode($aEncodeContent));

			//insert doc attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'docs_no'=>$this->sDocUuid,
								'docs_encode'=>$this->sEncode,
								'docs_note'=>$this->sDocsNote,
								'docs_status'=>$this->bStatus,
								'user_no'=>$oCurrentUser->iUserNo,
								'createtime'=>$sDate,
								'modifiedtime'=>$sDate
							);
			$oDB->sInsert("galaxy_docs_$iFolderNo",array_keys($aValues),array_values($aValues));

			//upload file
			$this->vUploadFile($_FILES);

			$oDB->vCommit();
			if(!is_null($oCurrentUser))
				$oCurrentUser->vAddUserLog("galaxy_docs_".$iFolderNo,$this->sDocUuid,'docs','add');
			return $this->sDocUuid;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('CDoc->sAddDoc: '.$e->getMessage());
		}
	}

	/*
		delete this CDoc from doc DB
	*/
	public function vDelete(){
		$aTempData = explode('_', $this->sDocUuid);
		$sSystem = $aTempData[0];
		$iFolderNo = $aTempData[1];

		$oDB = self::oDB($sSystem);	//equal to $oDB = self::oDB($this->sSystem);

		try{
			$oDB->vBegin();
			$oDB->vDelete("galaxy_docs_$iFolderNo","docs_no='{$this->sDocUuid}'");
			$oDB->vDelete("galaxy_docs_mapping_$iFolderNo","docs_no='{$this->sDocUuid}'");
			$oDB->vDelete("galaxy_docs_mapping_option_$iFolderNo","docs_no='{$this->sDocUuid}'");
			$oDB->vDelete("galaxy_docs_url","docs_no='{$this->sDocUuid}'");
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception("CDoc->vDelete: ".$e->getMessage());
		}
		
	}
	/*
		add CDocElement
		if same element_mapping_no exist in this doc, this function will overwrite it
	*/
	private function vSetDocElement($oCDocElement){
		$this->__aCDocElement[$oCDocElement->iElementMappingNo] = $oCDocElement;
	}

	/*
		upload file from subsystem, this function is call by subsystem, such like beauty2 or puppets
		throw exception if error
	*/
	public function vUploadFile($aFile){
		if(empty($aFile['file_file']['tmp_name']))	//no file to be upload
			return;

		$upload_url = 'http://'.GENESIS_SERVER.'/api/api.CDoc.php?action=upload_file&docs_no='.$this->sDocUuid;

		//file content
		$file_name_with_full_path = realpath($aFile['file_file']['tmp_name']);
        preg_match("/.*\//", $file_name_with_full_path, $match);
        $file_rename_with_full_path = $match[0].$aFile['file_file']['name'];
        rename($file_name_with_full_path, $file_rename_with_full_path);
        $aUpload['file_file'] = '@'.$file_rename_with_full_path.';type='.$aFile['file_file']['type'];

        //$aUpload['docs_no'] = $this->sDocUuid;

		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_VERBOSE, 1); 
        curl_setopt($ch, CURLOPT_URL, $upload_url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        //將post設定為 $params
        curl_setopt($ch, CURLOPT_POST, true); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $aUpload); 
        //回傳上傳的狀況                
        $jResult = curl_exec($ch);
		curl_close($ch);
		$aResult = json_decode($jResult,true);

        if($aResult['status'] == true)
        	return;
        else
        	throw new Exception("CDoc->vUploadFile:檔案上傳失敗");
	}

	/*
		accept upload request from subsystem, this function is call by genesis(doc) system
		throw exception if error
		modified from old CDoc.php
	*/
	public function vAcceptFile($aFile){
		if(empty($aFile['file_file']['tmp_name']))	//no file to be accept
			return;

		//get file folder_no and system
		$aTempData = explode('_', $this->sDocUuid);
		$sSystem = $aTempData[0];
		$iFolderNo = $aTempData[1];
		$sSubUuid = $aTempData[1].'_'.$aTempData[2];

		if(preg_match("/^([0-9]+)_([a-z0-9]{1})([a-z0-9]{1})/i",strtoupper($sSubUuid),$aMatch)){
			$sSubDir1 = $aMatch[1];
			$sSubDir2 = $aMatch[2];
			$sSubDir3 = $aMatch[3];
		}else{
			@unlink($aFile['file_file']['tmp_name']);   
			throw new Exception("CDoc->vAcceptFile: bad format : $this->sDocUuid ");
		}

		$sDir = self::sGetDataPath();
	
		$sFileName = "";
		
		if(!is_dir($sDir))mkdir($sDir,0777);
		$sDir=$sDir."/$sSubDir1/"; 
		if(!is_dir($sDir))mkdir($sDir,0777);
		$sDir=$sDir."$sSubDir2/"; 
		if(!is_dir($sDir))mkdir($sDir,0777);
		$sDir=$sDir."$sSubDir3/"; 
		if(!is_dir($sDir))mkdir($sDir,0777);
		
		$aTmp = explode(".",$aFile['file_file']['name']);
		$c = count($aTmp);
		if($aTmp[$c-1])
			$sFileName = md5(uniqid(rand(), true)).".".$aTmp[$c-1];
		else
			$sFileName = md5(uniqid(rand(), true)).".".$aTmp[1];

       	if(copy($aFile['file_file']['tmp_name'], $sDir.$sFileName)) { 		
			chmod($sDir.$sFileName,0666);
			
			//insert file name to db
			$aValues = array(	"file_file"=>$sFileName,
								"file_name"=>$aFile['file_file']['name'],
								"file_type"=>$aFile['file_file']['type'],
								"file_size"=>$aFile['file_file']['size']
								);

			$oDB = self::oDB($sSystem);	//equal to $oDB = self::oDB($this->sSystem);

			try{
				$oDB->sUpdate("galaxy_docs_$iFolderNo",array_keys($aValues),array_values($aValues),"docs_no='{$this->sDocUuid}'");
				@unlink($aFile['file_file']['tmp_name']);
			}catch (Exception $e){
				@unlink($sDir.$sFileName);
				@unlink($aFile['file_file']['tmp_name']);
				throw new Exception("CDoc->vAcceptFile:".$e->getMessage());
			}
		}
		
		return;
	}

	/*
		return file path of this doc
		if no file exist, return ''
		should be call by genesis(doc) system
	*/
	public function sFilePath(){
		//get file folder_no and system
		$aTempData = explode('_', $this->sDocUuid);
		$sSubUuid = $aTempData[1].'_'.$aTempData[2];

		if(preg_match("/^([0-9]+)_([a-z0-9]{1})([a-z0-9]{1})/i",strtoupper($sSubUuid),$aMatch)){
			$sSubDir1 = $aMatch[1];
			$sSubDir2 = $aMatch[2];
			$sSubDir3 = $aMatch[3];
		}

		$sFilePath = self::sGetDataPath()."/$sSubDir1/$sSubDir2/$sSubDir3/".$this->sFileFile;

		return $sFilePath;
	}

	/*
		file upload root path
		modified from old CDoc.php
	*/
	static private function sGetDataPath($iType=0) {
		if($iType!==0){
			return "/data/docs"; //網頁相對位置
		}else{ 
			$sPath=$_SERVER["DOCUMENT_ROOT"]; //unix絕對路徑
			return $sPath."/data/docs";	
		}
	}

	static public function oDB($sSystem){
		if(is_null(parent::$aDB[$sSystem])){
			$aConfig = CDocDBConfig::aGetConfig($sSystem);
            parent::$aDB[$sSystem] = new CDbShell(    $aConfig['db'],
            										$aConfig['host'],
            										$aConfig['user'],
            										$aConfig['password']
                                                    );
        }
        return parent::$aDB[$sSystem];
	}

}
?>