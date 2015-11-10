<?php

class UsersController extends Helper_Controller_Default {

	
	///////////////////////////////// v2 ////////////////////////////////////////
	
	public function registerAction() {
	
		$request = $this->getRequest();
	
		if( JO_Session::get('user[user_id]') ) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') ) );
		}
	
		$shared_content = null;
		if(!Helper_Config::get('enable_free_registration')) {
			$invateObject = new Model_Users_Invate();
			$shared_content = $invateObject->isInvated( $request->getParam('key'), $request->getParam('user_id') );
			
			if(!$shared_content) {
				$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=landing' ) );
			}
		} 
	
		$this->view->error = false;
		if($request->isPost()) {
				
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('username'), $this->translate('Username'), 'not_empty;min_length[3];max_length[100];username');
			$validate->_set_rules($request->getPost('firstname'), $this->translate('First name'), 'not_empty;min_length[3];max_length[100]');
			$validate->_set_rules($request->getPost('lastname'), $this->translate('Last name'), 'not_empty;min_length[3];max_length[100]');
			$validate->_set_rules($request->getPost('email'), $this->translate('Email'), 'not_empty;min_length[5];max_length[100];email');
			$validate->_set_rules($request->getPost('password'), $this->translate('Password'), 'not_empty;min_length[4];max_length[30]');
			$validate->_set_rules($request->getPost('password2'), $this->translate('Confirm password'), 'not_empty;min_length[4];max_length[30]');
				
			if($validate->_valid_form()) {
				if( md5($request->getPost('password')) != md5($request->getPost('password2')) ) {
					$validate->_set_form_errors( $this->translate('Password and Confirm Password should be the same') );
					$validate->_set_valid_form(false);
				}
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
						'username' => $request->getPost('username'),
						'firstname' => $request->getPost('firstname'),
						'lastname' => $request->getPost('lastname'),
						'email' => $request->getPost('email'),
						'password' => $request->getPost('password')
				));
	
				if($result->user_id) {
					$user_data = new Model_Users_User($result->user_id);
					if($user_data) {
						if($shared_content) {
							$follow = new Model_Users_Follow($result->user_id,$shared_content['user_id']);
							if(!$follow->is_follow) {
								$follow->followUser();
							}
							$follow = new Model_Users_Follow($shared_content['user_id'],$result->user_id);
							if(!$follow->is_follow) {
								$follow->followUser();
							}
						}
						JO_Session::set(array('user' => $user_data->toArray()));
						$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
				} else {
					$this->view->error = $this->translate('There was a problem with the record. Please try again!');
				}
	
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
		}
	
	
		$this->view->baseUrl = $request->getBaseUrl();
	
		if($request->issetPost('email')) {
			$this->view->email = $request->getPost('email');
		} else {
			if(isset($shared_content['email'])) {
				$this->view->email = $shared_content['email'];
			} else {
				$this->view->email = '';
			}
		}
	
		if($request->issetPost('firstname')) {
			$this->view->firstname = $request->getPost('firstname');
		} else {
			$this->view->firstname = '';
		}
	
		if($request->issetPost('lastname')) {
			$this->view->lastname = $request->getPost('lastname');
		} else {
			$this->view->lastname = '';
		}
	
		if($request->issetPost('username')) {
			$this->view->username = $request->getPost('username');
		} else {
			$this->view->username = '';
		}
	
		$this->view->password = $request->getPost('password');
		$this->view->password2 = $request->getPost('password2');
	
		//extensions
		$this->view->user_login_buttons = array();
		$user_login_buttons = Model_Extensions::getByMethod('user_register');
		foreach($user_login_buttons AS $id => $login) {
			$this->view->user_login_buttons[] = array(
				'html' => $this->view->callChildren('modules_' . $login . '_registerbutton', WM_Router::create( $request->getBaseUrl() . '?controller=modules_' . $login . '_register' )),
				'view' => $this->view->callChildrenView('modules_' . $login . '_registerbutton', WM_Router::create( $request->getBaseUrl() . '?controller=modules_' . $login . '_register' )),
				'key' => $login
			);
		}
	
	
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
	
	}
	
	public function deleteAction() {
		if(JO_Session::get('user[user_id]')) {
			$result = new Model_Users_Edit(JO_Session::get('user[user_id]'), array(
				'delete_account' => '1',
				'delete_account_date' => date('Y-m-d H:i:s')
			));
			if($result->affected_rows) {
				
				$template = Model_Notification::getTemplate('delete_account');
				if($template) {
						
					$template_data = array(
							'user_id' => JO_Session::get('user[user_id]'),
							'user_firstname' => JO_Session::get('user[firstname]'),
							'user_lastname' => JO_Session::get('user[lastname]'),
							'user_fullname' => JO_Session::get('user[fullname]'),
							'user_username' => JO_Session::get('user[username]'),
							'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
					);
						
					if(!$template['title']) {
						$template['title'] = $this->translate('Delete account request');
					}
						
					$template['title'] = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
					$template['template'] = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
						
					$result_send = Model_Email::send(
							JO_Session::get('user[email]'),
							Helper_Config::get('noreply_mail'),
							$template['title'],
							$template['template']
					);
	
				}
			}
		}
		$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() , '?controller=settings' ) );
	}
	
	public function loginAction() {
	
		$request = $this->getRequest();
	
		if($request->getQuery('verify')) {
			
			$user_data = new Model_Users_User($request->getParam('user_id'));
			$error = false;
			if($user_data->count() && $user_data['new_email_key'] == $request->getQuery('verify')) {
				$result = new Model_Users_Edit($user_data['user_id'],array(
					'new_email_key' => '',
					'email' => $user_data['new_email']		
				));
				if($result->affected_rows) {
					JO_Session::set('user', array());
					JO_Session::set('successful', $this->translate('You verifying your email. Now you can access with the data from e-mail!'));
					$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
				} else {
					$error = true;
				}
			} else {
				$error = true;
			}
			
			if($error) {
				$this->view->error = $this->translate('There was a problem with the record. Please try again!');
			}
			
		} else {
			if( !$request->getParam('user_id') || !$request->getQuery('key') ) {
				if( JO_Session::get('user[user_id]') ) {
					$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') ) );
				}
			}
		}
	
		$this->view->successful = false;
		if( JO_Session::get('successful')) {
			$this->view->successful = JO_Session::get('successful');
			JO_Session::clear('successful');
		}
	
		$this->view->error = false;
		if( $request->getParam('user_id') && $request->getQuery('key') ) {
			
			$user_data = new Model_Users_User($request->getParam('user_id')); 
			$error = false;
			if($user_data->count() && $user_data['new_password_key'] == $request->getQuery('key')) {
				$result = new Model_Users_Edit($user_data['user_id'],array(
						'password' => new JO_Db_Expr('`new_password`'),
						'new_password' => '',
						'new_password_key' => ''
				));
				if($result->affected_rows) {
					JO_Session::set('user', array());
					JO_Session::set('successful', $this->translate('You verifying forgotten password. Now you can access with the data from e-mail!'));
					$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
				} else {
					$error = true;
				}
			} else {
				$error = true;
			}
				
			if($error) {
				$this->view->error = $this->translate('There was a problem with the record. Please try again!');
			}
			
		}
		 
		$referer = $request->getServer('HTTP_REFERER');
		$this->view->next = urlencode($request->getBaseUrl());
		if($referer) {
			$data = parse_url($referer);
			if(isset($data['host'])) {
				if( str_replace('www.', '', $data['host']) == $request->getDomain() ) {
					$this->view->next = urlencode($referer);
				}
			}
		}
		if($request->issetPost('next')) {
			$this->view->next = html_entity_decode($request->getPost('next'));
		} elseif($request->getQuery('popup') == 'true' && $request->issetQuery('next')) {
			$this->view->next = urlencode(html_entity_decode($request->getQuery('next')));
		}
	
	
		$this->view->is_forgot_password = (int)$request->getPost('forgot_password');
		if(JO_Session::issetKey('forgot_password')) {
			$this->view->is_forgot_password = JO_Session::get('forgot_password');
			JO_Session::clear('forgot_password');
		}
	
		if( $request->isPost() && $request->issetPost('login') ) {
				
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('email'), $this->translate('Email Address'), 'not_empty;min_length[5];max_length[100];email');
			if( $request->getPost('forgot_password') != 1 ) {
				$validate->_set_rules($request->getPost('password'), $this->translate('Password'), 'not_empty;min_length[4];max_length[30]');
			}
	
			if($validate->_valid_form()) {
	
				if( $request->getPost('forgot_password') == 1 ) {
					$result = new Model_Users_UserByEmail($request->getPost('email'));
					if($result->count()) {
						if($result['status']) {
							$new_password = JO_Rand::generateRandumString(8);
								
							$key_forgot = md5($result['user_id'] . md5($new_password));

							$add_new_pass = new Model_Users_Edit($result['user_id'], array(
									'new_password' => $new_password,
									'new_password_key' => $key_forgot
							));
								
							if($add_new_pass->affected_rows) {
								
								$template = Model_Notification::getTemplate('send_forgot_password_request');
								if($template) {
									
									$template_data = array(
											'user_id' => $result['user_id'],
											'user_firstname' => $result['firstname'],
											'user_lastname' => $result['lastname'],
											'user_fullname' => $result['fullname'],
											'user_username' => $result['username'],
											'site_url' => $request->getBaseUrl(),
											'site_name' => Helper_Config::get('site_name'),
											'forgot_password_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login&user_id='.$result['user_id'].'&key=' . $key_forgot ),
											'new_password' => $new_password,
											'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
									);
									
									if(!$template['title']) {
										$template['title'] = $this->translate('Your new password in') . ' ${site_name}';
									}
									
									$template['title'] = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
									$template['template'] = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
									
									$result_send = Model_Email::send(
											$result['email'],
											Helper_Config::get('noreply_mail'),
											$template['title'],
											$template['template']
									);
									
									if($result_send) {
										JO_Session::set('forgot_password', $this->view->is_forgot_password);
										JO_Session::set('successful', $this->translate('Was sent the e-mail with instructions for the new password!'));
										$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
									} else {
										$this->view->error = $this->translate('There was an error. Please try again later!');
									}
									
								} else {
									$this->view->error = $this->translate('There was an error. Please try again later!');
								}
									
							} else {
								$this->view->error = $this->translate('There was a problem with the record. Please try again!');
							}
								
						} else {
							$this->view->error = $this->translate('This profile is not active.');
						}
					} else {
						$this->view->error = $this->translate('E-mail address was not found!');
					}
				} else {
					$result = new Model_Users_Login($request->getPost('email'), $request->getPost('password'));
					if($result->count()) {
						if($result['status']) {
							//@setcookie('csrftoken_', md5($result['user_id'] . $request->getDomain() . $result['date_added'] ), (time() + ((86400*366)*5)), '/', '.'.$request->getDomain());
							JO_Session::set(array('user' => $result->toArray()));
							$this->redirect( urldecode($this->view->next) );
						} else {
							$this->view->error = $this->translate('This profile is not active.');
						}
					} else {
						$this->view->error = $this->translate('E-mail address and password do not match');
					}
				}
	
			} else {
				$this->view->error = $validate->_get_error_messages();
			} 
		}
	
		$this->view->login_login = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
	
		//extensions
		$this->view->user_login_buttons = array();
		$user_login_buttons = Model_Extensions::getByMethod('user_login');
		foreach($user_login_buttons AS $id => $login) {
			$this->view->user_login_buttons[] = array(
				'html' => $this->view->callChildren('modules_' . $login . '_loginbutton', $this->view->next),
				'view' => $this->view->callChildrenView('modules_' . $login . '_loginbutton', $this->view->next),
				'key' => $login
			);
		}

		
		if($request->getQuery('popup') == 'true') {
				
			$this->view->site_name = Helper_Config::get('site_name');
			$this->view->meta_title = Helper_Config::get('meta_title');
				
			$this->view->popup = true;
				
			$this->view->baseUrl = $request->getBaseUrl();
			$this->view->site_logo = $request->getBaseUrl() . 'data/images/logo.png';
			if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
				$this->view->site_logo = $request->getBaseUrl() . 'uploads' . Helper_Config::get('site_logo');
			}
				
			$this->setViewChange('loginPopup');
				
			$this->view->children = array(
					'header_part' 	=> 'layout/header_part',
					'footer_part' 	=> 'layout/footer_part'
			);
				
		} else {
				
			$this->view->loginPopup = $this->view->render('loginPopup', 'users');
				
			$this->view->children = array(
					'header_part' 	=> 'layout/header_part',
					'footer_part' 	=> 'layout/footer_part'
			);
		}
	}
	
	public function resendAction() {
		$request = $this->getRequest();
		
		if($request->isXmlHttpRequest()) {
			if(JO_Session::get('user[user_id]')) {
				$user_data = JO_Session::get('user');
				
				$template = Model_Notification::getTemplate('verify_email');
				if($template) {
					
					$template_data = array(
							'user_id' => $user_data['user_id'],
							'user_firstname' => $user_data['firstname'],
							'user_lastname' => $user_data['lastname'],
							'user_fullname' => $user_data['fullname'],
							'user_username' => $user_data['username'],
							'site_url' => $request->getBaseUrl(),
							'site_name' => Helper_Config::get('site_name'),
							'verify_email_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login&user_id='.JO_Session::get('user[user_id]').'&verify=' . $user_data['new_email_key'] ),
							'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
					);
					
					if(!$template['title']) {
						$template['title'] = $this->translate('Please verify your email');
					}
					
					$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
					$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
						
					$result = Model_Email::send(
							$user_data['new_email'],
							Helper_Config::get('noreply_mail'),
							$title,
							$body
					);
					if($result) {
						$this->view->ok = $this->translate('Thanks! You should receive a verification email soon.');
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}	
					
				} else {
					$this->view->error = $this->translate('There was a problem with the record. Please try again!');
				}
			} else {
				$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
			}
			echo $this->renderScript('json');
		} else {
			$this->forward('error', 'error404');
		}
	}
	
	public function followAction(){
	
		$this->noViewRenderer(true);
		
		$request = $this->getRequest();
		
		if($request->isXmlHttpRequest()) {
			if((int)JO_Session::get('user[user_id]')) {
		
				$user_id = $request->getRequest('user_id');
				
				$user_info = new Model_Users_User($user_id);
				
				if($user_info->count()) {
					
					$follow = new Model_Users_Follow($user_id);
					
					if($user_info['following_user']) {
						$result = $follow->unfollowUser();
						if($result === true) {
							new Model_History_AddHistory($user_id, Model_History_Abstract::UNFOLLOW_USER);
							$this->view->ok = $this->translate('Follow');
							$this->view->classs = 'add';
							$this->view->boardauthorid = $user_id;
						} else {
							$this->view->error = $this->translate('There was a problem with the record. Please try again!');
						}
					} else {
						$result = $follow->followUser();
						if($result === true) {
							new Model_History_AddHistory($user_id, Model_History_Abstract::FOLLOW_USER);
							$this->view->ok = $this->translate('Unfollow');
							$this->view->classs = 'remove';
							$this->view->boardauthorid = $user_id;
							if($user_info['email_interval'] && $user_info['follows_email']) {
								$template = Model_Notification::getTemplate('follow_user');
								if($template) {
									$template_data = array(
											'user_id' => $user_info['user_id'],
											'user_firstname' => $user_info['firstname'],
											'user_lastname' => $user_info['lastname'],
											'user_fullname' => $user_info['fullname'],
											'user_username' => $user_info['username'],
											'author_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') ),
											'author_fullname' => JO_Session::get('user[fullname]'),
											'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
									);
										
									if(!$template['title']) {
										$template['title'] = '${author_fullname} ' . $this->translate('now follow you');
									}
										
									$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
									$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
								
									Model_Email::send(
											$user_info['email'],
											Helper_Config::get('noreply_mail'),
											$title,
											$body
									);
								}
							}
						} else {
							$this->view->error = $this->translate('There was a problem with the record. Please try again!');
						}
					}
					
				} else {
					$this->view->error = $this->translate('There was a problem with the record. Please try again!');
				}
			
			} else {
				$this->view->location = WM_Router::create( $request->getBaseUrl() . '?controller=landing' );
			}
		} else {
			$this->forward('error', 'error404');
		}
		
		echo $this->renderScript('json');

	}
	
	public function logoutAction(){

		@setcookie('csrftoken_', md5(JO_Session::get('user[user_id]') . $this->getRequest()->getDomain() . JO_Session::get('user[date_added]') ), (time() - 100 ), '/', '.'.$this->getRequest()->getDomain());
		
		JO_Session::set('user', array());
		
		$url_logout = $this->getRequest()->getBaseUrl();
		
		$this->redirect( $url_logout );
	}
	
    public function allowInvateAction(){
        $this->noViewRenderer(true);	
        $request = $this->getRequest();
        if(!JO_Session::get('user[user_id]')) {
            $this->redirect(WM_Router::create( $request->getBaseUrl()));	
        }
        
        if($request->getRequest('board_id') && $request->getRequest('type')) {
        	
        	$board_info = new Model_Boards_Board($request->getRequest('board_id'));
        	if($board_info->count()) {
        		$users = explode(',', $board_info['board_users_not_allow']);
        		if(in_array(JO_Session::get('user[user_id]'), $users)) {
        			if($request->getRequest('type') == 'accept') {
        				Model_Boards::acceptUsersBoard($request->getRequest('board_id'));
        			} elseif($request->getRequest('type') == 'decline') {
        				Model_Boards::deleteUsersBoard($request->getRequest('board_id'));
        			}
        		}
        	}
        } 

        $this->redirect(WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id='.JO_Session::get('user[user_id]') ));	
	}
	
	public function indexAction() {
	    $this->forward('users', 'profile');
	}
	
	public function editDescriptionAction(){
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			if($request->isXmlHttpRequest()) {
				$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
			} else {
				$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
			}
		} else {
			$result = Helper_Db::update('users', array(
				'description' => $request->getPost('description')
			), array('user_id = ?' => (string)JO_Session::get('user[user_id]')));
			if($result) {
				$this->view->ok = $request->getPost('description');
			} else {
				$this->view->error = $this->translate('There was a problem with the record. Please try again!');
			}
		}

		echo $this->renderScript('json');
	}
	
	private function profileHelp() {
		$request = $this->getRequest();
		$user_data = new Model_Users_User( $request->getRequest('user_id') );
        
        if(!$user_data->count()) {
            $this->forward('error', 'error404');
        }
        
        if(!$user_data['facebook_connect']) {
        	$user_data['facebook_id'] = 0;
        }
        
        if(!$user_data['twitter_connect']) {
        	$user_data['twitter_id'] = 0;
        }
        
        
        $user_data['avatars'] = Helper_Uploadimages::userAvatars($user_data);
       	
		//$user_data['image_href'] = $user_data['avatar'];
		if($user_data['user_id'] == JO_Session::get('user[user_id]')) {
			$user_data['image_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=settings' );
		} else {
			$user_data['image_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_data['user_id'] );
		}

        $this->view->active = 'boards';
        
	
		if($user_data['website'] && !preg_match('/^https?:\/\//',$user_data['website'])) {
			$user_data['website'] = 'http://' . $user_data['website'];
		}
       
        $this->view->userdata = $user_data;  
        
        $this->view->self_profile = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_data['user_id'] );
		$this->view->user_pins = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=pins&user_id=' . $user_data['user_id']  );
		$this->view->user_pins_likes = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=pins&user_id=' . $user_data['user_id'] . '&filter=likes' );
		$this->view->settings = WM_Router::create( $request->getBaseUrl() . '?controller=settings' );
        $this->view->user_activity = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=activity&user_id=' . $user_data['user_id']  );
		$this->view->user_followers = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=followers&user_id=' . $user_data['user_id']  );
		$this->view->user_following = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=following&user_id=' . $user_data['user_id']  );
		$this->view->edit_description = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=editDescription');
       
		$this->view->enable_edit = JO_Session::get('user[user_id]') && JO_Session::get('user[user_id]') == $user_data['user_id'];
		/* v2.2 mod */
		$this->view->enable_follow = JO_Session::get('user[user_id]') && JO_Session::get('user[user_id]') != $user_data['user_id'] && (Helper_Config::get('config_enable_follow_private_profile') ? $user_data['enable_follow'] : true);
		/* v2.2 mod */
		
		$this->view->order_boards = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=sort_order');
		
		$this->view->reload_page = $request->getFullUrl();
		
		if(JO_Session::get('user[user_id]') && $user_data['user_id'] != JO_Session::get('user[user_id]')) {
			$this->view->userIsFollow = Model_Users::isFollowUser($user_data['user_id']);
			
			$this->view->follow_user = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user_data['user_id'] );
		}
		
		$this->view->class_contaner = $request->getAction();
		
		$data = array(
			'start' => 0,
			'limit' => 3,
			'filter_history_action' => Model_History_Abstract::REPIN
		);
		
		$history = new Model_History_Activity($data, 'from_user_id', $user_data['user_id']);
		
		$this->view->history_data = array();
		
		$this->view->title_right = $this->translate('Repins from');
		
		if(!$history->count()) {
			$this->view->title_right = $this->translate('Following');
			$data['filter_history_action'] = Model_History_Abstract::FOLLOW_USER;
			$history = new Model_History_Activity($data, 'from_user_id', $user_data['user_id']);
		}
		
		if($history->count()) { 
			foreach($history AS $r) {
				$ud = array();
				foreach($r AS $k => $v) {
					if(strpos($k, 'user_') === 0) {
						$ud[substr($k, 5)] = $v;
					}
				}
				$this->view->history_data[] = array(
					'title' => $r['user_fullname'],
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $r['user_user_id']),
					'avatars' => Helper_Uploadimages::userAvatars($ud)
				);
			}
		}
		
		//extensions
		$user_data['user_social_icons'] = array();
		$user_login_buttons = Model_Extensions::getByMethod('user_login');
		$tmp = $user_data;
		foreach($user_login_buttons AS $id => $login) {
			$user_data['user_social_icons'][] = $this->view->callChildren('modules_' . $login . '_profileicons', (array)$tmp );
		}
		
		////metas
		$user_data['meta_title'] = $user_data['fullname'] . ' ' . sprintf($this->translate('on %s'), Helper_Config::get('site_name'));
		
		$this->getLayout()->placeholder('title',$user_data['meta_title']);
		
		JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('users/header_metas', $user_data));
		
		return $user_data;
	}
	
	public function header_metasAction($user_info = array()) {
		
		if($user_info instanceof ArrayObject && $user_info->count()) {
			
			$this->view->site_name = Helper_Config::get('site_name');
			
			$this->view->user = array(
				'title' => $user_info['meta_title'],
				'description' => $user_info['description'],
				'avatars' => $user_info['avatars'],
				'user_url' => WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_info['user_id'] ),
				//feed
				'user_feed_url' => WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_info['user_id'] . '&feed' ),
				'user_feed_title' => $user_info['meta_title']
			);
			
			Helper_Config::set('extra_metatags', array('user' => $this->view->user));
			
		} else {
			$this->noViewRenderer(true);
		}
	}
	
	public function activityAction(){
        $request = $this->getRequest();
		$user_data = $this->profileHelp();
        
        $this->setViewChange('profile');
		
        $this->view->active = 'activity';

        
        /*//get pins data
        if($request->isXmlHttpRequest()) {
        	$this->forward('users', 'getActivity', $user_data);
        }*/
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('users', 'getActivity', $user_data);
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('users', 'getActivity', $user_data);
			}
			$pins = (array)$this->getActivityAction($user_data,true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
        
        $this->view->children = array(
        		'header_part' 	=> 'layout/header_part',
        		'footer_part' 	=> 'layout/footer_part'
        );
        
		
	}
	
	public function feedAction(){
		
		$request = $this->getRequest();
		
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$data = array(
				'start' => ( $pp * $page ) - $pp,
				'limit' => $pp,
				'filter_user_id' => $request->getRequest('user_id')
		);
		
		$user_data = new Model_Users_User($request->getRequest('user_id'));
		if($user_data->count()) {
			
			JO_Registry::set('meta_title', $user_data['fullname'] . ' - ' . Helper_Config::get('meta_title'));
			
			if($user_data['pins']) {
				$pins = new Model_Pins_Users($data);
						
				$this->view->item = array();
				if($pins->count()) {
					$formatObject = new Helper_Format();
					foreach($pins->data AS $pin) {
						$pin = $formatObject->fromatList($pin);
					
						$category_info = Model_Categories::getCategory($pin['category_id']);
						if($category_info) {
							$pin['text_board'] = $category_info['title'] . ' >> ' . $pin['text_board'];
						}
				
						$this->view->item[] = array(
								'guid' => $pin['pin_id'],
								'enclosure' => $pin['images']['thumb_image_b'],
								'description' => $pin['description'],
								'title' => JO_Utf8::splitText($pin['description'], 60, '...'),
								'link' => $pin['pin_url'],
								'author' => $pin['author_profile']['fullname'],
								'pubDate' => WM_Date::format($pin['date_added'], JO_Date::RSS_FULL),
								'category' => $pin['text_board']
						);
					}
				}
			
			}
		
		}
			
		echo $this->renderScript('rss');
		
	}
	
	public function profileAction() {
        $request = $this->getRequest();
        
        $method = $request->getSegment(2);
        
        if( method_exists($this, strtolower($method).'Action') ) {
        	$this->forward('users', $method);
        } else if($method) {
        	if(Model_Users::isExistUsername($method)) {
        		$this->redirect( $request->getBaseUrl() . $method );
        	}
        }
        
        $page = (int)$request->getRequest('page');
        if($page < 1) {
        	$page = 1;
        }
        
		$user_data = $this->profileHelp();
        
        $this->view->active = 'boards';
		
        $this->view->enable_sort = JO_Session::get('user[user_id]') && JO_Session::get('user[user_id]') == $user_data['user_id']; 
        
        $this->view->has_edit_boards = true;
        $this->view->enable_sort = true;
        $this->view->current_page = $page;
        
        if(JO_Session::get('user[user_id]') && $user_data['user_id'] == JO_Session::get('user[user_id]')) {
        	$has_invates = new Model_Boards_TotalInvates(array(
        		'filter_user_id' => JO_Session::get('user[user_id]')
        	));
        	$this->view->get_invate_boards = $has_invates->total;
        }
        
		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('users', 'getBoards', $user_data);
		}*/
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('users', 'getBoards', $user_data);
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('users', 'getBoards', $user_data);
			}
			$pins = (array)$this->getBoardsAction($user_data, true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		
        
        $this->view->children = array(
        		'header_part' 	=> 'layout/header_part',
        		'footer_part' 	=> 'layout/footer_part'
        );

	}
	
	public function pinsAction() {
        $request = $this->getRequest();
		$user_data = $this->profileHelp();
        
        $this->setViewChange('profile');
		
        $this->view->active = 'pins';

        if($request->getQuery('filter') == 'likes') {
        	$this->view->active = 'likes';
        }
        
        /*//get pins data
        if($request->isXmlHttpRequest()) {
        	$this->forward('users', 'getPins', $user_data);
        }*/
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('users', 'getPins', $user_data);
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('users', 'getPins', $user_data);
			}
			$pins = (array)$this->getPinsAction($user_data, true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
        
        $this->view->children = array(
        		'header_part' 	=> 'layout/header_part',
        		'footer_part' 	=> 'layout/footer_part'
        );

	}
	
	public function followersAction() {
        $request = $this->getRequest();
		$user_data = $this->profileHelp();
        
        $this->setViewChange('profile');
		
        $this->view->active = 'followers';
        
		$page = (int)$request->getRequest('page');
		if($page < 1) { $page = 1; }
        
		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('users', 'getFollowers', $user_data);
		}*/
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('users', 'getFollowers', $user_data);
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('users', 'getFollowers', $user_data);
			}
			$pins = (array)$this->getFollowersAction($user_data,true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
		
	}
	
	public function followingAction(){
        $request = $this->getRequest();
		$user_data = $this->profileHelp();
        
        $this->setViewChange('profile');
		
        $this->view->active = 'following';
        
		$page = (int)$request->getRequest('page');
		if($page < 1) { $page = 1; }
        
		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('users', 'getFollowing', $user_data);
		}*/
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('users', 'getFollowing', $user_data);
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('users', 'getFollowing', $user_data);
			}
			$pins = (array)$this->getFollowingAction($user_data,true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
		
	}
	
	
	public function getPinsAction($user_data = array(), $return_data = false) {
	
		if(!$user_data) {
			$this->forward('error', 'error404');
		}
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
	
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
	
		$data = array(
			'start' => ( $pp * $page ) - $pp,
			'limit' => $pp,
			'filter_user_id' => $user_data['user_id']
		);
		
	
		$return = array();
	
		/* set board count */
		$has_pins = true;
		if($request->getQuery('filter') == 'likes') {
			if($user_data['likes'] < 1) {
				$has_pins = false;
			}
		} else {
			if($user_data['pins'] < 1) {
				$has_pins = false;
			}
		}
		
		// pins data
		if($request->getQuery('filter') == 'likes') {
			$data['filter_like_pin_id'] = $user_data['user_id'];
			$pins = $has_pins ? new Model_Pins_Likes($data) : new ArrayObject();
		} else {
			$pins = $has_pins ? new Model_Pins_Users($data) : new ArrayObject();
		}
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_pins && $pins->count()) {
			$banners = Model_Banners::getBanners(
					new JO_Db_Expr("`controller` = '".$request->getController()."' AND position >= '".(int)$data['start']."' AND position <= '".(int)($data['start']+$pp)."'")
			);
				
			foreach($pins->data AS $row => $pin) {
				///banners
				$key = $row + (($pp*$page)-$pp);
				if(isset($banners[$key]) && $banners[$key]) {
					if( ($banners_result = $formatObject->fromatListBanners($banners[$key])) !== false) {
						$return[] = $banners_result;
					}
				}
				//pins
				$return[] = $formatObject->fromatList($pin);
			}
				
		} else {
			if($page == 1) {
				$message = $this->translate('No pyngs!');
			} else {
				$message = $this->translate('No more pyngs!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
	
	}
	
	public function getBoardsAction($user_data = array(), $return_data = false) {
	
		if(!$user_data) {
			$this->forward('error', 'error404');
		}
		
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
	
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
	
		$data = array(
			'start' => ( $pp * $page ) - $pp,
			'limit' => $pp,
			'filter_user_id' => $user_data['user_id'],
			'sort' => 'ASC',
			'order' => 'boards.sort_order'
		);
	
		$return = array();
	
		/* set board count */
		$has_boards = true;
		if($user_data['boards'] < 1) {
			$has_boards = false;
		}

		// pins data
		$boards = $has_boards ? new Model_Boards_BoardsWithShared($data) : new ArrayObject();
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_boards && $boards->count()) {
			$enable_sort = $user_data['user_id'] == JO_Session::get('user[user_id]');
			foreach($boards AS $row => $board) {
				//boards
				$board['enable_sort'] = $enable_sort;
				$return[] = $formatObject->fromatListBoard($board);
			}
				
		} else {
			if($page == 1) {
				$message = $this->translate('No boards!');
			} else {
				$message = $this->translate('No more boards!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
	
	}
	
	public function friendsAction(){
		$request = $this->getRequest();
		
		$this->view->users = array();
		
		$self_id = JO_Session::get('user[user_id]');
		
		if($self_id && $request->getPost('value')) {
			
			$has_friends = (int)JO_Session::get('user[following]') + (int)JO_Session::get('user[followers]');
			
			$users = new Model_Users_SearchAutocomplete(array(
				'start' => 0,
				'limit' => 100,
				'filter_username' => $request->getPost('value')
			));
			
			if($has_friends && $users->count()) {
				foreach($users AS $user) {
					if($user['user_id'] == $self_id) {
						continue;
					}
					$this->view->users[] = array(
						'template' => 'friends',
						'template_2' => 'board_friend',
						'user_id' => $user['user_id'],
						'avatars' => Helper_Uploadimages::userAvatars($user),
						'fullname' => $user['fullname'],
						//links
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id']),
						//texts
						'text_remove' => $this->translate('Remove')
					);
				}
			}
		} 
		
		if($request->isXmlHttpRequest()) {
			echo $this->renderScript('json');
		} else {
			$this->forward('error', 'error404');
		}
		
	}
	
	public function getFollowingAction($user_data = array(), $return_data = false) {
	
		if(!$user_data) {
			$this->forward('error', 'error404');
		}
		
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
	
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$data = array(
			'start' => ( $pp * $page ) - $pp,
			'limit' => $pp,
			'filter_following_user_id' => $user_data['user_id']
		);
	
		$return = array();
	
		/* set board count */
		$has_following = true;
		if($user_data['following'] < 1) {
			$has_following = false;
		}
	
		// pins data
		$users = $has_following ? new Model_Users_Following($data) : new ArrayObject();
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_following && $users->count()) {
			foreach($users AS $row => $user) {
				$user['row'] = $row;
				$return[] = $formatObject->fromatListUserFollowing($user);
			}
				
		} else {
			if($page == 1) {
				$message = $this->translate('No following users!');
			} else {
				$message = $this->translate('No more following users!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
		
	}
	
	public function getFollowersAction($user_data = array(), $return_data = false) {
	
		if(!$user_data) {
			$this->forward('error', 'error404');
		}
		
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
	
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$data = array(
			'start' => ( $pp * $page ) - $pp,
			'limit' => $pp,
			'filter_followers_user_id' => $user_data['user_id']
		);
	
		$return = array();
	
		/* set board count */
		$has_followers = true;
		if($user_data['followers'] < 1) {
			$has_followers = false;
		}
	
		// pins data
		$users = $has_followers ? new Model_Users_Followers($data) : new ArrayObject();
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_followers && $users->count()) {
			foreach($users AS $row => $user) {
				$user['row'] = $row;
				$return[] = $formatObject->fromatListUserFollowers($user);
			}
				
		} else {
			if($page == 1) {
				$message = $this->translate('No followers users!');
			} else {
				$message = $this->translate('No more followers users!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
		
	}
	
	public function getInvateBoardsAction() {
		
		$boards = new Model_Boards_Invates(array(
				'filter_user_id' => JO_Session::get('user[user_id]')
		));
		 
		$return = array();
		$formatObject = new Helper_Format();
		if($boards->count()) {
			foreach($boards AS $board) {
				$res = $formatObject->fromatListBoard($board);
				$res['template'] = 'boards_invates';
				$return[] = $res;
			}
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
		
	}
	
	public function getActivityAction($user_data = array(), $return_data = false) {
	
		if(!$user_data) {
			$this->forward('error', 'error404');
		}
		
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
	
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$data = array(
			'start' => ( $pp * $page ) - $pp,
			'limit' => $pp
		);
		
		$history = new Model_History_Activity($data, 'from_user_id', $user_data['user_id']);
		
		$return = array();
		$formatObject = new Helper_Format();
		if($history->count()) {
			foreach($history AS $key => $row) { 
				$via_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $row['to_user_id']);
				if($row['history_action'] == Model_History_Abstract::REPIN) {
					$row_data = $formatObject->fromatList($row);
					$row_data['set_activity_title'] = sprintf($this->translate('Repinned to %s via %s.'), '<a href="'.$row_data['board_url'].'">'.$row_data['text_board'].'</a>', '<a href="'.$via_href.'">'.$row['user_fullname'].'</a>');
					$row_data['history_id'] = $row['history_id'];
					$row_data['activity_class'] = 'a_repin';
					$return[] = $row_data;
				} elseif($row['history_action'] == Model_History_Abstract::ADDPIN) {
					$row_data = $formatObject->fromatList($row);
					$row_data['set_activity_title'] = sprintf($this->translate('Pinned to %s.'), '<a href="'.$row_data['board_url'].'">'.$row_data['text_board'].'</a>');
					$row_data['activity_class'] = 'a_addpin';
					$return[] = $row_data;
				} elseif($row['history_action'] == Model_History_Abstract::LIKEPIN) {
					$row_data = $formatObject->fromatList($row);
					$row_data['set_activity_title'] = sprintf($this->translate("Liked %s's pin on %s."), '<a href="'.$via_href.'">'.$row['user_fullname'].'</a>', '<a href="'.$row_data['board_url'].'">'.$row_data['text_board'].'</a>');
					$row_data['history_id'] = $row['history_id'];
					$row_data['activity_class'] = 'a_like';
					$return[] = $row_data;
				} elseif($row['history_action'] == Model_History_Abstract::UNLIKEPIN) {
					$row_data = $formatObject->fromatList($row);
					$row_data['set_activity_title'] = sprintf($this->translate("Unliked %s's pin on %s."), '<a href="'.$via_href.'">'.$row['user_fullname'].'</a>', '<a href="'.$row_data['board_url'].'">'.$row_data['text_board'].'</a>');
					$row_data['history_id'] = $row['history_id'];
					$row_data['activity_class'] = 'a_unlike';
					$return[] = $row_data;
				} elseif($row['history_action'] == Model_History_Abstract::COMMENTPIN) {
					$row_data = $formatObject->fromatList($row);
					$row_data['set_activity_title'] = sprintf($this->translate("Commented on %s's pin and said \"%s\"."), '<a href="'.$via_href.'">'.$row['user_fullname'].'</a>', JO_Utf8::splitText($row['comment'], 60, '...'));
					$row_data['history_id'] = $row['history_id'];
					$row_data['activity_class'] = 'a_comment';
					$return[] = $row_data;
				} elseif($row['history_action'] == Model_History_Abstract::ADDBOARD) {
					$row['user_user_id'] = $row['from_user_id'];
					$row_data = $formatObject->fromatListBoard($row);
					$row_data['set_activity_title'] = $this->translate('Created');
					$row_data['history_id'] = $row['history_id'];
					$row_data['activity_class'] = 'a_addboard';
					$return[] = $row_data;
				} else {
					if($row['history_action'] == Model_History_Abstract::FOLLOW_USER) {
						$row_data = $formatObject->fromatUserFollow($row);
						$row_data['activity_class'] = 'a_follow_user';
						$return[] = $row_data;
					} elseif($row['history_action'] == Model_History_Abstract::UNFOLLOW_USER) {
						$row_data = $formatObject->fromatUserFollow($row);
						$row_data['activity_class'] = 'a_unfollow_user';
						$return[] = $row_data;
					} elseif($row['history_action'] == Model_History_Abstract::FOLLOW) {
						$row['user_user_id'] = $row['from_user_id'];
						$row_data = $formatObject->fromatListBoard($row);
						$row_data['set_activity_title'] = $this->translate('Follow');
						$row_data['history_id'] = $row['history_id'];
						$row_data['activity_class'] = 'a_follow_board';
						$return[] = $row_data;
					} elseif($row['history_action'] == Model_History_Abstract::UNFOLLOW) {
						$row['user_user_id'] = $row['from_user_id'];
						$row_data = $formatObject->fromatListBoard($row);
						$row_data['set_activity_title'] = $this->translate('Unfollow');
						$row_data['history_id'] = $row['history_id'];
						$row_data['activity_class'] = 'a_unfollow_board';
						$return[] = $row_data;
					}
				}
			}
		} else {
			if($page == 1) {
				$message = $this->translate('No activity!');
			} else {
				$message = $this->translate('No more activity!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
		
	}
	
}

?>