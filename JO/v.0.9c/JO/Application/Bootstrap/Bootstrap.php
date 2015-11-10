<?php

class JO_Application_Bootstrap_Bootstrap {

    /**
     * @var JO_Application
     */
    protected $_application;
    /**
     * @var string
     */
    protected $_environment;
    /**
     * @var array Internal resource methods (resource/method pairs)
     */
    protected $_classResources;
    /**
     * @var array Initializers that have been run
     */
    protected $_run = array();
    /**
     * @var array Initializers that have been started but not yet completed (circular dependency detection)
     */
    protected $_started = array();

    public function __construct($application) {
		$this->setApplication($application);
    }

    /**
     * Get class resources (as resource/method pairs)
     *
     * Uses get_class_methods() by default, reflection on prior to 5.2.6,
     * as a bug prevents the usage of get_class_methods() there.
     *
     * @return array
     */
    public function getClassResources() {
        if (null === $this->_classResources) {
            if (version_compare(PHP_VERSION, '5.2.6') === -1) {
                $class        = new ReflectionObject($this);
                $classMethods = $class->getMethods();
                $methodNames  = array();

                foreach ($classMethods as $method) {
                    $methodNames[] = $method->getName();
                }
            } else {
                $methodNames = get_class_methods($this);
            }

            $this->_classResources = array();
            foreach ($methodNames as $method) {
                if (5 < strlen($method) && '_init' === substr($method, 0, 5)) {
                    $this->_classResources[strtolower(substr($method, 5))] = $method;
                }
            }
        }

        return $this->_classResources;
    }

    /**
     * Get class resource names
     *
     * @return array
     */
    public function getClassResourceNames() {
        $resources = $this->getClassResources();
        return array_keys($resources);
    }

    /**
     * Set application/parent bootstrap
     *
     * @param  JO_Application $application
     * @return JO_Application_Bootstrap_Bootstrap
     */
    public function setApplication($application){
        if (($application instanceof JO_Application)
            || ($application instanceof JO_Application_Bootstrap_Bootstrap)
        ) {
            if ($application === $this) {
                throw new JO_Exception('Cannot set application to same object; creates recursion');
            }
            $this->_application = $application;
        } else {
            throw new JO_Exception('Invalid application provided to bootstrap constructor (received "' . get_class($application) . '" instance)');
        }
        return $this;
    }

    /**
     * Retrieve parent application instance
     *
     * @return Zend_Application|Zend_Application_Bootstrap_Bootstrapper
     */
    public function getApplication() {
        return $this->_application;
    }

    /**
     * Retrieve application environment
     *
     * @return string
     */
    public function getEnvironment() {
        if (null === $this->_environment) {
            $this->_environment = $this->getApplication()->getEnvironment();
        }
        return $this->_environment;
    }

    public function run() {
		foreach ($this->getClassResourceNames() as $resource) {
			$this->_executeResource($resource);
		}
    }

    protected function _executeResource($resource) {
        $resourceName = strtolower($resource);

        if (in_array($resourceName, $this->_run)) {
            return;
        }

        if (isset($this->_started[$resourceName]) && $this->_started[$resourceName]) {
            throw new JO_Exception('Circular resource dependency detected');
        }

        $classResources = $this->getClassResources();
        if (array_key_exists($resourceName, $classResources)) {
            $this->_started[$resourceName] = true;
            $method = $classResources[$resourceName];
            $return = $this->$method();
            unset($this->_started[$resourceName]);
            $this->_markRun($resourceName);

            if (null !== $return) {
				JO_Registry::set($resourceName, $return);
            }

            return;
        }

        throw new JO_Exception('Resource matching "' . $resource . '" not found');
    }

    /**
     * Mark a resource as having run
     *
     * @param  string $resource
     * @return void
     */
    protected function _markRun($resource) {
        if (!in_array($resource, $this->_run)) {
            $this->_run[] = $resource;
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

    /**
     * Set PHP configuration settings
     *
     * @param  array $settings
     * @param  string $prefix Key prefix to prepend to array values (used to map . separated INI values)
     * @return JO_Application_Bootstrap_Bootstrap
     */
    public function setPhpSettings(array $settings, $prefix = '')
    {
        foreach ($settings as $key => $value) {
            $key = empty($prefix) ? $key : $prefix . $key;
            if (is_scalar($value)) {
                ini_set($key, $value);
            } elseif (is_array($value)) {
                $this->setPhpSettings($value, $key . '.');
            }
        }

        return $this;
    }
    
    public function _initXSS() {
    	$_POST = self::htmlspecialchars($_POST);
    	$_GET = self::htmlspecialchars($_GET);
    	$_REQUEST = self::htmlspecialchars($_REQUEST);
    	$_COOKIE = self::htmlspecialchars($_COOKIE);
    }
    
	protected function htmlspecialchars($in, $quote_style = 2, $charset='utf-8', $double_encode = true) {
	    if(is_array($in)) {
	        $tmp = array();
	        foreach($in AS $k=>$v) {
	            if(is_array($v)) {
					$tmp[$k] = self::htmlspecialchars($v, $quote_style, $charset, $double_encode);
				} else {
					if(ini_get('magic_quotes_gpc')) {
						self::stripslashes_deep($v);
						$tmp[$k] = htmlspecialchars($v, $quote_style, $charset, $double_encode);
					} else {
						$tmp[$k] = htmlspecialchars($v, $quote_style, $charset, $double_encode);
					}
				}
	        }
	        return $tmp;
	    } else {
	        return htmlspecialchars($in, $quote_style, $charset, $double_encode);
	    }
	}
	
	protected function _formatControllerName($unformatted) {
        // preserve directories
        $segments = explode('_', $unformatted);

        foreach ($segments as $key => $segment) {
            $segment        = str_replace(array('-', '.'), ' ', strtolower($segment));
            $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
            $segments[$key] = str_replace(' ', '', ucwords($segment));
        }

        return implode('/', $segments);
    }
  	
	public function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	} 

} 
