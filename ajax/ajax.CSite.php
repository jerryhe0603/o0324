<?php
include_once('../inc/config.php');
include_once('../inc/class.session.php');
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CGalaxyClass.php');

//default 語系
include_once('../inc/CLang.php');
$CLang	= new CLang(1); //define 語系
include_once("../lang/"._LANG.".php");

$session	= new session($_GET['PHPSESSID']);
CGalaxyClass::$session =$session;	//insert to basic class static member

if(is_null($session->get('oCurrentUser'))){
	exit(false);
}

//quotes info from client
$_GET = CMisc::my_quotes($_GET);
$_POST = CMisc::my_quotes($_POST);


include_once('../inc/model/CSite.php');

$name = $_POST['text'];
$limit = "8";

switch($_GET['action']){
	case 'typeahead':
		$aAllSite = CSite::aAllSite("site_title LIKE '%$name%'","LIMIT $limit");
		foreach ($aAllSite as $oCSite) {
			$aMap = array(	'site_no'=>$oCSite->iSiteNo,
							'site_title'=>"{$oCSite->sTitle}"
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