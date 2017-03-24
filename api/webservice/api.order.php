<?php

/**
 *  @desc 抓取訂單資料 API
 *  @created 2015/10/26
 */

//header('Content-Type: text/xml; charset=utf-8');

//error_reporting(E_ERROR | E_WARNING | E_PARSE);

$sNowPath = realpath(dirname(dirname(dirname( __FILE__ ))));

//include files
include_once $sNowPath."/inc/config.php";
include_once $sNowPath."/inc/CDbShell.php";
include_once $sNowPath."/inc/class.session.php";
include_once $sNowPath."/inc/controller/CGalaxyController.php";
include_once $sNowPath."/inc/smarty.config.php";
include_once $sNowPath."/inc/nusoap/lib/nusoap.php";
include_once $sNowPath."/inc/CArrayToObject.php";
include_once $sNowPath."/inc/model/order/COrder.php";

// init
$namespace = "http://".ORDER_SERVER."/";
$server = new soap_server();

$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$server->debug_flag=true;
$server->configureWSDL("OrderService",$namespace);
$server->wsdl->schemaTargetNamespace = $namespace;

// 產業的型別
$server->wsdl->addComplexType(
	'product', // name
	'complexType', // typeClass (complexType|simpleType|attribute)
	'array', // phpType: array and struct
	'all', // compositor (all|sequence|choice)
	'', // restrictionBase namespace:name
	array(
		'product_name' => array('name' => 'product_name', 'type' => 'xsd:string'),
		'sign_count' => array('name' => 'sign_count', 'type' => 'xsd:int'),
		'sign_date' => array('name' => 'sign_date', 'type' => 'xsd:date')
	)
);


// 註冊函式
$server->register(
	'hello',
	array('name'=>'xsd:string'),
	array('return'=>'xsd:string')
);

$server->register(
	'aGetProducts',
	array('co_id'=>'xsd:int'),
	array('return'=>'tns:product')
);


// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
// $server->service($HTTP_RAW_POST_DATA);
$server->service(file_get_contents("php://input"));


function hello($name) {
	if (!$name) {
        return new soap_fault('Client', '', 'Put your name!');
    }
	return 'Hello, '. $name;
	// return new soapval('return', 'xsd:string', 'Hello, '.$name);
}

/**
 *  @desc 抓取某公司產品
 *  @created 2015/10/26
 */
function aGetProducts($co_id=0){
	$aData = array();
	$aReturn[] = array(
		'product_name' => '',
		'sign_count' => '',
		'sign_date' => ''
	);
	if (!$co_id) return $aData;
	//$aObject = array();

	$aAllOrder = COrder::aAllOrder("phase_no=3 AND contract_client_no=$co_id");

	if (!$aAllOrder) return $aData;

	$sProductName = '';
	$sSignDate = '1911-01-01';
	foreach($aAllOrder as $oCOrder){
		// 最近回簽日
		if(strtotime($sSignDate)<strtotime($oCOrder->sProposalDate)){
			$sSignDate = $oCOrder->sProposalDate;
		}

		// 產品名稱
		if ($sProductName) $sProductName .= "、".$oCOrder->sGetProductName("、");
		else $sProductName = $oCOrder->sGetProductName("、");
	}

	$aRow = array();
	$aRow['product_name'] = $sProductName;
	$aRow['sign_count'] = COrder::iGetCount("phase_no=3 AND contract_client_no=$co_id");
	$aRow['sign_date'] = $sSignDate;
	$aData[] = $aRow;

	//$aObject[] = new CArrayToObject($aRow);

	return $aData;
}

/* End of File */