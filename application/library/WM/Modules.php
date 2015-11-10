<?php

class WM_Modules {

	public static function getList($ignore = array()) {
		
		$modules_path = rtrim(JO_Front::getInstance()->getModuleDirectory(), '/');
    	$list = glob($modules_path . '/*');
    	$modules = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			if(basename($dir) != 'admin') {
    				if(in_array(basename($dir), $ignore)) { continue; }
    				$modules[] = basename($dir);
    			}
    		}
    	} 
    	
    	return $modules;
	}

	public static function getConfig() {
		$modules_path = rtrim(JO_Front::getInstance()->getModuleDirectory(), '/');
    	$list = glob($modules_path . '/*');
    	$modules = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			if(!in_array(basename($dir), array('admin','update','install'))) {
    				if(file_exists($dir . '/config.ini')) {
    					$config = new JO_Config_Ini($dir . '/config.ini');
    					$modules[basename($dir)] = $config->toArray();
    				}
    			}
    		}
    	} 
    	return $modules;
	}

	public static function getControllers($modules_path = null) {
		if(!$modules_path) {
			$front = JO_Front::getInstance();
			$modules_path = rtrim($front->getDispatchDirectory(), '/');
		}
		
    	$list = glob($modules_path . '/*Controller.php');
    	$methods = array();
    	if($list) {
    		foreach($list AS $controller) {
    			$methods[] = strtolower( basename($controller, 'Controller.php') );    			
    		}
    	} 
    	$list = glob($modules_path . '/*/*Controller.php');
    	if($list) {
    		foreach($list AS $controller) {
    			$methods[] = strtolower( basename($controller, 'Controller.php') );
    			$methods[] = strtolower( basename(dirname($controller)) . '_' . basename($controller, 'Controller.php') );
    		}
    	}
    	$list = glob($modules_path . '/*/*/*Controller.php');
    	if($list) {
    		foreach($list AS $controller) {
    			$methods[] = strtolower( basename($controller, 'Controller.php') );
    			$methods[] = strtolower( basename(dirname($controller)) . '_' . basename($controller, 'Controller.php') );
    			$methods[] = strtolower( basename(dirname(dirname($controller))) . '_' . basename(dirname($controller)) . '_' . basename($controller, 'Controller.php') );
    		}
    	}
    	return $methods;
	}

	public static function getControllersWithFolders($modules_path = null) {
		if(!$modules_path) {
			$front = JO_Front::getInstance();
			$modules_path = rtrim($front->getDispatchDirectory(), '/');
		}
		
    	$list = glob($modules_path . '/*Controller.php');
    	$methods = array();
    	if($list) {
    		foreach($list AS $controller) {
    			$methods[] = strtolower( basename($controller, 'Controller.php') );    			
    		}
    	} 
    	$list = glob($modules_path . '/*/*Controller.php');
    	if($list) {
    		foreach($list AS $controller) {
    			$methods[] = strtolower( basename($controller, 'Controller.php') );
    			$methods[] = strtolower( basename(dirname($controller)) . '_' . basename($controller, 'Controller.php') );
    		}
    	}
    	$list = glob($modules_path . '/*/*/*Controller.php');
    	if($list) {
    		foreach($list AS $controller) {
    			$methods[] = strtolower( basename($controller, 'Controller.php') );
    			$methods[] = strtolower( basename(dirname($controller)) . '_' . basename($controller, 'Controller.php') );
    			$methods[] = strtolower( basename(dirname(dirname($controller))) . '_' . basename(dirname($controller)) . '_' . basename($controller, 'Controller.php') );
    		}
    	}
    	return $methods;
	}
	
	public static function getControllerActions($controller, $patch = null) {
		
		$actions = array();
		$front = JO_Front::getInstance();
		$filename = $front->classToFilename($controller);
		
		if(file_exists($patch . '/' . $filename)) {
			if(!class_exists($controller, false)) {
				if($patch) {
					JO_Loader::setIncludePaths(array($patch));
				}
				JO_Loader::loadClass($controller);
			}
			
			$methods = get_class_methods($controller);
			
			if($methods) {
				foreach($methods AS $methodName) {
					if ('Action' == substr($methodName, -6)) {
						$action = substr($methodName, 0, strlen($methodName) - 6);
						$actions[$action] = $action;
					}
				}
			}
		}
		
		return $actions;
	}
	
    /**
     * @param string|object $object
     * @return multitype:
     */
    public static function getControllerResources($controller) {
    	
    	$front = JO_Front::getInstance();
    	$controller_name = $front->formatControllerName($controller);
    	
    	if($front->isDispatchable($controller)) {
    		JO_Loader::setIncludePaths(array($front->getDispatchDirectory()));
    		JO_Loader::loadFile($front->classToFilename($controller_name), null, true);
    		
	    	if (version_compare(PHP_VERSION, '5.2.6') === -1) {
				$class        = new ReflectionObject(new $controller_name);
				$classMethods = $class->getMethods();
				$methodNames  = array();
	
				foreach ($classMethods as $method) {
					$methodNames[] = $method->getName();
				}
			} else {
				$methodNames = get_class_methods(new $controller_name);
			}
			
	    	$_classResources = array();
			foreach ($methodNames as $method) {
				if (6 < strlen($method) && 'Action' === substr($method, -6)) {
					$_classResources[substr($method, 0,-6)] = substr($method, 0,-6);
				}
			}
			
			return $_classResources;
    	}
    	
    	return array();
    	
    }
    
    public static function getTemplates() {
    	$template_path = JO_Layout::getInstance()->getTemplatePath();
    	$list = glob($template_path . '*');
    	$templates = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			$templates[] = basename($dir);
    		}
    	}
    	return $templates;
    }
	
}

?>