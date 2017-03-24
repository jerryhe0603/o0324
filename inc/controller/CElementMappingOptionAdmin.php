<?php
include_once('../inc/controller/CGalaxyController.php');
//include model.php
include_once('../inc/model/CElementMapping.php');
include_once('../inc/model/CElementMappingOption.php');

class CElementMappingOptionAdmin extends CGalaxyController
{
	
	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

	private $sDBName = 'GENESIS';

	/*
		constructor of this controller
	*/
	public function __construct(){
	}

	/*
		entry of this controller
		handle exception and decide where to redirect by exception code
	*/
	public function tManager() {
		$action = isset($_GET['action'])?$_GET['action']:'';
		try{
			switch($action){
				case "add":
					return $this->tOptionAdd();
					break;
				case "edit":
					return $this->tOptionEdit();
					break;
				case "active":
					return $this->vOptionActive();
					break;
				default:
				case "search":
				case "list":
					return $this->tOptionList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'];
					if(!empty($_GET['element_mapping_no']))
						$sUrl .= '?func='.$_GET['func'].'&element_mapping_no='.$_GET['element_mapping_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					$sUrl = $_SERVER['PHP_SELF'];
					if(!empty($_GET['element_mapping_no']))
						$sUrl .= '?func='.$_GET['func'].'&element_mapping_no='.$_GET['element_mapping_no'];
					if(!empty($_GET['element_mapping_option_no']))
						$sUrl .= '&action=edit&element_mapping_option_no='.$_GET['element_mapping_option_no'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tOptionAdd(){
		$Smarty = self::$Smarty;
		if(empty($_GET['element_mapping_no']))
			throw new Exception('');

		$iElementMappingNo = $_GET['element_mapping_no'];
		$oEleMap = CElementMapping::oGetElementMapping($iElementMappingNo);

		if(empty($_POST)){
			$Smarty->assign('oEleMap',$oEleMap);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/element_mapping_option_edit.html');
		}else{
			$aInit = 
			$oOption = new CElementMappingOption($_POST);
			try{
				$oOption->iAddElementMapOption();
			}catch (Exception $e){
				throw new Exception('CElementMappingOptionAdmin->tOptionAdd:'.$e->getMessage(),self::BACK_TO_LIST);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&element_mapping_no={$oEleMap->iElementMappingNo}");
	}

	private function tOptionEdit(){
		$Smarty = self::$Smarty;
		if(empty($_GET['element_mapping_option_no']))
			throw new Exception('',self::BACK_TO_LIST);
		
		$iElementOptionNo = $_GET['element_mapping_option_no'];
		$oOption = CElementMappingOption::oGetElementMappingOption($iElementOptionNo);

		if(empty($_POST)){

			$iElementMappingNo = $_GET['element_mapping_no'];
			$oEleMap = CElementMapping::oGetElementMapping($iElementMappingNo);

			$Smarty->assign('oEleMap',$oEleMap);
			$Smarty->assign('oOption',$oOption);
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/element_mapping_option_edit.html');
		}else{
			
			$oOption->sName = $_POST['element_mapping_option_name'];
			$oOption->bStatus = $_POST['element_mapping_option_status'];
			//update
			try{
				$oOption->vUpdateElementMapOption();
			}catch (Exception $e){
				throw new Exception('CElementMappingOptionAdmin->tOptionEdit:'.$e->getMessage(),self::BACK_TO_EDIT);
			}
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&element_mapping_no={$oOption->iElementMappingNo}");
	}

	private function vOptionActive(){
		if(empty($_GET['element_mapping_no']))
			throw new Exception('',self::BACK_TO_LIST);
		$iElementOptionNo = $_GET['element_mapping_option_no'];
		$oOption = CElementMappingOption::oGetElementMappingOption($iElementOptionNo);
		try{
			$oOption->vActivate();
		}catch (Exception $e){
			throw new Exception('CElementMappingOptionAdmin->vOptionActive:'.$e->getMessage(),self::BACK_TO_LIST);
		}
		CJavaScript::vAlertRedirect('',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&element_mapping_no={$oOption->iElementMappingNo}&goid={$oOption->iElementOptionNo}");
	}

	private function tOptionList(){
		$Smarty = self::$Smarty;
		$session = self::$session;
		$oDB = self::oDB($this->sDBName);

		if(empty($_GET['element_mapping_no']))
			throw new Exception('');

		$iElementMappingNo = $_GET['element_mapping_no'];
		$oEleMap = CElementMapping::oGetElementMapping($iElementMappingNo);

        $Smarty->assign("oEleMap",$oEleMap);

		return $output = $Smarty->fetch('./admin/'.get_class($this).'/element_mapping_option_list.html');
	}
}
?>