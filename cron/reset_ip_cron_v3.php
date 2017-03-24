<?php
/*
sleep(3);
echo $argv[1]."\n";
exit;
*/
/**
* 換IP
* 
* @package php5classes
* @author Ophidian.Wang
* @version 3.0
*/

include_once('../inc/config.php');
include_once('../inc/model/CProxy.php');

$sProxyName = $argv[1];

//$aRouter = CProxy::SetProxyRouterIP($sProxyName);
//CProxy::UpdateResetIPInfo($aRouter);

//log 
$resource = fopen('reset_ip_cron_v3.log', "a");
$sNow = date("Y-m-d H:i:s");
fwrite($resource, "Proxy:$sProxyName , reset IP at $sNow \n");

exit;
?>