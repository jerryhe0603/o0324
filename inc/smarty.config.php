<?php

	include_once(PATH_ROOT."/smarty/Smarty.class.php");
    include_once(PATH_ROOT.'/inc/controller/CGalaxyController.php');
	
    $Smarty = new Smarty();

    $Smarty->template_dir  =  PATH_SMARTY_TPL  .  "/templates_utf8/"._LANG."/";
    $Smarty->compile_dir  	=  PATH_SMARTY_TPL  .  "/templates_c/";
    $Smarty->cache_dir  	=  PATH_SMARTY_TPL  .  "/cache/"; 
	//$Smarty->config_dir  =  __SITE_ROOT  .  "/configs/";

    $Smarty->left_delimiter  	=  '<{';
    $Smarty->right_delimiter  	=  '}>';

    CGalaxyController::$Smarty = $Smarty;
?>