<?php

class JO_Action {
 
	private static $_instance;
	
	private $_classMethods;
	
	private $front;
	
	private $response;
	
	private $request;
	
	private $layout;
	
	private $noLayout = false;
	
	protected $isChildren = false;
	
	private $view_change;
	
	private $response_callback = false;
	
	/**
	 * @var JO_View
	 */
	protected $view;
	
	private $_invokeArgs = array();
	
	/**
	 * @param array $options
	 * @return JO_Action
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	public function __construct() {
		$this->init();
	}
	
	/**
	 * call this method after call action
	 */
	public function postDispatch() {}
	
	/**
	 * call this method befor call action
	 */
	public function preDispatch() {}
	
	/**
	 * init /replace __construct in controller/
	 */
	public function init() {}
	
	/**
	 * @param string (url) $url
	 */
	public function redirect($url) {
		if (!headers_sent()){
			header("HTTP/1.1 301"); 
			header('Location: ' . $url);
		} else {
			echo '<script type="text/javascript">';
	        echo 'window.location.href="'.$url.'";';
	        echo '</script>';
	        echo '<noscript>';
	        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
	        echo '</noscript>';
		}
		exit;
	}
	
	/**
	 * @param string (url) $url
	 * @return JO_Action
	 */
	public function refresh($url, $time = 5) {
		header('Refresh:'.(int)$time.';url=' . $url);
	}
	
	/**
	 * @param string $view_change
	 * @return JO_Action
	 */
	public function setViewChange($view_change) {
		$this->view_change = $view_change;
		return $this;
	}
	
	/**
	 * @param string $callback
	 * @return JO_Action
	 */
	public function setCallback($callback) {
		$this->response_callback = $callback;
		return $this;
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
	 * @param JO_Front $controller
	 * @return JO_Action
	 */
	public function setFrontController(JO_Front $controller) {
		$this->front = $controller;
		return $this;
	}
	
	/**
	 * @return JO_Front
	 */
	public function getFrontController() {
		if($this->front == null) {
			$this->setFrontController(JO_Front::getInstance());
		}
		return $this->front;
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
			$response = JO_Response::getInstance();
			$this->setResponse($response);
		}
		return $this->response;
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
	 * @param string $controller
	 * @param string $action
	 */
	public function forward($controller = 'index', $action = 'index', $params = array()) {
		$this->getRequest()->setParams('forwarded', $this->getRequest()->getController())->setController($controller)->setAction($action);
		$this->getFrontController()->dispatch($controller, $action, $params);
		exit;
	}
	
	/**
	 * @param string $renderType
	 * @return unknown
	 */
	public function renderScript($renderType) {
		$view = $this->initView();
		$this->noViewRenderer(true);
		
		$class = 'JO_View_' . ucfirst(strtolower($renderType));
		
		return new $class($view);
	}
	
	/**
	 * @return JO_View
	 */
	public function getView() {
		return $this->view;
	}
	
	/**
	 * @return JO_View|JO_View
	 */
	public function initView() {
		
		require_once 'JO/View.php';
		if (isset($this->view) && ($this->view instanceof JO_View)) {
            return $this->view;
        }
		
		$dir = $this->getFrontController()->getDispatchDirectory();
		$baseDir = dirname($dir) . DIRECTORY_SEPARATOR . 'views';
		
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Missing base view directory ("' . $baseDir . '")');
        }
		
		require_once 'JO/View.php';
//        $this->view = new JO_View(array('basePath' => $baseDir));
		$this->view = JO_View::getInstance(array('basePath' => $baseDir))->resetInstance();

        return $this->view;
	}
	
	public function __call($methodName, $args) {
        require_once 'JO/Exception.php';
        if ('Action' == substr($methodName, -6)) {
            $action = substr($methodName, 0, strlen($methodName) - 6);
            throw new JO_Exception(sprintf('Action "%s" does not exist and was not trapped in __call()', $action), 404);
        }

        throw new JO_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }
	
	public function call_error($methodName) {
        require_once 'JO/Exception.php';
        if ('Action' == substr($methodName, -6)) {
            $action = substr($methodName, 0, strlen($methodName) - 6);
            return new JO_Exception(sprintf('Action "%s" does not exist and was not trapped in __call()', $action), 404);
        }

        return new JO_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }
    
    /**
     * @param bool $noLayout
     * @return JO_Action
     */
    public function noLayout($noLayout = false) {
    	$this->noLayout = $noLayout;
    	return $this;
    }
    
    
    /**
     * @param bool $param
     * @return JO_Action
     */
    public function noViewRenderer($param = true) {
    	$this->setInvokeArg('noViewRenderer', $param);
    	return $this;
    }
    
	/**
	 * @param bool $children
	 * @return JO_Action
	 */
	public function isChildren($children = false) {
    	$this->isChildren = $children;
    	return $this;
    }
    
	
	/**
	 * @param string $action
	 */
	public function dispatch($controller, $action, $param = '') {
		
		$request = $this->getRequest();
		
		if($action == 'error404') {
			$request->setParams(array(
				'controller' => 'error',
				'action' => 'error404'
			));
		} 
		
		$name = $controller;
	
		$view = $this->initView();
	
		$this->preDispatch();
	
		if (null === $this->_classMethods) {
			$this->_classMethods = get_class_methods($this);
		}
		
		$origina_action = $action;
		$script = $action;
		$action = $action . 'Action';
		
		$throw = '';
		
		if(in_array($action, $this->_classMethods)) {
//			ob_start(array(new JO_Error, 'error_handler'));
			ob_start(array(new JO_Error, 'fatal_error_handler'));
			$this->$action($param);
			
		} else {
			
			if(in_array('callRewriteAction', $this->_classMethods)) {
				ob_start(array(new JO_Error, 'fatal_error_handler'));
				$this->callRewriteAction($origina_action);
			} else {
			
				$displayExceptions = JO_Front::getInstance()->getParam('displayExceptions');
				if($displayExceptions) {
					$throw = $this->call_error($action);
					$layout = JO_Layout::getInstance();
					$layout->content = '<pre>'.$throw.'<pre>';
					$response = $layout->response();
					return $this->getResponse()->appendBody($response);
				} else {
					
					if($this->getRequest()->getForwarded() != 'error') {
						$this->forward('error', 'error404');
					} else {
						$this->forward('JO_Action', 'error404');
					}
					
				}
			
			}
		}
		
		$this->postDispatch();

		if (!$this->getInvokeArg('noViewRenderer')) {
            $layout = JO_Layout::getInstance();
            
            if($this->view_change) {
            	$script = $this->view_change;
            }
            
			$layout->content = $view->render($script, ($script == 'error404' ? 'error' : $name));
			
			if(JO_Registry::forceGet('viewSetCallback')) {
				$layout->content = call_user_func(JO_Registry::forceGet('viewSetCallback'), $layout->content);
			} elseif($this->response_callback) {
				$layout->content = call_user_func($this->response_callback, $layout->content);
			}
			
			if($this->isChildren) {
				return $layout->content;
			} elseif($this->noLayout) {
				return $this->getResponse()->appendBody($layout->content);
			} else {
				$response = $layout->response();
			
				if($this->response_callback) {
					$response = call_user_func($this->response_callback, $response);
				}
				if(JO_Registry::isRegistered('static_cache_options') && JO_Registry::forceGet('static_cache_enable')) {
					$options = (array)unserialize(JO_Registry::get('static_cache_options'));
					$cache_object = new JO_Cache_Static($options);
					$cache_object->add(false, $response);
				} 
				return $this->getResponse()->appendBody($response);	
			}
			
        }
		
	}
	
	/**
	 * error 404 action
	 */
	public function error404Action() {
		$this->getResponse()->addHeader("HTTP/1.0 404 Not Found");
	}
	
	/**
	 * @param array $args
	 * @return JO_Action
	 */
	public function setInvokeArgs(array $args = array()) {
        $this->_invokeArgs = $args;
        return $this;
    }
    
	/**
	 * @param string $key
	 * @param multitype $value
	 * @return JO_Action
	 */
	public function setInvokeArg($key, $value) {
        $this->_invokeArgs[$key] = $value;
        return $this;
    }

    /**
     * @return multitype:
     */
    public function getInvokeArgs() {
        return $this->_invokeArgs;
    }

    /**
     * @param string $key
     * @return multitype:|NULL
     */
    public function getInvokeArg($key) {
        if (isset($this->_invokeArgs[$key])) {
            return $this->_invokeArgs[$key];
        }

        return null;
    }
    
    /**
     * @param string $key
     * @return NULL|JO_Registry
     */
    public function bootstrap($key) {
    	if(JO_Registry::isRegistered($key)) {
    		return JO_Registry::get($key);
    	}
    	return null;
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
 
