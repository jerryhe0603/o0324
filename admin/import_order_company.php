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
$sSql="SELECT `order_no`, `client_no`, `contract_client_no`, `contact_no` FROM `order`";
$iDBq=$oDB->iQuery($sSql);
$iCounter=1;
while($aRow=$oDB->aFetchAssoc($iDBq)){
	echo "$iCounter <br>";
	$sOrderId='';
	$aClient=array();
	$aContract=array();
	foreach($aRow as $key => $value){
		//echo "$key: $value<br>";
		switch($key){
			case 'order_no':
				if(!empty($sOrderId)) break;
				echo $sOrderId=$value; 
				break;
			case 'client_no':
				if($value==0) break;
				echo $aClient['no']=$value;
				echo $aClient['name']=CCompany::oGetCompany($value)->sName;
				break;
			case 'contract_client_no':
				if($value==0) break;
				echo $aContract['no']=$value;
				echo $aContract['name']=CCompany::oGetCompany($value)->sName;
				break;
			case 'contact_no':
				if($value==0) break;
				echo $aContract['contact_user_no']=$value;
				echo $aContract['contact_user_name']=CUserCompany::oGetUser($value)->sName;
				break;
		}
		echo "<br>";
	}

	if(!empty($aClient)){
		
		$aValues=array(
			"order_no"=>$sOrderId,
			"co_id"=>$aClient['no'],
			"type"=>2,
			"name"=>$aClient['name'],			
		);
		$oDB->sInsert("order_company", array_keys($aValues), array_values($aValues));
		
	}
	if(!empty($aContract)){
		
		$aValues=array(
			"order_no"=>$sOrderId,
			"co_id"=>$aContract['no'],
			"type"=>1,
			"name"=>$aContract['name'],
			"contact_user_no"=>$aContract['contact_user_no'],
			"contact_user_name"=>$aContract['contact_user_name'],
		);
		$oDB->sInsert("order_company", array_keys($aValues), array_values($aValues));
		
		CMisc::vPrintR($aContract);
	}


	echo "<br>";
	$iCounter++;
}
?>