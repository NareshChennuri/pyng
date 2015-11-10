<?php

class Modules_Instagram_LoginController extends Helper_Controller_Default {
	
	public $user_data = null;
	
	private function initInstagram(&$instagramoauth) {
		
		if($this->user_data) {
			return true;
		}
		
		$instagramoauth = new Helper_Modules_Instagram();
		
		$InstagramAccessToken = $instagramoauth->getAccessToken();
		$user_data = JO_Json::decode($instagramoauth->getUser(), true);
		
		if( isset($user_data['meta']['code']) && $user_data['meta']['code'] == 200 ) {
			JO_Session::set('InstagramAccessToken', $InstagramAccessToken);
			$this->user_data = $user_data['data'];
		} elseif($InstagramAccessToken) {
			JO_Session::set('InstagramAccessToken', $InstagramAccessToken);
			$instagramoauth->setAccessToken($InstagramAccessToken);
		} elseif(JO_Session::get('InstagramAccessToken')) {
			$instagramoauth->setAccessToken(JO_Session::get('InstagramAccessToken'));
		}
		
		if(!$this->user_data) {
			$user_data = JO_Json::decode($instagramoauth->getUser(), true);
			if( isset($user_data['meta']['code']) && $user_data['meta']['code'] == 200 ) {
				$this->user_data = $user_data['data'];
			}
		}
		
		JO_Session::set('instagram_user_data', $this->user_data);
		
		return $this->user_data ? true : false;
		
	}
	
	public function forwardAction() {
		
		$request = $this->getRequest();
		
		JO_Session::clear('instagram_user_data');
		JO_Session::clear('instagram_fnc');
		JO_Session::clear('instagram_next');
		
		/**
		 * @var Helper_Modules_Instagram
		 */
		$instagramoauth = null;
		$this->initInstagram($instagramoauth);
		
		
		if($request->issetQuery('next')) {
			JO_Session::set('instagram_next', urlencode(html_entity_decode($request->getQuery('next'))));
		}
			
		if($request->getQuery('instagram_fnc')) {
			JO_Session::set('instagram_fnc', $request->getQuery('instagram_fnc'));
		}
		
		if(!$this->user_data) {
			$instagramoauth->openAuthorizationUrl();
		} else {
			if(JO_Session::get('instagram_fnc')) {
				$this->redirect( urldecode(JO_Session::get('instagram_next')) );
			} else {
				$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_login') );
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
		
		$settings = Model_Extensions::getSettingsPairs('instagram');
		if(!isset($settings['instagram_login_with_instagram']) || !$settings['instagram_login_with_instagram']) {
			$this->forward('error', 'error404');
		} elseif(!isset($settings['instagram_module_status_enable']) || !$settings['instagram_module_status_enable']) {
			$this->forward('error', 'error404');
		}
		
		$instagramoauth = null;
		$this->initInstagram($instagramoauth);
		
		$InstagramAccessToken = JO_Session::get('InstagramAccessToken');
		$user_data = $this->user_data;
		
		
		$settings = Model_Extensions::getSettingsPairs('instagram');
		if(!isset($settings['instagram_login_with_instagram']) || !$settings['instagram_login_with_instagram']) {
			$this->forward('error', 'error404');
		}
		
		if($user_data && isset($user_data['id']) && $user_data['id']) {
			
			$modelLogin = new Model_Instagram_Login($user_data['id']);
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
						'access_token' => $InstagramAccessToken,
						'username' => $user_data['username']
					));
					$next = JO_Session::get('instagram_next');
// 					JO_Session::clear('instagram_user_data');
// 					JO_Session::clear('instagram_next');
// 					JO_Session::clear('instagram_fnc');
					if($next) {
						$this->redirect( ( urldecode($next) ) );
					} else {
						$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
					}
				}
			}
				
			if((Helper_Config::get('enable_free_registration') || JO_Session::get('instagram_fnc') == 'connect') && JO_Session::get('instagram_next') && JO_Session::get('instagram_fnc')) {
				$this->redirect( urldecode(JO_Session::get('instagram_next')) );
			}
			
			if(Helper_Config::get('enable_free_registration')) {
				$this->forward('modules_instagram_register', 'index', $user_data);
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
			
			if(JO_Session::get('instagram_fnc') == 'connect' && JO_Session::get('instagram_next')) {
				$this->redirect( urldecode(JO_Session::get('instagram_next')) );
			} elseif(Helper_Config::get('enable_free_registration')) {
				$this->forward('modules_instagram_register');
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