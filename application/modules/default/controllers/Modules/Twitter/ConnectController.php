<?php

class Modules_Twitter_ConnectController extends Helper_Controller_Default {

	public function indexAction() {
		$settings = Model_Extensions::getSettingsPairs('twitter');
		if(isset($settings['twitter_module_status_enable']) && $settings['twitter_module_status_enable']) {
			if(isset($settings['twitter_login_with_twitter']) && $settings['twitter_login_with_twitter']) {
		
				$request = $this->getRequest();
				
				$next = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_connect&action=connect' );
				$next2 = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_connect&action=twit' );
				
				$this->view->twitter_connect_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_login&action=forward&twitter_fnc=connect&next=' . $next );
				$this->view->twitter_twit_connect_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_login&action=forward&twitter_fnc=connect&next=' . $next2 );
				
				$this->view->add_pin_to_twitter = isset($settings['twitter_add_pin_to_twitter'])&&$settings['twitter_add_pin_to_twitter'];
				
				$this->view->site_name = Helper_Config::get('site_name');
				
				$this->view->twitter_connect = false;
				$connectObject = new Model_Twitter_Login();
				$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
				if($user_info) {
					$this->view->twitter_connect = true;
					$this->view->twitter_twit = $user_info['twit'];
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
			
			$twitteroauth = new Helper_Modules_Twitter(null, null, JO_Session::get('twitter_oauth[oauth_token]'), JO_Session::get('twitter_oauth[oauth_token_secret]'));
			$user_data = JO_Session::get('user_info_twitteroauth');
			if(!$user_data || $user_data->errors) {
				$access_token = $twitteroauth->getAccessToken($request->getQuery('oauth_verifier'));
				$user_data = $twitteroauth->get('account/verify_credentials');
				JO_Session::set('user_info_twitteroauth', $user_data);
				JO_Session::set('access_token_twitteroauth', $access_token);
			} else {
				$user_data = JO_Session::get('user_info_twitteroauth');
			}
			
			$connectObject = new Model_Twitter_Login();
		
			$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
			if($user_data) {
				
				$connectObjectCheck = new Model_Twitter_Login($user_data->id);
				
				if($connectObjectCheck->row) {
					if($connectObjectCheck->row['user_id'] == JO_Session::get('user[user_id]')) {
						$enable_action = true;
					} else {
						$is_user = new Model_Users_User($connectObjectCheck->row['user_id']);
						if(!$is_user->count()) {
							$enable_action = true;
							$connectObject->deleteDataByUserId($connectObjectCheck->row['user_id']);
						} else {
							$enable_action = $this->translate('There is another profile that is associated with your twitter account');
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
								'twitter_oauth_token' => JO_Session::get('twitter_oauth[oauth_token]'),
								'twitter_oauth_token_secret' => JO_Session::get('twitter_oauth[oauth_token_secret]'),
								'username' => $user_data->screen_name,
								'user_id' => JO_Session::get('user[user_id]'),
								'oauth_uid' => $user_data->id
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
				$twitteroauth = new Helper_Modules_Twitter();
				$next = WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_connect&action=connect' );
				$request_token = $twitteroauth->getRequestToken( WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_login&action=forward&twitter_fnc=connect&next=' . $next ) );
				$request_token_url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
				if($twitteroauth->http_code == 200) {
					if(isset($request_token['oauth_token']) && $request_token['oauth_token_secret']) {
						JO_Session::set('twitter_oauth', $request_token);
						$this->redirect( $request_token_url );
					}
				}
			}
		}
		$this->view->close_box = true;
		
	}
	
	public function twitAction() {
		
		$request = $this->getRequest();
		$this->noLayout(true);
		$this->setViewChange('connect');
		$this->view->close_box = false;
		if(JO_Session::get('user[user_id]')) {
			
			$connectObject = new Model_Twitter_Login();
		
			$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
			
			if($user_info) {
				$user_data = JO_Session::get('user_info_twitteroauth');
				if($user_info['twit']) {
					$res = $connectObject->updateTwitByUserId(JO_Session::get('user[user_id]'),array(
						'twit' => 0,
						'twitter_oauth_token' => JO_Session::get('twitter_oauth[oauth_token]'),
						'twitter_oauth_token_secret' => JO_Session::get('twitter_oauth[oauth_token_secret]'),
						'username' => $user_data->screen_name
					));
				} else {
					$res = $connectObject->updateTwitByUserId(JO_Session::get('user[user_id]'),array(
						'twit' => 1,
						'twitter_oauth_token' => JO_Session::get('twitter_oauth[oauth_token]'),
						'twitter_oauth_token_secret' => JO_Session::get('twitter_oauth[oauth_token_secret]'),
						'username' => $user_data->screen_name
					));
				}
			}
		}
		JO_Session::clear('user_info_twitteroauth');
		JO_Session::clear('access_token_twitteroauth');
		JO_Session::get('twitter_oauth');
		$this->view->close_box = true;
		
	}
	
}

?>