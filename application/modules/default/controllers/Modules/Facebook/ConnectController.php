<?php

class Modules_Facebook_ConnectController extends Helper_Controller_Default {

	public function indexAction() {
		$settings = Model_Extensions::getSettingsPairs('facebook');
		$this->noLayout(true);
		
		if(isset($settings['facebook_module_status_enable']) && $settings['facebook_module_status_enable']) {
			if(isset($settings['facebook_login_with_facebook']) && $settings['facebook_login_with_facebook']) {
		
				$request = $this->getRequest();
				$facebook = new Helper_Modules_Facebook();
				if($facebook->checkValidAppId()) {

					$connectObject = new Model_Facebook_Login();
					$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
					
					$this->view->facebook_connect = false;
					$this->view->facebook_timeline = false;
					if($user_info) {
						$this->view->facebook_connect = true;
						$this->view->facebook_timeline = $user_info['timeline'];
					}
					
					$this->view->site_name = Helper_Config::get('site_name');
					
					$this->view->facebook_connect_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_connect&action=connect' );
					$this->view->facebook_timeline_connect_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_connect&action=timeline' );
					$this->view->facebook_invites_fb = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_invates' );
					$this->view->add_pin_to_facebook_timeline = isset($settings['facebook_add_pin_to_facebook_timeline'])&&$settings['facebook_add_pin_to_facebook_timeline'];
					
					/////////
						
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
	
	public function connectAction() { 
		$request = $this->getRequest();
		$this->noLayout(true);
		$this->view->close_box = false;
		if(JO_Session::get('user[user_id]')) {
			$facebookObject = new Helper_Modules_Facebook();
			if($request->getQuery('scope')) {
				$facebookObject->scope = $request->getQuery('scope');
			} 
			$user_data = $facebookObject->getUser(true);
			$connectObject = new Model_Facebook_Login();
			$connectObject->facebook = $facebookObject->facebook;
			$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
			if($user_data) {
				
				$connectObjectCheck = new Model_Facebook_Login($user_data['id']);
				if($connectObjectCheck->row) {
					if($connectObjectCheck->row['user_id'] == JO_Session::get('user[user_id]')) {
						$enable_action = true;
					} else {
						$is_user = new Model_Users_User($connectObjectCheck->row['user_id']);
						if(!$is_user->count()) {
							$enable_action = true;
							$connectObject->deleteDataByUserId($connectObjectCheck->row['user_id']);
						} else {
							$enable_action = $this->translate('There is another profile that is associated with your facebook account');
						}
					}
				} else {
					$enable_action = true;
				}
				
				if($enable_action === true) {
					if($user_info) {
						if($connectObject->deleteDataByUserId(JO_Session::get('user[user_id]'))) {
							$this->view->close_box = true;
						} else {
							$this->view->close_box = true;
						}
					} else {
						$res = $connectObject->insert(array(
							'email' => isset($user_data['email']) ? $user_data['email'] : '',
							'user_id' => JO_Session::get('user[user_id]'),
							'oauth_uid' => $user_data['id'],
							'access_token' => $facebookObject->facebook->getAccessToken()
						));
						if($res) {
							$this->view->close_box = true;
						} else {
							$this->view->close_box = true;
						}
					}
				} else {
					JO_Session::set('connect_error', $enable_action);
					$this->view->close_box = true;
				}
			} else {
				$redirect = $facebookObject->getLoginUrl(
						WM_Router::create( $request->getBaseUrl() . '?controller=settings' ),
						'modules_facebook_connect&action=connect'
				);
				$this->redirect($redirect);
			}
		} else {
			$this->view->close_box = true;
		}
	}
	
	public function timelineAction() {
		$request = $this->getRequest();
		$this->noLayout(true);
		$this->setViewChange('connect');
		$this->view->close_box = false;
		if(JO_Session::get('user[user_id]')) {
			$facebookObject = new Helper_Modules_Facebook();
			if($request->getQuery('scope')) {
				$facebookObject->scope = $request->getQuery('scope');
			}
			$user_data = $facebookObject->getUser(true);
			$connectObject = new Model_Facebook_Login();
			$connectObject->facebook = $facebookObject->facebook;
			$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
			if($user_data) { 
				
				$permissions = $facebookObject->facebook->api(array(
				    "method"    => "users.hasAppPermission",
				    "ext_perm"   => "publish_actions",
				    "uid"       => $user_data['id']
				));
				
				if( $permissions ) {
					if($user_info) {
						if($user_info['timeline']) {
							$connectObject->updateTimelineByUserId(JO_Session::get('user[user_id]'),0);
						} else {
							$connectObject->updateTimelineByUserId(JO_Session::get('user[user_id]'),1);
						}
					}
					$this->view->close_box = true;
				} else {
					$redirect = $facebookObject->getLoginUrl(
							WM_Router::create( $request->getBaseUrl() . '?controller=settings' ),
							'modules_facebook_connect&action=timeline'
					); 
					$this->redirect($redirect);
				}
			} else {
				$redirect = $facebookObject->getLoginUrl(
						WM_Router::create( $request->getBaseUrl() . '?controller=settings' ),
						'modules_facebook_connect&action=timeline'
				);
				$this->redirect($redirect);
			}
		} else {
			$this->view->close_box = true;
		}
	}
	
	
	
}

?>