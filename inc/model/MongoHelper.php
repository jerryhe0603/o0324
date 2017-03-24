<?php
/*
	Version: 0.0.2
	Author: Ophidian Wang

	1. This class has no instance for now.
	2. This class provides helper functions which allow user acess MongoDB object
	3. MongoClients are manage by this class and thus user wouldn't have to new instance on their own.
	4. extension = mongo.so (in php.ini) is required
	5. Installation: http://us2.php.net/manual/en/mongo.installation.php

	USAGE:
		include_once('MongoHelper.php');
		$oMongoDB = MongoHelper::oDB('MACRO');	//constant in config.mongodb.php
	
*/
include_once(__DIR__.'/config.mongodb.php');

if(!class_exists('MongoClient')){
	print_r("No such class: MongoClient; please install mongodb library first.");
	exit;
}

class MongoHelper{

	static private $aMongoClients = array();

    static private function selectHost($aHost,$aOption){
    	//Create url, which will be unique key and connection url.
    	$sUrl = 'mongodb://'.implode(',', $aHost);

    	if(is_null(self::$aMongoClients[$aOption['username'].'@'.$sUrl.'/'.$aOption['db']])){
    		
			$oMongoClient = new MongoClient($sUrl, $aOption);			
			self::$aMongoClients[$aOption['username'].'@'.$sUrl.'/'.$aOption['db']] = $oMongoClient;
    	}
    	return self::$aMongoClients[$aOption['username'].'@'.$sUrl.'/'.$aOption['db']];
    }

    static public function oDB($sMacro){
    	$aConfig = json_decode(constant('_'.$sMacro.'_CONFIG'),true);

    	return self::selectHost($aConfig['host'],$aConfig['option'])->selectDB($aConfig['dbname']);
    }
}

?>