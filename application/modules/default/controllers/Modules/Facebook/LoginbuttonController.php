<?php

class Modules_Facebook_LoginbuttonController extends Helper_Controller_Default {
	
	public function indexAction($next = null) {
		
		JO_Session::clear('facebook_user_data_register');
		$this->noLayout(true);
		$settings = Model_Extensions::getSettingsPairs('facebook');
		
		if(isset($settings['facebook_module_status_enable']) && $settings['facebook_module_status_enable']) {
			if(isset($settings['facebook_login_with_facebook']) && $settings['facebook_login_with_facebook']) {
				
				$request = $this->getRequest();
				$facebook = new Helper_Modules_Facebook();
				if($facebook->checkValidAppId()) {
					
					$this->view->login_url = $facebook->getLoginUrl($next);
					
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