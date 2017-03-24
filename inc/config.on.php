<?php

error_reporting(E_ALL & ~(E_NOTICE|E_STRICT|E_DEPRECATED));
ini_set('display_errors', true);
defined('PATH_ROOT')|| define('PATH_ROOT', realpath(dirname(__FILE__) . '/..'));

define('BEAUTY2_SERVER','beauty2.on.net');
define('GENESIS_SERVER','genesis.on.net');
define('ORDER_SERVER', 'order2.on.net');
define('CSSCOMPANY_SERVER', 'csscompany.on.net');

		/**
		 * SMARTY 設定
		 */
		define('PATH_SMARTY_TPL', PATH_ROOT.'/tpl');
		
		
		/**
		 * 連線到後台資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_HOST',	'172.16.2.109');
		define('_GENESIS_DB',	'genesis');
		define('_GENESIS_USER',	'robot');
		define('_GENESIS_PASS',	'robot');
		
		/**
		 * 連線到後台LOG資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_LOG_HOST',	'172.16.2.111');
		define('_GENESIS_LOG_DB',	'genesis_log');
		define('_GENESIS_LOG_USER',	'robot');
		define('_GENESIS_LOG_PASS',	'robot');
		

		
		define('_OLDCAT_DB', 'galaxy_oldcat');
		define('_OLDCAT_HOST', '172.16.1.246:33306');
		define('_OLDCAT_USER', 'echelon_web');
		define('_OLDCAT_PASS', 'p0o9i8u7y6');

		/**
		 *  @desc 公司
		 */
		
		define('_COMPANY_HOST',	'172.16.2.109');
		define('_COMPANY_DB',	'galaxy_company');
		define('_COMPANY_USER',	'robot');
		define('_COMPANY_PASS',	'robot');
		
        define('_USER_HOST',	'172.16.2.109');
		define('_USER_DB',		'galaxy_user');
		define('_USER_USER',	'robot');
		define('_USER_PASS',	'robot');

		/**
		 *  @desc 訂單
		 */
		
		define('_ORDER_HOST',	'order.on.net'); 
		define("_ORDER_DB",		'galaxy_order2');
		define('_ORDER_USER',	'robot');
		define('_ORDER_PASS',	'robot');	
		
		/*
		*	Git Web Interface DB
		*/
		define('_GIT_HOST',	'172.16.2.109');
		define('_GIT_DB', 'galaxy_git');
		define('_GIT_USER',	'robot');
		define('_GIT_PASS',	'robot');
		

//php self path
defined('PHP_SELF_PATH')
|| define('PHP_SELF_PATH', dirname($_SERVER['PHP_SELF']));
//根目錄
defined('PATH_ROOT')
|| define('PATH_ROOT', realpath(dirname(__FILE__) . '/..'));
/**
 * 定義分頁的資料序號
 */
define("PAGE_INPUT_TYPE_NO", 142); 
defined('_LANG_NEXT_PAGE')||define('_LANG_NEXT_PAGE', '下一頁');
defined('_LANG_LAST_PAGE')||define('_LANG_LAST_PAGE', '最後一頁');


/**
 * session db config *Required*
 */
defined('_SESSION_HOST')||define('_SESSION_HOST',	'172.16.2.111');
defined('_SESSION_DB')||define('_SESSION_DB',	'genesis');
defined('_SESSION_USER')||define('_SESSION_USER',	'robot');
defined('_SESSION_PASS')||define('_SESSION_PASS',	'robot');

/**
 * date.timezone
 */
date_default_timezone_set("UTC");
/**
 * php script exec unlimit in time
 */
set_time_limit( 0 );

/**
 * SMTP AUTH
 */
define('_ICB_SMTP_ACCOUNT', 	'icb@iwant-in.net');
define('_ICB_SMTP_PASSWORD', 	'xfh62yz7');

/**
 * ProgressBar Style
 */
define('PAGING_NUM', 			10);


/**
 * layout 語言
 */
define('_LANG', 'tw');

/**
 *  @desc 提供的服務
 */
$_service = array (
	0 => "無",
	1 => "圖文專案",
	2 => "ICB",
	3 => "台灣好東西"
);

?>