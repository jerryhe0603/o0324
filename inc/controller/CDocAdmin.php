<?php
include_once('../inc/model/CDoc.php');
include_once('../inc/model/beauty/CBeautyDoc.php');
include_once('../inc/model/beauty/CBeautySchedule.php');


class CDocAdmin
{

	/*
		exception code of this controller
	*/
	const BACK_TO_LIST = 1;
	const BACK_TO_VIEW = 2;
	const BACK_TO_EDIT = 3;

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
					return $this->tDocAdd();
					break;
				case "edit":
					return $this->tDocEdit();
					break;
				case "view":
					return $this->tDocView();
					break;
				default:
				case "search":
				case "list":
					return $this->tDocList();
					break;		
			}
		}catch (Exception $e){
			switch($e->getCode()){
				case self::BACK_TO_LIST:
					$sUrl = $_SERVER['PHP_SELF'].'?func='.$_GET['func'];
					if(isset($_GET['doc_uuid']))
						$sUrl .= '&action=list&goid='.$_GET['doc_uuid'];
					CJavaScript::vAlertRedirect($e->getMessage(),$sUrl);
					break;
				case self::BACK_TO_EDIT:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF'].'?func='.$_GET['func'].'&action=edit&doc_uuid='.$_GET['doc_uuid']);
					break;
				default:
					CJavaScript::vAlertRedirect($e->getMessage(),$_SERVER['PHP_SELF']);
					break;
			}
		}
		exit;
	}

	private function tDocAdd(){
		if(!$_POST) {
			//pagetypes, which sort element_mapping 
			$Smarty->assign('aPageType',CPageType::aAllType());
			//html
			$Smarty->assign('targetForm',$Smarty->fetch('./admin/CDocAdmin/doc_content_add.html'));
			$Smarty->assign('elementMappingSelector',$Smarty->fetch('./admin/docs_fields_selector.html'));
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/doc_add.html');
		}else{
			try{
				$oCDoc = new CDoc($_POST,$iFolderNo);	//lack of docs_no, and thus require folder_no
				$oCDoc->sAddDoc();
			}catch (Exception $e){
				throw new Exception('CDocAdmin->tDocAdd: '.$e->getMessage(),self::BACK_TO_LIST);
			}
			CJavaScript::vAlertRedirect('doc add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCDoc->sDocUuid}");
		}
	}


	private function tDocEdit(){
		$sDocUuid = $_GET['docs_no'];
		$oCDoc = CDoc::oGetDoc($sDocUuid);	//full doc object
		if(!$_POST) {
			$Smarty->assign('oCDoc',$oCDoc);
			//pagetypes, which sort element_mapping 
			$Smarty->assign('aPageType',CPageType::aAllType());
			//html
			$Smarty->assign('targetForm',$Smarty->fetch('./admin/CDocAdmin/doc_content_edit.html'));
			$Smarty->assign('elementMappingSelector',$Smarty->fetch('./admin/docs_fields_selector.html'));
			return $output = $Smarty->fetch('./admin/'.get_class($this).'/doc_edit.html');
		}else{
			try{
				$oCDocUpdate = new CDoc($_POST);	//full doc object from $_POST
				$oCDoc->vOverwrite($oCDocUpdate);	//overwrite old data
				$oCDoc->vUpdateDoc();	//update
				
			}catch (Exception $e){
				throw new Exception('CDocAdmin->tDocDeit: '.$e->getMessage(),self::BACK_TO_LIST);
			}
			CJavaScript::vAlertRedirect('doc add success',$_SERVER['PHP_SELF'].'?func='.$_GET['func']."&action=list&goid={$oCDoc->sDocUuid}");
		}
	}


	private function tDocView(){
		//$sDocUuid = $_GET['doc_uuid'];

		
		echo '<pre>';
		/*
		$sDocUuid = '5_E4B87105-279B-F600-6ECA-86CF0CA1CD89';
		$oCDoc = CDoc::oGetDoc($sDocUuid);
		$oCDoc->aDocElement();	//set & get doc element
		print_r($oCDoc);
		*/
		/*
		$iBeautyDocNo = 74;
		$oCBeautyDoc = CBeautyDoc::oGetDoc($iBeautyDocNo);
		$oCBeautyDoc->oCDoc();
		$oCBeautyDoc->vGetAnalysis();
		$oCBeautyDoc->vGetWriting();
		$oCBeautyDoc->oLastUser();
		print_r($oCBeautyDoc);
		*/
		$oSchedule = CBeautySchedule::oGetSchedule('beauty_8DAEEBFA-7C34-4C0E-1206-864D558390D9');
		print_r($oSchedule);
		echo '</pre>';
		exit;
	}

	private function tDocList(){
		//$iTableNo decided by sub system, example: beauty2's $iTableNo==5
		$iTableNo = 5;
		echo '<pre>';
		print_r(CDoc::aAllDoc($iTableNo,'','ORDER BY createtime DESC LIMIT 0,10'));
		echo '</pre>';
		exit;
	}

	static private function aGetDocsFieldsSelector(){
		
	}
}
?>