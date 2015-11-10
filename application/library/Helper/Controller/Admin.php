<?php

class Helper_Controller_Admin extends JO_Action {
	
	public function __construct() {
		$request = JO_Request::getInstance();
		
		parent::__construct();
		
		if(Model_Allowips::getTotalWords()) {
			if(!Model_Allowips::getTotalWords(array('filete_ip' => $request->getClientIp()))) {
				if(!in_array($request->getController(), array('login','error'))) {
					$this->forward('error', 'noPermission');
				}
			}
		}
		
		//set default timezone if is not set
		if( !ini_get('date.timezone') ) {
			ini_set('date.timezone', 'UTC');
		}
		
		WM_Users::initSession(JO_Session::get('user[user_id]'));
		if(!JO_Session::get('user[user_id]')) {
			JO_Session::set('user', array('user_id' => 0));
		}
		
		//admin check login
		$login_page = $request->getController() != 'login';
		if(JO_Session::get('user[is_developer]')) {
			$login_page = false;
		} else if(JO_Session::get('user[is_admin]')) {
			$login_page = false;
		}
		if(in_array($request->getController(), array('login','error'))) {
			$login_page = false;
		}
		if($login_page) {
			$this->forward('login', 'index');
		}
		
		//admin top menu
		Helper_Config::set('adminmenupermisions', WM_Users::initPermision());
		
		//no permisions
		$controller_name = JO_Front::getInstance()->formatControllerName($request->getController());
		if(!class_exists($controller_name, false)) {
			JO_Loader::loadFile(APPLICATION_PATH . '/modules/' . $request->getModule() . '/controllers/' . JO_Front::getInstance()->classToFilename($controller_name));
		}
		if(method_exists($controller_name, 'config')) {
			$data = call_user_func(array($controller_name, 'config'));
			if(isset($data['has_permision']) && $data['has_permision'] && !WM_Users::allow('read', $request->getController())) {
				$this->forward('error', 'noPermission');
			}
		}
		
		WM_Rebuild::getInformation();
		
		WM_Licensecheck::checkIt();
	
		
	}

}

?>