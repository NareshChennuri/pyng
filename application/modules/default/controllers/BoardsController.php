<?php

class BoardsController extends Helper_Controller_Default {
	
	/////////////////////////////////// v2 /////////////////////////////////////
	
	public function followAction() {	
		
		$this->noViewRenderer(true);
		
		$request = $this->getRequest();
		
		if($request->isXmlHttpRequest()) {
			if((int)JO_Session::get('user[user_id]')) {
		
				$board_id = $request->getRequest('board_id');
				
				$board_info = new Model_Boards_Board($board_id);
				
				if($board_info->count()) {
					
					$follow = new Model_Boards_Follow($board_id);
					
					$followed_user = new Model_Users_Follow($board_info['user_user_id']);
					$is_user_followed = $followed_user->is_follow;
					
					if($board_info['following_board']) {
						$result = $follow->unfollowBoard();
						if($result === true) {
							new Model_History_AddHistory($board_info['user_user_id'], Model_History_Abstract::UNFOLLOW, 0, $board_id);
							$this->view->ok = $this->translate('Follow');
							$this->view->classs = 'add';
							$this->view->boardauthorid = $board_info['user_user_id'];
							$this->view->is_follow_user = $follow->is_follow_user;
						} else {
							$this->view->error = $this->translate('There was a problem with the record. Please try again!');
						}
					} else {
						$result = $follow->followBoard();
						if($result === true) {
							new Model_History_AddHistory($board_info['user_user_id'], Model_History_Abstract::FOLLOW, 0, $board_id);
							$this->view->ok = $this->translate('Unfollow');
							$this->view->classs = 'remove';
							$this->view->boardauthorid = $board_info['user_user_id'];
							$this->view->is_follow_user = $follow->is_follow_user;
							if(!$is_user_followed && $board_info['user_email_interval'] && $board_info['user_follows_email']) {
								$template = Model_Notification::getTemplate('follow_user');
								if($template) {
									$template_data = array(
											'user_id' => $board_info['user_user_id'],
											'user_firstname' => $board_info['user_firstname'],
											'user_lastname' => $board_info['user_lastname'],
											'user_fullname' => $board_info['user_fullname'],
											'user_username' => $board_info['user_username'],
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
											$board_info['user_email'],
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
	
	public function indexAction() {	
		
		$request = $this->getRequest();
		
		$board_id = $request->getRequest('board_id');
		$user_id = $request->getRequest('user_id');
		
		$board_info = new Model_Boards_Board($board_id);
		
		if(!$board_info->count()) {
			$this->forward('error', 'error404');
		}

		if(!Helper_Config::get('config_board_description_enable')) {
			$board_info['board_description'] = false;		
		}
		
		$board_users_allow = array_filter(explode(',',$board_info['board_users_allow']));
		$board_users = array_merge(array($board_info['user_user_id']), array_filter($board_users_allow));
		
		if(!$board_info['board_public'] && !in_array(JO_Session::get('user[user_id]'), $board_users)) {
			$this->forward('error', 'error404');
		}
		
		if(!$board_info['board_category_id'] && JO_Session::get('user[user_id]') == $board_info['user_user_id']) {
			JO_Registry::set('board_category_change', $board_info);
		}
		
		$this->view->board_users = array();
		foreach($board_users AS $user_id) {
			$user_info = new Model_Users_User($user_id);
			if($user_info->count()) {
				$user_avatars = Helper_Uploadimages::userAvatars($user_info);
				$this->view->board_users[] = array(
					'fullname' => $user_info['fullname'],
					'avatars' => $user_avatars,
					'href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_info['user_id'] )		
				);
			}
		}
		
		///disable follow board for board users
		$this->view->is_enable_follow = JO_Session::get('user[user_id]');
		//if(in_array(JO_Session::get('user[user_id]'), $board_users)) {
		if(JO_Session::get('user[user_id]') == $board_info['board_user_id']) {
			$this->view->is_enable_follow = false;
		}
		
		/* v2.2 */
		if(Helper_Config::get('config_enable_follow_private_profile') && !$board_info['user_enable_follow']) {
			$this->view->is_enable_follow = false;
		}
		/* v2.2 */
		
		if($this->view->is_enable_follow) {
			$this->view->follow = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=follow&user_id=' . $board_info['user_user_id'] . '&board_id=' . $board_info['board_board_id'] );
		} else {
			$this->view->follow = false;
		}
		
		//enable edit for board user
		$board_info['edit'] = false;
		if(JO_Session::get('user[user_id]') == $board_info['user_user_id']) {
			$board_info['edit'] = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=edit&user_id=' . $board_info['user_user_id'] . '&board_id=' . $board_info['board_board_id'] );
		}
		
		// board url
		$this->view->board_url = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board_info['board_user_id'] . '&board_id=' . $board_info['board_board_id'] );
		
		$this->view->board = $board_info;
		
		$this->getLayout()->placeholder('title', $board_info['board_title']);
		JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('boards/header_metas', $board_info));
		
		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('boards', 'getPins', $board_info);
		}*/
		
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		if($request->getQuery('ignoreDisabled') == 'true') {
			Helper_Config::set('config_disable_js', 0);
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('boards', 'getPins', $board_info);
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('boards', 'getPins', $board_info);
			}
			$pins = (array)$this->getPinsAction($board_info, true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		//==== FEED ====//
		
		$_route_ = $request->getParam('_route_');
		$_route_parts = explode('/', $_route_);
		
		if( isset($_route_parts[2]) && $_route_parts[2] == 'feed' ) {
			$this->forward('boards', 'feed', array(
					'view' => $this->view
			));
		}
		
		//call header and footer childrens
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
		//update count view
		Model_Boards::updateViewed($board_id);
		
		
	}
	
	public function getPinsAction($board_info = array(), $return_data = false) {
		
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
				'filter_board_id' => $request->getParam('board_id')
		);
		
		$return = array();
		
		/* set board count */
		$has_pins = true;
		if(isset($board_info['board_pins'])) {
			$has_pins = $board_info['board_pins'];
		}
		
		// pins data
		$pins = $has_pins ? new Model_Pins_Boards($data) : new ArrayObject();
		
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
	
	public function viewAction() {
		$this->forward('boards', 'index');
	}
	
	public function pageAction() {
		$this->forward('boards', 'index');
	}
	
	public function deleteAction() {
		
		$request = $this->getRequest();
		
		$board_id = $request->getRequest('board_id');
		
		$board_info = Model_Boards::getBoard($board_id/*, $user_id*/);
		
		if(!$board_info) {
			$this->forward('error','error404');
		}
		
		if($board_info['user_id'] != JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=boards&user_id=' . $board_info['user_id'].'&board_id=' . $board_info['board_id']) );
		} else {
			$del = new Model_Boards_Delete($board_id);
			if($del->affected_rows) {
				$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $board_info['user_id']) );
			} else {
				$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=boards&action=edit&user_id=' . $board_info['user_id'].'&board_id=' . $board_info['board_id']) );
			}
		}
	}
	
	public function feedAction($data = array()) {
		
		$request = $this->getRequest();
		
		if(!$data) {
			$this->forward('error', 'error404');
		} else {
			
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
					'filter_board_id' => $request->getParam('board_id')
			);
			
			$pins = new Model_Pins_Boards($data);
			$formatObject = new Helper_Format();
			$this->view->item = array();
			if($pins->count()) {
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
			
			echo $this->renderScript('rss');
		}
	}
	
	public function createAction(){
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
			if( $request->isPost() ) {
				
				$validate = new Helper_Validate();
				$validate->_set_rules($request->getPost('newboard'), $this->translate('Board Name'), 'not_empty;min_length[3];max_length[100]');
				$validate->_set_rules($request->getPost('category_id'), $this->translate('Board Category'), 'not_empty');
				
				if($validate->_valid_form()) {
					
					$postData = array_merge($request->getPost(), array('title' => $request->getPost('newboard')));
					if(Helper_Config::get('config_private_boards')) {
						$postData['public'] = (int)!$request->issetPost('public');
					}
					$result = new Model_Boards_Create($postData);
					if($result->board_id) {
						$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . JO_Session::get('user[user_id]') . '&board_id=' . $result->board_id );
						$this->view->created = true;
						//send notifications
						if(is_array($request->getPost('friends'))) {
							$template = Model_Notification::getTemplate('board_invite');
							if($template) {
								$mail_footer = html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8');
								foreach($request->getPost('friends') AS $user_id) {
									$user = new Model_Users_User($user_id);
									
									if($user->count()) {
										if($user['email_interval'] == 1 && $user['groups_pin_email']) {
											$template_data = array(
													'user_id' => $user['user_id'],
													'user_firstname' => $user['firstname'],
													'user_lastname' => $user['lastname'],
													'user_fullname' => $user['fullname'],
													'user_username' => $user['username'],
													'author_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') ),
													'author_fullname' => JO_Session::get('user[fullname]'),
													'board_url' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . JO_Session::get('user[user_id]') . '&board_id=' . $result->board_id ),
													'board_name' => $request->getPost('newboard'),
													'mail_footer' => $mail_footer
											);
												
											if(!$template['title']) {
												$template['title'] = '${author_fullname} ' . $this->translate('invited you to add pins');
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
					
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
					
				} else {
					$this->view->error = $validate->_get_error_messages();
				}
				
				echo $this->renderScript('json');
				
			} else {
				
				$this->view->avatars = Helper_Uploadimages::userAvatars(JO_Session::get('user'));
				
				$this->view->fullname = JO_Session::get('user[fullname]');
				$this->view->userhref = WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]'));
				$this->view->friends_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=friends');
					
				$this->view->form_action = WM_Router::create($request->getBaseUrl() . '?controller=boards&action=create');
				$this->view->private = 1;
				
				/////private boards
				$this->view->enable_private_boards = Helper_Config::get('config_private_boards');
				$private_boards = Model_Pages::getPage( Helper_Config::get('page_private_boards') );
				if($private_boards) {
					$this->view->text_private_boards = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . Helper_Config::get('page_private_boards'));
				}
				
				//////////// Categories ////////////
				$this->view->categories = Model_Categories::getCategories(array(
						'filter_status' => 1
				));

				$this->view->config_board_description_enable = Helper_Config::get('config_board_description_enable');
				
				$this->view->popup_main_box = $this->view->render('popup_form','boards');
				$this->setViewChange('form');
				
				if($request->isXmlHttpRequest()) {
					$this->view->popup = true;
					echo $this->view->popup_main_box;
					$this->noViewRenderer(true);
				} else {
					$this->view->children = array(
			        	'header_part' 	=> 'layout/header_part',
			        	'footer_part' 	=> 'layout/footer_part',
			        	'left_part' 	=> 'layout/left_part'
			        );
				}
				
			}
		} else {
			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
// 			echo $this->renderScript('json');
			$this->setViewChange('redirect');
		}
	}
	
	public function createboardwithoutcategoryAction() {
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
			if( $request->isPost() ) {
				
				$validate = new Helper_Validate();
				$validate->_set_rules($request->getPost('newboard'), $this->translate('Board Name'), 'not_empty;min_length[3];max_length[100]');
				
				if($validate->_valid_form()) {
					
					$postData = array_merge($request->getPost(), array('title' => $request->getPost('newboard')));
					$result = new Model_Boards_Create($postData);
					if($result->board_id) {
						$this->view->data = array(
							'board_id' => $result->board_id,
							'title' => $request->getPost('newboard')
						);
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
					
				} else {
					$this->view->error = $validate->_get_error_messages();
				}
			}
		} else {
			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		echo $this->renderScript('json');
	}
	
	public function editAction() {
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
			
			$board_id = $request->getRequest('board_id');
			
			$board_info = new Model_Boards_Board($board_id);
			
			if(!$board_info->count()) {
				$this->forward('error', 'error404');
			}
			
			if( $board_info['board_user_id'] != JO_Session::get('user[user_id]') ) {
				$this->forward('error', 'error404');
			}
			
			$this->view->is_edit = true;
			
			if( $request->isPost() ) {
				
				$validate = new Helper_Validate();
				$validate->_set_rules($request->getPost('newboard'), $this->translate('Board Name'), 'not_empty;min_length[3];max_length[100]');
				$validate->_set_rules($request->getPost('category_id'), $this->translate('Board Category'), 'not_empty');
				
				if($validate->_valid_form()) {
					
					$postData = array_merge($request->getPost(), array('title' => $request->getPost('newboard')));
					if(Helper_Config::get('config_private_boards')) {
						$postData['public'] = (int)!$request->issetPost('public');
					}
					$result = new Model_Boards_Edit($board_id, $postData);
					if($result->affected_rows) {
						$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board_info['board_user_id'] . '&board_id=' . $board_info['board_board_id'] );
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
					
				} else {
					$this->view->error = $validate->_get_error_messages();
				}
				
				echo $this->renderScript('json');
				
			} else {
				
				$this->view->title = $board_info['board_title'];
				$this->view->category_id = $board_info['board_category_id'];
				$this->view->board_description = $board_info['board_description'];
				$this->view->another_users = array();
				$this->view->private = $board_info['board_public'];
					
				$this->view->board_id = $board_id;
					
				$b_users = $board_info['board_users_all'] ? explode(',',$board_info['board_users_all']) : array();
					
				if($b_users) {
					$board_users = new Model_Users_UsersInId( $b_users );
				
					if($board_users->count()) {
						foreach($board_users AS $user) {
							$this->view->another_users[] = array(
									'user_id' => $user['user_id'],
									'avatars' => Helper_Uploadimages::userAvatars($user),
									'fullname' => $user['fullname'],
									//links
									'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'])
							);
						}
					}
				}
					
				$self = array();
				foreach($board_info AS $key => $data) {
					if(strpos($key, 'user_') === 0) {
						$self[ substr($key, 5) ] = $data;
					}
				}
					
				$uin = Model_Users::getUser($board_info['user_id']);
					
				$this->view->avatars = Helper_Uploadimages::userAvatars($self);
					
				$this->view->fullname = $self['fullname'];
				$this->view->userhref = WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $board_info['user_user_id']);
				$this->view->friends_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=friends');
					
				$this->view->form_action = WM_Router::create($request->getBaseUrl() . '?controller=boards&action=edit&user_id=' . $board_info['user_user_id'].'&board_id=' . $board_info['board_board_id']);
				$this->view->board_href = WM_Router::create($request->getBaseUrl() . '?controller=boards&user_id=' . $board_info['user_user_id'].'&board_id=' . $board_info['board_board_id']);
				$this->view->board_delete = WM_Router::create($request->getBaseUrl() . '?controller=boards&action=delete&user_id=' . $board_info['user_user_id'].'&board_id=' . $board_info['board_board_id']);

				

				/////private boards

				$this->view->enable_private_boards = Helper_Config::get('config_private_boards');

				$private_boards = Model_Pages::getPage( Helper_Config::get('page_private_boards') );

				if($private_boards) {

					$this->view->text_private_boards = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . Helper_Config::get('page_private_boards'));

				}
				
				//////////// Categories ////////////
				$this->view->categories = Model_Categories::getCategories(array(
						'filter_status' => 1
				));
				
				$this->view->config_board_description_enable = Helper_Config::get('config_board_description_enable');
					
				$this->view->popup_main_box = $this->view->render('popup_form','boards');
				$this->setViewChange('form');
					
				if($request->isXmlHttpRequest()) {
					$this->view->popup = true;
					echo $this->view->popup_main_box;
					$this->noViewRenderer(true);
				} else {
					$this->view->children = array(
							'header_part' 	=> 'layout/header_part',
							'footer_part' 	=> 'layout/footer_part'
					);
				}
			}
			
		} else {
			if($request->isXmlHttpRequest()) {
				$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
				echo $this->renderScript('json');
			} else {
				$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
			}
		}
		
	}
	
	public function coverAction() {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		if(!$request->isXmlHttpRequest()) {
			$this->forward('error', 'error404');
		}
		
		$board_id = $request->getRequest('board_id');
		
		$board_info = new Model_Boards_Board($board_id);
		
		if($board_info->count() > 0) {

			if($board_info['user_user_id'] != JO_Session::get('user[user_id]')) {
				$this->forward('error', 'error404');
			}
			
			if($request->isPost() && $request->getPost('pin_id')) {
				$result = Helper_Db::update('boards', array(
					'cover' => $request->getPost('pin_id')		
				), array('board_id = ?' => $board_id));
				
				if($result !== false) {
					$this->view->success = $request->getPost('pin_id');
				} else {
					$this->view->error = $this->translate('There was an error, please try again!');
				}
				echo $this->renderScript('json');
				exit;
			}
			
			$pins = new Model_Pins_Boards(array(
				'filter_board_id' => $board_id,
				'start' => 0,
				'limit' => 100
			));
			
			$this->view->pins = array();
			if($pins->count()) {
				foreach($pins->data AS $pin) {
					$filter = array(
						'pin_id' => $pin['pin_pin_id'],
						'pin_pin_id' => $pin['pin_pin_id'],
						'image' => $pin['pin_image'],
						'pin_store' => $pin['pin_store']	
					);
					foreach($pin AS $k=>$v) {
						if(strpos($k, 'pin_thumb_') !== false) {
							$filter[$k] = $v;
						}
					}
					$image = Helper_Uploadimages::pinThumbs($filter);
					if($image['thumb_image_b']) {
						$this->view->pins[] = array(
							'image' => $image['thumb_image_b'],
							'image_c' => $image['thumb_image_c'],
							'pin_id' => (string)$pin['pin_pin_id']
						);
					}
				}
			}
			
			$this->view->history_id = $request->getParam('hid');
			
			$this->view->form_action = $request->getFullUrl();
			$this->view->board_id = $board_id;
			
		} else {
			$this->forward('error', 'error404');
		}
		
		
	}
	
	public function header_metasAction($board_info = array()) {
		
		if($board_info instanceof ArrayObject && $board_info->count()) {
			
			$pins = new Model_Pins_Boards(array(
					'filter_board_id' => $board_info['board_board_id'],
					'start' => 0,
					'limit' => 50
			));
				
			$board_image = '';
			$board_keywords = $board_info['board_title'] . html_entity_decode($board_info['board_description'] ? ' ' . $board_info['board_description'] : '');
			if($pins->count()) {
				foreach($pins->data AS $pin) {
					if($pin['pin_description']) {
						$board_keywords .= ' ' . html_entity_decode($pin['pin_description']);
					}
					if(!$board_image) {
						$filter = array(
								'pin_id' => $pin['pin_pin_id'],
								'pin_pin_id' => $pin['pin_pin_id'],
								'image' => $pin['pin_image'],
								'pin_store' => $pin['pin_store']
						);
						foreach($pin AS $k=>$v) {
							if(strpos($k, 'pin_thumb_') !== false) {
								$filter[$k] = $v;
							}
						}
						$board_image = Helper_Uploadimages::pinThumbs($filter);
					}
				}
			}
			
			$params = array(
					'min_word_occur' => 2,
					'min_2words_phrase_occur' => 2
			);
			$params['content'] = $board_keywords; //page content
			$keywords = new WM_Keywords($params);
			
			$this->view->site_name = Helper_Config::get('site_name');

			$this->view->board = array(
				'title' => $board_info['board_title'],
				'description' => ($board_info['board_description'] ? $board_info['board_description'] : $board_info['board_title']),
				'keywords' => htmlspecialchars($keywords->get_keywords()),
				'images' => $board_image,
				'board_url' => WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board_info['user_user_id'] . '&board_id=' . $board_info['board_board_id'] ),
				//feed
				'board_feed_url' => WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board_info['user_user_id'] . '&board_id=' . $board_info['board_board_id'] . '&feed' ),
				'board_feed_title' => $board_info['board_title'] . ' ' . sprintf($this->translate('on %s'), $this->view->site_name)
			);
			
			Helper_Config::set('extra_metatags', array('board' => $this->view->board));
			
		} else {
			$this->noViewRenderer(true);
		}
	}
	
	public function sort_orderAction(){
		
		$request = $this->getRequest();

		$page = (int)$request->getRequest('page');
		if($page < 1) { $page = 1; }

		if($request->isXmlHttpRequest() && JO_Session::get('user[user_id]')) {
			$sort_order = new Model_Boards_SortOrder($request->getPost('ids'), $page);
			if($sort_order->affected_rows) {
				$this->view->ok = $this->translate('The arrangement is saved!');
			} else if($sort_order->affected_rows == 0) {
				$this->view->empty = $this->translate('Sorting was not changed');
			}
		} else {
			$this->forward('error', 'error404');
		}
		
		echo $this->renderScript('json');
		
	}
	
}

?>