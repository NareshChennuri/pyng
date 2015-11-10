<?php

class JO_Application
{
	
	/**
	 * 
	 * Framework version
	 * 
	 * @var string
	 */
	const VERSION = '0.9c';
	
    /**
     * Autoloader to use
     *
     * @var JO_Loader_Autoloader
     */
    protected $_autoloader;

    /**
     * Bootstrap
     *
     * @var JO_Application_Bootstrap_Bootstrap
     */
    protected $_bootstrap;

    /**
     * Application environment
     *
     * @var string
     */
    protected $_environment;

    /**
     * Flattened (lowercase) option keys
     *
     * @var array
     */
    protected $_optionKeys = array();

    /**
     * Options for JO_Application
     *
     * @var array
     */
    protected $_options = array();
	/**
     * Layout
     *
     * @var JO_Layout
     */
    protected $_layout;
	/**
     * Db
     *
     * @var JO_Db
     */
    protected $_db;
	/**
     * FrontController
     *
     * @var JO_Front
     */
    protected $_front;
    
	/**
	 * @var JO_Route
	 */
	protected $_route;
	
	/**
	 * @var JO_Session
	 */
	protected $_session;
	
	
	/**
	 * @var Holder hor shell 
	 */
	protected $argv;
    /**
     * Constructor
     *
     * Initialize application. Potentially initializes include_paths, PHP
     * settings, and bootstrap class.
     *
     * @param  string                   $environment
     * @param  string|array|JO_Config $options String path to configuration file, or array/JO_Config of configuration options
     * @throws JO_Application_Exception When invalid options are provided
     * @return void
     */
    public function __construct($environment, $options = null, $argv = null)
    {
    	if (version_compare(PHP_VERSION, '5.0.0', '<') ) exit("Sorry, this version of this system will only run on PHP version 5 or greater!\n");

    	$this->_environment = (string) $environment;

        require_once 'JO/Loader/Autoloader.php';
        $this->_autoloader = JO_Loader_Autoloader::getInstance();
        
        if (null !== $options) {
            if (is_string($options)) {
                $options = $this->_loadConfig($options);
            } elseif ($options instanceof JO_Config) {
                $options = $options->toArray();
            } elseif (!is_array($options)) {
                throw new JO_Exception('Invalid options provided; must be location of config file, a config object, or an array');
            }

            $this->setOptions($options);
            $this->setArgv($argv);
        }
    }

    /**
     * Retrieve current environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * @param string $environment
     * @return JO_Application
     */
    public function setEnvironment($environment)
    {
        $this->_environment = $environment;
        return $this;
    }

    /**
     * Retrieve autoloader instance
     *
     * @return JO_Loader_Autoloader
     */
    public function getAutoloader()
    {
        return $this->_autoloader;
    }

    /**
     * Set application options
     *
     * @param  array $options
     * @throws JO_Application_Exception When no bootstrap path is provided
     * @throws JO_Application_Exception When invalid bootstrap information are provided
     * @return JO_Application
     */
    public function setOptions(array $options)
    {
        if (!empty($options['config'])) {
            if (is_array($options['config'])) {
                $_options = array();
                foreach ($options['config'] as $tmp) {
                    $_options = $this->mergeOptions($_options, $this->_loadConfig($tmp));
                }
                $options = $this->mergeOptions($_options, $options);
            } else {
                $options = $this->mergeOptions($this->_loadConfig($options['config']), $options);
            }
        }
        
        $this->_options = $options;

        $options = array_change_key_case($options, CASE_LOWER);

        $this->_optionKeys = array_keys($options);

        foreach($options AS $name => $value) {
        	$method = 'set' . $name; 
        	if(method_exists($this,$method)) {
        		$return = $this->$method($value);	
				if($return) {
					JO_Registry::set($name, $return);
				}
        	} else {
				JO_Registry::set($name, $value);
			}
        }

        return $this;
    }
    
    public function setArgv($argv) {
    	JO_Shell::setArgv($argv);
    }

    /**
     * Retrieve application options (for caching)
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Is an option present?
     *
     * @param  string $key
     * @return bool
     */
    public function hasOption($key)
    {
        return in_array(strtolower($key), $this->_optionKeys);
    }

    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        if ($this->hasOption($key)) {
            $options = $this->getOptions();
            $options = array_change_key_case($options, CASE_LOWER);
            return $options[strtolower($key)];
        }
        return null;
    }
    
    const BASE_URL = 'http://shopynga.com/';

    /**
     * Merge options recursively
     *
     * @param  array $array1
     * @param  mixed $array2
     * @return array
     */
    public function mergeOptions(array $array1, $array2 = null)
    {
        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    $array1[$key] = (array_key_exists($key, $array1) && is_array($array1[$key]))
                                  ? $this->mergeOptions($array1[$key], $array2[$key])
                                  : $array2[$key];
                } else {
                    $array1[$key] = $val;
                }
            }
        }
        return $array1;
    }
    
    /**
     * Set Db configuration settings
     *
     * @param  array $options
     * @return JO_Db
     */
    public function setDb($options) { 
		$adapter = null;
		if(!isset($options['adapter'])) {
			throw new JO_Exception("Db adapter not set");
		} elseif(empty($options['adapter'])) {
			throw new JO_Exception("Db adapter is empty");
		} 
		
		if(!isset($options['params']) || !is_array($options['params'])) {
			throw new JO_Exception("Db params error");
		}
		
		$this->_db = JO_Db::factory($options['adapter'], $options['params']);
		JO_Db_Table::setDefaultAdapter($this->_db);
		
		return $this->_db;
    }
	
	/**
    * Set Layout configuration settings
    *
    * @param  array $options
    * @return JO_Layout
    */
	
	public function setLayout($options) {
		$this->_layout = JO_Layout::getInstance($options);
		return $this->_layout;
	}
	
	/**
    * Get Layout
    *
    * @return JO_Layout
    */
	
	public function getLayout() {
		if($this->_layout == null) {
			$this->setLayout($this->getOption('layout'));
		}
		return $this->_layout;
	}
	
	/**
	 * @param array $options
	 * @return JO_Session_Abstract
	 */
	public function setSession($options) {
		$this->_session = JO_Session::getInstance($options);
		return $this->_session;
	}
	
	/**
	 * @return JO_Session_Abstract
	 */
	public function getSession() {
		if($this->_session == null) {
			$this->setSession($this->getOption('session'));
		}
		return $this->_session;
	}
	
	/**
	 * @param string $router
	 * @return JO_Route
	 */
	public function setRouter($routers) {
		$rout = $this->getRouter();
		foreach($routers AS $name => $value) { 
			if(!isset($value['type'])) {
				throw new JO_Exception("Router type not set");
			}
			if(!isset($value['route'])) {
				throw new JO_Exception("Router route not set");
			}
			if(!isset($value['defaults'])) {
				throw new JO_Exception("Router defaults not set");
			} 
			$value['reqs'] = isset($value['reqs']) && is_array($value['reqs']) ? $value['reqs'] : array();
			
			$rout->addRoute($name, new $value['type'](
				$value['route'],
				$value['defaults'],
				$value['reqs'],
                isset($value['reverse']) ? $value['reverse'] : null
			));
		} 
		
		return $rout;
	}
	
	
	/**
	 * @return JO_Router
	 */
	public function getRouter() {
		if($this->_route == null) {
			$this->_route = JO_Router::getInstance();
		}
		return $this->_route;
	}
	
	/**
    * Set FrontController configuration settings
    *
    * @param  array $options
    * @return JO_Front
    */
	
	public function setFrontController($options) {
		$this->_front = JO_Front::getInstance($options);
		return $this->_front;
	}
	
	/**
    * Get FrontController
    *
    * @return JO_Front
    */
	
	public function getFrontController() {
		if($this->_front == null) {
			$this->setFrontController($this->getOption('frontController'));
		}
		return $this->_front;
	}

    /**
     * Set PHP configuration settings
     *
     * @param  array $settings
     * @param  string $prefix Key prefix to prepend to array values (used to map . separated INI values)
     * @return JO_Application
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

    /**
     * Set include path
     *
     * @param  array $paths
     * @return JO_Application
     */
    public function setIncludePaths(array $paths)
    {
        $path = implode(PATH_SEPARATOR, $paths);
        set_include_path($path . PATH_SEPARATOR . get_include_path());
        return $this;
    }

    /**
     * Set autoloader namespaces
     *
     * @param  array $namespaces
     * @return JO_Application
     */
    public function setAutoloaderNamespaces(array $namespaces)
    {
        $autoloader = $this->getAutoloader();

        foreach ($namespaces as $namespace) {
            $autoloader->registerNamespace($namespace);
        }

        return $this;
    }

    /**
     * Set bootstrap path/class
     *
     * @param  string $path
     * @param  string $class
     * @return JO_Application
     */
    public function setBootstrap($options = array())
    { 
		if (empty($options['path'])) {
			throw new JO_Exception('No bootstrap path provided');
		}
		
		$path  = $options['path'];
		$class = null;

		if (!empty($options['class'])) {
			$class = $options['class'];
		}
                
        // setOptions() can potentially send a null value; specify default
        // here
        if (null === $class) {
            $class = 'Bootstrap';
        }

        if (!class_exists($class, false)) {
            require_once $path;
            if (!class_exists($class, false)) {
                throw new JO_Exception('Bootstrap class not found');
            }
        }
        $this->_bootstrap = new $class($this);

        if (!$this->_bootstrap instanceof JO_Application_Bootstrap_Bootstrap) {
            throw new JO_Exception('Bootstrap class does not implement JO_Application_Bootstrap_Bootstrap');
        }

        return $this;
    }

    /**
     * Get bootstrap object
     *
     * @return JO_Application_Bootstrap_BootstrapAbstract
     */
    public function getBootstrap()
    {
        if (null === $this->_bootstrap) {
            $this->_bootstrap = new JO_Application_Bootstrap_Bootstrap($this);
        }
        return $this->_bootstrap;
    }

    
    /**
     * Run the application
     *
     * @return void
     */
    public function dispatch() {
		$router = $this->getRouter();
		$request = JO_Request::getInstance();
		$matched = $router->match($request->getRequest('uri'), false);
        
        JO_Registry::set('router',$router);
        
		if($matched) {
			foreach($matched AS $key=>$value) {
				$request->setParams($key, $value);
			}
		} else {
			$router->addRoute('default', new JO_Router_Regex(
				'([^\/]+)?/?([^\/]+)?/?',
				array('controller' => 'index','action' => 'index'),
				array(1 => 'controller',2 => 'action')
			));
			$matched = $router->match($request->getRequest('uri'), true);
			
			if($matched) {
				foreach($matched AS $key=>$value) {
					$request->setParams($key, $value);
				}
			}
		}
        
        $this->getBootstrap()->run();
		
    	
		$front = JO_Front::getInstance();
		
		try {
			$front->dispatch();
		} catch (JO_Exception $e) {
			throw new JO_Exception($e);
		}
		
    }

    /**
     * Load configuration file of options
     *
     * @param  string $file
     * @throws JO_Application_Exception When invalid configuration file is provided
     * @return array
     */
    protected function _loadConfig($file)
    {
        $environment = $this->getEnvironment();
        $suffix      = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($suffix) {
            case 'ini':
                $config = new JO_Config_Ini($file, $environment);
                break;

            case 'php':
            case 'inc':
                $config = include $file;
                if (!is_array($config)) {
                    throw new JO_Exception('Invalid configuration file provided; PHP file does not return array value');
                }
                return $config;
                break;

            default:
                throw new JO_Exception('Invalid configuration file provided; unknown config type');
        }

        return $config->toArray();
    }
}
