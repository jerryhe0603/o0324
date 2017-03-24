<?php
/*



*/

include_once('../inc/controller/CGalaxyController.php');
include_once('../inc/model/CTag.php');
class CTagSetAdmin extends CGalaxyController
{

	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;
	const BACK_TO_ADMIN = 4;

	private $sDBName = 'PUPPETS';//sub system database

	public function __construct(){
	}

	function tManager() {
		$action = isset($_GET['action'])?$_GET['action']:'';
		try{
			switch($action){
				
				default:
				case "set":
					return $this->tSetTagLog();
				break;
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func_name'];
					if(isset($_GET['table_no']))
						$sUrl .= '&action=list&goid='.$_GET['table_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
			
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;

	}

	private function tSetTagLog() {
		$Smarty = self::$Smarty;
		
		if(empty($_POST)){
			
			$aTagsData = CTag::aGetTageLogByTableNo($this->sDBName,$_GET['func_name'],$_GET['table_name'],$_GET['table_no']);
			
			
			
						
			$Smarty->assign("tagSetSubmit",$_SERVER['PHP_SELF']."?". $_SERVER['QUERY_STRING']."&time=".time());
			$Smarty->assign('dataUrl',$_SERVER['PHP_SELF']."?func=".$_GET['func_name']."&goid=".$_GET['table_no']);
			$Smarty->assign('dataViewUrl',$_SERVER['PHP_SELF']."?func=".$_GET['func_name']."&action=view&goid=".$_GET['table_no']);
			$Smarty->assign('tagsData',$aTagsData);

			return $output = $Smarty->fetch('./admin/'.get_class($this).'/tag_set.html');
			
			
		} else {

			try{
				

				 CTag::vSetTagLog($this->sDBName,$_GET['func_name'],$_GET['table_name'],$_GET['table_no'],$_POST);


			}catch (Exception $e){
				throw new Exception('CTagSetAdmin->tSetTagLog: '.$e->getMessage(),self::BACK_TO_LIST);
			}
			
		
			CJavaScript::vAlertRedirect(_LANG_TAG_SET_SUCESS,$_SERVER['PHP_SELF']."?func=".$_GET['func_name']."&action=list&goid=".$_GET['table_no']);


		}

	}
}
?>
