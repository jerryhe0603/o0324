<?php
/**
 * CJavascrip 類別
 *
 * @package dcmc
 */
class CJavaScript {

	/**
	 * 建構子
	 */
	function __construct() {
	}

	/**
	 * javascrip
	 *
	 * @param string $msg 專案序號
	 */
	static public function vJsAlertHistory($msg, $go = -1) {
		header('Content-Type: text/html; charset=utf-8');
		echo "<script>";
		echo "alert('".CMisc::my_quotes($msg)."');";
		echo "window.history.go($go);";
		echo "</script>";
		exit;
	}

	static public function vJsHistory($go = -1) {
		header('Content-Type: text/html; charset=utf-8');

		echo "<script>";
		echo "window.history.go($go);";
		echo "</script>";
		exit;
	}

	static public function vAlertRedirect($msg , $url) {
		header('Content-Type: text/html; charset=utf-8');

		echo "<script>";
		if($msg) echo "alert('".CMisc::my_quotes($msg)."');";
		//echo "window.location.href='./index.php?mode=logout'";
		echo "window.location.href='$url'";
		echo "</script>";
		exit;
	}

	static public function vAlert($msg) {
		//header('Content-Type: text/html; charset=utf-8');
		echo "<script>";
		echo "alert('".CMisc::my_quotes($msg)."');";
		echo "</script>";
	}

	static public function vRedirect($url) {
		header('Content-Type: text/html; charset=utf-8');
		echo "<script>";
		echo "window.location.href='$url'";
		echo "</script>";
		exit;
	}

	/**
	* @param 設定 $go 第幾頁 負數為前幾頁
        	* @desc  回第幾頁
       	*/
	static public function vBack($go=-1) {
		echo "<script language=javascript>";
		echo "Javascript:history.go($go)";
		echo "</script>";
		exit;
	}
}
?>