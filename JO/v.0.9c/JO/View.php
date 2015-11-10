<?php

class JO_View {

	private static $_instance;
	
	private $basePath;
	
	protected $data = array();
	
	public $children = array();
	
	protected $template = 'default';
	
	protected $null = null;
	
	private $layout;
	
	private static $optionsInstance = array();
	
	/**
	 * @var JO_Request
	 */
	private $request;
	
	/**
	 * @param string $key
	 * @param multitype $value
	 * @return JO_View
	 */
	public function __set($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, multitype:>
	 */
	public function &__get($key) {
		if(array_key_exists($key, $this->data)) {
			return $this->data[$key];	
		}
		return $this->null;
	}
	
	public function __isset($key) {
		return array_key_exists($key, $this->data);
	}
	
	public function __unset($key) {
		if(array_key_exists($key, $this->data)) {
			unset($this->data[$key]);
		}
	}
	
	public function getAll() {
		return $this->data;
	}
	
	public function reset() {
		$this->data = array();
		return $this;
	}
	
	/**
	 * @param array $options
	 * @return JO_View
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	/**
	 * @param array $options
	 * @return JO_View
	 */
	public static function resetInstance($options = array()) {
		$options_new = array();
		foreach($options AS $key => $value) {
			self::$optionsInstance[$key] = $value;
		}
		self::$_instance = new self(self::$optionsInstance);
		return self::$_instance;
	}
	
	/**
	 * @param array $options
	 */
	public function __construct($options = array()) {
		foreach($options AS $name => $value) {
        	$method = 'set' . $name;
        	if(method_exists($this,$method)) {
        		$this->$method($value);	
        	}
        }
        self::$optionsInstance = $options;
	}
	
	/**
	 * @param JO_Layout $layout
	 * @return JO_Action
	 */
	public function setLayout(JO_Layout $layout) {
		$this->layout = $layout;
		return $this;
	}
	
	/**
	 * @return JO_Layout
	 */
	public function getLayout() {
		if($this->layout == null) {
			$this->setLayout(JO_Layout::getInstance());
		}
		return $this->layout;
	}
	
	/**
	 * @param string $basePath
	 * @return JO_View
	 */
	public function setBasePath($basePath) {
		$this->basePath = $basePath;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getBasePath() {
		return $this->basePath;
	}
	
	
	/**
	 * @return string
	 */
	public function getTemplate() {
		if(JO_Registry::isRegistered('template')) {
			return JO_Registry::get('template');
		}
		return $this->template;
	}
	
	/**
	 * @param JO_Request $request
	 * @return JO_Action
	 */
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
	 * @param JO_Response $response
	 * @return JO_Action
	 */
	public function setResponse(JO_Response $response) {
		$this->response = $response;
		return $this;
	}
	
	/**
	 * @return JO_Response
	 */
	public function getResponse() {
		if($this->response == null) {
			$this->setResponse(JO_Response::getInstance());
		}
		return $this->response;
	}
	
	/**
	 * @param string|null $key
	 * @param multitype|null $value
	 * @return JO_Layout|multitype:|JO_Layout
	 */
	public function placeholder($key = null, $value= null) {
		return $this->getLayout()->placeholder($key, $value);
	}
	
	/**
	 * @param string $index
	 * @return NULL
	 */
	public function getChildren($index) {
		return isset($this->childrens[$index]) ? $this->childrens[$index] : null;
	}
	
	/**
	 * @param string $unformatted
	 * @param bool $isAction
	 * @return string
	 */
	protected function _formatName($unformatted, $isAction = false)
    {
        $segments = explode('_', $unformatted);

        foreach ($segments as $key => $segment) {
            $segment        = str_replace(array('-', '.'), ' ', strtolower($segment));
            $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
            $segments[$key] = str_replace(' ', '', ucwords($segment));
        }

        return implode('_', $segments);
    }
	
	/**
	 * @param string $unformatted
	 * @return string
	 */
	protected function _formatViewName($unformatted)
    {
        $segments = explode('_', $unformatted);

        foreach ($segments as $key => $segment) {
            $segment        = str_replace(array('-', '.'), ' ', $segment);
            $segment        = preg_replace('/[^a-z0-9 ]/i', '', $segment);
            $segments[$key] = str_replace(' ', '', $segment);
        }

        return implode('/', $segments);
    }
	
	
    public function formatControllerName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted)) . 'Controller';
    }
	
	
    public function formatActionName($unformatted)
    {
        return ucfirst($this->_formatName($unformatted)) . 'Action';
    }
	
	public function callChildren($children, $param = '') {
		$controller = $action = '';
		if(is_array($children)) { 
			$controller = $children[0];
			$action = isset($children[1]) ? $children[1] : 'index';
		} elseif(preg_match('/^([a-z0-9_]{1,})(->|::|\/)?([a-z0-9_]{1,})?$/i', $children, $match)) {
			$controller = $match[1];
			$action = (isset($match[3]) && $match[3] ? $match[3] : 'index');
		}
		
		if($controller && $action) {
			
			$class = $this->formatControllerName($controller);
			
			if(!class_exists($class, false)) {
				JO_Loader::loadClass($class);
			}
			/* @var $child JO_Action */
			$child = new $class;
			$child->initView();
			$child->isChildren(true);
			
			$result = $child->dispatch($controller, $action, $param);
			if(JO_Registry::forceGet('viewSetCallbackChildren')) {
				$result = call_user_func(JO_Registry::forceGet('viewSetCallbackChildren'), $controller, $action, $result);
			}
			
			return $result;
		}
		
		return null;
	}
	
	public function callChildrenView($children, $param = '') {
		$controller = $action = '';
		if(is_array($children)) { 
			$controller = $children[0];
			$action = isset($children[1]) ? $children[1] : 'index';
		} elseif(preg_match('/^([a-z0-9_]{1,})(->|::|\/)?([a-z0-9_]{1,})?$/i', $children, $match)) {
			$controller = $match[1];
			$action = (isset($match[3]) && $match[3] ? $match[3] : 'index');
		}
		
		if($controller && $action) {
			
			$class = $this->formatControllerName($controller);
			$action = $this->formatActionName($action);
			
			if(!class_exists($class, false)) {
				JO_Loader::loadClass($class);
			}

			/**
			 * @var $child JO_Action
			 */
			$child = new $class;
			$child->initView();
			call_user_func(array($child, $action), $param);
			$result = $child->getView();
			
			return $result;
		}
		
		return null;
	}
	
	/**
	 * @param string $script
	 * @param string $controller
	 * @param string $module
	 * @return string
	 */
	public function renderByModule($script, $controller, $module) {
		
		$old_module = $this->getRequest()->getModule();
		
		$this->getRequest()->setModule($module);
		
		$dir = JO_Action::getInstance()->getFrontController()->getDispatchDirectory();
		
		$baseDir = dirname($dir) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->getTemplate() . DIRECTORY_SEPARATOR . $this->_formatViewName($controller);
		
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Missing base view directory ("' . $baseDir . '")');
        }
        
        $scriptFile = $baseDir . DIRECTORY_SEPARATOR . $script . '.phtml';
        
		if (!file_exists($scriptFile) || !is_file($scriptFile)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Missing base view file ("' . $scriptFile . '")');
        }
        
        ob_start();
        include $scriptFile;
        $content = ob_get_contents();
        ob_get_clean();
        
        if(JO_Registry::forceGet('enable_html_minify')) {
        	$min = new JO_Minify_Html();
        	$content = $min->minify($content);
        }
        
        $this->getRequest()->setModule($old_module);
        
		return $content;
	}
	
	/**
	 * @param string $script
	 * @param string $controller
	 * @return string
	 */
	public function render($script, $controller) {

		$baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->getTemplate() . DIRECTORY_SEPARATOR . $this->_formatViewName($controller);
		
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
            require_once 'JO/Exception.php';
            return '<pre>'.new JO_Exception('Missing base view directory ("' . $baseDir . '")').'</pre>';
        }
        
        $scriptFile = $baseDir . DIRECTORY_SEPARATOR . $script . '.phtml';
        
		if (!file_exists($scriptFile) || !is_file($scriptFile)) {
            require_once 'JO/Exception.php';
            return '<pre>'.new JO_Exception('Missing base view file ("' . $scriptFile . '")').'</pre>';
        }
        
        
        
        if(is_array($this->children)) {
	        foreach($this->children AS $key => $child) { 
	        	$this->{$key} = $this->callChildren($child, $key);
	        }
        } 
        
        ob_start();
        include $scriptFile;
        $content = ob_get_contents();
        ob_get_clean();
        
        if(JO_Registry::forceGet('enable_html_minify')) {
        	$min = new JO_Minify_Html();
        	$content = $min->minify($content);
        }
        
		return $content;
	}
	
	
	/**
	 * @param string $script
	 * @param string $controller
	 */
	public function renderImage($script, $controller) {

		$base = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->getTemplate() . DIRECTORY_SEPARATOR;
		$scriptFile = $this->_formatViewName($controller) . DIRECTORY_SEPARATOR . $script;
		if (!file_exists($base . $scriptFile) || !is_file($base . $scriptFile)) {
            exit;
        }
        
        if(!file_exists(dirname(BASE_PATH . '/cache/' . $scriptFile))) {
        	mkdir(dirname(BASE_PATH . '/cache/' . $scriptFile), 0777, true);
        }
        if(!file_exists(BASE_PATH . '/cache/' . $scriptFile)) {
        	copy($base . $scriptFile, BASE_PATH . '/cache/' . $scriptFile);
        }
        
        if(filemtime($base . $scriptFile) > filemtime(BASE_PATH . '/cache/' . $scriptFile)) {
        	unlink(BASE_PATH . '/cache/' . $scriptFile);
        	copy($base . $scriptFile, BASE_PATH . '/cache/' . $scriptFile);
        }
        
        if($image = @getimagesize($base . $scriptFile)) {
	        $response = $this->getResponse();
	        $response->addHeader('Content-type: ' . $image['mime']);
	        $response->addHeader("Last-Modified: " . JO_Date::getInstance(filemtime($base . $scriptFile), "D, dd MM yy H:i:s e", true)->toString());
        	$response->setLevel(9);
	        $response->appendBody(file_get_contents($base . $scriptFile));
        }
        exit;
	}
	
	/**
	 * @param string $script
	 * @param string $controller
	 */
	public function renderCss($script, $controller) {

		$base = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->getTemplate() . DIRECTORY_SEPARATOR;
		$scriptFile = $this->_formatViewName($controller) . DIRECTORY_SEPARATOR . $script;
		if (!file_exists($base . $scriptFile) || !is_file($base . $scriptFile)) {
            exit;
        }
        
        if(!file_exists(dirname(BASE_PATH . '/cache/' . $scriptFile))) {
        	mkdir(dirname(BASE_PATH . '/cache/' . $scriptFile), 0777, true);
        }
        
        ob_start();
        include $base . $scriptFile;
        $content = ob_get_contents();
        ob_get_clean();
        
		if(!file_exists(BASE_PATH . '/cache/' . $scriptFile)) {
        	file_put_contents(BASE_PATH . '/cache/' . $scriptFile, $content);
        }
        
        if(filemtime($base . $scriptFile) > filemtime(BASE_PATH . '/cache/' . $scriptFile)) {
        	unlink(BASE_PATH . '/cache/' . $scriptFile);
        	file_put_contents(BASE_PATH . '/cache/' . $scriptFile, $content);
        }
        
        JO_Action::getInstance()->noViewRenderer(true);
        $response = $this->getResponse();
        $response->addHeader('Content-type: text/css');
//        $response->addHeader("Last-Modified: " . JO_Date::getInstance(filemtime($scriptFile), "D, dd MM yy H:i:s e", true)->toString());
//        $response->setLevel(9);
        $response->appendBody($content);
		exit;
	}
	
	/**
	 * @param string $script
	 * @param string $controller
	 */
	public function renderJs($script, $controller) {

		$base = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->getTemplate() . DIRECTORY_SEPARATOR;
		$scriptFile = $this->_formatViewName($controller) . DIRECTORY_SEPARATOR . $script;
		if (!file_exists($base . $scriptFile) || !is_file($base . $scriptFile)) {
            exit;
        }
        
        if(!file_exists(dirname(BASE_PATH . '/cache/' . $scriptFile))) {
        	mkdir(dirname(BASE_PATH . '/cache/' . $scriptFile), 0777, true);
        } 
        
        
        ob_start();
        include $base . $scriptFile;
        $content = ob_get_contents();
        ob_get_clean();
        
		if(!file_exists(BASE_PATH . '/cache/' . $scriptFile)) {
        	file_put_contents(BASE_PATH . '/cache/' . $scriptFile, $content);
        }
        
        if(filemtime($base . $scriptFile) > filemtime(BASE_PATH . '/cache/' . $scriptFile)) {
        	unlink(BASE_PATH . '/cache/' . $scriptFile);
        	file_put_contents(BASE_PATH . '/cache/' . $scriptFile, $content);
        }
        
        $response = $this->getResponse();
        $response->addHeader('Content-type: application/x-javascript; charset=utf-8');
//        $response->addHeader('Content-type: text/javascript');
//        $response->addHeader("Last-Modified: " . JO_Date::getInstance(filemtime($scriptFile), "D, dd MM yy H:i:s e", true)->toString());
//        $response->setLevel(9);
        $response->appendBody($content);
		exit;
	}
	
    /**
     * @param string $string
     */
	public function translate($string) {
    	if(JO_Registry::isRegistered('JO_Translate')) {
    		$translate = JO_Registry::get('JO_Translate');
    		if(!$translate instanceof JO_Translate) {
    			require_once 'JO/Exception.php';
                throw new JO_Exception('JO_Translate already registered in registry but is '
                                   . 'no instance of JO_Translate');
    		}
    		return $translate->translate($string);
    	}
    	return $string;
    }

}
 
