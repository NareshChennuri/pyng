<?php

class SettingsController extends Helper_Controller_Default {
	
	/**
	 * @var WM_Facebook
	 */
	protected $facebook;
	
	public function init() {
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
		}
		
		$this->facebook = JO_Registry::get('facebookapi');
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$user_data = Model_Users::getUser( JO_Session::get('user[user_id]') );
		
		$upload = new JO_Upload_SessionStore();
		$upload->setName('upload_avatar');
		$info = $upload->getFileInfo();
		
		if(JO_Session::get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		JO_Session::clear('successfu_edite'); 
    	}
		
		if(JO_Session::get('connect_error')) {
    		$this->view->error = JO_Session::get('connect_error');
    		JO_Session::clear('connect_error'); 
    	}
		
    	$this->view->config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
    	
		if( $request->isPost() ) {
			
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('username'), $this->translate('Username'), 'not_empty;min_length[3];max_length[100];username');
			$validate->_set_rules($request->getPost('firstname'), $this->translate('First name'), 'not_empty;min_length[3];max_length[100]');
			$validate->_set_rules($request->getPost('lastname'), $this->translate('Last name'), 'not_empty;min_length[3];max_length[100]');
			$validate->_set_rules($request->getPost('email'), $this->translate('Email'), 'not_empty;min_length[5];max_length[100];email');
			
			$data = $request->getPost();
		
			if($validate->_valid_form()) {
				if( Model_Users::isExistEmail($request->getPost('email'), JO_Session::get('user[email]')) ) {
					$validate->_set_form_errors( $this->translate('This e-mail address is already used') );
					$validate->_set_valid_form(false);
				}
				if( Model_Users::isExistUsername($request->getPost('username'), JO_Session::get('user[username]')) ) {
					$validate->_set_form_errors( $this->translate('This username is already used') );
					$validate->_set_valid_form(false);
				}
			}
			
			if($validate->_valid_form()) {
				
				$data['dont_search_index'] = (int)$request->issetPost('dont_search_index');
				$data['facebook_timeline'] = (int)$request->issetPost('facebook_timeline');
				/* v2.2 */
				if($this->view->config_enable_follow_private_profile) {
					$data['enable_follow'] = (int)(!$request->issetPost('enable_follow'));
					$data['public'] = (int)(!$request->issetPost('public'));
				}
				/* v2.2 */
				
				if($info) {
					if(!@file_exists(BASE_PATH . '/cache/avatar/') || !is_dir(BASE_PATH . '/cache/avatar/')) {
						mkdir(BASE_PATH . '/cache/avatar/');
					}
					$filename = BASE_PATH . '/cache/avatar/' . md5(mt_rand().time()) . $upload->get_extension($info['name']);
					if( file_put_contents( $filename, $info['data'] ) ) {
						$data['avatar'] = $filename;
					}
				}
				
				$new_email_key = md5( JO_Session::get('user[email]') . mt_rand() . time() );
				if(JO_Session::get('user[email]') != $request->getPost('email')) {
					$data['new_email_key'] = $new_email_key;
				} else {
					$data['new_email_key'] = '';
				}
				
				$data['new_email'] = $data['email'];
				unset($data['email']);
				
				$result = new Model_Users_Edit(JO_Session::get('user[user_id]'), $data);
				
				if($result->affected_rows) {
					JO_Session::set('successfu_edite', true);
					$upload->getFileInfo(true);
					if(JO_Session::get('user[email]') != $request->getPost('email')) {
						
						/*$this->view->verify_email_href = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login&user_id='.JO_Session::get('user[user_id]').'&verify=' . $new_email_key );
						$this->view->user_info = $user_data;
						Model_Email::send(
		    	        	$request->getPost('email'),
		    	        	Helper_Config::get('noreply_mail'),
		    	        	$this->translate('Please verify your email'),
		    	        	$this->view->render('verify_email', 'mail')
		    	        );*/
						
						$template = Model_Notification::getTemplate('verify_email');
						if($template) {
							
							$template_data = array(
									'user_id' => JO_Session::get('user[user_id]'),
									'user_firstname' => JO_Session::get('user[firstname]'),
									'user_lastname' => JO_Session::get('user[lastname]'),
									'user_fullname' => JO_Session::get('user[fullname]'),
									'user_username' => JO_Session::get('user[username]'),
									'verify_email_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login&user_id='.JO_Session::get('user[user_id]').'&verify=' . $new_email_key ),
									'site_url' => $request->getBaseUrl(),
									'site_name' => Helper_Config::get('site_name'),
									'user_message' => '',
									'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
							);
							
							if(!$template['title']) {
								$template['title'] = $this->translate('Please verify your email');
							}
							
							$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
							$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
								
							Model_Email::send(
									$request->getPost('email'),
									Helper_Config::get('noreply_mail'),
									$title,
									$body
							);
							
						}
						
					}
					
					$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=settings' ) );
				} else {
					$this->view->error = $this->translate('There was a problem with the record. Please try again!');
				}
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
			
			foreach($data AS $k=>$v) {
				if(isset($user_data[$k])) {
					$user_data[$k] = $v;
				}
			}
		} 
		
		if($info) {
			$user_data['avatar'] = WM_Router::create( $request->getBaseUrl() . '?controller=settings&action=temporary_avatar&s=' . microtime(true) );
			$user_data['has_avatar'] = true;
		} else {
			$avatar = Helper_Uploadimages::avatar($user_data, '_C');
			$user_data['avatar'] = $avatar['image'] . '?s=' . microtime(true);
			$user_data['has_avatar'] = @getimagesize($user_data['avatar']) ? true : false;
		}
		
		$this->view->instagram_enable = JO_Registry::get('oauth_in_key');
		$this->view->twitteroauth_enable = JO_Registry::get('oauth_tw_key');
		$this->view->facebook_enable = JO_Registry::get('oauth_fb_key');
        
        $this->view->user_data = $user_data;
		
		$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=settings&action=upload_avatar' );
		
		$this->view->check_username = WM_Router::create( $request->getBaseUrl() . '?controller=settings&action=check_username' );
		$this->view->delete_username = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=delete&user_id=' . $user_data['user_id'] );
		
		$this->view->prefs_action = WM_Router::create( $request->getBaseUrl() . '?controller=prefs' );
		
		$this->view->new_password = WM_Router::create( $request->getBaseUrl() . '?controller=password&action=change' );
		
		$this->view->site_name = JO_Registry::get('site_name');
		$this->view->base_href = $request->getBaseUrl();
		
		$this->view->delete_account = '';
		if( JO_Registry::get('delete_account') ) {
			$page_description = Model_Pages::getPage(JO_Registry::get('delete_account'));
			if($page_description) {
				$this->view->delete_account = html_entity_decode($page_description['description'], ENT_QUOTES, 'utf-8');
			}
		}
		
		
		//extensions
		$this->view->user_login_buttons = array();
		$user_login_buttons = Model_Extensions::getByMethod('user_login');
		foreach($user_login_buttons AS $id => $login) {
			$this->view->user_login_buttons[] = $this->view->callChildren('modules_' . $login . '_connect', WM_Router::create( $request->getBaseUrl() . '?controller=modules_' . $login . '_register' ));
		}
		
		$this->view->user_avatars = array();
		$user_login_buttons = Model_Extensions::getByMethod('user_avatar');
		foreach($user_login_buttons AS $id => $login) {
			$this->view->user_avatars[] = $this->view->callChildren('modules_' . $login . '_useravatar', WM_Router::create( $request->getBaseUrl() . '?controller=modules_' . $login . '_register' ));
		}
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
	}
	
	public function check_usernameAction(){
		
		$request = $this->getRequest();
		
		$username = trim($request->getPost('raw'));
		
		if($username && $request->getPost('raw') == JO_Session::get('user[username]')) {
			$this->view->success = $this->translate('Thats *your* username');
		} else {
			$validate = new Helper_Validate();
			$validate->_set_rules($username, $this->translate('Username'), 'not_empty;min_length[3];max_length[100];username');
			if($validate->_valid_form()) {
				if( Model_Users::isExistUsername($username, JO_Session::get('user[username]')) ) {
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
	
	public function upload_avatarAction(){
		
		$request = $this->getRequest();
		
		$upload = new JO_Upload_SessionStore($request->getFile('file'));
		$upload->setName('upload_avatar');
		if( $upload->upload(true) ) {
			$info = $upload->getFileInfo();
			$this->view->success = WM_Router::create( $request->getBaseUrl() . '?controller=settings&action=temporary_avatar&hash=' . microtime(true) );//'data:'.$info['type'].';base64,'.base64_encode($info['data']);
		} else {
			$this->view->error = $upload->getError();
		}
		
		echo $this->renderScript('json');
	}
	
	public function temporary_avatarAction(){
		
		if(!JO_Session::get('user[user_id]')) {
			exit;
		}
		
		$upload = new JO_Upload_SessionStore();
		$upload->setName('upload_avatar');
		$info = $upload->getFileInfo();
		if($info) {
			$this->getResponse()->addHeader('Content-Type: ' . $info['type']);
			echo $info['data'];
		}
		$this->noViewRenderer(true);
	}
	
}

?>