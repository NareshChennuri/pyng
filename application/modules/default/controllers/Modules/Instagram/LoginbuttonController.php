<?php

class Modules_Instagram_LoginbuttonController extends Helper_Controller_Default {
	
	public function indexAction($next = null) {
		$this->noLayout(true);
		
		$settings = Model_Extensions::getSettingsPairs('instagram');
		if(isset($settings['instagram_module_status_enable']) && $settings['instagram_module_status_enable']) {
			if(isset($settings['instagram_login_with_instagram']) && $settings['instagram_login_with_instagram']) {
					
				$request = $this->getRequest();
				
				$this->view->login_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_login&action=forward&next=' . $next );
					
			} else {
				$this->noViewRenderer(true);
			}
		} else {
			$this->noViewRenderer(true);
		}
		
	}

}

?>