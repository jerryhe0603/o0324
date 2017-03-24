<?php
include_once('../inc/config.php');
include_once('../inc/model/CProxy.php');

$aCProxy = CProxy::aAllProxys("`proxy_status`='1'");

$sProxyNames = '';
for ($i=0; $i < count($aCProxy) ; $i++) { 
	if($i!=0)
		$sProxyNames .= ' ';

	$oCProxy = $aCProxy[$i];
	$sProxyNames .= $oCProxy->sProxyName;
}

echo $sProxyNames;

exit;
?>