<?php

class Modules_Instagram_RegisterController extends Helper_Controller_Default {
	
	public function indexAction($user_data = null) {

		$request = $this->getRequest();
		
		if(!Helper_Config::get('enable_free_registration')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=landing' ) );
		}
		
		if(JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
		}
		
		$settings = Model_Extensions::getSettingsPairs('instagram');
		if(!isset($settings['instagram_register_with_instagram']) || !$settings['instagram_register_with_instagram']) {
			$this->forward('error', 'error404');
		} elseif(!isset($settings['instagram_module_status_enable']) || !$settings['instagram_module_status_enable']) {
			$this->forward('error', 'error404');
		}
		
		$InstagramAccessToken = JO_Session::get('InstagramAccessToken');
		$user_data = $user_data ? $user_data : JO_Session::get('instagram_user_data');
		
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
					$up = $modelLogin->update(array(
							'access_token' => $InstagramAccessToken,
							'username' => $user_data['username']
					));
					$next = JO_Session::get('instagram_next');
					if($next) {
						$this->redirect( ( urldecode($next) ) );
					} else {
						$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
					}
				}
			}
			
			
			if(isset($user_data['profile_picture']) && @getimagesize($user_data['profile_picture'])) {
				$image = $user_data['profile_picture'];
				$user_data['avatar'] = $image;
			} else {
				$image = 'uploads' . Helper_Config::get('no_image');
				$user_data['avatar'];
			}
			
			$names = explode(' ',$user_data['full_name']);
			$user_data['first_name'] = array_shift($names);
			$user_data['last_name'] = implode(' ', $names);
			
			
			if($request->isPost()) {
				$validate = new Helper_Validate();
				$validate->_set_rules($request->getPost('username'), $this->translate('Username'), 'not_empty;min_length[3];max_length[100];username');
				$validate->_set_rules($request->getPost('email'), $this->translate('Email'), 'not_empty;min_length[5];max_length[100];email');
				$validate->_set_rules($request->getPost('password'), $this->translate('Password'), 'not_empty;min_length[4];max_length[30]');
			
				if($validate->_valid_form()) {
					if( Model_Users::isExistEmail($request->getPost('email')) ) {
						$validate->_set_form_errors( $this->translate('This e-mail address is already used') );
						$validate->_set_valid_form(false);
					}
					if( Model_Users::isExistUsername($request->getPost('username')) ) {
						$validate->_set_form_errors( $this->translate('This username is already used') );
						$validate->_set_valid_form(false);
					}
				}
			
				if($validate->_valid_form()) {
						
					$result = new Model_Users_Create(array(
							'avatar' => $user_data['avatar'],
							'website' => (isset($user_data['website']) ? $user_data['website'] : ''),
							'username' => $request->getPost('username'),
							'firstname' => isset($user_data['first_name'])?$user_data['first_name']:'',
							'lastname' => isset($user_data['last_name'])?$user_data['last_name']:'',
							'email' => $request->getPost('email'),
							'password' => $request->getPost('password')
					));
						
					if(!$result->error) {
						$userObject = new Model_Users_User($result->user_id);
						JO_Session::set('user', $userObject->toArray());
			
						$modelObject = new Model_Instagram_Login();
						$modelObject->insert(array(
								'username' => $user_data['username'],
								'user_id' => $result->user_id,
								'oauth_uid' => $user_data['id'],
								'access_token' => JO_Session::get('InstagramAccessToken')
						));
						$next = JO_Session::get('instagram_next');
						JO_Session::clear('instagram_user_data');
						JO_Session::clear('instagram_next');
						JO_Session::clear('instagram_fnc');
						if($next) {
							$this->redirect( ( urldecode($next) ) );
						} else {
							$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
						}
					} else {
						$this->view->error = implode('<br />', $result->error);//$this->translate('There was a problem with the record. Please try again!');
					}
						
				} else {
					$this->view->error = $validate->_get_error_messages();
				}
			
			}
			
			$this->view->avatar = $image;
	
			$this->view->baseUrl = $request->getBaseUrl();
			
			if($request->issetPost('email')) {
				$this->view->email = $request->getPost('email');
			} else {
				if(isset($user_data['email'])) {
					$this->view->email = $user_data['email'];
				} else {
					$this->view->email = '';
				}
			}
		
			if($request->issetPost('username')) {
				$this->view->username = $request->getPost('username');
			} else {
				if(isset($user_data['username'])) {
					$this->view->username = $user_data['username'];
				} else {
					$this->view->username = '';
				}
			}
			
			$this->view->password = $request->getPost('password');
			
		} else {
			//not session
			$this->setViewChange('../login/error_login');
				
			$page_login_trouble = Model_Pages::getPage( Helper_Config::get('page_login_trouble') );
			if($page_login_trouble) {
				$this->view->page_login_trouble = array(
						'title' => $page_login_trouble['title'],
						'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
				);
			}
		}
		
		if($this->getLayout()->meta_title) {
			$this->getLayout()->placeholder('title', ($this->getLayout()->meta_title . ' - ' . Helper_Config::get('meta_title')));
		} else {
			$this->getLayout()->placeholder('title', Helper_Config::get('meta_title'));
		}
  
		if($this->getLayout()->meta_description) {
			$this->getLayout()->placeholder('description', $this->getLayout()->meta_description);
		} else {
			$this->getLayout()->placeholder('description', Helper_Config::get('meta_description'));
		}
  
		if($this->getLayout()->meta_keywords) {
			$this->getLayout()->placeholder('keywords', $this->getLayout()->meta_keywords);
		} else {
			$this->getLayout()->placeholder('keywords', Helper_Config::get('meta_keywords'));
		}
		
		$this->getLayout()->placeholder('site_name', Helper_Config::get('site_name'));
		
		$this->view->site_name = Helper_Config::get('site_name');
		$this->view->meta_title = Helper_Config::get('meta_title');
		
		$this->getLayout()->placeholder('google_analytics', html_entity_decode(Helper_Config::get('google_analytics'), ENT_QUOTES, 'utf-8'));
		
		$this->view->baseUrl = $request->getBaseUrl();
		$this->view->site_logo = $request->getBaseUrl() . 'data/images/logo.png';
		if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
		    $this->view->site_logo = $request->getBaseUrl() . 'uploads' . Helper_Config::get('site_logo'); 
		}
		
		$this->view->login = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		
		$this->view->check_username = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_register&action=check_username' );
		$this->view->check_email = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_register&action=check_email' );
		
		
		$this->view->children = array(
       		'header_part' 	=> 'layout/header_part',
       		'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
	public function check_emailAction(){
		
		$request = $this->getRequest();
		
		$username = trim($request->getPost('raw'));
		
		if(strlen($username) < 5) {
//			$this->view->error = $this->translate('Please use at least 5 characters');
		} else {
			$validate = new Helper_Validate();
			$validate->_set_rules($username, $this->translate('Email'), 'not_empty;min_length[5];max_length[100];email');
			if($validate->_valid_form()) {
				if( Model_Users::isExistEmail($username) ) {
					$validate->_set_form_errors( $this->translate('This email is already used') );
					$validate->_set_valid_form(false);
				}
			}
			
			if($validate->_valid_form()) {
				$this->view->success = $this->translate('Available');
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
		}
		
		
		echo $this->renderScript('json');
	}
	
	public function check_usernameAction(){
		
		$request = $this->getRequest();
		
		$username = trim($request->getPost('raw'));
		
		if(strlen($username) < 3) {
			$this->view->error = $this->translate('Please use at least 3 characters');
		} else {
			$validate = new Helper_Validate();
			$validate->_set_rules($username, $this->translate('Username'), 'not_empty;min_length[3];max_length[100];username');
			if($validate->_valid_form()) {
				if( Model_Users::isExistUsername($username) ) {
					$validate->_set_form_errors( $this->translate('This username is already used') );
					$validate->_set_valid_form(false);
				}
			}
			
			if($validate->_valid_form()) {
				$this->view->success = $this->translate('Available');
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
		}
		
		
		echo $this->renderScript('json');
	}
	
	

}

?>