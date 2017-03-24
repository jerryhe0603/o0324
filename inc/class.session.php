<?php

/**
	CREATE TABLE IF NOT EXISTS `sessions2` (
	  `sesskey` varchar(64) NOT NULL DEFAULT '',
	  `expiry` datetime NOT NULL,
	  `expireref` varchar(250) DEFAULT '',
	  `created` datetime NOT NULL,
	  `modified` datetime NOT NULL,
	  `sessdata` longtext,
	  PRIMARY KEY (`sesskey`),
	  KEY `sess2_expiry` (`expiry`),
	  KEY `sess2_expireref` (`expireref`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/

define("_SESSION_DOMAIN", "");

class session {

	var $_link = null;
	var $_session_lifetime 	= 3600;
	var $_gc_probability 	= 1;
	var $_gc_divisor 		= 100;
	var $_SESSION_HOST 		= _SESSION_HOST;
	var $_SESSION_DB 		= _SESSION_DB;
	var $_SESSION_USER 		= _SESSION_USER;
	var $_SESSION_PASS 		= _SESSION_PASS;
	

    function __construct($set_sid="") {
    	$this->_link = @mysqli_connect($this->_SESSION_HOST, $this->_SESSION_USER, $this->_SESSION_PASS, $this->_SESSION_DB) or die("Can't connect to " . $this->_SESSION_HOS . " MySQL Error: " . mysqli_error($this->_link));


    	$db_selected = mysqli_select_db($this->_link, $this->_SESSION_DB);


		if (!$db_selected) {
			//echo "Unable to select session database "._SESSION_DB;
			//exit;
			die('Can\'t use '.$this->_SESSION_DB.' : '.mysqli_error($this->_link));
		}

		mysqli_query($this->_link, "SET character_set_client = utf8");
		mysqli_query($this->_link, "SET character_set_results = utf8");
		mysqli_query($this->_link, "SET character_set_connection = utf8");
		
		ini_set('session.cookie_lifetime', 0);
		
		if ($this->_gc_probability) {
			ini_set('session.gc_probability', $this->_gc_probability);
		}

		if ($this->_gc_divisor) {
			ini_set('session.gc_divisor', $this->_gc_divisor);			
		}
		
		if ($this->_session_lifetime) {
			ini_set('session.gc_maxlifetime', $this->_session_lifetime);
		}

		session_set_save_handler(
			array(&$this, '_open'), array(&$this, '_close'),  array(&$this, '_read'), 
			array(&$this, '_write'), array(&$this, '_destroy'), array(&$this, '_gc')
		);
		
		if ($set_sid) {
			session_id($set_sid);
			@session_start();
			
			if(!isset($_COOKIE_LIFETIME)) $_COOKIE_LIFETIME=3600; // 一小時 default: 3600
            setcookie("PHPSESSID", $set_sid, time()+$_COOKIE_LIFETIME,"/","."._SESSION_DOMAIN); 

		} else if (isset($_COOKIE['PHPSESSID']) and $_COOKIE['PHPSESSID'] != NULL) {
			session_id($_COOKIE['PHPSESSID']);
			@session_start();
        		} else {
			@session_start();
			$set_sid = session_id();  
			
			if(!isset($_COOKIE_LIFETIME)) $_COOKIE_LIFETIME=3600; // 一小時 default: 3600
            			setcookie("PHPSESSID", $set_sid, time()+$_COOKIE_LIFETIME,"/","."._SESSION_DOMAIN); 
        		}
		// start the session
		// session_start();
    }
	
    /**
     *  close
     */
    function _close() {
		mysqli_close($this->_link);
        return true;
    }

    /**
     *  destroy
     */
    function _destroy($session_id) {
		$sql = "DELETE FROM sessions WHERE sesskey = '".$session_id."'";
		mysqli_query($this->_link, $sql);
		return true;
    }

    /**
     *  gc
     */
    function _gc() {
		$sql = "DELETE FROM sessions WHERE expiry < '" . date("Y-m-d H:i:s", time()) . "'";
		mysqli_query($this->_link, $sql);
    }

    /**
     *  open
     */
    function _open() {
		return true;
    }

    /**
     * read
     */
    function _read($session_id) {
		$sql = "SELECT sessdata FROM sessions 
					WHERE sesskey 	= '" . $session_id . "' 
					AND expiry 		> '" . date("Y-m-d H:i:s", time() ) . "' LIMIT 1";
		$iRs = mysqli_query($this->_link, $sql);
        $fields = @mysqli_fetch_assoc($iRs);
		return $fields['sessdata'];
    }

    /**
     *  write
     */
    function _write($session_id, $session_data) {
    	try{
	    	$this->_gc();
	        $sql = "INSERT INTO sessions 
						SET sesskey	= '" . $session_id . "', 
							sessdata	= '" . $session_data . "',
							expiry		= '" . date("Y-m-d H:i:s", time() + $this->_session_lifetime ) . "',
							created		= '" . date("Y-m-d H:i:s", time() ) . "',
							modified	= '" . date("Y-m-d H:i:s", time() ) . "'
						ON DUPLICATE KEY UPDATE
							sessdata	= '" . $session_data . "',
							modified	= '" . date("Y-m-d H:i:s", time() ) . "',
							expiry		= '" . date("Y-m-d H:i:s", time() + $this->_session_lifetime ) . "'";
			$iRs = mysqli_query($this->_link, $sql);
	        return $iRs;	
    	}catch (Exception $e){
			echo ('Caught exception: '.$e->getMessage());
		}
    }

	function get_settings() {
		// get the settings
        $gc_maxlifetime = ini_get('session.gc_maxlifetime');
        $gc_probability = ini_get('session.gc_probability');
        $gc_divisor     = ini_get('session.gc_divisor');

        // return them as an array
        return array(
            'session.gc_maxlifetime'    =>  $gc_maxlifetime . ' seconds (' . round($gc_maxlifetime / 60) . ' minutes)',
            'session.gc_probability'    =>  $gc_probability,
            'session.gc_divisor'        =>  $gc_divisor,
            'probability'               =>  $gc_probability / $gc_divisor * 100 . '%',
        );
    }
	
	function regenerate_id() {
        $old_session_id = session_id();
        session_regenerate_id();
        $this->_destroy($old_session_id);
    }

    function destroy() {
    	@session_start();
        session_destroy();
    }
	
	function set($key, $val) {
		$_SESSION[$key] = $val;
	}
	
	function sess_unset() {
		session_unset();
	}
	
	function get($key) {
		return isset($_SESSION[$key])?$_SESSION[$key]:null;
	}
	
	
}

?>
