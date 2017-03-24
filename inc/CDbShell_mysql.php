<?php
class CDbShell {
    // Variables 
	private $m_sHost    =       "";
	private $m_sUser   =       "";
	private $m_sPass   =       "";
	private $m_sDb      =       "";
	private $m_iDbh     =       0;
	private $m_iRs       =       0;
	private $m_aRs       =       array();
	private $m_iDebug   =	   1;
	private $m_iVersion  =	   0;	

	private $m_character = "utf8";
	private $m_cache_dir = "";
	private $m_sec2cache = "";
	private $i_qry_cnt = 0;

	private $iTransactionLayer = 0;	//used to control nested transaction(for nested classes' functions)
	//

	/**
	* @param 設定 $m_sHost $m_sUser $m_sPass $m_sDb 
	* @desc  連線資料庫
        	*/
	// This is the constructor for this class
        	// Initialize all your default variables here
	public function __construct($sDb='',$sHost='',$sUser='',$sPass='') {
			
//			unset($this->m_sHost);
//			unset($this->m_sUser);
//			unset($this->m_sPass);
//			unset($this->m_iDbh);
			
			//$this->m_sHost=_MYSQL_HOST.":"._MYSQL_PORT;
			$this->m_sHost=defined('_MYSQL_HOST')?_MYSQL_HOST:null;
	     	$this->m_sUser=defined('_MYSQL_USER')?_MYSQL_USER:null;
	     	$this->m_sPass=defined('_MYSQL_PASS')?_MYSQL_PASS:null;
	     	$this->m_sDb=defined('_MYSQL_DB')?_MYSQL_DB:null;

	     	if($sDb) $this->m_sDb=$sDb;
			if($sHost) $this->m_sHost=$sHost;
			if($sUser) $this->m_sUser=$sUser;
			if($sPass) $this->m_sPass=$sPass;
			if(!$this->m_iDbh) {
				$this->vConnect();
			}
			if($this->m_iDbh){
				$this->bSetCharacter($this->m_character);
			}
             		
    }

	public function __destruct()
	{
		$this->bFreeAllRows();
		$this->vClose();
	}

	public function ReConnect($sDb='',$sHost='',$sUser='',$sPass='') {
		
		if($sDb) $this->m_sDb=$sDb;
		if($sHost) $this->m_sHost=$sHost;
		if($sUser) $this->m_sUser=$sUser;
		if($sPass) $this->m_sPass=$sPass;
		
		$this->vConnect();
		$this->bSetCharacter($this->m_character);
	}
	
	public function bSetCharacter($encode = "utf8") {
		$this->iQuery("SET character_set_client = $encode");
		$this->iQuery("SET character_set_results = $encode");
		$this->iQuery("SET character_set_connection = $encode");
	}
	
	public function bSelectDatabase($sDb=null)
	{
		if($sDb){
			$this->m_sDb=$sDb;
		}else{
			$sDb = $this->m_sDb;
		}
		return mysql_select_db($sDb, $this->m_iDbh);
	}

	/**
	* @return true false
        	* @desc  檢查連線是否成功
        	*/
	public function bCheckConnect(){
		if($this->m_iDbh) return true;
		else return false;
	}
				
	/**
	* @return returns value of variable $m_iVersion
        	* @desc  取得資料庫版本
        	*/
	public function getMysqlVersion(){
		if (!$this->m_iVersion) {
        		//$iResult = mysql_db_query($this->m_sDb,'SELECT VERSION() AS version');
				$iResult = mysql_query('SHOW VARIABLES LIKE \'version\'',$this->m_iDbh);
        		if ($iResult != FALSE && @mysql_num_rows($iResult) > 0) {
            			$aRow   = mysql_fetch_array($iResult);
            			$aMatch = explode('.', $aRow['version']);
        		} else {
            			//$iResult = @mysql_db_query($this->sDb,'SHOW VARIABLES LIKE \'version\'');
						$iResult = mysql_query('SHOW VARIABLES LIKE \'version\'',$this->m_iDbh);
            			if ($iResult != FALSE && @mysql_num_rows($iResult) > 0){
                			$aRow   = mysql_fetch_row($iResult);
                			$aMatch = explode('.', $aRow[1]);
           			}
        		}
    			if (!isset($aMatch) || !isset($aMatch[0])) {
        			$aMatch[0] = 3;
    			}
    			if (!isset($aMatch[1])) {
        			$aMatch[1] = 21;
    			}
    			if (!isset($aMatch[2])) {
        			$aMatch[2] = 0;
    			}

    			$this->m_iVersion = (int)sprintf('%d%02d%02d', $aMatch[0], $aMatch[1], intval($aMatch[2]));
    			unset($aMatch);
    			return $this->m_iVersion;
    			
		} else return $this->m_iVersion;
	}

	/**
    	* @desc  連線資料庫
    	*/
	public function vConnect() {
		$i=0;
		while(!$this->m_iDbh){
			
			$this->m_iDbh = mysql_connect($this->m_sHost,$this->m_sUser,$this->m_sPass, true);
			if($this->m_iDbh) break;
			if($i==10) break;
			//if($i==9) echo $this->m_sHost." ".$this->m_sDb." ".$this->m_sUser." ".$this->m_sPass;
			$i++;
			sleep(1); 
		}	
	}

 	/**
    	* @desc  關閉資料庫
    	*/
	public function vClose() {

		if(is_resource($this->m_iDbh)){
			mysql_close($this->m_iDbh);
			
		}	
	}

 	/**
    	* @param $sSql SQL語法
    	* @return returns value of variable $m_iRs
    	* @desc query db
    	*/
	public function iQuery($sSql) {
		if(version_compare(PHP_VERSION, '5.3') >= 0){
//    		echo "版本大於5.3不能使用mysql_db_query".PHP_EOL;
			mysql_select_db($this->m_sDb,$this->m_iDbh);
			$this->m_iRs = mysql_query($sSql,$this->m_iDbh);
    	}else{
//    		echo "版本小於5.3".PHP_EOL;
    		$this->m_iRs = mysql_db_query($this->m_sDb,$sSql,$this->m_iDbh);
    	}
		
		if( $this->m_iDebug && mysql_error()) {
			echo mysql_error().":: SQL= $sSql<BR>";
		}
		$this->m_aRs[]= $this->m_iRs;
		return $this->m_iRs;
	}

	public function iQuery2($sSql) {
		$this->m_iRs=mysql_query($sSql,$this->m_iDbh);
	  
		if($this->m_iDebug && mysql_error()) {
			echo ":: SQL= $sSql<br>";
			echo mysql_error();
			return false;
		}
		$this->m_aRs[]= $this->m_iRs;
		return $this->m_iRs;
	}

	/**
	* @param $iRs resource result 
	* @return Free result memory
	* @desc Free result memory
	*/
	public function bFreeRows($iRs=0) {
		if($iRs) $iTmpRs=$iRs;
		else	$iTmpRs=$this->m_iRs;
		if(!is_resource($iTmpRs)) return false;
		return mysql_free_result($iTmpRs);
	}

	public function bFreeAllRows($iRs=0) {
		if(count($this->m_aRs) == 0) return false;
		for($i=0;$i<count($this->m_aRs);$i++){
			if(is_resource($this->m_aRs[$i]))
				mysql_free_result($this->m_aRs[$i]);
		}	
	}

	/**
	* @param $iRs resource result 
	* @return Get fields of rows in result
	* @desc 取得sql結果欄位數
	*/	
	public function iNumFields($iRs=0) {
		if($iRs) $iTmpRs=$iRs;
		else	$iTmpRs=$this->m_iRs;
		if(!$iTmpRs) return 0;
		return mysql_num_fields($iTmpRs);
    }

	/**
	* @param $iRs resource result 
	* @return Get number of rows in result
	* @desc 取得sql結果比數
	*/
	public function iNumRows($iRs=0) {
		if($iRs) $iTmpRs=$iRs;
		else	$iTmpRs=$this->m_iRs;
		if(!$iTmpRs) return 0;
		return mysql_num_rows($iTmpRs);
	}
      	
    /**
	* @param $iRs resource result 
	* @return Fetch a result row as an associative array, a numeric array, or both. 
	* @desc 取得sql結果
	*/
	public function aFetchArray ($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
           
		if($iRs) $iTmpRs=$iRs;
		else	$iTmpRs=$this->m_iRs;
		return  mysql_fetch_array($iTmpRs);
	}
	
	/**
	* @param $iRs resource result 
	* @return Fetch a result row as an associative array, a numeric array, or both. 
	* @desc 取得sql結果
	*/
	public function aFetchRow ($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
           
		if($iRs) $iTmpRs=$iRs;
		else	$iTmpRs=$this->m_iRs;
		return  mysql_fetch_row($iTmpRs);
	}
	
	/**
	* @param $iRs resource result 
	* @return Fetch a result row as an associative array, a numeric array, or both. 
	* @desc 取得sql結果
	*/
	public function aFetchAssoc ($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
           
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		return mysql_fetch_assoc($iTmpRs);
	}
	
	/**
	* @param $iRs resource result  $i field
	* @return Fetch a result fields as an associative array, a numeric array, or both. 
	* @desc 取得sql結果 某一欄位
	*/
	public function aFetchFieldArray ($iRs=0,$i=0) {
        if(!$this->m_iRs && !$iRs) return 0;
           
		if($iRs) $iTmpRs=$iRs;
		else	$iTmpRs=$this->m_iRs;
		return  mysql_fetch_field($iTmpRs,$i);
	}
	
	/**
	* @param $sTableName db table 
	* @return 數字 
	* @desc 得到table primary key
	*/
	public function sGetTablePrimaryKey($sTableName=""){
		
		if(!defined('_MYSQL_KEY_CLUSTER_ID'))
			return "";
		return md5(_MYSQL_KEY_CLUSTER_ID+$sTableName+uniqid(rand(), true));
	
	}
	
	/**
	* @return Get the ID generated from the previous INSERT operation 
	* @desc 取得insert後的自動流水號
	*/
	public function iGetInsertId() {
         
		if(!$this->m_iRs) return 0;
		return mysql_insert_id($this->m_iDbh);
	}
  	
  	/**
	* @param $sTable db table $aField field array $aValue value array
	* @return if return sql is ok  "" is failure
	* @desc insert into table
	*/
	public function sInsert($sTable,$aField,$aValue) {
               
		if(!is_array($aField)) return 0;
		if(!is_array($aValue)) return 0;
		
		
		count($aField)==count($aValue) or die(count($aField) .":". count($aValue) );
		
		$sSql="INSERT INTO `$sTable` ( ";
		for($i=1;$i<=count($aField);$i++) {                      
			$sSql.="`".$aField[$i-1]."`";
			if($i!=count($aField)) $sSql.=",";        
		} 
		$sSql.=") values(";
		
		for($i=1;$i<=count($aValue);$i++) {
			$sSql.="'".$aValue[$i-1]."'";
			if($i!=count($aValue)) $sSql.=",";        
		} 
		$sSql.=")";
		$this->iQuery($sSql);
		
		if(!$this->m_iRs) {
			throw new Exception("CDbShell->sInsert: fail to insert data into $sTable");
		}	
		else return $sSql;
	}

	/**
	* @param $sTable db table $aField field array $aValue value array $sWhere trem
	* @return if return sql is ok  "" is failure
	* @desc update  table
	*/
	public function sUpdate($sTable,$aField,$aValue,$sWhere) {
		
		if(!is_array($aField)) return 0;
		if(!is_array($aValue)) return 0;
		
		if(count($aField)!=count($aValue)) return 0;
		
		$sSql="update `$sTable` set ";
		for($i=0;$i<count($aField);$i++) {                       
			$sSql.="`".$aField[$i]."`='".$aValue[$i]."'";
			if(($i+1)!=count($aField)) $sSql.=",";    
		} 
		$sSql.=" where ".$sWhere;
		$this->iQuery($sSql);
		if(!$this->m_iRs)
			throw new Exception("CDbShell->sUpdate: fail to update data in $sTable");
		else
			return $sSql;
                
    }

    /*
    	delete data from target table
    */
    public function vDelete($sTable,$sWhere){
    	$this->iQuery("DELETE FROM $sTable WHERE $sWhere");
		if(!$this->m_iRs)
			throw new Exception("CDbShell->vDelete: fail to delete data in $sTable");
    }

	/**
    * @param $sTable db table
	* @return array
	* @desc 得到table所有欄位
	*/
	public function aGetAllFields($sTable){
		$aRow = array();
		$aFields = mysql_list_fields($this->sDb, $sTable, $this->m_iDbh);
		
		if(!$aFields) return $aRow;
		$iColumns = mysql_num_fields($aFields);
		for ($i = 0; $i < $iColumns; $i++) {
			$aRow[]= mysql_field_name($aFields, $i) ;
		}
		return $aRow;
	}

	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table所有欄位資訊
	*/
	public function aGetAllFieldsInfo($sTable){
		
			$this->iQuery("SHOW FULL FIELDS FROM $sTable"); 
		while($aRow=$this->aFetchArray()){
			//array( Field  	 Type  	 Collation  	 Null  	 Key  	 Default  	 Extra  	 Privileges  	 Comment)
			$aFields[]=$aRow;			
		}	
		return $aFields;		
	}

	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table create sql info
	*/
	public function aGetCreateTableInfo($sTable){
		$this->iQuery("SET SQL_QUOTE_SHOW_CREATE = 1"); 
		$this->iQuery("SHOW CREATE TABLE $sTable"); 
		$aRow=$this->aFetchArray();
		//arrary field (Table,Create Table)
		return $aRow;		
	}
	
	public function aGetTableStatus($sTable){
		$this->iQuery("SHOW TABLE STATUS LIKE '$sTable'"); 
		//arrary field ( Name  	 Engine  	 Version  	 Row_format  	 Rows  	 Avg_row_length  	 Data_length  	 Max_data_length  	 Index_length  	 Data_free  	 Auto_increment  	 Create_time  	 Update_time  	 Check_time  	 Collation  	 Checksum  	 Create_options  	 Comment
		$aRows = array();
		while($aRow=$this->aFetchArray()){
			$aRows[] = $aRow;
		}
		return $aRows;		
	}

	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table create sql info
	*/
	public function bIsTableExist($sTable){
		//echo "show tables like '$sTable'";
		//SHOW TABLES LIKE '%project_article%' 
		$sql = "SHOW TABLES LIKE '%$sTable%'";
		$iDbq = $this->iQuery($sql); 
		if($this->iNumRows($iDbq))
			return true;
		return false;
	}
	
	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table create sql info
	*/
	public function bIsDatabaseExist($sDatabase){
		$iDbq = $this->iQuery("show databases like '$sDatabase'"); 
		if($this->iNumRows($iDbq))
			return true;
		return false;
	}
	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table create sql info
	*/
	
	public function bCheckTableExist($sTable){
		$this->iQuery("CHECK TABLE $sTable"); 
		$aRow=$this->aFetchArray();
		if($aRow['Msg_text'] == "OK")
			return true;
		return false;
	}

	// transactions function
	function vBegin () {
		//if this CDbShell already start a trancation, it won't "begin" again , but only +1 on layer
		if($this->iTransactionLayer===0)
			$this->iQuery("begin");
		$this->iTransactionLayer++;
	}
	function vCommit () {
		//if this CDbShell's trancation layer is more than 1, it won't "commit" right away
		//, but only -1 on layer, there will be another vCommit later
		$this->iTransactionLayer--;
		if($this->iTransactionLayer===0)
			$this->iQuery("commit");
	}
	function vRollback () {
		$this->iTransactionLayer = 0;
		$this->iQuery("rollback");
	}

	/**
	* @param $sTable db table $iGoId 流水號 $iPageItems 每頁顯示比數 $sSearchSql 條件 $sPostFix 順序&limit
	* @return 數字 
	* @desc 得到某筆資料是在第幾頁
	*/
	public function iGetItemAtPage($sTable="",$sField="", $iGoId,$iPageItems,$sSearchSql='',$sPostFix=''){
			
		if(!$sTable || !$sField) return 0;
		$sSql = "SELECT `$sField`  FROM `$sTable`";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$this->iQuery2($sSql);
		$i=0;
		$biFind = false;
		while($aRow=$this->aFetchArray()) {
			if($aRow[$sField]==$iGoId) {
				$biFind = true;
				break;
			}	
			$i++;
		}
		if(!$biFind) $i=0;
		
		return (INT)($i/$iPageItems);
	}
	
	
	public function iGetJoinItemAtPage($sTable1="",$sTable2="",$sField="", $iGoId, $iPageItems,$sSearchSql='',$sPostFix=''){
				
		if(!$sTable ||$sTable2 || !$sField) return 0;
		$sSql = "SELECT $sField FROM $sTable1 LEFT JOIN $sTable2 ON $sTable1.$sField=$sTable2.$sField";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$this->iQuery2($sSql);
        $i=0;
		$biFind = false;
		while($aRow=$this->aFetchArray()) {
			if($aRow[$sField]==$iGoId) {
				$biFind = true;
				break;
			}	
			$i++;
		}
		if(!$biFind) $i=0;
		
		return (INT)($i/$iPageItems);
	}
}
?>