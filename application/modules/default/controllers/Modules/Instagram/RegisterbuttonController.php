<?php

class Modules_Instagram_RegisterbuttonController extends Helper_Controller_Default {
	
	public function indexAction($next = null) {
		$this->noLayout(true);

		$settings = Model_Extensions::getSettingsPairs('instagram');
		if(isset($settings['instagram_module_status_enable']) && $settings['instagram_module_status_enable']) {
			if(Helper_Config::get('enable_free_registration') && isset($settings['instagram_register_with_instagram']) && $settings['instagram_register_with_instagram']) {
				
				$request = $this->getRequest();
					
				$next = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_register');
					
				$this->view->login_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_login&action=forward&instagram_fnc=register&next=' . $next );
				
			} else {
				$this->noViewRenderer(true);
			}
		} else {
			$this->noViewRenderer(true);
		}
		
	}

}

?>