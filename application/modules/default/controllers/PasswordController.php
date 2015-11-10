<?php

class PasswordController extends Helper_Controller_Default {

	public function indexAction(){
		$this->forward('error', 'error404');
	}
	
	public function changeAction() {
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
		}
		
		if( $request->isPost() ) {
			
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('old_password'), $this->translate('Old'), 'not_empty;min_length[4];max_length[30]');
			$validate->_set_rules($request->getPost('new_password1'), $this->translate('New'), 'not_empty;min_length[4];max_length[30]');
			$validate->_set_rules($request->getPost('new_password2'), $this->translate('New, Again'), 'not_empty;min_length[4];max_length[30]');
			
			if($validate->_valid_form()) {
				if( md5($request->getPost('old_password')) != JO_Session::get('user[password]') ) {
					$validate->_set_form_errors( $this->translate('Your old password was entered incorrectly. Please enter it again.') );
					$validate->_set_valid_form(false);
				} elseif( md5($request->getPost('new_password1')) != md5($request->getPost('new_password2')) ) {
					$validate->_set_form_errors( $this->translate('Password and Confirm Password should be the same') );
					$validate->_set_valid_form(false);
				}
			}
			
			if($validate->_valid_form()) {
				$result = Model_Users::edit(JO_Session::get('user[user_id]'), array(
					'password' => $request->getPost('new_password1')
				));
				
				if($result) {
					$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]')) );
				} else {
					$this->view->error = $this->translate('There was a problem with the record. Please try again!');
				}
				
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
			
		}
		
		$this->view->reset = WM_Router::create( $request->getBaseUrl() . '?controller=password&action=reset' );
		$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=password&action=change' );
		
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
	}
	
	public function resetAction(){
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
		}
		
		$done = $request->issetQuery('done');
		$this->view->success = false;
		if($done) {
			$this->view->success = true;
		} 
		
		if( $request->isPost() ) {
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('email'), $this->translate('E-mail'), 'not_empty;min_length[5];max_length[100];email');
			
			if($validate->_valid_form()) {
				
				$result = Model_Users::forgotPassword($request->getPost('email'));
				if($result) {
					if($result['status']) {
						$new_password = Model_Users::generatePassword(8);
						
						$key_forgot = md5($result['user_id'] . md5($new_password));
						
						$add_new_pass = Model_Users::edit($result['user_id'], array(
							'new_password' => $new_password,
							'new_password_key' => $key_forgot
						));
						
						if($add_new_pass) {
							
							$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');			
			    			$mail = new JO_Mail;
			    			if($is_mail_smtp) {
			    				$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
			    			}
			    			
			    			$this->view->new_password = $new_password;
			    			$this->view->user_info = $result;
			    			$this->view->forgot_password_href = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login&user_id='.$result['user_id'].'&key=' . $key_forgot );
			    			$this->view->header_title = JO_Registry::get('site_name');
			    			$this->view->base_href = WM_Router::create( $request->getBaseUrl());
							
			    	        $result_send = Model_Email::send(
			    	        		$result['email'],
			    	        		Helper_Config::get('noreply_mail'),
			    	        		$this->translate('Request for forgotten password') . ' ' . JO_Registry::get('site_name'),
			    	        		$this->view->render('send_forgot_password_request', 'mail')
			    	        );
			    	        
			    	        if($result_send) {
			    	        	$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=password&action=reset&done=' ) );
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
				$this->view->error = $validate->_get_error_messages();
			}
			
		}
		
		$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=password&action=reset' );
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
		
	}
	
	public function doneAction() {
		
		$this->view->site_name = JO_Registry::get('site_name');
		
		$this->view->support_page = '';
		if( JO_Registry::get('support_page') ) {
			$page_description = Model_Pages::getPage(JO_Registry::get('support_page'));
			if($page_description) {
				$this->view->support_page = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . JO_Registry::get('support_page'));
			}
		}
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
	}
	
}

?>