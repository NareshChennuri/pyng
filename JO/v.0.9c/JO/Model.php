<?php

class JO_Model {
	
	private $front;
	
	private $response;
	
	private $request;
	
	/**
	 * @var JO_View
	 */
	protected $view;
	
	/**
	 * @param string (url) $url
	 */
	public function redirect($url) {
		if (!headers_sent()){
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
	
	/**
	 * @param string (url) $url
	 * @return JO_Action
	 */
	public function refresh($url, $time = 5) {
		header('Refresh:'.(int)$time.';url=' . $url);
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
			$this->setResponse(JO_Response::getInstance());
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
	
	public function __call($methodName, $args) {
        require_once 'JO/Exception.php';

        throw new JO_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }
	
	/*public function __callStatic($methodName, $args) {
        require_once 'JO/Exception.php';

        throw new JO_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }*/
    
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

?>