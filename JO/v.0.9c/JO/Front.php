<?php

class JO_Front {

	private static $_instance;
	
	private $params;
	
	private $moduleDirectory;
	
	private $defaultModule = 'default';
	
	private $request;
	
	private $default_controller = 'index';
	
	private $default_action = 'index';
	
	/**
	 * @param array $options
	 * @return JO_Front
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	public function __construct($options = array()) {
		foreach($options AS $name => $value) {
        	$method = 'set' . $name;
        	if(method_exists($this,$method)) {
        		$this->$method($value);	
        	}
        }
	}
	
	public function setBaseUrl($url) {
		$this->getRequest()->setBaseUrl($url);
		return $this;	
	}
	
	public function setParams($value) {
		$this->params = $value;
		return $this;
	}
	
	public function getParams() {
		return $this->params;
	}
	
	public function getParam($key) {
		return isset($this->params[$key]) ? $this->params[$key] : null;
	}
	
	public function setRequest(JO_Request $value) {
		$this->request = $value;
		return $this;
	}
	
	public function getRequest() {
		if($this->request == null) {
			$this->setRequest(JO_Request::getInstance());
		}
		return $this->request;
	}
	
	public function setModuleDirectory($value) {
		$this->moduleDirectory = $value;
		return $this;
	}
	
	public function getModuleDirectory() {
		return $this->moduleDirectory;
	}
	
	public function getModuleDirectoryWithDefault($value) {
		$path = $this->getModuleDirectory() . '/' . $value; 
		if(!file_exists($path) || !is_dir($path)) {
			throw new JO_Exception("Module path $path mising");
		}
		return $path;
	}
	
	public function setDefaultModule($value) {
		$this->defaultModule = $value;
		return $this;
	}
	
	public function getDefaultModule() {
		return $this->defaultModule;
	}
	
	public function setDefaultController($value) {
		$this->default_controller = $value;
		return $this;
	}
	
	public function getDefaultController() {
		return $this->default_controller;
	}
	
	public function setDefaultAction($value) {
		$this->default_action = $value;
		return $this;
	}
	
	public function getDefaultAction() {
		return $this->default_action;
	}
	
	
    protected function _formatName($unformatted, $isAction = false)
    {
        // preserve directories
        if (!$isAction) {
            $segments = explode('_', $unformatted);
        } else {
            $segments = (array) $unformatted;
        }

        foreach ($segments as $key => $segment) {
            $segment        = str_replace(array('-', '.'), ' ', strtolower($segment));
            $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
            $segments[$key] = str_replace(' ', '', ucwords($segment));
        }

        return implode('_', $segments);
    }
	
	
    public function formatControllerName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted)) . 'Controller';
    }

    
    public function formatActionName($unformatted)
    {
        $formatted = $this->_formatName($unformatted, true);
        return strtolower(substr($formatted, 0, 1)) . substr($formatted, 1) . 'Action';
    }
	
	/**
     * Format the module name.
     *
     * @param string $unformatted
     * @return string
     */
    public function formatModuleName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted));
    }

    /**
     * Format action class name
     *
     * @param string $moduleName Name of the current module
     * @param string $className Name of the action class
     * @return string Formatted class name
     */
    public function formatClassName($moduleName, $className)
    {
        return $this->formatModuleName($moduleName) . '_' . $className;
    }

    /**
     * Convert a class name to a filename
     *
     * @param string $class
     * @return string
     */
    public function classToFilename($class)
    {
        return str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
    }
	
	
	
	public function getDispatchDirectory() {
		return $this->getModuleDirectoryWithDefault($this->getRequest()->getModule()) . DIRECTORY_SEPARATOR . 'controllers';
	}
	
	public function isDispatchable($controller) {
	
		$className = $this->formatControllerName($controller);
		
		if (class_exists($className, false)) {
            return true;
        }
		
		$fileSpec    = $this->classToFilename($className);
        $dispatchDir = $this->getDispatchDirectory();
        $test        = $dispatchDir . DIRECTORY_SEPARATOR . $fileSpec;
        return JO_Loader::isReadable($test);
	}
	
	private function setHelpersPath() {
		JO_Loader::setIncludePaths(array(
			($this->getModuleDirectory() . DIRECTORY_SEPARATOR . JO_Request::getInstance()->getModule())
		));
		JO_Loader_Autoloader::getInstance()->registerNamespace(array(
			'Model_', 'Helper_', 'Plugin_'
		));
	}
	
	public function dispatch($controller = null, $action = null, $params = array()) {
		
		$this->setHelpersPath();
		
		$controller = $controller ? $controller : $this->getRequest()->getController();
		
		$response = JO_Response::getInstance();
		
		if(!$this->isDispatchable($controller) && $this->isDispatchable('error')) {
			$controller = 'error';	
			$action = 'error404';
		}
		
		if($this->isDispatchable($controller)) {
			JO_Loader::setIncludePaths(array($this->getDispatchDirectory()));
			$className = $this->formatControllerName($controller); 
			JO_Loader::loadFile($this->classToFilename($className), null, true);
			$controller_instance = new $className($this->getRequest());
			
			if (!$controller_instance instanceof JO_Action) {
				require_once 'JO/Exception.php';
				throw new JO_Exception(
					'Controller "' . $className . '" is not an instance of JO_Action'
				);
			}
			
			$action = $action ? $action : $this->getRequest()->getAction();
			
			// by default, buffer output
			$disableOb = $this->getParam('disableOutputBuffering');
			$obLevel   = ob_get_level();
			if (empty($disableOb)) {
				ob_start();
			}

			try {
				$controller_instance->dispatch($controller,$action, $params);
			} catch (Exception $e) {
				// Clean output buffer on error
				$curObLevel = ob_get_level();
				if ($curObLevel > $obLevel) {
					do {
						ob_get_clean();
						$curObLevel = ob_get_level();
					} while ($curObLevel > $obLevel);
				}
				throw $e;
			}

			if (empty($disableOb)) {
				$content = @ob_get_clean();
				$response->appendBody($content);
			}

			// Destroy the page controller instance and reflection objects
			$controller_instance = null;
			
		} else {
			
			$controller_instance = new JO_Action;
			$controller_instance->dispatch($controller,'error404');
			
			// Destroy the page controller instance and reflection objects
			$controller_instance = null;
			
//			require_once 'JO/Exception.php';
//			throw new JO_Exception(
//				'Controller "' . $controller . '" is not found'
//			);
			
		}
	}

}
 
