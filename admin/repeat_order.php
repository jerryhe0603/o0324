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

$oDB=COrder::oDB('ORDER');
$sSql="SELECT * FROM `order`";
$iDBq=$oDB->iQuery($sSql);
try{
	$oDB->vBegin();
	while($aRow=$oDB->aFetchAssoc($iDBq)){
		$sOldOrderNo=$aRow['order_no'];
		$sNewOrderNo='order_'.CMisc::uuid_v1();

		$aRow['order_no']=$sNewOrderNo;
		$oDB->sInsert('order', array_keys($aRow), array_values($aRow));

		$aRow['create_time']=date('Y-m-d H:i:s');
		$aRow['modify_time']=date('Y-m-d H:i:s');
		
		$sSql_project="SELECT * FROM `order_project` where `order_no` = '$sOldOrderNo' ";
		$iDBq_project=$oDB->iQuery($sSql_project);
		try{

			while($aRow_project=$oDB->aFetchAssoc($iDBq_project)){
				/*
				switch($aRow_project['service_id']){
					case 3:
						$aRow_project['project_no']='goods_'.CMisc::uuid_v1();
						break;
					case 4:
						$aRow_project['project_no']='beauty_'.CMisc::uuid_v1();
						break;
					default:
						$aRow_project['project_no']=CMisc::uuid_v1();
						break;
				}
				*/
				$aRow['create_time']=date('Y-m-d H:i:s');
				$aRow['modify_time']=date('Y-m-d H:i:s');
				$oDB->sInsert('order_project', array_keys($aRow_project), array_values($aRow_project));
			}

		}catch(Exception $e){

			throw new Exception($e->getMessage());
		}

	}
	$oDB->vCommit();	
}catch(Exception $e){
	$oDB->vRollback();
	throw new Exception($e->getMessage());
}
echo 'end';
?>