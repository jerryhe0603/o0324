<?php

class CDate 
{

	public function __construct()
	{
	
	}
	
	
	/**
	* @desc 計算兩個日期差幾天
	* @created 2012/07/23
	*/
	public static function dateDiff($startTime, $endTime) {
		$start = strtotime($startTime);
		$end = strtotime($endTime);
		$timeDiff = $end - $start;
		return floor($timeDiff / (60 * 60 * 24));
	}

	





}
?>
