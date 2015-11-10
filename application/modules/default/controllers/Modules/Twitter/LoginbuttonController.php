<?php

class Modules_Twitter_LoginbuttonController extends Helper_Controller_Default {
	
	public function indexAction($next = null) {
		$this->noLayout(true);
		
		$settings = Model_Extensions::getSettingsPairs('twitter');
		if(isset($settings['twitter_module_status_enable']) && $settings['twitter_module_status_enable']) {
			if(isset($settings['twitter_login_with_twitter']) && $settings['twitter_login_with_twitter']) {
				
				$request = $this->getRequest();
	
				$this->view->login_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_login&action=forward&next=' . $next );
				
			} else {
				$this->noViewRenderer(true);
			}
		} else {
			$this->noViewRenderer(true);
		}
		
	}
	
}

?>