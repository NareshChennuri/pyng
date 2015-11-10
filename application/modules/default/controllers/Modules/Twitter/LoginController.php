<?php

class Modules_Twitter_LoginController extends Helper_Controller_Default {
	
	public function forwardAction() {
		JO_Session::clear('user_info_twitteroauth');
		JO_Session::clear('user_info_twitteroauth');
		JO_Session::clear('access_token_twitteroauth');
		JO_Session::get('twitter_oauth');
		
		$request = $this->getRequest();
		
		$next = '';
		if($request->issetQuery('next')) {
			$next = '&next=' . urlencode(html_entity_decode($request->getQuery('next')));
		}
		
		if($request->getQuery('twitter_fnc')) {
			$next .= '&twitter_fnc=' . $request->getQuery('twitter_fnc');
		}
		
		$twitteroauth = new Helper_Modules_Twitter();
		$request_token = $twitteroauth->getRequestToken( WM_Router::create( $request->getBaseUrl() . '?controller=modules_twitter_login' . $next ) );
		$request_token_url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
		if($twitteroauth->http_code == 200) {
			if(isset($request_token['oauth_token']) && $request_token['oauth_token_secret']) {
				JO_Session::set('twitter_oauth', $request_token);
				$this->redirect( $request_token_url );
			}
		}
		
		$this->setViewChange('no_account');
			
		$page_login_trouble = Model_Pages::getPage( Helper_Config::get('page_login_trouble') );
		if($page_login_trouble) {
			$this->view->page_login_trouble = array(
					'title' => $page_login_trouble['title'],
					'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
			);
		}
		
	}
	
	public function indexAction() {

		$request = $this->getRequest();
		
		$settings = Model_Extensions::getSettingsPairs('twitter');
		if(!isset($settings['twitter_login_with_twitter']) || !$settings['twitter_login_with_twitter']) {
			$this->forward('error', 'error404');
		} elseif(!isset($settings['twitter_module_status_enable']) || !$settings['twitter_module_status_enable']) {
			$this->forward('error', 'error404');
		}
		
		$twitteroauth = new Helper_Modules_Twitter(null, null, JO_Session::get('twitter_oauth[oauth_token]'), JO_Session::get('twitter_oauth[oauth_token_secret]'));
		$user_data = JO_Session::get('user_info_twitteroauth');
		if(!$user_data || (isset($user_data->errors) && $user_data->errors)) {
			$access_token = $twitteroauth->getAccessToken($request->getQuery('oauth_verifier'));
			$user_data = $twitteroauth->get('account/verify_credentials');
			JO_Session::set('user_info_twitteroauth', $user_data);
			JO_Session::set('access_token_twitteroauth', $access_token);
		} else {
			$user_data = JO_Session::get('user_info_twitteroauth');
		}

		
		
		if((!isset($user_data->error) || !$user_data->error) && $user_data->id) {
			
			$modelLogin = new Model_Twitter_Login($user_data->id);
			if($modelLogin->row) {
				$userObject = new Model_Users_User($modelLogin->row['user_id']);
				if($userObject->count()) {
					if(JO_Session::get('user[user_id]')) {
						if($modelLogin->row['user_id'] == JO_Session::get('user[user_id]')) {
							JO_Session::set('user', $userObject->toArray());
						}
					} else {
						JO_Session::set('user', $userObject->toArray());
					}
					new Model_Users_Edit($modelLogin->row['user_id'], array(
							'last_login' => new JO_Db_Expr('NOW()')
					));
					$up = $modelLogin->update(array(
						'twitter_oauth_token' => JO_Session::get('twitter_oauth[oauth_token]'),
						'twitter_oauth_token_secret' => JO_Session::get('twitter_oauth[oauth_token_secret]'),
						'username' => $user_data->screen_name
					));
					//JO_Session::clear('user_info_twitteroauth');
					//JO_Session::clear('access_token_twitteroauth');
					//JO_Session::get('twitter_oauth');
					if($request->getQuery('next')) {
						$this->redirect( ( urldecode($request->getQuery('next')) ) );
					} else {
						$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
					}
				}
			}
				
			if((Helper_Config::get('enable_free_registration') || $request->getQuery('twitter_fnc') == 'connect') && $request->getQuery('next')) {
				$full = html_entity_decode($request->getFullUrl());
				$next = parse_url(urldecode( $full ));
				if(isset($next['query'])) {
					parse_str($next['query'], $query);
					if(isset($query['next']) && $query['next'] && isset($query['twitter_fnc']) && $query['twitter_fnc']) {
						$this->redirect( $query['next'] );
					} elseif(Helper_Config::get('enable_free_registration')) {
						$this->forward('modules_twitter_register');
					}
				}
			}

			$this->setViewChange('no_account');
			
			$page_login_trouble = Model_Pages::getPage( Helper_Config::get('page_login_trouble') );
			if($page_login_trouble) {
				$this->view->page_login_trouble = array(
						'title' => $page_login_trouble['title'],
						'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
				);
			}
				
			
			
		} else {
			
			if($request->getQuery('twitter_fnc') == 'connect' && $request->getQuery('next')) {
				$full = html_entity_decode($request->getFullUrl());
				$next = parse_url(urldecode( $full ));
				if(isset($next['query'])) {
					parse_str($next['query'], $query);
					if(isset($query['next']) && $query['next'] && isset($query['twitter_fnc']) && $query['twitter_fnc']) {
						$this->redirect( $query['next'] );
					}
				}
			}
			
			//not session
			$this->setViewChange('error_login');
				
			$page_login_trouble = Model_Pages::getPage( Helper_Config::get('page_login_trouble') );
			if($page_login_trouble) {
				$this->view->page_login_trouble = array(
						'title' => $page_login_trouble['title'],
						'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
				);
			}
		}
		
	}
	
	

}

?>