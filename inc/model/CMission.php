<?php
include_once('../inc/model/CGalaxyClass.php');

class CMission extends CGalaxyClass
{

	static public function GetQueueFilesSavePath($quuid, $exectime="") {
		if($exectime){
			$queue_exectime_second = strtotime($exectime);	/* 先把queue的建立時間轉成時間戳 */
			$year = date("Y", $queue_exectime_second);	/* 取得「年」 */
			$month = date("m", $queue_exectime_second);	/* 取得「月」 */
			$day = date("d", $queue_exectime_second);		/* 取得「日」 */
		}else{


			$year = date("Y");	/* 取得「年」 */
			$month = date("m");	/* 取得「月」 */
			$day = date("d");		/* 取得「日」 */
		}
		
		/* 第一層，選擇伺服器(data底下的1, 2, 3) */
		$queue_exectime_day = $year . "-" . $month . "-" . $day;	/* 日期格式「年-月-日」 */
		$queue_exectime_day_second = strtotime($queue_exectime_day);	/* 用queue的建立日期轉成時間戳 */
		$to_now_day = floor($queue_exectime_day_second / 86400);	/* 用queue的建立日期算出現在與1970-01-01差幾天 */
		$to_now_day_save_as = $to_now_day % 3 + 1;	/* 依照天數算出要存在哪台伺服器「1~3」 */
		
		/* 第二層，選擇日期(伺服器底下的日期，格式「年月日」) */
		$year_month_day = $year . $month . $day;	/* 轉成資料夾「年月日」 */
		
		/* 第三層，quuid開頭第一個字元 */
		$quuid_first_prefix = substr($quuid, 0, 1);
		
		/* 第四層，quuid開頭第二個字元 */
		$quuid_second_prefix = substr($quuid, 1, 1);
		
		$files_save_path = $to_now_day_save_as . "/" . $year_month_day . "/" . $quuid_first_prefix . "/" . $quuid_second_prefix . "/" . $quuid . "/";
		
		return $files_save_path;
	}
	
	static public function uploadMissionFile($file_path) {
	
		$file_path = explode("/", $file_path);
		
		$move_result = '';
		
		if(count($file_path)){
			$file_dir = "../data/";
			foreach($file_path AS $key => $val){
				if($key == (count($file_path) - 1)){
					$file_dir .= $_FILES["up_file"]["name"];
					$move_result = move_uploaded_file($_FILES["up_file"]["tmp_name"], $file_dir);
				}else{
					$file_dir .= $val . "/";
					if( !is_dir( $file_dir ) ) mkdir( $file_dir , 0777);
				}
			}
		}
		return $move_result;
	}	
}

?>