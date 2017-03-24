<?php

/**
 *  @desc 驗證資料
 *  @created 2014/06/01
 */

class validator {

	public function __construct() {
	}
	
	
	/**
	 *  @desc 檢查身分證字號
	 *  @created 2014/06/01
	 */
	public static function bCheckIdendity($id) {
		$err = FALSE;
		$IDE = array(1, 10, 19, 28, 37, 46, 55, 64, 39, 73, 82, 2, 11, 20, 48, 29, 38, 47, 56, 65, 74, 83, 21, 3, 12, 30);
		$id = strtoupper($id);
		if (strlen($id) <> 10) {
			//echo "<Script Language=\"JavaScript\">\n";
			//echo "alert('身分證號碼長度有誤！')\n";
			//echo "</Script>\n";
			//$err = TRUE;
			return false;
		}
		
		$NUM = $IDE[ord($id[0]) - 65];
		for ($I=1;$I<9;$I++)
			$NUM += $id[$I] * (9 - $I);
		$NUM += $id[9];
		if ($NUM % 10 <> 0) {
			//echo "<Script Language=\"JavaScript\">\n";
			//echo "alert('身分證號碼檢查有誤！')\n";
			//echo "</Script>\n";
			//$err = TRUE;
			return false;
		}
		
		if ($id[1] <> 1 && $id[1] <> 2) {
			//echo "<Script Language=\"JavaScript\">\n";
			//echo "alert('身分證號碼檢查有誤！')\n";
			//echo "</Script>\n";
			//$err = TRUE;
			return false;
		}

		if ($err) return false;
		return true;
	}

	/**
	 *  @desc 檢查電子郵件
	 *  @created 2014/06/01
	 */
	public static function bCheckEmail($email='') {
		if (strlen(trim($email))==0) {
			return false;
		} else if (!preg_match('/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/', $email)) {
			return false;
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		} else if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email)) {
			return false;
		}
		return true;
	}
	
	/**
	 *  @desc 檢查網址
	 *  @created 2014/06/01	 
	 */
	public static function bCheckUrl($url='') {
		if (strlen(trim($url))==0) {
			return false;
		} else if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		} else if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url)) {
			return false;
		}
		return true;
	}

	
	/**
	 *  @desc 驗證統一編號
	 *  @created 2014/05/22
	 */
	public static function bCheckTaxId($tax_id=0) {
		$tbNum = array(1,2,1,2,1,2,4,1);
		if(strlen($tax_id)!=8 || !eregi("^[0-9\*]{8}",$tax_id)) return false;
		$intSum = 0;
		for ($i = 0; $i < count($tbNum); $i++)
		{
		$intMultiply=substr($tax_id,$i,1)*$tbNum[$i];
		$intAddition=(floor($intMultiply / 10) + ($intMultiply % 10));
		$intSum+=$intAddition;
		}
		return ($intSum % 10 == 0 ) || ($intSum%10==9 && substr($tax_id,6,1)==7);
	}
	
	
	/**
	 *  @desc 檢查台灣手機
	 *  @created 2014/06/01
	 */
	public static function bCheckMobile($mobile='') {
		// 手機格式1 09XX-XXX-XXX
		$regexp1 = "/09[0-9]{2}-[0-9]{3}-[0-9]{3}/";

		//手機格式2 09XX-XXXXXX
		//$regexp2 = "09[0-9]{2}-[0-9]{6}";

		if (strlen(trim($mobile))==0) return false;
		
		// 套用手機格式1的比對結果正常
		if (!preg_match($regexp1, $mobile)) {
			return false;
		}
		return true;
	}
	
	
	/**
	 *  @desc 檢查台灣電話號碼格式
	 *  @created 2014/06/01
	 */
	public static function bCheckTelephoe($tel='') {
		$pattern = '/^\\(?(0|\\+886)[-. ]?[2-9][\\)-. ]?([0-9][\\)-. ]?){2}([0-9][-. ]?){3}[0-9]{2}[0-9]?$/';

		if (strlen(trim($tel))==0) {
			return false;
		} else if (!preg_match($pattern, $tel)) {
			return false;
		}
		return true;
	}
	
	
	/**
	 *  @desc 檢查是否都是字串，沒有數字
	 *  @created 2014/06/01
	 */
	public static function bCheckOnlyString($str='') {
		//if (mb_strlen($str,'UTF-8')<2) return false;
		if (preg_match("/[0-9]/",$str)) { // 有數字
			return false;
		}
		return true;
	}

	
	/**
	 *  @desc 檢查輸入民國是否正確? 不可超過今年或小於0
	 *  @created 2014/06/01	 
	 */
	public static function bCheckTWYear($year=0) {
		// 檢查民國是否輸入數字
		$iNowYear = date('Y')-1911;
		if (!is_numeric($year)) {
			return false;
		} else if (preg_match("/[^0-9]{1,3}/",$year)) {
			return false;
		} else if ($year > $iNowYear) {
			return false;
		} else if ($year < 0) {
			return false;
		}
		return true;
	}
	
	
	/**
	 *  @desc 檢查網址是否為 youtube 的分享網址
	 *  @created 2014/06/01
	 */
	public static function bCheckYouTubeUrl($url='') {
		if (strlen(trim($url))==0) {
			return false;
		} else if (!preg_match("/^http:\/\/youtu.be\/.*/",$url)) {
			return false;
		}
		return true;
	}
	
	
	/**
	 *  @desc 檢查網址是否為 Facebook 個人網址
	 *  @created 2014/07/30
	 */
	public static function bCheckFBPersonUrl($url='') {
		if (strlen(trim($url))==0) {
			return false;
		} else if (preg_match("/^https:\/\/www.facebook.com\/.*/",$url) OR preg_match("/^https:\/\/m.facebook.com\/.*/",$url)) {
			return true;
		}
		return false;
	}
	
	/**
	 *  @desc 檢查檔案是否為圖檔
	 *  @usage array $_FILES['upload_file']: upload_file 為欄位名稱 
	 *  @created 2015/10/05
	 */
	public static function bCheckUploadImageFile($aFile = array()){
		array( 
			0=>"There is no error, the file uploaded with success", 
			1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini", 
			2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
			3=>"The uploaded file was only partially uploaded", 
			4=>"No file was uploaded", 
			6=>"Missing a temporary folder" 
		); 
		if ($aFile['error'] > 0){
			//throw new Exception("An error ocurred when uploading.");
			// An error ocurred when uploading.
			return false;
		}
		
		// Allow certain file formats
		if(!preg_match("/jpg|jpeg|gif|png/i",$aFile['name'])){
			//throw new Exception("An error file formats when uploading.");
			return false;
		}
		
		// Check if image file eis as actual image or fake image
		$check = getimagesize($aFile['tmp_name']);
		if ($check ===  false){
			//throw new Exception("An error fake image when uploading.");
			return false;
		}
		
		//if ($check[0]>=4000 OR $check[1]>=4000){
		//	//throw new Exception("照片請勿超過4000x4000像素.");
		//	return false;
		//}
		
		// Check file size
		//if ($aFile['size'] > 500000){
		//	//echo "Sorry, your file is too large.";
		//	return false;
		//}
		return true;
	}


}
?>