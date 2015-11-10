<?php

class ErrorController extends Helper_Controller_Admin {
	
	public function noPermissionAction() {
		
		$request = JO_Request::getInstance();
		
		$forwarded = $request->getParam('forwarded');
		if(!$forwarded) {
			$forwarded = $request->getController();
		}
		
		$controller_name = JO_Front::getInstance()->formatControllerName($forwarded);
	
		if(!class_exists($controller_name, false)) {
			JO_Loader::loadFile(APPLICATION_PATH . '/modules/' . $request->getModule() . '/controllers/' . JO_Front::getInstance()->classToFilename($controller_name));
		}
		
		if(method_exists($controller_name, 'config')) {
			$data = call_user_func(array($controller_name, 'config'));
			if(isset($data['name']) && $data['name']) {
				$controller_name = $data['name'];
			}
		}
		
		$this->view->moduleName = $controller_name;
		
		$this->view->fullUrl = $request->getFullUrl();
		
	}
	
	public function noLicenseAction() { 
		
		$this->view->errors = Helper_Config::get('LicenseError');

	}
	
}

?>