<?php

class BookmarkletController extends Helper_Controller_Default {

	public function indexAction() {
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login&popup=true&next=' . urlencode($request->getFullUrl()) ) );
		}
		
		$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=create' );

		$boards = new Model_Boards_BoardsWithShared(array(
			'filter_user_id' => JO_Session::get('user[user_id]')		
		));
		$this->view->boards = array();
		if($boards->count()) {
			foreach($boards AS $board) {
				$this->view->boards[] = array(
						'board_id' => $board['board_board_id'],
						'title' => $board['board_title']
				);
			}
		}
				
		///////////////// Extension on create //////////////////
		$this->view->form_extensions = array();
		$extensions = Model_Extensions::getByMethod('pin_oncreateform');
		if($extensions) {
			$front = JO_Front::getInstance();
			foreach($extensions AS $id => $ext) {
				$this->view->form_extensions[] = array(
						'html' => $this->view->callChildren('modules_' . $ext . '_oncreateform'),
						'view' => $this->view->callChildrenView('modules_' . $ext . '_oncreateform'),
						'key' => $ext
				);
			}
		}
		
		
		$this->view->title = JO_Utf8::convertToUtf8( $request->getQuery('title') );
		$this->view->url = JO_Utf8::convertToUtf8( urldecode($request->getQuery('url')) );
		$this->view->media = JO_Utf8::convertToUtf8( $request->getQuery('media') );
		$this->view->is_video = JO_Utf8::convertToUtf8( $request->getQuery('is_video') );
		$this->view->description = JO_Utf8::convertToUtf8( $request->getQuery('description') );
		$this->view->charset = JO_Utf8::convertToUtf8( $request->getQuery('charset') );
		
		if(is_array($request->getPost())) {
			foreach($request->getPost() AS $k=>$v) {
				$this->view->{$k} = $v;
			}
		}
		
		if(!trim($this->view->description)) {
			$this->view->description = $this->view->title;
		}
		
		if(JO_Session::get('success_added')) {
			return $this->success();
		} else if( $request->isPost() ) {
			
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('description'), $this->translate('Description'), 'not_empty;min_length[1];max_length[500]');
			$validate->_set_rules($request->getPost('board_id'), $this->translate('Board'), 'not_empty;');
			
			if($validate->_valid_form()) {
				$result = new Model_Pins_Create( $request->getPost() );
				
				if($result->count()) {
					$result = $result->data;
					///add history
					new Model_History_AddHistory(JO_Session::get('user[user_id]'), Model_History_Abstract::ADDPIN, $result['pin_id']);
					
					//send notification
					$users = new Model_Users_GroupBoardUsers($request->getPost('board_id'));
					if($users->count()) {
							
						$template = Model_Notification::getTemplate('group_board');
						if($template) {
							$pin_info = new Model_Pins_Pin($result['pin_id']);
							if($pin_info->count()) {
								$mail_footer = html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8');
								foreach($users AS $user) {
									if($user['email_interval'] == 1 && $user['groups_pin_email']) {
											
										$template_data = array(
												'user_id' => $user['user_id'],
												'user_firstname' => $user['firstname'],
												'user_lastname' => $user['lastname'],
												'user_fullname' => $user['fullname'],
												'user_username' => $user['username'],
												'author_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin_info['user_user_id'] ),
												'author_fullname' => $pin_info['user_fullname'],
												'board_url' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_info['user_user_id'] . '&board_id=' . $pin_info['board_board_id'] ),
												'board_name' => $pin_info['board_title'],
												'pin_url' => WM_Router::pinAction($result['pin_id']),
												'mail_footer' => $mail_footer
										);
											
										if(!$template['title']) {
											$template['title'] = '${author_fullname} ' . $this->translate('added new pin to a group board');
										}
											
										$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
										$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
					
										Model_Email::send(
												$user['email'],
												Helper_Config::get('noreply_mail'),
												$title,
												$body
										);
											
									}
								}
							}
						}
					}
					
					JO_Session::set('success_added', $result['pin_id']);
					$this->redirect( $request->getBaseUrl() . '?controller=bookmarklet' );
					
				} else {
					if($result->error) {
						$this->view->error = $result->error;
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
				}
				
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
			
		}
	}
	
	public function urlinfoAction() {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$array['url'] = $request->getQuery('url');
		$array['status'] = 'success';
		$array['pinnable'] = 'true';
		
		$this->noViewRenderer(true);
		
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		
		if( $request->getQuery('callback') ) {
			unset($array['status']);
			$response->addHeader('Content-type: application/javascript');
			echo $request->getQuery('callback') . '(' . JO_Json::encode($array) . ')';
		} else {
			$response->addHeader('Content-type: application/json');
			echo JO_Json::encode($array);
		}
	}
	
	private function success() {
		$pin_id = JO_Session::get('success_added');
		$this->view->pin_url = WM_Router::pinAction( $pin_id );
		$this->setViewChange('success');
		JO_Session::clear('success_added');
		
	}
	
}

?>