<?php

set_time_limit(0);

include_once(dirname(dirname(__FILE__))."/env.php");

defined('PATH_ROOT')|| define('PATH_ROOT', realpath(dirname(__FILE__) . '/..'));

define('CSSCOMPANY_SERVER', 'csscompany.on.net');

/**
 * date.timezone
 */
date_default_timezone_set("UTC");

$today = isset($_GET['date'])?($_GET['date']):date('Y-m-d');

switch($environment) {
	case 'release': // 正式機
		error_reporting(E_ALL^E_NOTICE ^E_STRICT^E_DEPRECATED);
		ini_set('display_errors', true);

		define('API_SERVER',	'localhost');
		
		/**
		 * SMARTY 設定
		 */
		define('PATH_SMARTY_TPL', PATH_ROOT.'/tpl');

		/**
		 * session db *Required*
		 */
		define('_SESSION_HOST',	'172.16.2.111');
		define('_SESSION_DB',	'genesis');
		define('_SESSION_USER',	'robot');
		define('_SESSION_PASS',	'robot');
		
		/**
		 * 連線到版面資料庫儲存DB 設定 *Required*
		 */
		define('_SITE_HOST',	'localhost');
		define('_SITE_DB',		'genesis');
		define('_SITE_USER',	'root');
		define('_SITE_PASS',	'up6ck6');
		
		/**
		 * 連線到後台LOG資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_LOG_HOST',	'172.16.2.111');
		define('_GENESIS_LOG_DB',	'genesis_log');
		define('_GENESIS_LOG_USER',	'robot');
		define('_GENESIS_LOG_PASS',	'robot');
		
		/**
		 * 連線到後台資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_HOST',	'localhost');
		define('_GENESIS_DB',	'genesis');
		define('_GENESIS_USER',	'robot');
		define('_GENESIS_PASS',	'robot');
		
		/**
		 * 連線到後台LOG資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_HOST',	'localhost');
		define('_GENESIS_DB',	'genesis');
		define('_GENESIS_USER',	'root');
		define('_GENESIS_PASS',	'up6ck6');
		
		/**
		 * puppets db *Required*
		 */
		define('_PUPPETS_HOST',	'localhost');
		define('_PUPPETS_DB',	'genesis_puppets');
		define('_PUPPETS_USER',	'root');
		define('_PUPPETS_PASS',	'up6ck6');
		
		/**
		 * 連線到身份LOG資料庫儲存DB *Required*
		 */
		define('_PUPPETS_LOG_HOST',	'172.16.2.111');
		define('_PUPPETS_LOG_DB',	'genesis_puppets');
		define('_PUPPETS_LOG_USER',	'robot');
		define('_PUPPETS_LOG_PASS',	'robot');
		
		/**
		 * docs db *Required*
		 */
		define('_DOCS_HOST',	'localhost');
		define('_DOCS_DB',		'genesis_docs');
		define('_DOCS_USER',	'root');
		define('_DOCS_PASS',	'up6ck6');
		
		/**
		 * queue db *Required*
		 */
		define('_QUEUE_HOST',	'172.16.2.110');
		define('_QUEUE_DB',	'queue_'.$today);
		define('_QUEUE_USER',	'robot');
		define('_QUEUE_PASS',	'robot');
		
		/**
		 * 連線到文案LOG資料庫儲存DB *Required*
		 */
		define('_DOCS_LOG_HOST',	'172.16.2.111');
		define('_DOCS_LOG_DB',		'genesis_docs');
		define('_DOCS_LOG_USER',	'robot');
		define('_DOCS_LOG_PASS',	'robot');
          
		/**
		 *  @desc 貓舍
		 */	
		define('_OLDCAT_DB',	'galaxy_oldcat');
		define('_OLDCAT_HOST',	'192.168.11.130');
		define('_OLDCAT_USER',	'echelon_web');
		define('_OLDCAT_PASS',	'p0o9i8u7y6');
		
		/**
		* @desc 公司資料庫
		*/
		define('_COMPANY_DB',	'galaxy_company');
		define('_COMPANY_HOST', 'localhost');
		define('_COMPANY_USER', 'root');
		define('_COMPANY_PASS', 'up6ck6');
		
		/**
		 *  @desc 使用者
		 */
		define('_USER_DB',		'galaxy_user');
		define('_USER_HOST',	'localhost');
		define('_USER_USER',	'root');
		define('_USER_PASS',	'up6ck6');
		
		/**
		* 訂單資料庫
		*/
		define('_ORDER_DB',		'galaxy_order_bak');
		define('_ORDER_HOST',	'localhost');
		define('_ORDER_USER',	'root');
		define('_ORDER_PASS',	'up6ck6');
	break;
	
	case 'develop': // 測試機
		/* debug */
		//error_reporting(E_ALL); // 顯示所有錯誤
		error_reporting(E_ALL^E_NOTICE ^E_STRICT^E_DEPRECATED);
		ini_set('display_errors', true);

		/**
		* @desc SESSION
		*/
		define("_SESSION_HOST", '172.16.2.41');
		define("_SESSION_DB",	'genesis');
		define("_SESSION_USER", 'robot');
		define("_SESSION_PASS", 'robot');
		
		/**
		 * 連線到後台資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_HOST',	'172.16.1.41');
		define('_GENESIS_DB',	'genesis');
		define('_GENESIS_USER',	'robot');
		define('_GENESIS_PASS',	'robot');

		/**
		 *  @desc 貓舍
		 */
		define('_OLDCAT_HOST',	'172.16.2.41');
		define('_OLDCAT_DB',	'galaxy_oldcat');
		define('_OLDCAT_USER',	'robot');
		define('_OLDCAT_PASS',	'robot');

		/**
		 *  @desc 公司
		 */
		define('_COMPANY_HOST',	'172.16.2.41');
		define('_COMPANY_DB',	'galaxy_company');
		define('_COMPANY_USER',	'robot');
		define('_COMPANY_PASS',	'robot');
		
		/**
		 *  @desc 訂單
		 */
		define('_ORDER_HOST',	'172.16.2.41'); 
		define("_ORDER_DB",		'galaxy_order_bak');
		define('_ORDER_USER',	'robot');
		define('_ORDER_PASS',	'robot');		
		
		/**
		 * 連線到版面資料庫儲存DB 設定 *Required*
		 */
		define('_SITE_HOST',	'172.16.1.41'); //'172.16.2.230:3306'
		define('_SITE_DB',		'genesis_puppets');
		define('_SITE_USER',	'robot');
		define('_SITE_PASS',	'robot');
		
		/**
		 * 連線到後台LOG資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_LOG_HOST',	'172.16.1.41');
		define('_GENESIS_LOG_DB',	'genesis_log');
		define('_GENESIS_LOG_USER',	'robot');
		define('_GENESIS_LOG_PASS',	'robot');
		
		/**
		 * puppets db *Required*
		 */
		define('_PUPPETS_HOST',	'172.16.1.41');
		define('_PUPPETS_DB',	'genesis_puppets');
		define('_PUPPETS_USER',	'robot');
		define('_PUPPETS_PASS',	'robot');
		
		/**
		 * 連線到身份LOG資料庫儲存DB *Required*
		 */
		define('_PUPPETS_LOG_HOST',	'172.16.1.41');
		define('_PUPPETS_LOG_DB',	'genesis_puppets');
		define('_PUPPETS_LOG_USER',	'robot');
		define('_PUPPETS_LOG_PASS',	'robot');
		
		/**
		 * docs db *Required*
		 */
		define('_DOCS_HOST',	'172.16.1.41');
		define('_DOCS_DB',		'genesis_docs');
		define('_DOCS_USER',	'robot');
		define('_DOCS_PASS',	'robot');

		/**
		 * queue db *Required*
		 */
		define('_QUEUE_HOST',	'172.16.1.97');
		define('_QUEUE_DB',		'queue_');
		define('_QUEUE_USER',	'robot');
		define('_QUEUE_PASS',	'robot');
		
		/**
		 * 連線到文案LOG資料庫儲存DB *Required*
		 */
		define('_DOCS_LOG_HOST',	'172.16.1.41');
		define('_DOCS_LOG_DB',		'genesis_docs');
		define('_DOCS_LOG_USER',	'robot');
		define('_DOCS_LOG_PASS',	'robot');
		
		/**
		 *  @desc 使用者
		 */
		define('_USER_HOST',	'172.16.1.41');
		define('_USER_DB',		'galaxy_user');
		define('_USER_USER',	'robot');
		define('_USER_PASS',	'robot');
		
		/**
		 *  @desc API
		 */
		define('API_SERVER',	'172.16.1.41');
		
		/**
		 * SMARTY 設定
		 */
		define('PATH_SMARTY_TPL', PATH_ROOT.'/tpl');
		
	break;

	case 'bill':
		/**
		* @desc SESSION
		*/
		define("_SESSION_HOST", 'localhost');
		define("_SESSION_DB",	'genesis');
		define("_SESSION_USER", 'robot');
		define("_SESSION_PASS", 'robot');
		
		/**
		 * 連線到後台資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_HOST',	'localhost');
		define('_GENESIS_DB',	'genesis');
		define('_GENESIS_USER',	'robot');
		define('_GENESIS_PASS',	'robot');

		/**
		 *  @desc 貓舍
		 */
		define('_OLDCAT_HOST',	'localhost');
		define('_OLDCAT_DB',	'galaxy_oldcat');
		define('_OLDCAT_USER',	'robot');
		define('_OLDCAT_PASS',	'robot');
		
		/**
		 *  @desc 公司
		 */
		define('_COMPANY_HOST',	'localhost');
		define('_COMPANY_DB',	'galaxy_company');
		define('_COMPANY_USER',	'robot');
		define('_COMPANY_PASS',	'robot');

		/**
		* @desc 訂單
		*/
		define('_ORDER_DB',		'galaxy_order_bak');
		define('_ORDER_HOST',	'localhost');
		define('_ORDER_USER',	'robot');
		define('_ORDER_PASS',	'robot');
		
		/**
		 * 連線到版面資料庫儲存DB 設定 *Required*
		 */
		define('_SITE_HOST',	'localhost'); //'172.16.2.230:3306'
		define('_SITE_DB',		'genesis_puppets');
		define('_SITE_USER',	'robot');
		define('_SITE_PASS',	'robot');
		
		/**
		 * 連線到後台LOG資料庫儲存DB 設定 *Required*
		 */
		define('_GENESIS_LOG_HOST',	'localhost');
		define('_GENESIS_LOG_DB',	'genesis_log');
		define('_GENESIS_LOG_USER',	'robot');
		define('_GENESIS_LOG_PASS',	'robot');
		
		/**
		 * puppets db *Required*
		 */
		define('_PUPPETS_HOST',	'localhost');
		define('_PUPPETS_DB',	'genesis_puppets');
		define('_PUPPETS_USER',	'robot');
		define('_PUPPETS_PASS',	'robot');
		
		/**
		 * 連線到身份LOG資料庫儲存DB *Required*
		 */
		define('_PUPPETS_LOG_HOST',	'localhost');
		define('_PUPPETS_LOG_DB',	'genesis_puppets');
		define('_PUPPETS_LOG_USER',	'robot');
		define('_PUPPETS_LOG_PASS',	'robot');
		
		/**
		 * docs db *Required*
		 */
		define('_DOCS_HOST',	'localhost');
		define('_DOCS_DB',		'genesis_docs');
		define('_DOCS_USER',	'robot');
		define('_DOCS_PASS',	'robot');
                                /**
		 * queue db *Required*
		 */
		define('_QUEUE_HOST',	'localhost');
		define('_QUEUE_DB',		'queue_');
		define('_QUEUE_USER',	'robot');
		define('_QUEUE_PASS',	'robot');
		
		/**
		 * 連線到文案LOG資料庫儲存DB *Required*
		 */
		define('_DOCS_LOG_HOST',	'localhost');
		define('_DOCS_LOG_DB',		'genesis_docs');
		define('_DOCS_LOG_USER',	'robot');
		define('_DOCS_LOG_PASS',	'robot');

		/**
		 *  @desc 使用者
		 */
		define('_USER_HOST',	'localhost');
		define('_USER_DB',		'galaxy_user');
		define('_USER_USER',	'robot');
		define('_USER_PASS',	'robot');
		
		/**
		 *  @desc API
		 */
		define('API_SERVER',	'localhost');
		
		/**
		 * SMARTY 設定
		 */
		define('PATH_SMARTY_TPL', PATH_ROOT.'/tpl');
		
	break;
	
	default:
		die("site config err in config.php");
	break;
}	

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