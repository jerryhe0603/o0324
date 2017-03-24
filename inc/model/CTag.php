<?php
/**
 * 標籤
 */
include_once(PATH_ROOT.'/inc/model/CGalaxyClass.php');
class CTag extends CGalaxyClass
{

	private $iTagNo;	

	
	public $sTagName;
	public $iParentTagNo;

	//database setting
	static protected $sDBName = 'GENESIS';


	public function __get($varName)
	{
		return $this->$varName;
	}

	/**
	 * 取得標籤資料
	 *	
	 * @param int $tag_no 標籤序號
	 */
	static public  function oGetTag($iTagNo) {
		$oDB = self::oDB(self::$sDBName);
		
		$sSql = "SELECT * FROM galaxy_tag WHERE tag_no = '$iTagNo'";
		$iDbq = $oDB->iQuery($sSql);
		
		$aRow = $oDB->aFetchAssoc($iDbq);
		if($aRow === false || $oDB->iNumRows($iDbq)>1)
			return null;

		$oCTag = new CTag($aRow);
		
		$oCTag->iTagNo = $iTagNo;
		return $oCTag;

		
	}
	

	
	static public function aAllTag($sSearchSql='',$sPostFix=''){
		$oDB = self::oDB(self::$sDBName);
		$aAllTag = array();
		$sSql = "SELECT * FROM galaxy_tag";
		if($sSearchSql!=='')
			$sSql .= " WHERE $sSearchSql";
		if($sPostFix!=='')
			$sSql .= " $sPostFix";
		$iDbq = $oDB->iQuery($sSql);
		while($aRow = $oDB->aFetchAssoc($iDbq)){
			$aAllTag[] = new CTag($aRow);
		}
		return $aAllTag;
	}
	
	static public function iGetCount($sSearchSql=''){
		$oDB = self::oDB(self::$sDBName);
		$sSql = "SELECT count(tag_no) as total FROM galaxy_tag";
		
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

	static public function aGetTageLogByTableNo($sDBName,$sFuncName, $sTableName, $sTableNo) {

		if(!isset($sDBName) || $sDBName==='' || $sDBName===0) 
			throw new Exception('oCTag->aGetTageLogByTableNo: db name not set');


		if(!isset($sFuncName) || $sFuncName==='' || $sFuncName===0) 
			throw new Exception('oCTag->aGetTageLogByTableNo: func_name not set');


		if(!isset($sTableNo) || $sTableNo==='' || $sTableNo===0) 
			throw new Exception('oCTag->aGetTageLogByTableNo: table_no not set');

		if(!isset($sTableName) || $sTableName==='') 
			throw new Exception('oCTag->aGetTageLogByTableNo: table_name not set');

		$oDB = self::oDB($sDBName);

		$aRows = array();
		
		
		$sSql = "SELECT `tag_no` 
				FROM `galaxy_tag_log`
				WHERE `func_name` = '$sFuncName'
				AND `table_name` = '$sTableName'
				AND `table_no` = '$sTableNo'";
		
		$iRes = $oDB->iQuery2($sSql);

		if($oDB->iNumRows($iRes) > 0) {
			while($fe = $oDB->aFetchAssoc($iRes)) {
				$aRows[] = self::oGetTag($fe['tag_no']);
			}
		}

		return $aRows;
	}

	static public function aGetTagSearch($sTerm){
		$oDB = self::oDB(self::$sDBName);
		
		// if(!isset($sTerm) || $sTerm==='' || $sTerm===0) {
		// 	throw new Exception('oCTag->sGetTagSearch: no term');
		// }
		
		if(!isset($sTerm) || $sTerm==='' || $sTerm===0) 
			$iDbq = $oDB->iQuery("SELECT * FROM galaxy_tag WHERE status='1'");
		else
			$iDbq = $oDB->iQuery("SELECT * FROM galaxy_tag WHERE tag_name like '%$sTerm%' AND status='1'");
		$aTagData = array();
		while($aRow = $oDB->aFetchArray($iDbq)) {
		
			//每個array表示一個群組標籤
			if($aRow['parent_tag_no']==0){

				$iDbq_exist = $oDB->iQuery("SELECT * FROM galaxy_tag WHERE parent_tag_no = ".$aRow['tag_no']." LIMIT 1");
				if($oDB->aFetchArray($iDbq_exist)){//父標籤
					$aTagData[$aRow['tag_no']] = $aRow;
					$aTagData[$aRow['tag_no']]['tag'][] = $aRow;//父標籤也可被選取所以也需加入tag 陣列
				}else{

					if(!$aTagData[0])
						$aTagData[0]['tag_name']="-----------------";
					$aTagData[0]['tag'][] = $aRow;;//此標籤不是父標籤,所以放在第0的陣列
				}
				

			}else{

				if($aTagData[$aRow['parent_tag_no']] ){
					$aTagData[$aRow['parent_tag_no']]['tag'][] = $aRow;
	
				}else{

					


					$iDbq2 = $oDB->iQuery("SELECT * FROM galaxy_tag WHERE tag_no = ".$aRow['parent_tag_no']);
					$aRow2 = $oDB->aFetchArray($iDbq2);
					$aTagData[$aRow['parent_tag_no']] = $aRow2;
					$aTagData[$aRow['parent_tag_no']]['tag'][] = $aRow;

				}
			}	

		
		}
		
		return $aTagData;
	}

	static public function vSetTagLog($sDBName,$sFuncName,$sTableName,$sTableNo,$aTagData){
		
		if(!isset($sDBName) || $sDBName==='' || $sDBName===0) 
			throw new Exception('oCTag->vSetTagLog: db name not set');

		if(!isset($sFuncName) || $sFuncName==='' || $sFuncName===0) 
			throw new Exception('oCTag->vSetTagLog: func_name not set');


		if(!isset($sTableNo) || $sTableNo==='' || $sTableNo===0) 
			throw new Exception('oCTag->vSetTagLog: table_no $sTableNo not set');

		if(!isset($sTableName) || $sTableName==='') 
			throw new Exception('oCTag->vSetTagLog: table_name not set');

		if(!isset($aTagData) || !is_array($aTagData)) 
			throw new Exception('oCTag->vSetTagLog: tag data not set');

		$oDB = self::oDB($sDBName);
		$session = self::$session;
		
		try{

			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");

			$sSql = "DELETE FROM galaxy_tag_log  WHERE  table_no = '".$sTableNo."' AND table_name = '".$sTableName."'";
			$oDB->iQuery($sSql);
		
			for($i=0;$i<count($aTagData['tag']);$i++){
				
				if($aTagData['tag'][$i]){
					$aFields=array("func_name","table_name","table_no","tag_no","cretatetime");
					$aValues=array($sFuncName,$sTableName,$sTableNo,$aTagData['tag'][$i],$sDate);
			
					$oDB->sInsert("galaxy_tag_log",$aFields,$aValues);
					

				}
			}
			
			$session->get('oCurrentUser')->vAddUserLog("galaxy_tag_log",0,$_GET['func'],$_GET['action']);

			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCTag->vSetTagLog: '.$e->getMessage());
		}
	}
	/*
	tag selector 
	function_admin.js
	getTagSelector(layout_element,target_element,component_element,parent_tag_no); 
	layout_element 顯示的位置
	target_element 拖曳顯示的位置
	component_element 所有tag的位置
	parent_tag_no 0顯示根目錄 >0 某一tag_no 的子tag
	
	
	
	*/



	static public function tGetTagSelector( $target_element="", $component_element="", $layout_element="", $parent_tag_no=0){
		$oDB = self::oDB(self::$sDBName);
		$Smarty = CGalaxyController::$Smarty;
		
		$aParentTagData = array();
		
		if($parent_tag_no){
			$sSql = "SELECT * 
				FROM `galaxy_tag`
				WHERE `tag_no` = '$parent_tag_no' 
				AND status = 1";
		
			$iRes = $oDB->iQuery2($sSql);
			$aParentTagData = $oDB->aFetchAssoc($iRes);
			
		}
		
		$aTagData = array();
		
		if($aParentTagData){
		
			$sSql = "SELECT * 
				FROM `galaxy_tag` WHERE parent_tag_no = '$parent_tag_no'";
				
			
			$iRes = $oDB->iQuery2($sSql);
			while($fe = $oDB->aFetchAssoc($iRes)) {
				
				$fe['view']=0;
				if($fe['tag_no'])
				$aTagData[] = $fe;
			}
			
		}else{
		
		
			$sSql = "SELECT * 
					FROM `galaxy_tag`
					WHERE `parent_tag_no` = 0
					AND status = '1'";
			
			$iRes = $oDB->iQuery2($sSql);
			
			
			while($fe = $oDB->aFetchAssoc($iRes)) {
				$iTagNo = $fe['tag_no'];
					
				$sSql = "SELECT * 
				FROM `galaxy_tag` 
				WHERE `parent_tag_no` = '$iTagNo' LIMIT 0,1";
				$iRes2 = $oDB->iQuery2($sSql);
				$fe['view']=0;
				if($oDB->iNumRows($iRes2))
					$fe['view']=1;
				
				
				$aTagData[] = $fe;
			}

			
		}
		
		
		$Smarty->assign('target_element',$target_element);		
		$Smarty->assign('component_element',$component_element);		
		$Smarty->assign('layout_element',$layout_element);		
		
		$Smarty->assign('parentTagData',$aParentTagData);		
		$Smarty->assign("tagData", $aTagData);

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/tag_selector.html');

	
	}

	public function __construct($multiData){
		
		parent::__construct($multiData);
		
		if(!is_array($multiData))
			throw new Exception("CTag: __construct failed, require an array");
		

		//initialize optional member

		$this->sTagName = $multiData['tag_name'];
		$this->iParentTagNo = $multiData['parent_tag'];
		
		//galaxy class memeber
		$this->bStatus = $multiData['status'];
		
		$this->sCreateTime = $multiData['createtime'];

	}

	
	public function vOverWrite($oCTag){
		//if not a CTag object or uuid not match
		if(get_class($oCTag)!=='CTag' || $this->iTagNo!==$oCTag->iTagNo)
			throw new Exception('CTag->vOverWrite: fatal error');
			
		foreach ($this as $name => $value) {
			if($name==='iTagNo')
				continue;
			$this->$name = $oCTag->$name;	//overwrite
		}
	}


	/*
		update puppet data
		step1: $oCTag = CTag:oGetTag($iTagNo) and create a CTag with all info
		step2: $oCTag>overwrite($oNewCTag);
		step3: call this function
	*/
	public function vUpdateTag(){
		$oDB = self::oDB(self::$sDBName);
		
		try{
			$oDB->vBegin();
			$oCUser = self::$session->get('oCurrentUser');
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	'tag_name'=>$this->sTagName,
						'parent_tag_no'=>$this->iParentTagNo,
						'status'=>$this->bStatus
					);
			$oDB->sUpdate("galaxy_tag",array_keys($aValues),array_values($aValues),"tag_no='{$this->iTagNo}'");
			$this->oLastUser()->vAddUserLog("galaxy_tag",$this->iTagNo,$_GET['func'],$_GET['action']);
			$oDB->vCommit();
		}catch (Exception $e){
			$oDB->vRollback();
			throw new Exception('CTag->vUpdateTag: '.$e->getMessage());
		}
	}

	/*
		add puppet to certain system
		return puppets_no(string of uuid)
		step1: new a $oCTag object with all info
		step2: call this function
	*/
	public function sAddTag(){
		$oDB = self::oDB(self::$sDBName);
		try{
			
			$oDB->vBegin();
			//insert puppet attr
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'tag_name'=>$this->sTagName,
						'parent_tag_no'=>$this->iParentTagNo,
						'status'=>$this->bStatus,
						'createtime'=>$sDate,
							);
			$oDB->sInsert("galaxy_tag",array_keys($aValues),array_values($aValues));
			$this->iTagNo = $oDB->iGetInsertId();
	

			$this->oLastUser()->vAddUserLog("galaxy_tag",$this->iTagNo,$_GET['func'],$_GET['action']);

			$oDB->vCommit();



			return $this->iTagNo;
		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCTag->sAddTag: '.$e->getMessage());
		}
	}

	public function vActiveTag($iFlag=0){
		$oDB = self::oDB(self::$sDBName);

		
		
		try{
			
			$oDB->vBegin();
			 $oCUser = self::$session->get('oCurrentUser');
			$sDate = date("Y-m-d H:i:s");
			$aValues = array(	
						'status'=>$iFlag,
						'user_no'=>$oCUser->__iUserNo
					);
			$oDB->sUpdate("galaxy_tag",array_keys($aValues),array_values($aValues),"tag_no='{$this->iTagNo}'");
			$oCUser->vAddUserLog("galaxy_tag",$oCTag->iTagNo,$_GET['func'],$_GET['action']);

			$oDB->vCommit();

		}catch (Exception $e){
			$oDB->vRollback();	
			throw new Exception('oCTag->vActiveTag: '.$e->getMessage());
		}
	}

	
	
/*
	
	
	
	
	
	function ReMoveParentTag($tag_no) {
		global $GenesisDbh;
		
		$sql = "DELETE FROM galaxy_tag_rel WHERE tag_no = '".$tag_no."'";
		$iRes = $GenesisDbh->iQuery2($sql);
	}
	
	
	function AddParentTag($tag_no, $parent_tag_no) {
		global $GenesisDbh;
		
		$sql = "INSERT INTO galaxy_tag_rel 
					SET parent_tag_no 	= '".$parent_tag_no."', 
						tag_no 			= '".$tag_no."'";
		$iRes = $GenesisDbh->iQuery2($sql);
	}
	
	
	function GetParentTag($tag_no) {
		global $GenesisDbh;
		
		$sql = "SELECT parent_tag_no FROM galaxy_tag_rel WHERE tag_no = '".$tag_no."'";
		$iRes = $GenesisDbh->iQuery2($sql);
		$fe = $GenesisDbh->aFetchAssoc($iRes);

		return $fe['parent_tag_no'];
	}
	
	
	function Total($where="WHERE 1") {
		global $GenesisDbh;
		
		$sql = "SELECT count(1) AS total FROM galaxy_tag $where";
		$iRes = $GenesisDbh->iQuery($sql);
		$fe = $GenesisDbh->aFetchAssoc($iRes);
			
		return $fe['total'];
	}
	
	function AllTag() {
		global $GenesisDbh;
		
		$sql = "SELECT * FROM galaxy_tag WHERE 1";
		$iRes = $GenesisDbh->iQuery2($sql);
		while($fe = $GenesisDbh->aFetchAssoc($iRes)) {
			$aRow[$fe['tag_no']] = $fe;
		}
		
		return $aRow;
	}
	
	
	
	
	
	function AllTagPagination($page = 0, $limit = 50, $order = "tag_no", $sort = "ASC", $where="WHERE 1 ") {
		global $GenesisDbh;
		
		$sql = "SELECT * FROM galaxy_tag $where ORDER BY $order $sort LIMIT " . ( $page * $limit ) . " , $limit";
		$iRes = $GenesisDbh->iQuery2($sql);
		while($fe = $GenesisDbh->aFetchAssoc($iRes)) {
			$aRow[$fe['tag_no']] = $fe;
			
			$parent = $this->GetTag($this->GetParentTag($fe['tag_no']));
			$aRow[$fe['tag_no']]['parent_tag_name'] = $parent['tag_name'];
			$aRow[$fe['tag_no']]['parent_tag_no'] 	= $parent['tag_no'];
		}
		//prt($aRow);
		return $aRow;
	}
	
	
	function AjaxNodeTag($id) {
		global $GenesisDbh;

		$sql = "SELECT tb1.*, tb2.* 
				FROM galaxy_tag as tb1 
				LEFT JOIN galaxy_tag_rel as tb2
				ON tb1.tag_no = tb2.tag_no
				WHERE `parent_tag_no` = $id";

		$iRes = $GenesisDbh->iQuery2($sql);

		while($fe = $GenesisDbh->aFetchAssoc($iRes)) {
			$aRow[] = $fe;
		}
		
		//$tag = $this->formatTag($aRow);
		return $aRow;		
	}
	
	
	function AjaxParentTag() {
		global $GenesisDbh;
			
		$sql = "SELECT * 
				FROM `genesis`.`galaxy_tag`
				WHERE `parent_tag` = '0'";
		
		$iRes = $GenesisDbh->iQuery2($sql);
		$GenesisDbh->iNumRows($iRes);
		if($GenesisDbh->iNumRows($iRes)) {
			while($fe = $GenesisDbh->aFetchAssoc($iRes)) {
				$aRow[] = $fe;
			}
			
			//$tag = $this->formatTag($aRow);
			return $aRow ;
		}
	} 
	
	
	function UpdateTagStat($tag_no, $status) {
		global $GenesisDbh;
		
		$sql = "UPDATE galaxy_tag SET status = '".$status."' WHERE tag_no = '".$tag_no."'";
		$iRes = $GenesisDbh->iQuery2($sql);
	}
	
	
	function formatTag($tag) {
		$Tag = array();
		
		$i = 0;
		foreach($tag as $key => $val) {		
			$Tag[$i]['data'] = $val['tag_name'];
			$Tag[$i]['attr'] = array("id" => $val['tag_no']);
			$Tag[$i]['state'] = "closed";

			$i++;
		}	
		
		return $Tag;
	}

	
	function getTageLog($func_name, $table_name, $id) {
		global $GenesisDbh;

		$sql = "SELECT `tag_no` 
				FROM `genesis`.`galaxy_tag_log`
				WHERE `status` = '1'
				AND  `func_name` = '$func_name'
				AND `table_name` = '$table_name'
				AND `table_no` = $id";

		$iRes = $GenesisDbh->iQuery2($sql);

		if($GenesisDbh->iNumRows($iRes) > 1) {
			while($fe = $GenesisDbh->aFetchAssoc($iRes)) {
				$aRow[] = $fe['tag_no'];
			}
		}else {
			$fe = $GenesisDbh->aFetchAssoc($iRes);
			$aRow = $fe['tag_no'];
		} 

		return $aRow;
	}

	function getTagById($id) {
		global $GenesisDbh;

		$id = implode(",", $id);

		$sql = "SELECT `tag_no`, `tag_name` 
					FROM `genesis`.`galaxy_tag`
					WHERE `tag_no` IN ($id) 
					AND `status` = '1'";

		$iRes = $GenesisDbh->iQuery2($sql);
		
		while($fe = $GenesisDbh->aFetchAssoc($iRes)) {
			$aRow[] = $fe;
		}

		return $aRow;
	}
	*/
	
	
	
	
	
	
	
	
}

?>