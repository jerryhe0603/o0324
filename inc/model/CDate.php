<?php

class CDate 
{

	public function __construct()
	{
	
	}
	
	
	/**
	* @desc �p���Ӥ���t�X��
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
