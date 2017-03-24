<?php

//build connection on db admin, if user's role has "readWriteAnyDatabase" on admin, then that user can access on all db

$aMongoConfig = array(	'host'=>array(	'172.16.58.3:10001'
									),	//may be replica
						'dbname'=>'test',
						'option'=>array(	'username'=>'admin',
											'password'=>'admin',
											'db'=>'admin'	//login enterence; don't modified this unless you know what you are doing
										)	//else option may be set here, see mongodb php document
					);
define('_MONGO_CONFIG',json_encode($aMongoConfig));

$aMongo2Config = array(	'host'=>array(	'172.16.58.3:10001'
									),
						'dbname'=>'test2',
						'option'=>array(	'username'=>'admin',
											'password'=>'admin',
											'db'=>'admin'
										)
					);
define('_MONGO2_CONFIG',json_encode($aMongo2Config));

$aMongo3Config = array(	'host'=>array(	'172.16.58.3:10001'
									),
						'dbname'=>'test3',
						'option'=>array(	'username'=>'admin',
											'password'=>'admin',
											'db'=>'admin'
										)
					);
define('_MONGO3_CONFIG',json_encode($aMongo3Config));

$aAdminConfig = array(	'host'=>array(	'172.16.58.3:10001'
									),
						'dbname'=>'admin',
						'option'=>array(	'username'=>'robot',
											'password'=>'robot',
											'db'=>'admin'
										)
					);
define('_ADMIN_CONFIG',json_encode($aAdminConfig));

?>