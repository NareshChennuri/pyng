<?php

class Modules_Facebook_RegisterController extends Helper_Controller_Default {
	
	public function indexAction() {

		$request = $this->getRequest();
		
		if(!Helper_Config::get('enable_free_registration')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=landing' ) );
		}
		
		if(JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
		}
		
		$settings = Model_Extensions::getSettingsPairs('facebook');
		if(!isset($settings['facebook_register_with_facebook']) || !$settings['facebook_register_with_facebook']) {
			$this->forward('error', 'error404');
		} elseif(!isset($settings['facebook_module_status_enable']) || !$settings['facebook_module_status_enable']) {
			$this->forward('error', 'error404');
		}
		
		$user_data = JO_Session::get('facebook_user_data_register');
		
		if(!$user_data) {
			$facebook = new Helper_Modules_Facebook();
			
			$user_data = $facebook->getUser(true);
			if($user_data) {
				$user_data['access_token'] = $facebook->facebook->getAccessToken();
			}
		}
		
		if($user_data) {
			
			$modelObject = new Model_Facebook_Login($user_data['id']);
			if($modelObject->row) {
				$userObject = new Model_Users_User($modelObject->row['user_id']);
				if($userObject->count()) {
					if(JO_Session::get('user[user_id]')) {
						if($modelLogin->row['user_id'] == JO_Session::get('user[user_id]')) {
							JO_Session::set('user', $userObject->toArray());
						}
					} else {
						JO_Session::set('user', $userObject->toArray());
					}
					$modelObject->facebook = $facebook;
					$modelObject->update($user_data);
					if($request->getQuery('next')) {
						$this->redirect( ( urldecode($request->getQuery('next')) ) );
					} else {
						$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
					}
				}
			}
			
			$shared_content = false;
			if(!Helper_Config::get('enable_free_registration')) {
				$shared_content = $modelObject->checkInvateFacebookID($user_data['id']);
				if(!$shared_content) {
					$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=landing' ) );
				}
			}
			
			$this->view->avatar = 'uploads' . Helper_Config::get('no_image');
			if(!isset($user_data['avatarimage'])) {
				$ph = new WM_Facebook_Photo();
				$user_data['avatar'] = $ph->getRealUrl('http://graph.facebook.com/'.$user_data['id'].'/picture?type=large');
				if( !@getimagesize($user_data['avatar']) ) {
					$user_data['avatar'] = '';
				} else {
					$this->view->avatar = $user_data['avatar'];
				}
			}
			
			JO_Session::set('facebook_user_data_register', $user_data);
			
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
							'gender' => (isset($user_data['gender']) ? $user_data['gender'] : ''),
							'avatar' => $user_data['avatar'],
							'location' => (isset($user_data['hometown']['name']) ? $user_data['hometown']['name'] : ''),
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
						
						$modelObject->insert(array(
							'email' => isset($user_data['email']) ? $user_data['email'] : '',
							'user_id' => $result->user_id,
							'oauth_uid' => $user_data['id'],
							'access_token' => $user_data['access_token']
						));
						
						if($shared_content) {
							$modelObject->setInvate(array(
								'self_id' => $result->user_id,
								'user_id' => $shared_content['user_id'],
								'if_id' => $shared_content['if_id']		
							));
						}
						
						JO_Session::clear('facebook_user_data_register');
						
						if(JO_Session::issetKey('next') && JO_Session::get('next')) {
							$this->redirect( ( urldecode(JO_Session::get('next')) ) );
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
		
			$this->view->user_id_fb = $user_data['id'];
	
	
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
		
		$this->view->check_username = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_register&action=check_username' );
		$this->view->check_email = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_register&action=check_email' );
		
		
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