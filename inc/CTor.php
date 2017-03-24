<?php
//jerome 
//example
/*
include_once("inc/CTor.php");
$CTor = new CTor("114.32.33.135",9051,"cj/6vup4ru,6");

if($CTor->bConnect()){
	
	if($CTor->bAuthenticate()){
		
				
		
		if(!$CTor->bSetSocksPort(9000,"iwantIn520GUHinet01"))
			echo $CTor->sGetErrorMsg();
		
		$addrList = $CTor->aGetSocksPort();
		
		print_r($addrList);
		
		echo $CTor->launch($port=9000,"www.myip.com.tw","",30);
		
		
		
		if($routerinfo = $CTor->aGetRouterInfo()){
		
			for($i=0;$i<count($routerinfo);$i++){
				if($routerdesc = $CTor->aGetRouterDesc($routerinfo[$i]['name']))
					$routerinfo[$i]['desc']=$routerdesc;
				else
					$routerinfo[$i]['desc'] =  $CTor->sGetErrorMsg();
			}
			print_r($routerinfo);
		}else
			echo $CTor->sGetErrorMsg();
			
		
	}else
		echo $CTor->sGetErrorMsg();

	$CTor->close();
}
*/




class CTor 
{
	
	private $ip; 
	private $controlPort;
	private $controlPassword;
	
	
	/**
     *
     * SetUp the tor configuration
     *
     * @param string $ip tor ip
     * @param number $port  tor Port
	 * @param string $password tor password
     */
	public function __construct($ip="",$port=0,$password=""){
		
		if(!$ip || !$port)
			return;
		
		$this->ip = $ip;
		$this->controlPort = $port;
		$this->controlPassword = $password;
			
		if($this->socket)
			fclose($this->socket);
			
		$this->socket = @fsockopen($this->ip, $this->controlPort, $errno, $errstr, 3);	
	}
	
	/**
     *
     * close connect the tor
     *
	 */
	public function __destruct()
	{
		
		$this->close();
	}
	
	
	/**
     *
     * connect the tor
     *
     * @return bool
     *          true is success connect
     *          false is fail 
     */
	public function bConnect($ip="",$port=0,$password=""){
		
		if($ip && $port){
			if($this->socket)
				$this->close();
			$this->ip = $ip;
			$this->controlPort = $port;
			$this->controlPassword = $password;
			$this->socket = @fsockopen($this->ip, $this->controlPort, $errno, $errstr, 3);
		}
				
		
		if($this->socket)
			return true;
		
		$this->errormsg = "no connect";
		return false;
	}
	
	public function close(){
		if(is_resource($this->socket)){
			$this->iSendCmd("quit",$response); //quit tor connect
			fclose($this->socket);
			$this->socket = null;
		}	
	
	}
	/**
     *
     * send command tor tor 
     *
     * @param string $cmd  control tor command
     * @param number $response  return response call by address
	 * @return int code
     *          250 is success connect
     *          other false is fail 
     */
	private function iSendCmd($cmd="",&$response){
		if(!$this->bConnect())
			return 0;
		if(strlen(trim($cmd)) == 0)
			return 0;

		fputs($this->socket, $cmd."\r\n");
		$line = fgets($this->socket, 128);
		
		
		list($code, $text) = explode('+', $line, 2);

		if ($code != '250') {
			
			list($code, $text) = explode('-', $line, 2);
			
			if ($code != '250') {
				
				list($code, $text) = explode(' ', $line, 2);
				if(strpos($line,"=",0))
					$response=substr($text, strpos($text,"=",0)+1);
				
				
				//$response = $text;
				return $code; 
				
			}else{
				$response="";
				if(strpos($line,"=",0))
					$response.=substr($line, strpos($line,"=",0)+1);
				
				while (($line = fgets($this->socket, 128)) !== false) {

					if (strpos($line,"250-",0) === 0){
						if(strpos($line,"=",0))
							$response.=substr($line, strpos($line,"=",0)+1);
					}	
					if (strpos($line,"250 ",0) === 0){
						if(strpos($line,"=",0))
							$response.=substr($line, strpos($line,"=",0)+1);
						break;
					}	
					
					
				}
			
			}
			
		}
		else
		{
		
			$response="";
			while (($line = fgets($this->socket, 128)) !== false) {

				if ($line == "250 OK\r\n") {
					break;
				}	
				if ($line != ".\r\n")
				$response.=$line;
			}
		}	
		return 250;
	}
	/**
     *
     * send command tor tor , get tor configure
     *
     * @param string $key  tor configure 
	 * @return string response
     */
	private function sGetConf($key=""){
		if(!$key) return "";
		
		if($this->iSendCmd("getconf $key",$response) != 250){
			$this->errormsg = $response;
			
			return "";
		}
		
		return $response;
	
	}
	
	/**
     *
     * send command tor tor , set tor configure
     *
     * @param string $key  tor configure 
	 * @return string response
     */
	private function bSetConf($cmd=""){
		if(!$cmd) return false;
		if($this->iSendCmd("setconf $cmd",$response) != 250){
			$this->errormsg = $response;
			return false;
		}
		
		return true;
	}
	
	
	
	/**
     *
     * base16 encode
     *
	 * @return string 
     */
	private function base16_encode($str) {
		$byteArray = str_split($str);
		foreach ($byteArray as &$byte) {
			$byte = sprintf('%02x', ord($byte));
		}
		return join($byteArray);
	}
	private function format_replace($str) {
		$keys = array("\r\n");
		$key_replace = array("");
		
		return str_replace($keys,$key_replace,$str);

	}
	private function format_bytes($bytes) {
		if ($bytes < 1024) return $bytes.' B';
		elseif ($bytes < 1048576) return round($bytes / 1024, 2).' KB';
		elseif ($bytes < 1073741824) return round($bytes / 1048576, 2).' MB';
		elseif ($bytes < 1099511627776) return round($bytes / 1073741824, 2).' GB';
		else return round($bytes / 1099511627776, 2).' TB';
	}
	
	/**
     *
     * Parse Router Status 
     *
     * @param string list array $routerStatusLines  
	 * @return string $aRouterStatus  Router Status array
     */
	private function aParseRouterStatus($routerStatusLines=array()){

		if(!$routerStatusLines) return array();
		$aRouterStatus=array();
		foreach ($routerStatusLines as $line) {
			
			if (strpos($line,"r ",0) === 0) {
				
				$parts = preg_split("/ /", $line);

				if (count($parts) < 10)
					return array();

				/* Nickname */
				$aRouterStatus['name'] = $parts[1];
				/* Identity key digest */
				$aRouterStatus['id'] = $this->base16_encode(base64_decode($parts[2]));
				if (!$aRouterStatus['id'])
					return array();
				/* Most recent descriptor digest */
				$aRouterStatus['digest'] = $this->base16_encode(base64_decode($parts[3]));
				if (!$aRouterStatus['digest'])
					return array();
				/* Most recent publication date */
				$aRouterStatus['published'] = $parts[4] . " " . $parts[5];
				
				if (!strlen(trim($aRouterStatus['published'])))
					return array();
				/* IP address */
				$aRouterStatus['ip'] = $parts[6];
				if (!strlen(trim($aRouterStatus['ip'])))
					return array();
					
				/* IP address 2 */
				$aRouterStatus['ip2'] = $parts[7];
				if (!strlen(trim($aRouterStatus['ip2'])))
					return array();

				/* ORPort */
				$aRouterStatus['orport'] = $parts[8];

				/* DirPort */

				$aRouterStatus['dirport'] = $parts[9];

			}
			
		}
		
		return $aRouterStatus;
	}
	/**
     *
     * Parse Router Descriptor 
     *
     * @param string list array $descriptor  
	 * @return string $aRouterDescriptor  Router Descriptor array
     */
	private function aParseRouterDescriptor($descriptor=array()){
	
		if(!$descriptor) return array();
		$aRouterDescriptor=array();
		foreach ($descriptor as $line) {
			if (strpos($line,"router ",0) === 0) {
				$parts = preg_split("/ /", substr($line, strlen("router ")));
				$aRouterDescriptor['name'] = $parts[0];
				$aRouterDescriptor['ip'] = $parts[1];
				$aRouterDescriptor['ip2'] = $parts[2];
				$aRouterDescriptor['orport'] = $parts[3];
				$aRouterDescriptor['dirport'] = $parts[4];
			} else if (strpos($line,"platform ",0) === 0) {
				$aRouterDescriptor['platform'] = substr($line, strlen("platform "));
			} else if (strpos($line,"published ",0) === 0) {
				$aRouterDescriptor['published'] = substr($line, strlen("published "));
			} else if (strpos($line,"opt fingerprint ",0) === 0) {
				$aRouterDescriptor['fingerprint'] = substr($line, strlen("opt fingerprint "));
				$aRouterDescriptor['id'] = preg_replace('/\s+/', '', $aRouterDescriptor['fingerprint']);
			} else if (strpos($line,"fingerprint ",0) === 0) {
				$aRouterDescriptor['fingerprint'] = substr($line, strlen("fingerprint "));
				$aRouterDescriptor['id'] = preg_replace('/\s+/', '', $aRouterDescriptor['fingerprint']);
			} else if (strpos($line,"uptime ",0) === 0) {
				$aRouterDescriptor['uptime'] = substr($line, strlen("uptime "));
			} else if (strpos($line,"bandwidth ",0) === 0) {
				$parts = preg_split("/ /", substr($line, strlen("bandwidth ")));
				$aRouterDescriptor['avgbandwidth'] = $this->format_bytes($parts[0]);
				$aRouterDescriptor['burstbandwidth'] = $this->format_bytes($parts[1]);
				$aRouterDescriptor['observedbandwidth'] = $this->format_bytes($parts[2]);
			} else if (strpos($line,"contact ",0) === 0) {
				
				$aRouterDescriptor['contact'] = substr($line, strlen("contact "));
			} else if (strpos($line,"opt hibernating ",0) === 0) {
				$aRouterDescriptor['hibernating'] = preg_replace('/\s+/', '', substr($line, strlen("opt hibernating ")));
			}
		}
		return $aRouterDescriptor;
	}
	/**
     *
     * error message
     *
	 * @return string 
     */
	public function sGetErrorMsg(){
		return $this->errormsg;
	}	
	/**
     *
     * reboot the tor
     *
	 */
	
	public function reboot(){
	
		$this->iSendCmd("resetip wan reboot",$response);
		
	}
	
	
	/**
     *
     * Authenticate the tor
     * @return bool
     *          true is success 
     *          false is fail and quit connect
	 */
	public function bAuthenticate(){
		
		
		
		if($this->iSendCmd("AUTHENTICATE \"".$this->controlPassword."\"",$response) == 250)
			return true;
			
		$this->errormsg = $response;
		if($this->socket){
			fclose($this->socket);
			$this->socket = null;
		}	
		return false;
		
	
	
	}
	
	/**
     *
     * get Router information 
     *
     * @param string $name router name ,null get all 
	 * @return string $networkStatus  Router status array
     */
	public function aGetRouterInfo($name = ""){
		
		$cmd = "getinfo ns/all";
		
		
		if($this->iSendCmd($cmd,$response) != 250){
			$this->errormsg = $response;
			return array();
		}	
		
		$networkStatusLines = explode("\r\n",$response);
		$i=0;
		$len = count($networkStatusLines);
		$networkStatus = array();
		while ($i < $len) {
			/* Extract the "r", "s", and whatever other status lines */
			$routerStatusLines = array();
			do {
			  $routerStatusLines[] = $networkStatusLines[$i];
			  
			  $i++;
			 
			} while ($i < $len && (strpos($networkStatusLines[$i],"r ",0) === false || strpos($networkStatusLines[$i],"r ",0)>0));

			/* Create a new RouterStatus object and add it to the network status, if
			 * it's valid. */
			
			$routerStatus = $this->aParseRouterStatus($routerStatusLines);
			
			if ($routerStatus){
				$networkStatus[] = $routerStatus;
				if($name == $routerStatus['name']) return $routerStatus;
				
				
			}
			  
		}
		
		return $networkStatus;
		
	}
	
	/**
     *
     * get Router Descriptor 
     *
     * @param string $name router name  
	 * @return string $networkStatus  Router Descriptor array
     */
	public function aGetRouterDesc($name = ""){
		if($name=="") return array();
		else $cmd = "getinfo desc/name/$name";
		
		if($this->iSendCmd($cmd,$response) != 250){
			$this->errormsg = $response;
			return array();
		}	
		
		$descriptor = explode("\r\n",$response);
		
		
		return $this->aParseRouterDescriptor($descriptor);
		
	}
	
	
	
	
	/**
     *
     * get Router ip 
     *
     * @param string $interface router eth interface   
	 * @return string   
	 *          true is Router ip 
	 *          false is x
     */
	public function sGetIP($interface="wan"){
		if(!$interface) return "x";
		
		if($interface == "wan" || $interface == "wan2"){ 
			
		
			if($this->iSendCmd("resetip $interface get",$response) != 250){
				$this->errormsg = $response;
				return "x";
			}
			
			$addrList = explode("\r\n",$response);
			foreach ($addrList as $line) {
				
				if(strlen(trim($line))==0)
					continue;
				
				return trim($line);
			}
		}
		
		return "x";
	}
	
	
	
	/**
     *
     * set Router ip  and return  ip
     *
     * @param string $interface router eth interface   
	 * @return string   
	 *          true is Router ip 
	 *          false is x
     */
	public function sSetIP($interface="wan"){
		if(!$interface) return "x";
		if($interface == "wan" || $interface == "wan2"){ 
			if($this->iSendCmd("resetip $interface set",$response) != 250){
				$this->errormsg = $response;
				return "x";
			}
			$c=0;
			$ip = $this->sGetIP($interface);
			while($ip == "x"){
				sleep(5);
				$ip = $this->sGetIP($interface);
				if($c>3) break;
			}
			return $ip;
		}
		
		return "x";
	}
	
	/**
     *
     * 重新建立防火牆
     *
     * @param string $interface router eth interface   
	 * @return string   
	 *          true is Router ip 
	 *          false is x
     */
	public function sSetGW($interface="wan"){
		if(!$interface) return false;
		if($interface == "wan" || $interface == "wan2"){ 
			if($this->iSendCmd("resetip $interface gw",$response) != 250){
				$this->errormsg = $response;
				return false;
			}
		}
		
		return true;
	}
	
	
	
	/**
     *
     * get Tor opens a socks proxy on port
     *
	 * @return array   
	 *          true is all proxy ports
	 *          false is array()
     */
	public function aGetSocksPort(){

		if($this->iSendCmd("getconf SOCKSPort",$response) != 250){
			$this->errormsg = $response;
			return array();
		}
		
		$addrList = explode("\r\n",$response);
		
		$aSocksPortList=array();
		foreach ($addrList as $line) {
			
			if(strlen(trim($line))==0)
				continue;
			
			$parts = preg_split("/ /",$line);

			$aTmp['port'] = $parts[0];
			$aTmp['exitnode'] = substr($parts[1], strlen("ExitNode="));
			$aSocksPortList[] = $aTmp;
		}

		return $aSocksPortList;
	
	}
	
	/**
     *
     * set Tor opens a socks proxy on port
     *
     * @param int $port proxy on port  
	 * @param string $exitnode exit router
	 * @return string   
	 *          true is success
	 *          false is fail
     */
	public function bSetSocksPort($port="9000",$exitnode=""){
		
		if(!$this->aGetRouterDesc($exitnode))
			return false;
		
		
		$aSocksPortList = $this->aGetSocksPort();
		
		
		if(count($aSocksPortList)==0){
			if(!$port) $port = 9000;
			return $this->bSetConf("socksport=\"9000 ExitNode=$exitnode\"");
			
		}

		$cmd="";
		$find = false;
		for($i=0;$i<count($aSocksPortList);$i++){
			if($aSocksPortList[$i]['port']==$port){
				$find = true;
				$cmd.=" socksport=\"$port ExitNode=$exitnode\"";
			}else			
				if($aSocksPortList[$i]['port'])
				$cmd.=" socksport=\"".$aSocksPortList[$i]['port']." ExitNode=".$aSocksPortList[$i]['exitnode']."\"";
		}
		
		if(!$find){
			if(!$port) $port = 9000;
			$cmd.=" socksport=\"$port ExitNode=$exitnode\"";
		}
		
		return $this->bSetConf($cmd);
	}
	
	public function aGetTraffic(){
		
		$aTraffic['read'] = 0;
		if($this->iSendCmd("getinfo traffic/read",$response) == 250){
			$aTraffic['read'] = $this->format_bytes($response);
			
		}
		
		$aTraffic['written'] = 0;
		if($this->iSendCmd("getinfo traffic/written",$response) == 250){
			$aTraffic['written'] = $this->format_bytes($response);
			
		}

		return $aTraffic;
	}
	
	public function aGetAccounting(){
		
		
		if($this->iSendCmd("getinfo accounting/enabled",$response) != 250){
			
			return array();
		}	
		
		

		$aAccounting['hibernating'] = "";
		if($this->iSendCmd("getinfo accounting/hibernating",$response) == 250){
			$aAccounting['hibernating'] = $this->format_replace($response);
			
		}
		
		
		
		$aAccounting['read'] = 0;
		$aAccounting['written'] = 0;
		if($this->iSendCmd("getinfo accounting/bytes",$response) == 250){
			$tmp = explode(" ",$this->format_replace($response));
			
			$aAccounting['read'] = $this->format_bytes($tmp[0]);
			$aAccounting['written'] = $this->format_bytes($tmp[1]);
			
		}
		
		
		$aAccounting['read_left'] = 0;
		$aAccounting['written_left'] = 0;
		
		if($this->iSendCmd("getinfo accounting/bytes-left",$response) == 250){
			$tmp = explode(" ",$this->format_replace($response));
			
			$aAccounting['read_left'] = $this->format_bytes($tmp[0]);
			$aAccounting['written_left'] = $this->format_bytes($tmp[1]);
			
		}
		
		$aAccounting['interval_start'] = "";
		if($this->iSendCmd("getinfo accounting/interval-start",$response) == 250){
			$aAccounting['interval_start'] = $this->format_replace($response);
			
		}
		
		$aAccounting['interval_wake'] = "";
		if($this->iSendCmd("getinfo accounting/interval-wake",$response) == 250){
			$aAccounting['interval_wake'] = $this->format_replace($response);
			
		}
		
		$aAccounting['interval_end'] = "";
		if($this->iSendCmd("getinfo accounting/interval-end",$response) == 250){
			$aAccounting['interval_end'] = $this->format_replace($response);

		}
		
		
	
		return $aAccounting;
	}
}
?>