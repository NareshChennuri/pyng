<?php

class JO_Request {

	private $_params = array();
	
	private static $_instance;
	
	public $baseUrl;
	
	/**
     * Scheme for http
     *
     */
    const SCHEME_HTTP  = 'http';

    /**
     * Scheme for https
     *
     */
    const SCHEME_HTTPS = 'https';
	
	/**
	 * @param array $options
	 * @return JO_Request
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}

	public function __construct() {
		if(ini_get('magic_quotes_gpc')) {
			$this->stripslashes_deep($_GET);
			$this->stripslashes_deep($_POST);
			$this->stripslashes_deep($_COOKIE);
			$this->stripslashes_deep($_SESSION);
			$this->stripslashes_deep($_REQUEST);
		}
		
		if (ini_get('register_globals')) {
			$globals = array($_REQUEST, $_SESSION, $_SERVER, $_FILES);
			foreach ($globals as $global) {
				if(is_array($global)) {
					if(is_array($global)) {
						foreach(array_keys($global) as $key) {
							unset($$key);
						}
					}
				}
			}
		}
		
		$results = explode('/',$this->getUri());
		for($i=0;$i<count($results); $i++) {
			if(isset($results[$i+1])) {
				$this->setParams($results[$i], $results[$i+1]);
			}
		}
	}
	
	private function stripslashes_deep(&$value) {
	    $quotes_sybase = strtolower(ini_get('magic_quotes_sybase'));
	    if((empty($quotes_sybase) || $quotes_sybase === 'off')) {
			$value = is_array($value) ? array_map(array(__CLASS__, 'stripslashes_deep'), $value) : stripslashes($value);
	    } else {
			$value = is_array($value) ? array_map(array(__CLASS__, 'stripslashes_deep'), $value) : str_replace("''","'",$value);
	    }
	    return $value;
	} 
	
	private function generateBaseUrl() {
		$url = $this->getScheme() . '://';
		$url .= $this->getHttpHost();
		$url .= $this->getServer('PHP_SELF') ? $this->getServer('PHP_SELF') : '/';
		return rtrim($url, 'index.php');
	}
	
	public function getBaseUrl() {
		if($this->baseUrl == null) {
			$this->setBaseUrl($this->generateBaseUrl());
		}
		return $this->baseUrl;
	}
	
	public function setBaseUrl($url) {
		$this->baseUrl = $url;
		return $this;
	}
	
	/**
	 * @param unknown_type $spec
	 * @param unknown_type $value
	 * @return JO_Request|JO_Request
	 */
	public function setParams($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Invalid value passed to setParams(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setParams($key, $value);
            }
            return $this;
        }
        $this->_params[(string) $spec] = $value;
        return $this;
    }
	
	public function getParams() {
		$return       = $this->_params;
//        if (isset($_REQUEST)
//            && is_array($_REQUEST)
//        ) {
//            $return += $_REQUEST;
//        }
        if (isset($_GET)
            && is_array($_GET)
        ) {
            $return += $_GET;
        }
        if (isset($_POST)
            && is_array($_POST)
        ) {
            $return += $_POST;
        }
        return $return;
	}
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, Ambigous>
	 */
	public function getParam($key) {
		return self::arrayKey($key, $this->getParams() );
//		return isset($this->_params[$key]) ? $this->_params[$key] : null;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetRequest($key) {
		return self::arrayKeyIsset($key, $_REQUEST);
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetParam($key) {
		return self::arrayKeyIsset($key, $this->_params);
	}
	
	public function setHost($value) {
		$_SERVER['HTTP_HOST'] = $value;
		return $this;
	}
	
	/**
	 * @param string $key
	 * @param string $value
	 * @return JO_Request
	 */
	public function setServer($key, $value) {
		$_SERVER[$key] = $value;
		return $this;
	}
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function getServer($key) {
		return self::arrayKey($key, $_SERVER);
//		return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetServer($key) {
		return self::arrayKeyIsset($key, $_SERVER);
	}
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function getQuery($key) {
		return self::arrayKey($key, $_GET);
//		return isset($_GET[$key]) ? $_GET[$key] : null;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetQuery($key) {
		return self::arrayKeyIsset($key, $_GET);
	}
    
	/**
	 * @param string|null $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function getPost($key = null) {
		if($key === null) {
			return $_POST;
		}
		return self::arrayKey($key, $_POST);
//		return isset($_POST[$key]) ? $_POST[$key] : null;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetPost($key) {
		return self::arrayKeyIsset($key, $_POST);
	}
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function getFile($key) {
		return self::arrayKey($key, $_FILES);
//		return isset($_FILES[$key]) ? $_FILES[$key] : null;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetFile($key) {
		return self::arrayKeyIsset($key, $_FILES);
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 * @param int $maxage
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $HTTPOnly
	 * @return Ambigous <NULL, unknown>
	 */
	public function setCookie($name, $value='', $maxage=0, $path='',$domain='', $secure=false, $HTTPOnly=false)
    {
        if(is_array($name))
        {
            list($k,$v)    =    each($name);

                $name    =    $k.'['.$v.']';

        }
        $ob = ini_get('output_buffering');
        // Abort the method if headers have already been sent, except when output buffering has been enabled
        if ( headers_sent() && (bool) $ob === false || strtolower($ob) == 'off' )
            return false;
        if ( !empty($domain) )
        {
            // Fix the domain to accept domains with and without 'www.'.
            if ( strtolower( substr($domain, 0, 4) ) == 'www.' ) $domain = substr($domain, 4);
            // Add the dot prefix to ensure compatibility with subdomains
            if ( substr($domain, 0, 1) != '.' ) $domain = '.'.$domain;
            // Remove port information.
            $port = strpos($domain, ':');
            if ( $port !== false ) $domain = substr($domain, 0, $port);
        }
        // Prevent "headers already sent" error with utf8 support (BOM)
        //if ( utf8_support ) header('Content-Type: text/html; charset=utf-8');
        if(is_array($name))
        {
            header('Set-Cookie: '.$name.'='.rawurlencode($value)
                                    .(empty($domain) ? '' : '; Domain='.$domain)
                                    .(empty($maxage) ? '' : '; Max-Age='.$maxage)
                                    .(empty($path) ? '' : '; Path='.$path)
                                    .(!$secure ? '' : '; Secure')
                                    .(!$HTTPOnly ? '' : '; HttpOnly'), false);
        }else{
            header('Set-Cookie: '.rawurlencode($name).'='.rawurlencode($value)
                                    .(empty($domain) ? '' : '; Domain='.$domain)
                                    .(empty($maxage) ? '' : '; Max-Age='.$maxage)
                                    .(empty($path) ? '' : '; Path='.$path)
                                    .(!$secure ? '' : '; Secure')
                                    .(!$HTTPOnly ? '' : '; HttpOnly'), false);
        }
        return setcookie($name, $value, (time()+$maxage), $path, $domain, $secure, $HTTPOnly);
    } 
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, unknown>
	 */
	public function getCookie($key) {
		return self::arrayKey($key, $_COOKIE);
//		return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetCookie($key) {
		return self::arrayKeyIsset($key, $_COOKIE);
	}
	
	/**
     * Set GET values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return JO_Request
     */
    public function setQuery($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Invalid value passed to setQuery(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setQuery($key, $value);
            }
            return $this;
        }
        $_GET[(string) $spec] = $value;
        return $this;
    }
	
    /**
     * @param string $expresion
     * @return string
     */
    public function makeQuery($expresion) {
    	$query = $_GET;
    	$temp = array();
    	foreach($query AS $key => $value) {
    		if(strpos($key, $expresion) !== false) {
    			$temp[$key] = $value;
    		}
    	}
    	return http_build_query($temp);
    }
    
	/**
	 * @param string $key
	 * @param string|multitype: $def
	 * @return string|multitype:
	 */
	public function getRequest($key, $def = null) {
		switch(true) {
			case (strtoupper($key) == 'URI'):
				return $this->getUri();
			break;
			case isset($this->_params[$key]):
				return $this->_params[$key];
			break;
			case isset($_POST[$key]):
				return $_POST[$key];
			break;
			case isset($_GET[$key]):
				return $_GET[$key];
			break;
			case isset($_REQUEST[$key]):
				return $_REQUEST[$key];
			break;
		}
		return $def;
	}
	
	/**
     * Retrieve the controller name
     *
     * @return string
     */
	public function getController() {
		return $this->getRequest('controller', JO_Front::getInstance()->getDefaultController());
	}
	
	/**
     * Retrieve the forwarded controller name
     *
     * @return string
     */
	public function getForwarded() {
		return $this->getRequest('forwarded', null);
	}
	
	/**
     * Retrieve the action name
     *
     * @return string
     */
	public function getAction() {
		return $this->getRequest('action', JO_Front::getInstance()->getDefaultAction());
	}
	
	/**
     * Retrieve the module name
     *
     * @return string
     */
	public function getModule() {
		return $this->getRequest('module', JO_Front::getInstance()->getDefaultModule());
	}
	
	/**
	 * @param string $controller
	 * @return JO_Request
	 */
	public function setController($controller) {
		$this->setParams('controller', $controller);
		return $this;
	}
	
	/**
	 * @param string $action
	 * @return JO_Request
	 */
	public function setAction($action) {
		$this->setParams('action', $action);
		return $this;
	}
	
	/**
	 * @param string $module
	 * @return JO_Request
	 */
	public function setModule($module) {
		$this->setParams('module', $module);
		return $this;
	}
	
	/**
     * Return the method by which the request was made
     *
     * @return string
     */
	public function getMethod() {
        return $this->getServer('REQUEST_METHOD');
    }
	
	/**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost() {
        if ('POST' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by GET?
     *
     * @return boolean
     */
    public function isGet() {
        if ('GET' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut() {
        if ('PUT' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by DELETE?
     *
     * @return boolean
     */
    public function isDelete() {
        if ('DELETE' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by HEAD?
     *
     * @return boolean
     */
    public function isHead() {
        if ('HEAD' == $this->getMethod()) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by OPTIONS?
     *
     * @return boolean
     */
    public function isOptions() {
        if ('OPTIONS' == $this->getMethod()) {
            return true;
        }

        return false;
    }
    
	/**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return boolean
     */
    public function isXmlHttpRequest() {
        if(($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest')) {
        	return true;
        }
   		if(($this->getHeader('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest')) {
        	return true;
        }
        if($this->getRequest('RSP') == 'ajax') {
        	return true;
        }
        
        return false;
    }

    /**
     * Is this a Flash request?
     *
     * @return boolean
     */
    public function isFlashRequest() {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }

    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure() {
        return ($this->getScheme() === self::SCHEME_HTTPS);
    }
    
    /**
     * Is chell request
     *
     * @return boolean
     */
	public function isCmd() {
        return (bool)$this->getServer('SHELL');
    }
    
	/**
	 * @return Ambigous <multitype, NULL>|string
	 */
	public function isMobile() {

		if (JO_Session::issetKey('ismobile')) {
        	return JO_Session::get('ismobile');
		}
        
		$is_mobile = false;

		if(preg_match('/(android|up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', strtolower($this->getServer('HTTP_USER_AGENT')))) {
			$is_mobile = true;
		}

		if((strpos(strtolower($this->getServer('HTTP_ACCEPT')),'application/vnd.wap.xhtml+xml')>0) or (($this->issetServer('HTTP_X_WAP_PROFILE') or $this->issetServer('HTTP_PROFILE')))) {
			$is_mobile = true;
		}

		$mobile_ua = strtolower(substr($this->getServer('HTTP_USER_AGENT'), 0, 4));
		$mobile_agents = array('w3c ','acs-','alav','alca','amoi','andr','audi','avan','benq','bird','blac','blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno','ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-','maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-','newt','noki','oper','palm','pana','pant','phil','play','port','prox','qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar','sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-','tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp','wapr','webc','winw','winw','xda','xda-');

		if(in_array($mobile_ua,$mobile_agents)) {
			$is_mobile = true;
		}

		if ($this->issetServer('ALL_HTTP')) {
			if (strpos(strtolower($this->getServer('ALL_HTTP')),'OperaMini') > 0) {
				$is_mobile = true;
			}
		}

		if (strpos(strtolower($this->getServer('HTTP_USER_AGENT')),'windows') > 0) {
			$is_mobile = false;
		}
		
		JO_Session::set('ismobile', $is_mobile);
      
		return $is_mobile;
	}
    
    /**
     * Is facebook bot
     *
     * @return boolean
     */
	public function isFacebookBot() {
        return (strpos(strtolower($this->getServer('HTTP_USER_AGENT')), 'facebook') !== false ? true : false);
    }
    
    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws JO_Exception
     */
	public function getHeader($header) {
        if (empty($header)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('An HTTP header name is required');
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers[$header])) {
                return $headers[$header];
            }
        }

        return false;
    }

    /**
     * Get the request URI scheme
     *
     * @return string
     */
    public function getScheme() {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }
    
    public function getSegment($key) {
    	$uri = explode('?',$this->getFullUri());
    	$parts = explode('/',$uri[0]);
    	return isset( $parts[($key-1)] ) ? $parts[($key-1)] : null;
    }
    
	/**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost() {
        $host = $this->getServer('HTTP_HOST');
        if (!empty($host)) {
            return $host;
        }

        $scheme = $this->getScheme();
        $name   = $this->getServer('SERVER_NAME');
        $port   = $this->getServer('SERVER_PORT');

        if (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        } else {
            return $name . ':' . $port;
        }
    }
    
	/**
     * Get the HTTP domain.
     *
     *@param  boolean $clear
     * @return string
     */
    public function getDomain($clear = true) {
    	if($clear) {
    		return preg_replace('/^www./i','',$this->getServer('HTTP_HOST'));
    	} else {
    		return $this->getServer('HTTP_HOST');
    	}
    }
    
	/**
     * Get the client's IP addres
     *
     * @param  boolean $checkProxy
     * @return string
     */
    public function getClientIp($checkProxy = true) {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        } else if ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        return $ip;
    }
    
    /**
     * @return string
     */
    public function getUri() {
    	if(dirname($this->getServer('SCRIPT_NAME')) != '/') {
    		$path = str_replace(dirname($this->getServer('SCRIPT_NAME')),'',$this->getServer('REQUEST_URI'));
    	} else {
    		$path = $this->getServer('REQUEST_URI');
    	}
    	if(strpos($path,'?') !== false) { 
    		$tmp = explode('?',$path);
			$path = $tmp[0];
	    	if(isset($tmp[1])) {
			    parse_str($tmp[1], $output);
			    if(ini_get('magic_quotes_gpc')) {
					$this->stripslashes_deep($output);
			    }
			    $this->setQuery($output);
			}
    	}
    	return urldecode(trim($path, '/'));
    }
    
    /**
     * @return string
     */
    public function getFullUri() {
    	if(dirname($this->getServer('SCRIPT_NAME')) != '/') {
    		$path = str_replace(dirname($this->getServer('SCRIPT_NAME')),'',$this->getServer('REQUEST_URI'));
    	} else {
    		$path = $this->getServer('REQUEST_URI');
    	}
    	return urldecode(trim($path, '/'));
    }
    
	/**
	 * @return string
	 */
	public function getFullUrl() {
    	$url = rtrim($this->getBaseUrl(), '/');
		if(dirname($this->getServer('SCRIPT_NAME')) == '/') {
		    $url .= '/' . ltrim($this->getServer('REQUEST_URI'), '/');
		} else {
		    $url .= '/' . ltrim(str_replace(dirname($this->getServer('SCRIPT_NAME')),'',$this->getServer('REQUEST_URI')), '/');
		}
    	
    	return $url;
    }
    
    /**
     * @param string $key
     * @param array $data
     * @return NULL|Ambigous <NULL, unknown>
     */
    private static function arrayKey($key, $data) {
    	$array_keys = array();
		if(preg_match('/^([^\[]{1,})\[(.*)\]+$/', $key, $match)) {
			$array_keys[] = $match[1];
			$ns = explode('[', '['.$match[2].']');
			foreach($ns AS $nss) {
				if($nss) {
					$array_keys[] = trim($nss, '][');
				}
			}

			$buf = $data;

			foreach($array_keys AS $k) {
				if(isset($buf[$k])) {
					$buf = $buf[$k];
				} else {
					$buf = null;
				}
			}
			return $buf;
		} else {
			return isset($data[$key]) ? $data[$key] : null;
		}
    }
    
    
    /**
     * @param string $key
     * @param string $data
     * @return boolean
     */
    private static function arrayKeyIsset($key, $data) {
    	$array_keys = array();
		if(preg_match('/^([^\[]{1,})\[(.*)\]+$/', $key, $match)) {
			$array_keys[] = $match[1];
			$ns = explode('[', '['.$match[2].']');
			foreach($ns AS $nss) {
				if($nss) {
					$array_keys[] = trim($nss, '][');
				}
			}

			$buf = $data;
			$isset = false;
			foreach($array_keys AS $k) {
				if(isset($buf[$k])) {
					$buf = $buf[$k];
					$isset = true;
				} else {
					$isset = false;
					break;
				}
			}
			return (bool)$isset;
		} else {
			return isset($data[$key]);
		}
    }

}
 
