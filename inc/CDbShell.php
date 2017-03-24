<?php

/**
 *  @desc MySQL class MySQLi 版本
 *  @created 2014/11/10
 */

class CDbShell {
    
	// Variables 
	var $m_sHost	=	"";
	var $m_sUser	=	"";
	var $m_sPass	=	"";
	var $m_sDb		=	"";
	var $m_iDbh		=	0;
	var $m_iRs		=	0;
	var $m_sPort	=	"3306";
	
	//var $m_aRs		=	array();
	var $m_iDebug	=	1;
	var $m_iVersion	=	0;	

	var $m_character	= "utf8";
	var $m_cache_dir	= "";
	var $m_sec2cache	= "";
	var $i_qry_cnt		= 0;
	var $sSql			= "";
	
	/**
	 *  @desc 連線資料庫
	 *  @param 設定 $m_sHost $m_sUser $m_sPass $m_sDb 
	 */
	public function __construct($sDb='',$sHost='',$sUser='',$sPass='') {
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
	
	public function __destruct() {
		//$this->bFreeAllRows();
		$this->vClose();
	}

	function vPing(){
		if(!mysqli_ping($this->m_iDbh)){
			throw new Exception("CDbShell->vPing: mysqli_ping failed");
		}
	}
	
	function ReConnect($sDb='',$sHost='',$sUser='',$sPass='') {
		if($sDb) $this->m_sDb=$sDb;
		if($sHost) $this->m_sHost=$sHost;
		if($sUser) $this->m_sUser=$sUser;
		if($sPass) $this->m_sPass=$sPass;
		
		$this->vConnect();
		$this->bSetCharacter($this->m_character);
	}
	
	/**
	 *  @desc 設定 MySQL 連結為 UTF-8
	 *  @created 2014/11/14
	 */
	function bSetCharacter($encode = "utf8") {
		$this->iQuery("SET character_set_client = $encode");
		$this->iQuery("SET character_set_results = $encode");
		$this->iQuery("SET character_set_connection = $encode");
	}
	
	/**
	 *  @desc 設定資料庫
	 *  @created 2014/11/14
	 */
	function bSelectDatabase($sDb=null) {
		if ($sDb) $this->m_sDb=$sDb;
		else $sDb = $this->m_sDb;
		return mysqli_select_db($this->m_iDbh, $sDb);
	}
	

	/**
	 *  @desc 檢查連線是否成功
	 *  @return boolean(true or false)
	 */
	function bCheckConnect() {
		if($this->m_iDbh) return true;
		else return false;
	}
	
	
	/**
	 *  @desc 取得資料庫版本
	 *  @return value of variable $m_iVersion
	 */
	function getMysqlVersion() {
		if (!$this->m_iVersion) {
			$iResult = mysqli_query($this->m_iDbh, 'SHOW VARIABLES LIKE \'version\'');
			if ($iResult != FALSE && @mysqli_num_rows($iResult) > 0) {
				$aRow   = mysqli_fetch_assoc($iResult);
				$aMatch = explode('.', $aRow['version']);
			} else {
				$iResult = mysqli_query($this->m_iDbh, 'SHOW VARIABLES LIKE \'version\'');
				if ($iResult != FALSE && @mysqli_num_rows($iResult) > 0){
					$aRow   = mysqli_fetch_row($iResult);
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
	 *  @desc 連線資料庫
	 */
	function vConnect() {
		if(!$this->m_iDbh){
			try {
				$aHost = explode(":", $this->m_sHost);
				if (isset($aHost[1])) $this->m_iDbh = @mysqli_connect($aHost[0],$this->m_sUser,$this->m_sPass,"",$aHost[1]);
				else $this->m_iDbh = @mysqli_connect($aHost[0],$this->m_sUser,$this->m_sPass);
				if (mysqli_connect_errno()){
					printf("Connect failed: %s<br>\n", mysqli_connect_error());
				}
			}catch (Exception $e){
				echo "Could not connect: ". $this->m_sHost. "<br>\n".mysqli_error($this->m_iDbh)."<br>\n";
				echo $e->getMessage()."<br>\n";
				exit;
			}
		}else if (!mysqli_ping($this->m_iDbh)){
			$aHost = explode(":", $this->m_sHost);
			if (isset($aHost[1])) $this->m_iDbh = mysqli_connect($aHost[0],$this->m_sUser,$this->m_sPass,"",$aHost[1]);
			else $this->m_iDbh = mysqli_connect($aHost[0],$this->m_sUser,$this->m_sPass);
			if (mysqli_connect_errno()){
				printf("Connect failed: %s<br>\n", mysqli_connect_error());
			}
			if(!$this->m_iDbh){
				echo 'Could not reconnect: '. mysqli_error($this->m_iDbh).'<br>\n';
			}
		}
	}
	
	/**
	 *  @desc 關閉資料庫
	 */
	function vClose() {
		if ($this->m_iDbh instanceof mysqli_result) {
			$thread_id = mysqli_thread_id($this->m_iDbh);
			mysqli_kill($this->m_iDbh, $thread_id);
			mysqli_close($this->m_iDbh);
		}
	}

	/**
	 *  @desc query db
	 *  @param $sSql SQL語法
	 *  @return value of variable $m_iRs
	 */
	function iQuery($sSql) {
		if (!mysqli_ping($this->m_iDbh)){
			$this->vConnect();
		}
		mysqli_select_db($this->m_iDbh, $this->m_sDb);
		$this->m_iRs = mysqli_query($this->m_iDbh, $sSql);
		
		if($this->m_iDebug && mysqli_error($this->m_iDbh)) {
			$err_message = preg_replace("/'/i","\'",mysqli_error($this->m_iDbh).":: SQL = $sSql");
			throw new Exception($err_message);
			return false;
		}
		return $this->m_iRs;
	}
	
	function iQuery2($sSql) {
		$this->m_iRs=mysqli_query($this->m_iDbh, $sSql);
	  
		if($this->m_iDebug && mysqli_error($this->m_iDbh)) {
			$err_message = preg_replace("/'/i","\'",mysqli_error($this->m_iDbh).":: SQL = $sSql");
			throw new Exception($err_message);
			return false;
		}
		return $this->m_iRs;
	}
	
	/**
	 *  @desc Free result memory
	 *  @param $iRs resource result
	 *  @return Free result memory
	 */
	function bFreeRows($iRs=0) {
		if ($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		if ($this->m_iDbh instanceof mysqli_result) {
			return mysqli_free_result($iTmpRs);
		}
		return false;
	}
	
	//function bFreeAllRows($iRs=0) {
	// 	if(count($this->m_aRs) == 0) return false;
	// 	for($i=0;$i<count($this->m_aRs);$i++){
	// 		if(is_resource($this->m_aRs[$i])) mysql_free_result($this->m_aRs[$i]);
	// 	}	
	//}
	
	/**
	* @param $iRs resource result 
	* @return Get fields of rows in result
	* @desc 取得sql結果欄位數
	*/	
	function iNumFields($iRs=0) {
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		if(!$iTmpRs) return 0;
		return mysqli_field_count($this->m_iDbh);
    }
	
	/**
	* @param $iRs resource result 
	* @return Get number of rows in result
	* @desc 取得sql結果比數
	*/
	function iNumRows($iRs=0) {
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		if(!$iTmpRs) return 0;
		return mysqli_num_rows($iTmpRs);
	}
	
	/**
	 *  @desc 取得sql結果
	 *  @param $iRs resource result 
	 *  @param result_type: MYSQLI_BOTH, MYSQLI_ASSOC, MYSQLI_NUM
	 *  @return Fetch a result row as an associative array, a numeric array, or both. 
	 */
	function aFetchArray($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		return  mysqli_fetch_array($iTmpRs, MYSQLI_BOTH);
	}
	
	/**
	* @param $iRs resource result 
	* @return Fetch a result row as an associative array, a numeric array, or both. 
	* @desc 取得sql結果
	*/
	function aFetchRow($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		return mysqli_fetch_row($iTmpRs);
	}
	
	/**
	* @param $iRs resource result 
	* @return Fetch a result row as an associative array, a numeric array, or both. 
	* @desc 取得sql結果
	*/
	function aFetchAssoc($iRs=0) {
        if(!$this->m_iRs && !$iRs) return 0;
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		return mysqli_fetch_assoc($iTmpRs);
	}
	
	/**
	* @param $iRs resource result  $i field
	* @return Fetch a result fields as an associative array, a numeric array, or both. 
	* @desc 取得sql結果 某一欄位
	*/
	function aFetchFieldArray($iRs=0,$i=0) {
        if(!$this->m_iRs && !$iRs) return 0;
		if($iRs) $iTmpRs=$iRs;
		else $iTmpRs=$this->m_iRs;
		return mysqli_fetch_field($iTmpRs);
	}
	
	/**
	* @param $sTableName db table 
	* @return 數字 
	* @desc 得到table primary key
	*/
	function sGetTablePrimaryKey($sTableName=""){
		if(!defined('_MYSQL_KEY_CLUSTER_ID')) return "";
		return md5(_MYSQL_KEY_CLUSTER_ID+$sTableName+uniqid(rand(), true));
	}
	
	/**
	* @return Get the ID generated from the previous INSERT operation 
	* @desc 取得insert後的自動流水號
	*/
	function iGetInsertId() {
		if(!$this->m_iRs) return 0;
		return mysqli_insert_id($this->m_iDbh);
	}
  	
  	/**
	* @param $sTable db table $aField field array $aValue value array
	* @return if return sql is ok  "" is failure
	* @desc insert into table
	*/
	function sInsert($sTable,$aField,$aValue) {
		if(!is_array($aField)) return 0;
		if(!is_array($aValue)) return 0;
		
		count($aField)==count($aValue) or die(count($aField) .":". count($aValue) );
		
		$sSql="INSERT INTO $sTable ( ";
		for($i=1;$i<=count($aField);$i++) {                      
			$sSql.="`".$aField[$i-1]."`";
			if($i!=count($aField)) $sSql.=",";        
		} 

		$sSql.=") values(";
		
		for($i=1;$i<=count($aValue);$i++) {
			$sSql.="'".$this->my_quotes($aValue[$i-1])."'";
			if($i!=count($aValue)) $sSql.=",";        
		} 
		$sSql.=")";
		
		$this->iQuery($sSql);
		
		//if(!$this->m_iRs) return NULL;
		if(!$this->m_iRs) throw new Exception("CDbShell->sInsert: fail to insert data into $sTable");
		else return $sSql;
	}
	
	/**
	* @param $sTable db table $aField field array $aValue value array $sWhere trem
	* @return if return sql is ok  "" is failure
	* @desc update  table
	*/
	function sUpdate($sTable,$aField,$aValue,$sWhere) {
		if(!is_array($aField)) return 0;
		if(!is_array($aValue)) return 0;
		
		if(count($aField)!=count($aValue)) return 0;
		
		$sSql="update $sTable set ";
		for($i=0;$i<count($aField);$i++) {                       
			$sSql.="`".$aField[$i]."`='".$this->my_quotes($aValue[$i])."'";
			if(($i+1)!=count($aField)) $sSql.=",";    
		} 
		$sSql.=" where ".$sWhere;

		$this->sSql = $sSql;
		$this->iQuery($sSql);
		//if(!$this->m_iRs) return NULL;
		if(!$this->m_iRs) throw new Exception("CDbShell->sUpdate: fail to update data in $sTable");
		else return $sSql;
    }
	
	/**
	* @param string $sTable The table name, array $aAdd The add data array 
	* @return boolean
	* @desc insert into table
	*/
	function bInsert( $sTable , $aAdd ) {
		if (!mysqli_ping($this->m_iDbh)){
			$this->vConnect();
		}
		mysqli_select_db($this->m_iDbh, $this->m_sDb);
		
		$sSql="INSERT INTO $sTable (";
		foreach( $aAdd AS $key => $value ) {
			$sSql.="`".$key."`,";
		}
		$sSql = substr($sSql,0,-1);
		$sSql.=") values (";
		foreach( $aAdd AS $key => $value ) {
			//$sSql.="'".$this->my_quotes($value)."',";
			$sSql.="'".mysqli_real_escape_string($this->m_iDbh, $value)."',";
		}
		$sSql = substr($sSql,0,-1);
		$sSql.=")";
		
		/*foreach( $aAdd AS $key => $value ) {
			$aFields[] = $key;
			$aValues[] = "'".$this->my_quotes($value)."'";
		}		
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
		$sSql = "INSERT INTO $sTable(" . implode( "," , $aFields ) . ") VALUES(" . implode( "," , $aValues ) . ")";*/
		
		$this->sSql = $sSql;
		$this->iQuery( $sSql );
		if(!$this->m_iRs) throw new Exception("CDbShell->bInsert: fail to insert data in $sTable");
		return $this->iGetInsertId();
	}

	/**
	* @param string $sTable The table name, array $aSrc The source data array, array $aTar The target data array 
	* @return boolean
	* @desc update table
	*/
	function bUpdate( $sTable , $aSrc , $aTar ) {
		$aWhere = array();
		foreach( $aSrc AS $key => $value ) {
			$aWhere[] = "$key = '".$this->my_quotes($value)."'";
		}
		$aSrc = array();
		foreach( $aTar AS $key => $value ) {
			$aSet[] = "$key = '".$this->my_quotes($value)."'";
		}
		$sSQL = "UPDATE $sTable SET " . implode( "," , $aSet ) . " WHERE " . ( count( $aWhere ) > 0 ? implode( " AND " , $aWhere ) : "1" );

		$this->sSql = $sSQL;
		$result = (bool)$this->iQuery( $sSQL );
		if(!$this->m_iRs) throw new Exception("CDbShell->bUpdate: fail to update data in $sTable");
		return $result;
	}



	/**
	 * delete
	 *
	 * @param string $table
	 * @param string $where
	 * @param integer $limit
	 * @return integer Affected Rows
	 */
    public function vDelete($sTable,$sWhere){
		if (!$sWhere) throw new Exception("CDbShell->vDelete: fail no where. table: $sTable");
    	$this->iQuery("DELETE FROM $sTable WHERE $sWhere");
		if(!$this->m_iRs)
			throw new Exception("CDbShell->vDelete: fail to delete data in $sTable");
    }
	
	/**
    * @param $sTable db table
	* @return array
	* @desc 得到table所有欄位
	*/
	function aGetAllFields($sTable){
		$aRow = array();
		//$aFields = mysql_list_fields($this->sDb, $sTable, $this->m_iDbh);
		//if(!$aFields) return $aRow;
		//$iColumns = mysql_num_fields($aFields);
		//for ($i = 0; $i < $iColumns; $i++) {
		//	$aRow[]= mysql_field_name($aFields, $i) ;
		//}
		
		$iDbq = $this->iQuery("SHOW COLUMNS FROM $sTable");
		$aResult = $this->aFetchRow($iDbq);
		foreach($aResult as $value){
			$aRow[] = $value[0];
		}
		return $aRow;
	}
	
	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table所有欄位資訊
	*/
	function aGetAllFieldsInfo($sTable){
		$this->iQuery("SHOW FULL FIELDS FROM $sTable"); 
		while($aRow=$this->aFetchArray()){
			$aFields[]=$aRow;			
		}	
		return $aFields;		
	}
	
	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table create sql info
	*/
	function aGetCreateTableInfo($sTable){
		$this->iQuery("SET SQL_QUOTE_SHOW_CREATE = 1"); 
		$this->iQuery("SHOW CREATE TABLE $sTable"); 
		$aRow=$this->aFetchArray();
		return $aRow;		
	}
	
	function aGetTableStatus($sTable){
		$this->iQuery("SHOW TABLE STATUS LIKE '$sTable'"); 
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
	function bIsTableExist($sTable){
		$sql = "SHOW TABLES LIKE '%$sTable%'";
		$iDbq = $this->iQuery($sql); 
		if ($this->iNumRows($iDbq)) return true;
		return false;
	}
	
	/**
	* @param $sTable db table
	* @return array
	* @desc 得到table create sql info
	*/
	function bIsDatabaseExist($sDatabase){
		$iDbq = $this->iQuery("SHOW DATABASES LIKE '$sDatabase'"); 
		if($this->iNumRows($iDbq)) return true;
		return false;
	}
	
	// transactions function
	function vBegin () {
		$this->iQuery("begin");
	}
	
	function vCommit () {
		$this->iQuery("commit");
	}
	
	function vRollback () {
		$this->iQuery("rollback");
	}
	
	function my_quotes($data) {

		if(is_array($data)) {
			foreach($data as $key => $val) {
				if(is_array($data[$key])) {
					$data[$key] = $this->my_quotes($data[$key]);
				} else {
					$data[$key] = get_magic_quotes_gpc() ?  trim($data[$key]) : addslashes(trim($data[$key]));
				}
			}
		} else {
			$data = get_magic_quotes_gpc() ?  trim($data) : addslashes(trim($data));
		}
	
		return $data;
	}
	function my_quotes_for_update($data) {//for update    2015/07/17

		if(is_array($data)) {
			foreach($data as $key => $val) {
				if(is_array($data[$key])) {
					$data[$key] = $this->my_quotes($data[$key]);
				} else {
					$data[$key] = get_magic_quotes_gpc() ?  trim($data[$key]) : addslashes(trim($data[$key]));
				}
			}
		} else {
			$data = get_magic_quotes_gpc() ?  trim($data) : addslashes(trim($data));
		}
		
		return $data;
	}
	
	/**
	* @param $sTable db table $iGoId 流水號 $iPageItems 每頁顯示比數 $sSearchSql 條件 $sPostFix 順序&limit
	* @return 數字 
	* @desc 得到某筆資料是在第幾頁
	*/
	public function iGetItemAtPage($sTable="",$sField="", $iGoId,$iPageItems,$sSearchSql='',$sPostFix=''){
			
		if(!$sTable || !$sField) return 0;
		$sSql = "SELECT $sField  FROM $sTable";
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
				
		if(!$sTable1 || $sTable2 || !$sField) return 0;
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
    
    public function escape($str=''){
        $str = trim($str);
        if (!$str) return '';
        $str = mysqli_real_escape_string($this->m_iDbh, $str);
        return $str;
    }
	
}

?>