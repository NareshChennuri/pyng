<?php

class Modules_Facebook_UseravatarController extends Helper_Controller_Default {
	
	public function indexAction() {
		$this->noLayout(true);
		
		$settings = Model_Extensions::getSettingsPairs('facebook');
		if(isset($settings['facebook_module_status_enable']) && $settings['facebook_module_status_enable']) {
			if(isset($settings['facebook_get_user_avatar']) && $settings['facebook_get_user_avatar']) {
		
				$request = $this->getRequest();
				$facebook = new Helper_Modules_Facebook();
				if($facebook->checkValidAppId()) {
						
					$connectObject = new Model_Facebook_Login();
					$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
						
					$this->view->facebook_connect = false;
						
					if($user_info) {
						$this->view->facebook_connect = true;
					}

					$this->view->facebook_connect_avatar = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_useravatar&action=get_avatar' );
					
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
	
	public function get_avatarAction() {
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		if(JO_Session::get('user[user_id]')) {
			
			$connectObject = new Model_Facebook_Login();
			$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
			
			$user_id = 0;
			if($user_info) {
				$user_id = $user_info['oauth_uid'];
			} else {
				$facebookObject = new Helper_Modules_Facebook();
				$user_data = $facebookObject->getUser(true);
				if($user_data && isset($user_data['id'])) {
					$user_id = $user_data['id'];
				}
			}
			
			if($user_id) {
				$ph = new WM_Facebook_Photo();
				$image = $ph->getRealUrl('http://graph.facebook.com/'.$user_id.'/picture?type=large');
				
				$image_info = @getimagesize($image);
				if( $image_info ) {
					$image_data = @file_get_contents($image);
					if($image_data) {
						JO_Session::set('upload_avatar', array(
								'name' => basename($image),
								'type' => $image_info['mime'],
								'data' => $image_data
						));
						$this->view->success = WM_Router::create( $request->getBaseUrl() . '?controller=settings&action=temporary_avatar&hash=' . microtime(true) );
					}
				}
			} else {
				$this->view->error = $this->translate('There is no established connection with facebook!');
			}
			
		}
		
		echo $this->renderScript('json');
		
	}
	
}

?>