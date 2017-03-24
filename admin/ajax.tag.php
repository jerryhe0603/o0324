<?php
	include_once('../inc/config.php');
	include_once('../inc/smarty.config.php');
	
	include_once('../inc/class.session.php');
	include_once('../inc/CMisc.php');
	include_once('../inc/model/CGalaxyClass.php');
	include_once('../inc/model/CTag.php');
	
	$session	= new session($_GET['PHPSESSID']);
	CGalaxyClass::$session =$session;	//insert to basic class static member

	//quotes info from client
	$_GET = CMisc::my_quotes($_GET);


	
	
	
	$target_element = $_GET['target_element'];
	$component_element = $_GET['component_element']; 
	$layout_element = $_GET['layout_element'];
	$parent_tag_no = $_GET['parent_tag_no'];


	if($target_element=="" || $component_element == ""){
		echo "target_element || component_element NULL";
	
	}else
		echo CTag::tGetTagSelector($target_element, $component_element,$layout_element,$parent_tag_no );
	
	exit;


?>
