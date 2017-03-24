<?php

/**
 *  @desc 測試 WebService
 *  @created 2015/10/27
 */

header('Content-Type: text/html; charset=utf-8');

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));
include_once $sNowPath."/inc/config.php";
include_once $sNowPath."/inc/nusoap/lib/nusoap.php";

// Create the Client instance
$namespace = "http://".ORDER_SERVER."/";
$client = new nusoap_client($namespace.'api/webservice/api.order.php?wsdl', true);
$client->soap_defencoding = 'utf-8';
$client->xml_encoding = 'utf-8';
$client->decode_utf8 = false;

$result = $client->call(
	'aGetProducts',
	array(
		'co_id'=> 27934
	)
);

/*$result = $client->call(
	'hello',
	array(
		'name'=> 'Everyone'
	)
);*/


if ($client->fault) {
	echo "<h2>Fault</h2><pre>".print_r($result)."</pre>";
} else {
	$error = $client->getError();
	if ($error) {
		echo "<h2>Error</h2><pre>".$error."</pre>";
	} else {
		echo "<h2>Main</h2>";echo "<pre>";echo print_r($result); echo "</pre>";
	}
}

// DEBUG show soap request and response
//echo "<h2>Request</h2>";
//echo "<pre>". htmlspecialchars($client->request, ENT_QUOTES) ."</pre>";
//echo "<h2>Response</h2>";
//echo "<pre>". htmlspecialchars($client->response, ENT_QUOTES) ."</pre>";

/*
$client = new nusoap_client('http://www.oldcat.com.tw/api/webservice/api.oldcat.php');
$client->soap_defencoding = 'utf-8';
$client->xml_encoding = 'utf-8';
$client->decode_utf8 = false;
$result = $client->call(
	'aGetManagement',
	array()
);
if ($client->fault) {
	$fault="Fault: $result";
	CJavaScript::vAlertRedirect($fault, $_SERVER['PHP_SELF']);
} else {
	$err = $client->getError();
	if ($err) {
		CJavaScript::vAlertRedirect($err, $_SERVER['PHP_SELF']);
	} else {
		echo "<pre>";echo print_r( $result);echo "</pre>";
	}
}
*/

/* End of File */