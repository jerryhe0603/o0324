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

//default 語系
include_once('../inc/CLang.php');
CLang::$iBackendLang=1;

include_once("../lang/"._LANG.".php");

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);

include_once('../inc/model/CPuppets.php');
include_once('../inc/model/CPuppetsField.php');
include_once('../inc/model/CPuppetsElement.php');

try{
	switch($_GET['action']){
		case 'full_name':
			$sPuppetsNo = $_POST['puppets_no'];
			$oCPuppets = CPuppets::oGetPuppets($sPuppetsNo);
			$iElementMappingNo = CPuppetsField::$_field['allName']['element_mapping_no'];
			$oCPuppetsElement = CPuppetsElement::oGetPuppetsElement($oCPuppets->sPuppetsUuid,$iElementMappingNo);
			$aReturn = array(	'element_mapping_name' => $oCElementMapping->sName,
								'element_mapping_value' => $oCPuppetsElement->sValue
								);
			break;
		case 'set_site_no':
			$aSiteNos = $_POST['site_no'];	//supposed to be array
			$aSelectorData = array( 'site_no'=>$aSiteNos
									);
			$session->set('puppets_selector', $aSelectorData);
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