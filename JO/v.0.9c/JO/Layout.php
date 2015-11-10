<?php

class JO_Layout {

	private static $_instance;
	
	private $layoutPath;
	
	private $layout;
	
	private $placeholder = array();
	
	public $content;
	
	protected $template = 'default';
	
	/**
	 * @var JO_Request
	 */
	private $request;
	
	/**
	 * @param array $options
	 * @return JO_Layout
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
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
	}
	
	/**
	 * @param string $key
	 * @param multitype $value
	 * @return JO_Layout
	 */
	public function __set($key, $value) {
		$this->{$key} = $value;
		return $this;
	}
	
	/**
	 * @param string $key
	 * @return Ambigous <NULL, multitype:>
	 */
	public function __get($key) {
		return isset($this->{$key}) ? $this->{$key} : null;
	}
	
	/**
	 * @param string|null $key
	 * @param multitype|null $value
	 * @return JO_Layout|multitype:|JO_Layout
	 */
	public function placeholder($key = null, $value= null) {
		if($key == 'content' && $value == null) {
			return $this->content;
		} elseif($key == 'content' && $value != null) {
			include_once 'JO/Exception.php';
			throw new JO_Exception('Key content is reserved');
		}
		$hash = md5(is_array($value) ? serialize($value) : $value);
		if($key == null && $value == null) {
			return $this;
		} elseif($value == null && isset($this->placeholder[$key])) {
			return implode('', $this->placeholder[$key]);
		} elseif($value != null && $key != null) {
			if(isset($this->placeholder[$key])) {
				if(!isset($this->placeholder[$key][$hash])) {
					$this->placeholder[$key][$hash] = "\n" . $value;	
				}
			} else {
				$this->placeholder[$key][$hash] = $value;
			}
			return $this;
		}
	}
	
	/**
	 * @param string $value
	 * @return JO_Layout
	 */
	public function setLayoutPath($value) {
		$this->layoutPath = $value;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getLayoutPath() {
		return $this->layoutPath;
	}
	
	/**
	 * @return string
	 */
	public function getTemplatePath() {
		return $this->getLayoutPath() . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * @param string $value
	 * @return JO_Layout
	 */
	public function setLayout($value) {
		$this->layout = $value;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getLayout() {
		return $this->layout;
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
	 * @return string
	 */
	public function getTemplate() {
		if(JO_Registry::isRegistered('template')) {
			return JO_Registry::get('template');
		}
		return $this->template;
	}
	
	/**
	 * @return string
	 */
	public function response() {
		
		$model = JO_Request::getInstance()->getModule();
		
		$baseDir = $this->getLayoutPath() . DIRECTORY_SEPARATOR . $this->getTemplate() . DIRECTORY_SEPARATOR . $model;
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Missing base layout directory ("' . $baseDir . '")');
        }
        
        $script = $baseDir . DIRECTORY_SEPARATOR . $this->getLayout() . '.phtml';

		if (!file_exists($script) || !is_file($script)) {
            require_once 'JO/Exception.php';
            throw new JO_Exception('Missing base layout file ("' . $script . '")');
        }
        
        ob_start();
        include $script;
        $content = ob_get_contents();
        ob_get_clean();
        
        if(JO_Registry::forceGet('enable_html_minify')) {
        	$min = new JO_Minify_Html();
        	$content = $min->minify($content);
        }
        
		return $content;
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
 
