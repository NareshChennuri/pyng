<?php

class Modules_Twitter_RegisterbuttonController extends Helper_Controller_Default {
	
	public function indexAction($next = null) {
		$this->noLayout(true);

		$settings = Model_Extensions::getSettingsPairs('twitter');
		if(isset($settings['twitter_module_status_enable']) && $settings['twitter_module_status_enable']) {
			if(Helper_Config::get('enable_free_registration') && isset($settings['twitter_login_with_twitter']) && $settings['twitter_login_with_twitter']) {
				
				$request = $this->getRequest();
				
				$next = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_register');
				
				$this->view->login_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_login&action=forward&twitter_fnc=register&next=' . $next );
					
			} else {
				$this->noViewRenderer(true);
			}
		} else {
			$this->noViewRenderer(true);
		}
	}

}

?>