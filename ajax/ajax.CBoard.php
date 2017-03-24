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


include_once('../inc/model/CBoard.php');

$name = $_POST['text'];
$limit = "8";

switch($_GET['action']){
	case 'typeahead':
		$aAllBoard = CBoard::aAllBoard("board_title LIKE '%$name%'","LIMIT $limit");
		foreach ($aAllBoard as $oCBoard) {
			$oCSite = $oCBoard->oSite();
			$aMap = array(	'board_no'=>$oCBoard->iBoardNo,
							'board_title'=>"{$oCSite->sTitle}:{$oCBoard->sTitle}"
							);
			$aReturn[] = $aMap;
		}
		if($aReturn)
			exit(json_encode($aReturn));
		break;
	case 'by_site':
		$iSiteNo = $_POST['site_no'];
		$aAllBoard = CBoard::aAllBoard("site_no=$iSiteNo");
		foreach ($aAllBoard as $oCBoard) {
			$aMap = array(	'board_no'=>$oCBoard->iBoardNo,
							'board_title'=>$oCBoard->sTitle
							);
			$aReturn[] = $aMap;
		}
		if($aReturn)
			exit(json_encode($aReturn));
		break;
	case 'all':
		
		break;
	case 'one':
		
		break;
	default:
		break;
}
exit(false);
?>