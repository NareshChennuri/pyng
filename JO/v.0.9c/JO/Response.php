<?php

class JO_Response {

	private static $_instance;
	
	private $headers = array(); 
	
	private $level = 0;
	
	private $keepAlive = 0;
	
	private $request;
	
	public function getLive() {
		return $this->keepAlive;
	}
	
	public function setLive($keepAlive) {
		$this->keepAlive = $keepAlive;
		return $this;
	}
	
	public function getLevel() {
		return $this->level;
	}
	
	public function setLevel($level) {
		$this->level = $level;
		return $this;
	}
	
	public function setRequest(JO_Request $request) {
		$this->request = $request;
		return $this;
	}
	
	/**
	 * @return JO_Request
	 */
	public function getRequest() {
		if($this->request == null) {
			$this->setRequest(JO_Request::getInstance());
		}
		return $this->request;
	}
	
	/**
	 * @param string $header
	 * @return JO_Response
	 */
	public function addHeader($header) {
		$this->headers[] = $header;
		return $this;
	}

	public function redirect($url) {
		header('Location: ' . $url);
		exit;
	}
	
	/**
	 * @param array $options
	 * @return JO_Response
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}

	public function __construct($options = array()) {
	
	}
	
	private function compress($data, $level = 0) {
		$request = $this->getRequest();
		
		if ((strpos($request->getServer('HTTP_ACCEPT_ENCODING'), 'gzip') !== FALSE)) {
			$encoding = 'gzip';
		} 

		if ((strpos($request->getServer('HTTP_ACCEPT_ENCODING'), 'x-gzip') !== FALSE)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) { 
			return $data;
		}
		
		$this->addHeader('Content-Encoding: ' . $encoding);
		
		return gzencode($data, (int)$level);
	}
	
	public function appendBody($body) {
		
		if ($this->level) {
			ini_set("zlib.output_compression", 4096);
			$body = $this->compress($body, $this->level);
		}	
		
		if($this->keepAlive) {
			$this->headers[] = "Keep-Alive: timeout=" . (int)$this->keepAlive;
		}
		
		if (!headers_sent()) {
			foreach ($this->headers as $header) {
				header($header, TRUE);
			}
		}
		
		echo $body;
	}
	

}
 
