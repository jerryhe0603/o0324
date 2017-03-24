<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <guenter@grodotzki.ph> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Günter Grodotzki
 * ----------------------------------------------------------------------------
 */

/**
 * a socks5 client in php (original by sergey krivoy, ported to php5
 * + bugfixes/improvements
 * @author Günter Grodotzki <guenter@grodotzki.ph>
 * @version 20081224
 */
class CSocks5
{
	private $socket;
	private $debug;
	private $socks_server;
	private $socks_port;
	private $socks_auth;
	private $interface;
	private $timeout = 30;
	private $dnstunnel = true;

	
	/**
	 * specify socks5-proxy to use here
	 * @param $server
	 * @param $port
	 * @param $username
	 * @param $password
	 */
	public function __construct($server, $port, $username = null, $password = null)
	{
		$this->socks_server = $server;
		$this->socks_port = $port;

		if($username !== null && $password !== null)
		{
			$this->socks_auth['username'] = $username;
			$this->socks_auth['password'] = $password;
		}

	}
	
	public function __destruct()
	{
	
		if(is_resource($this->socket))
		{
			socket_close($this->socket);
		}
	}
	
	
	/**
	 * set interface IP for outgoing connection to the socks proxy
	 * @param $ip
	 */
	public function set_interface($ip)
	{
		$this->interface = $ip;
	}
	
	
	/**
	 * set timeouts for connection and receiving data
	 * @param $seconds
	 */
	public function set_timeout($seconds)
	{
		if(is_int($seconds))
		{
			$this->timeout = $seconds;
		}		
	}
	
	
	/**
	 * if true, dns lookups will be performed by the socks-proxy 
	 * @param $bool
	 */
	public function set_dnstunnel($bool)
	{
		if(is_bool($bool))
		{
			$this->dnstunnel = $bool;
		}
	}
	

	/**
	 * connect to given host through previously given socks5-proxy
	 * @param $host
	 * @param $port
	 * @return bool
	 */
	public function connect($host, $port)
	{		
		if($this->bind_socks() === true)
		{
			if(socket_write($this->socket, $this->get_connection_request($host, $port)) !== false)
			{
				if(@socket_recv($this->socket, $buffer, 1024, 0) !== false)
				{
					$response = unpack("Cversion/Cresult/Creg/Ctype/Lip/Sport", $buffer);
					if(isset($response['version']) && isset($response['result']) && $response['version'] == 0x05 && $response['result'] == 0x00)
					{
						return true;			
					}
				}			
			}			
		}
		return false;
	}
	
	
	/**
	 * send request and return response
	 * @param $request
	 * @return string
	 */
	public function request($request)
	{
		$buffer = "";
		
		if($this->socket !== false && socket_write($this->socket, $request) !== false)
		{
			while(@socket_recv($this->socket, $recv, 1024, 0) !== false)
			{
				if($recv === null)
				{
					break;
				}
				$buffer .= $recv;
			}		
			
			return $buffer;
		}
	}
	
	
	/**
	 * connect to given socks-proxy and bind
	 * @return unknown_type
	 */
	private function bind_socks()
	{
		// check dependency
		if(function_exists("socket_create"))
		{
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if($this->socket !== false)
			{
				socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $this->timeout, "usec" => 0));	
				socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => $this->timeout, "usec" => 0));		
				
				// bind to local interface
				$this->bind_interface();
				
				/* timeout http://de2.php.net/manual/en/function.socket-connect.php#36223 */
				//socket_set_nonblock($this->socket);
				
				$attempts = 0;
				// socket_connect + socket_set_nonblock throws out error-warning
				while(!($connected = @socket_connect($this->socket, $this->socks_server, $this->socks_port)) && $attempts++ < $this->timeout)
				{
					$err = socket_last_error($this->socket);
					if($err != SOCKET_EINPROGRESS && $err != SOCKET_EALREADY)
					{
						socket_close($this->socket);
						trigger_error("connection error", E_USER_WARNING);
						return false;
					}
					sleep(1);
				}
				
				if(!$connected)
				{					
					trigger_error("connection timed out", E_USER_WARNING);
					socket_close($this->socket);
					return false;
				}
				
				//socket_set_block($this->socket);

				if(isset($this->socks_auth['username']) && isset($this->socks_auth['password']))
				{
					$method = 0x02;
				}
				else
				{
					$method = 0x00;
				}

				if(socket_write($this->socket, pack("C3", 0x05, 0x01, $method)) !== false)
				{
					socket_recv($this->socket, $buffer, 1024, 0);
					$response = unpack("Cversion/Cmethod", $buffer);
					
					if(isset($response['version']) && isset($response['method']) && $response['version'] == 0x05 && $response['method'] == $method)
					{						
						switch($method)
						{
							case 0x02:
								return $this->auth_userpass();
								break;
								
							default:
								return true;
								break;
						}
					}
				}
				
				socket_close($this->socket);
				return false;
			}
			else
			{
				trigger_error(socket_strerror(socket_last_error($this->socket)), E_USER_WARNING);
				return false;
			}
		}
		else
		{
			trigger_error("module 'sockets' not found", E_USER_ERROR);
			return false;			
		}
		/*
		if($response['method'] == 0xFF)
		{
			echo "not supported";
		}
		*/
	}

	
	/**
	 * bind to local interface
	 */
	private function bind_interface()
	{
		if($this->interface !== null)
		{
			return socket_bind($this->socket, $this->interface);
		}		
	}
	
	
	/**
	 * auth via the second method, e.g. plaintext username/password
	 * @see http://tools.ietf.org/html/rfc1929
	 * @return bool
	 */
	private function auth_userpass()
	{
		// (thx2dinesh)
		if(socket_write($this->socket, pack("CC", 0x01, strlen($this->socks_auth['username'])) . $this->socks_auth['username'] . pack("C", strlen($this->socks_auth['password'])) . $this->socks_auth['password']))
		{						
			socket_recv($this->socket, $buffer, 1024, 0);
			$response = unpack("Cversion/Cstatus", $buffer);	
			if(isset($response['status']) && $response['status'] == 0x00)
			{
				return true;
			}
			else
			{
				socket_close($this->socket);
				return false;
			}
		}
	}
	
	
	/**
	 * generate socks5 connection request
	 * @param $host
	 * @param $port
	 * @return binary string
	 */
	private function get_connection_request($host, $port)
	{
		switch($this->dnstunnel)
		{
			case true:
				return pack("C5", 0x05, 0x01, 0x00, 0x03, strlen($host)).$host.pack("n", $port);
				break;
				
			case false:
				return pack("C4Nn", 0x05, 0x01, 0x00, 0x01, ip2long(gethostbyname($host)), $port);
				break;
		}
	}
}
?>