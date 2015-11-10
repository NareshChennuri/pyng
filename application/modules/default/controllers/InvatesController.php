<?php

class InvatesController extends Helper_Controller_Default {
	
	public function indexAction(){
		
		$request = $this->getRequest();
		
		$this->view->invate_limit = 5;
		
		if($request->isPost()) {
			$emails = array();
			$this->view->send = array();
			for($i = 1; $i < $this->view->invate_limit; $i++) {
				
				$this->view->send[$i] = array(
					'success' => false,
					'error' => false
				);
				
				$validate = new Helper_Validate(); 
				if($request->getPost('email-' . $i) != $this->translate('Email Adress ' . $i)) {
					$validate->_set_rules($request->getPost('email-' . $i), $this->translate('Email Adress ' . $i), 'not_empty;min_length[5];max_length[100];email');
				
					if($validate->_valid_form()) {
						
						$shared_content = Model_Users::sharedContentInvate($request->getPost('email-' . $i));
						if($shared_content == 1) {
							$this->view->send[$i]['error'] = $this->translate('With this email address is already registered users!');
						} else if($shared_content == 2) {
							$this->view->send[$i]['error'] = $this->translate('To this email has been sent an invitation!');
						} else {
							$inser_key = Model_Users::sharedContent($request->getPost('email-' . $i));
							if($inser_key == -1) {
								$this->view->send[$i]['error'] = $this->translate('There was an error. Please try again later!');
							} else {
								
								$template = Model_Notification::getTemplate('send_invate');
								if($template) {
									
									$template_data = array(
											'user_id' => JO_Session::get('user[user_id]'),
											'user_firstname' => JO_Session::get('user[firstname]'),
											'user_lastname' => JO_Session::get('user[lastname]'),
											'user_fullname' => JO_Session::get('user[fullname]'),
											'user_message' => $request->getPost('note') != $this->translate('Add a personal note') ? $request->getPost('note') : '',
											'site_url' => $request->getBaseUrl(),
											'site_name' => Helper_Config::get('site_name'),
											'invate_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=register&user_id=' . JO_Session::get('user[user_id]') . '&key=' . $inser_key),
											'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
									);
										
									if(!$template['title']) {
										$template['title'] = $this->translate('Join and create your own pinboards');
									}
									
									$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
									$template = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
										
									$result = Model_Email::send(
											$request->getPost('email-' . $i),
											Helper_Config::get('noreply_mail'),
											$title,
											$template
									);
									
									if($result) {
										$this->view->send[$i]['success'] = $this->translate('The invitation was sent successfully!');
									} else {
										$this->view->send[$i]['error'] = $this->translate('There was an error. Please try again later!');
									}
									
								} else {
				    	        	$this->view->send[$i]['error'] = $this->translate('There was an error. Please try again later!');
				    	        }
								
				    	        
							}
						}
						
					} else {
						$this->view->send[$i]['error'] = strip_tags($validate->_get_error_messages());
					}
				
				}
			}
			
			if($request->isXmlHttpRequest()) {
				echo $this->renderScript('json');
				exit;
			} else {
				JO_Session::set('result_from_invate', $this->view->send);
				$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=invites' ) );
			}
		}
		
		if( JO_Session::get('result_from_invate') ) {
			$this->view->result_from_invate = JO_Session::get('result_from_invate');
			JO_Session::clear('result_from_invate');
		}
		

		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part',
			'methodsforinvates' => 'invates/methodsforinvates'
        );
		
		
	}
	
	public function methodsforinvatesAction() {
		
		$request = $this->getRequest();
		
		//user invate friends
		$this->view->user_invate_friends = array();
		$this->view->user_invate_friends[] = array(
				'title' => $this->translate('Email'),
				'href' => WM_Router::create( $request->getBaseUrl() . '?controller=invates' ),
				'active' => $request->getController() == 'invates',
				'class_icon' => 'icon-invites-email'
		);
		$user_invate_friends = Model_Extensions::getByMethod('user_invate_friends');
		foreach($user_invate_friends AS $id => $method) {
			$this->view->user_invate_friends[] = array(
					'title' => $this->translate(ucfirst(strtolower($method))),
					'href' => WM_Router::create( $request->getBaseUrl() . '?controller=modules_' . $method . '_invates' ),
					'active' => $request->getController() == 'modules_' . $method . '_invates',
					'class_icon' => 'icon-invites-' . $method
			);
		}
	}
	
}

?>