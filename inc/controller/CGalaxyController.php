<?php

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

include_once($sNowPath.'/inc/model/CGalaxyClass.php');

class CGalaxyController extends CGalaxyClass
{
	static public $Smarty;

	public function __construct(){

	}
}
?>