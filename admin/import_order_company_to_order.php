<?php
include_once('../inc/config.php');
include_once('../inc/smarty.config.php');
include_once('../inc/class.session.php');

/*
	CAUTION: DO NOT include any other class , unless it is used here
*/
include_once('../inc/CMisc.php');
include_once('../inc/CJavaScript.php');
include_once('../inc/model/CUser.php');
include_once('../inc/model/CGalaxyClass.php');
include_once('../inc/model/order/COrder.php');
include_once('../inc/model/CUserCompany.php');

$oDB=COrder::oDB('ORDER');
$sSql="SELECT * FROM `order_company`";
$iDBq=$oDB->iQuery($sSql);
$aCompany=array();
while($aRow=$oDB->aFetchAssoc($iDBq)){
	$sOrderUuid=$aRow['order_no'];
	//SELECT `order_company_no`, `order_no`, `co_id`, `type`, `name`, `contact_user_no`, `contact_user_name` FROM `order_company` WHERE 1
	switch($aRow['type']){
		case 1:
			$aCompany[$sOrderUuid]['contract_client_name']=$aRow['name'];
			$aCompany[$sOrderUuid]['contact_user_name']=$aRow['contact_user_name'];
			break;
		case 2:
			$aCompany[$sOrderUuid]['client_name']=$aRow['name'];
			break;
	}
}
foreach($aCompany as $key=> $aRow){
	echo $sSql="UPDATE `order` SET `client_name`='".$aRow['client_name']."', `contract_client_name`='".$aRow['contract_client_name']."',`contact_user_name`='".$aRow['contact_user_name']."' WHERE `order_no`='$key'";
	$iDBq=$oDB->iQuery($sSql);
}
?>