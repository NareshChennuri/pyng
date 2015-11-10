<?php

class Modules_Facebook_RegisterbuttonController extends Helper_Controller_Default {
	
	public function indexAction($next = null) {
		$this->noLayout(true);
		
		JO_Session::clear('facebook_user_data_register');
		
		$settings = Model_Extensions::getSettingsPairs('facebook');
		if(isset($settings['facebook_module_status_enable']) && $settings['facebook_module_status_enable']) {
			if(Helper_Config::get('enable_free_registration') && isset($settings['facebook_register_with_facebook']) && $settings['facebook_register_with_facebook']) {
				
				$request = $this->getRequest();
				$facebook = new Helper_Modules_Facebook();
				if($facebook->checkValidAppId()) {
					
					$this->view->login_url = $facebook->getLoginUrl($next, 'modules_facebook_register');
					
				} else {
					$this->noViewRenderer(true);
				}
			} else {
				$this->noViewRenderer(true);
			}
		} else {
			$this->noViewRenderer(true);
		}
		
	}

}

?>