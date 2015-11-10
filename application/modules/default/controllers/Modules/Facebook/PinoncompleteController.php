<?php

class Modules_Facebook_PinoncompleteController extends Helper_Controller_Default {

	public function indexAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if($request->getRequest('type') !== 'create') {
			return;
		}
		
		$settings = Model_Extensions::getSettingsPairs('facebook');
		if(isset($settings['facebook_add_pin_to_facebook_timeline']) && $settings['facebook_add_pin_to_facebook_timeline']) {
			$user_id = $request->getParam('user');
			$pin_id = $request->getParam('pin');
			
			if($user_id && $pin_id) {
				$connectObject = new Model_Facebook_Login();
				$user_fb = $connectObject->getDataByUserId($user_id);
				if($user_fb && $user_fb['timeline']) {
					$facebook = new Helper_Modules_Facebook();
	
					$pin_url = WM_Router::pinAction($pin_id);
					$params = array('link'=>$pin_url,'access_token'=>$user_fb['access_token'], 'cb' => '');
					$response = $facebook->facebook->api('/me/feed', 'post', $params);
					
					if($settings['facebook_og_namespace'] && $settings['facebook_og_recipe']) {
						$params = array($settings['facebook_og_recipe']=>$pin_url,'access_token'=>$user_fb['access_token']);
						$response1 = $facebook->facebook->api('/me/'.$settings['facebook_og_namespace'].':'.$settings['facebook_og_recipe'],'post',$params);
					}
				}
			}
		}
	}
}

?>