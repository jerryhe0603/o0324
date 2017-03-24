<?php
include_once('../inc/config.php');
include_once('../inc/class.session.php');
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CGalaxyClass.php');

$session	= new session($_GET['PHPSESSID']);
CGalaxyClass::$session =$session;	//insert to basic class static member

if(is_null($session->get('oCurrentUser'))){
	exit(false);
}

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

include_once('../inc/model/CScriptElement.php');

$multidata = $_POST['text'];
try{
	switch($_GET['action']){
		case 'get_element':
			$iScriptNo = $multidata;
			$aScriptElement = CScriptElement::aAllElement("`script_no`='$iScriptNo' AND `element_no`!=0 AND `script_element_source`='2'");
			foreach ($aScriptElement as $oElement) {
				$oCElement = $oElement->oElement();
				$oElementMapping = $oCElement->oElementMapping();

				//if($oCElement->sTagType === 'file'){
				if($oElementMapping->iElementMappingNo == 999){
					$aMap = array(	'element_no'=>$oElement->iElementNo,
									'element_mapping_name'=>$oElementMapping->sName,
									'element_mapping_tag_type'=>$oElementMapping->sTagType,
									'element_mapping_value'=>$oElement->sValue,
									'element_status'=>$oElementMapping->bStatus
									);
					$aReturn['file'][] = $aMap;
				}else{
					$aMap = array(	'element_mapping_no'=>$oElementMapping->iElementMappingNo,
									'element_mapping_name'=>$oElementMapping->sName,
									'element_mapping_tag_type'=>$oElementMapping->sTagType,
									'element_mapping_value'=>$oElement->sValue,
									'element_status'=>$oElementMapping->bStatus,
									'script_element_order'=>$oElement->iOrder
									);
					 if($oElementMapping->sTagType==='select' || $oElementMapping->sTagType==='radio' || $oElementMapping->sTagType==='checkbox'){
					 	$i=0;
					 	foreach($oElementMapping->aOption as $option){
					 		$aMap['option'][$i]['bStatus']= $option->bStatus;
					 		$aMap['option'][$i]['sName']= $option->sName;
					 		$aMap['option'][$i]['element_mapping_option_no']= $option->iElementOptionNo;

					 		$i++;
					 	}
					 }
            						

					$aReturn['require'][] = $aMap;
				}
			}
			break;
		default:
			break;	
	}
}catch (Exception $e){
	$aReturn = array("errorMsg"=>$e->getMessage());
}

if(!is_null($aReturn))
	exit(json_encode($aReturn));
exit(false);
?>