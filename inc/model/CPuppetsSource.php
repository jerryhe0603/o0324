<?php
include_once('../inc/model/CGalaxyClass.php');
class CPuppetsSource extends CGalaxyClass
{
	static protected $sDBName = 'PUPPETS';
	function __construct() {
		
		
	}
	
	function __destruct() {
		
	}

	static public function getLastName() {
		$oDB = self::oDB(self::$sDBName);
		$sql = "SELECT MAX(`fn_id`) AS `fn_id`	
					FROM `first_name`";
					
		$iRes = $oDB->iQuery($sql);	
		$fe = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['fn_id']);
				  
		$sql  = "SELECT `fn_name` FROM `first_name` WHERE `fn_id` = $num";
		$iRes = $oDB->iQuery($sql);

		if($oDB->iNumFields($iRes) > 0)
			$fe = $oDB->aFetchAssoc($iRes);
		else 
			throw new Exception("get last name fail");	


		return $fe['fn_name'];
	}	

	static public function getFirstName($gender) {
		$oDB = self::oDB(self::$sDBName);
		$append = $gender?"_boy":"_girl";

		$sql = "SELECT MAX(`sn_name_no`) AS `sn_name_no`	
					FROM `second_name{$append}`";
					
		$iRes = $oDB->iQuery($sql);	
		$fe = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['sn_name_no']);
				  
		$sql = "SELECT `text` 
					FROM `second_name{$append}` 
					WHERE `sn_name_no` = $num";

		$iRes = $oDB->iQuery($sql);

		if($oDB->iNumFields($iRes) > 0) 
			$fe = $oDB->aFetchAssoc($iRes);
		else 
			throw new Exception("get last name fail");	

		
		return $second_name = $fe['text'];
	}

	static public function getRoad($ZIP) {
		$oDB = self::oDB(self::$sDBName);
		$sql = "SELECT * 
					FROM `road`
					WHERE `zip` = '".$ZIP."'";

		$iRes = $oDB->iQuery($sql);
		while($fe = $oDB->aFetchAssoc($iRes)) {
			$aRow[] = $fe;
		}

		if($aRow) {
			$number = mt_rand(1,200);
					
			$address = $aRow[mt_rand(0,count($aRow)-1)]['name'].$number."號";

			return $address;
		}
	} 

	static public function getNickName($count, $gender) {

		$nickName = "";

		switch($count) {
			case 2:
				$nickName = self::getTwoWordsNickName($gender);
				break;

			case 3:
				$nickName = self::getThreeWordsNickName($gender);
				break;	

			case 4:
				$nickName = self::getFourWordsNickName($gender);
				break;	

			case 5:
				$nickName = self::getFiveWordsNickName($gender);
				break;	

			default:	
				throw new Exception("miss args: $conut");	

			break;
		}

		return $nickName;
	}

	static public function getChWord() {
		$oDB = self::oDB(self::$sDBName);
		$sql = "SELECT MAX(`ch_word_no`) AS `ch_word_no`	
					FROM `ch_word`";
		
		$iRes = $oDB->iQuery($sql);	
		$fe   = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['ch_word_no']);
	
		$sql = "SELECT `text` 
					FROM `ch_word` 
					WHERE `ch_word_no` = $num";
		
		$iRes = $oDB->iQuery($sql);
		
		$fe = $oDB->aFetchAssoc($iRes);
					
		return $fe['text'];
	}

	static public function getTwoWordsNickName($gender) {
		//1+1
		//2+1
		//1+3
		$type = rand(0, 2);
		$word = "";

		if($type == 0) 
			$word = self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 1, $gender);
		 else if($type == 1) 
			$word = self::getNickNameWord(1, 2, $gender).self::getNickNameWord(1, 1, $gender);
		 else 
			$word = self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 3, $gender);
		
		return $word;
	}

	static public function getThreeWordsNickName($gender) {

		$type = rand(0, 5);
		$word = "";

		switch($type) {
			case 0:
				$word = self::getNickNameWord(1, 2, $gender).self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 3, $gender);	
				break;

			case 1:
				$word = self::getNickNameWord(1, 2, $gender).self::getNickNameWord(2, 3, $gender);
				break;

			case 2:
				$word =self::getNickNameWord(1, 2, $gender).self::getNickNameWord(2, 1, $gender);
				break;

			case 3:
				$word = self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 3, $gender);	
				break;

			case 4:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(1, 3, $gender);	
				break;

			case 5:
				$word = self::getNickNameWord(3, 1, $gender);	
				break;

			default:
				trigger_error("miss args: $type", E_USER_ERROR);	
				break;
		}

		return $word;
	}

	static public function getFourWordsNickName($gender) {
		$type = rand(0, 5);
		$word = "";

		switch($type) {
			case 0:
				$word = self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 1, $gender).self::getNickNameWord(2, 3, $gender);	
				break;

			case 1:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(1, 1, $gender).self::getNickNameWord(1, 3, $gender);
				break;

			case 2:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(2, 3, $gender);
				break;

			case 3:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(2, 1, $gender);
				break;

			case 4:
				$word = self::getNickNameWord(2, 1, $gender).self::getNickNameWord(2, 3, $gender);	
				break;

			case 5:
				$word = self::getNickNameWord(2, 1, $gender).self::getNickNameWord(2, 1, $gender);
				break;

			default:
				throw new Exception("miss args: $type");	
				break;
		}

		return $word;
	}

	static public function getFiveWordsNickName($gender) {

		$type = rand(0, 8);
		$word = "";

		switch($type) {
			case 0:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(3, 1, $gender);	
				break;

			case 1:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(1, 4, null).self::getNickNameWord(2, 3, $gender);
				break;

			case 2:
				$word = self::getNickNameWord(2, 2, $gender).self::getNickNameWord(1, 4, null).self::getNickNameWord(2, 1, $gender);
				break;

			case 3:
				$word = self::getNickNameWord(2, 1, $gender).self::getNickNameWord(3, 1, $gender);
				break;

			case 4:
				$word = self::getNickNameWord(2, 1, $gender).self::getNickNameWord(3, 1, $gender);	
				break;

			case 5:
				$word = self::getNickNameWord(2, 1, $gender).self::getNickNameWord(1, 4, null).self::getNickNameWord(2, 3, $gender);
				break;
			
			case 6:
				$word = self::getNickNameWord(2, 1, $gender).self::getNickNameWord(1, 4, null).self::getNickNameWord(2, 1, $gender);
				break;	

			case 7:
				$word = self::getNickNameWord(3, 1, $gender).self::getNickNameWord(2, 3, $gender);
				break;

			case 8:
				$word = self::getNickNameWord(3, 1, $gender).self::getNickNameWord(2, 1, $gender);
				break;			

			default:
				throw new Exception("miss args: $type");	

				break;
		}
		
		return $word;
	}

	static public function getNickNameWord($count, $type, $gender) {
		$oDB = self::oDB(self::$sDBName);
		$gender = ($type == 4)?"":"_".$gender;

		$sql = "SELECT MAX(`nick_name_no`) AS `nick_name_no`	
					FROM `nick_name_{$count}_{$type}{$gender}`";

		$iRes = $oDB->iQuery($sql);	
		$fe   = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['nick_name_no']);
	
		$sql = "SELECT `text` 
					FROM `nick_name_{$count}_{$type}{$gender}` 
					WHERE `nick_name_no` = $num";
		
		$iRes = $oDB->iQuery($sql);
		
		$fe = $oDB->aFetchAssoc($iRes);
					
		return $fe['text'];
	}

	static public function getEnglishName($gender) {
		$oDB = self::oDB(self::$sDBName);
		$append = $gender?"_boy":"_girl";

		$sql = "SELECT MAX(`english_name_no`) AS `english_name_no`	
					FROM `english_name{$append}`";
					
		$iRes = $oDB->iQuery($sql);	
		$fe = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['english_name_no']);
				  
		$sql = "SELECT `text` 
					FROM `english_name{$append}` 
					WHERE `english_name_no` = $num";

		$iRes = $oDB->iQuery($sql);

		if($oDB->iNumFields($iRes) > 0) 
			$fe = $oDB->aFetchAssoc($iRes);
		else 
			throw new Exception("get english fail");	
		
		return $second_name = $fe['text'];
	}

	static public function getWord() {
		$oDB = self::oDB(self::$sDBName);
		$sql = "SELECT MAX(`word_no`) AS `word_no`	
					FROM `word`";
					
		$iRes = $oDB->iQuery($sql);	
		$fe = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['word_no']);
				  
		$sql = "SELECT `word_text` 
					FROM `word` 
					WHERE `word_no` = $num";

		$iRes = $oDB->iQuery($sql);

		if($oDB->iNumFields($iRes) > 0) 
			$fe = $oDB->aFetchAssoc($iRes);
		else 
			throw new Exception("get english fail");	

		
		return $fe['word_text'];
	}

	static public function getQuestion() {
		$oDB = self::oDB(self::$sDBName);
		$sql = "SELECT MAX(`question_no`) AS `question_no`	
					FROM `question`";
					
		$iRes = $oDB->iQuery($sql);	
		$fe = $oDB->aFetchAssoc($iRes);	

		$num = mt_rand(1, $fe['question_no']);
				  
		$sql = "SELECT `text` 
					FROM `question` 
					WHERE `question_no` = $num";

		$iRes = $oDB->iQuery($sql);

		if($oDB->iNumFields($iRes) > 0) 
			$fe = $oDB->aFetchAssoc($iRes);
		else 
			throw new Exception("get question fail");	

		
		return $fe['text'];

	}
}
?>