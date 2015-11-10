<?php

class Modules_Instagram_MediaController extends Helper_Controller_Default {
	
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

		return $this->user_data ? true : false;
		
	}

	public function indexAction() {
		
		
		$request = $this->getRequest();
		
		if( !JO_Session::get('user[user_id]') ) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login') );
		}
		
		$request = $this->getRequest();
		
		///////////// boards
		$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory' );
		
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
		
		/////// add media
		
		$this->view->add_media_href = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=pinMediaCheck');
		
		//$this->initInstagram();
		
		$this->view->checkLoginInstagram = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=isInstagramUser');
		$this->view->getMediaInstagramFirst = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=getMedias&first=true');
		$this->view->getMediaInstagram = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=getMedias');
		
		
		/////////////curl request to get instagram media's
		/*$curl = new JO_Http();
		$curl->initialize(array(
				'target' => WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=cronfirst&user=' . JO_Session::get('user[user_id]') ),
				'method' => 'GET',
				'timeout' => 2
		));
		$curl->useCurl(true);
		$curl->execute();*/
		
		$req = new JO_Http_Async();
		$req->curl_get_async($this->getRequest()->getBaseUrl(), array(
			'controller' => 'modules_instagram_media',
			'action' => 'cronfirst',
			'user' => JO_Session::get('user[user_id]')
		) );
		
// 					var_dump($curl->result); exit;
		
		
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
	}
	
	public function isInstagramUserAction() {
		$instagram = null;
		$this->view->isUser = $this->initInstagram($instagram);
		$this->view->redirect = false;
		if(!$this->view->isUser) {
			$this->view->redirect = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_login&instagram_fnc=connect&action=forward&next=' . WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media') );
		}
		echo $this->renderScript('json');
	}
	
	public function getMediasAction() {
		$request = $this->getRequest();
		$medias = array();
		if($request->isXmlHttpRequest()) {
	
			$media_id_not_in = JO_Session::get('media_id_not_in_' . JO_Session::get('user[user_id]'));
			if($request->getQuery('first') == 'true') {
				$media_id_not_in = array();
			}
				
			if(!is_array($media_id_not_in)) {
				$media_id_not_in = array();
			}
				
			$data = array(
					'filter_user_id' => JO_Session::get('user[user_id]'),
					'media_id_not_in' => $media_id_not_in
			);
				
			$meduas = Model_Instagram_Media::getUserMediasData($data);
				
			
			foreach($meduas AS $image) {
				$old_image = basename($image['media']);
				$new_image = str_replace($old_image, str_replace('_7', '_5', $old_image), $image['media']);
				$medias[] = array(
						'title' => $image['title'],
						'media_id' => $image['media_id'],
						'thumb' => $new_image
				);
				$media_id_not_in[$image['media_id']] = $image['media_id'];
			}
				
			JO_Session::set('media_id_not_in_' . JO_Session::get('user[user_id]'), $media_id_not_in);
			//echo 'addResponseData('.JO_Json::encode($medias).');';
				
		}
		$this->noViewRenderer(true);
		$objectFormat = new Helper_Format();
		$objectFormat->responseJsonCallback($medias);
		
	}
	
	public function cronfirstAction() {
	
		set_time_limit(0);
		ignore_user_abort(true);
	
		$max_id = $this->getRequest()->getParam('max_id');
	
		$ud = new Model_Users_User($this->getRequest()->getParam('user'));
		
		if(!$ud->count()) {
			exit;
		}
	
		JO_Session::set('user', $ud->toArray());
	
		$connectObject = new Model_Instagram_Login();
		$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
		
		$InstagramAccessToken = $user_info['access_token'];
		$user_id = $ud['user_id'];
		$instagram_id = $user_info['oauth_uid'];
	
		// 		$this->initInstagram();
	
		$params = array(
				'access_token' => $InstagramAccessToken,
				'count' => 60,
				'max_id' => $max_id ? $max_id : ''
		);
	
		$result = $this->getMediaData($instagram_id, 300, $params);
	
		if( isset($result['meta']['code']) && $result['meta']['code'] == 200 ) {
				
			$return = (array)$result['data'];
			if($return) {
	
				foreach($return AS $img) {
					list($instagram_media_id, $instagram_profile_id) = explode('_', $img['id']);
					Model_Instagram_Media::addMedia(array(
							'user_id' => $user_id,
							'instagram_media_id' => $instagram_media_id,
							'width' => $img['images']['standard_resolution']['width'],
							'from' => $img['link'],
							'height' => $img['images']['standard_resolution']['height'],
							'media' => $img['images']['standard_resolution']['url'],
							'instagram_profile_id' => $instagram_profile_id,
							'md5key' => md5($img['id']),
							'title' => (string)(isset($img['caption']['text'])?$img['caption']['text']:$img['user']['username']),
							'pin_id' => ($this->checkDisabled($img['images']['standard_resolution']['url']) ? '0' : '-1')
					));
				}
	
				if (array_key_exists('next_url', $result['pagination'])) {
					$curl = new JO_Http();
					$curl->initialize(array(
							'target' => WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=cronfirst&user=' . $instagram_id . '&user_id='.$user_id.'&max_id=' . $result['pagination']['next_max_id'] ),
							'method' => 'GET',
							'timeout' => 10
					));
					$curl->useCurl(true);
					$curl->execute();
				}
			}
		}
	
		exit;
	}
	
	public function pinMediaCheckAction() {
		
		$request = $this->getRequest();
		
		$this->view->media = array();
		
		if(JO_Session::get('user[user_id]')) {
			
			$media_ids = $request->getPost('media_id');
			$board_info = new Model_Boards_Board($request->getPost('board_id'));
			if(is_array($media_ids) && count($media_ids) > 0) {
				if($board_info->count()) {
					$data = array(
							'filter_user_id' => JO_Session::get('user[user_id]'),
							'media_id_in' => $media_ids,
							'limit' => 'none'
					);
						
					$meduas = Model_Instagram_Media::getUserMediasData($data);
					$medias = array();
					foreach($meduas AS $image) {
						$medias[] = $image['media_id'];
					}
					
					$instagram_media = array(
						'media_id' => $medias,
						'board_id' => $board_info['board_board_id']
					);
					
					if($medias) {
						JO_Session::set('instagram_media', $instagram_media);
						$this->view->location = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=pinMedia');
					} else {
						$this->view->error = $this->translate('You must select media to pinit!');
					}
				} else {
					$this->view->error = $this->translate('You must select board to pinit!');
				}
			} else {
				$this->view->error = $this->translate('You must select media to pinit!');
			}
		} else {
			$this->view->location = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login');
		}
		
		echo $this->renderScript('json');
	}
	
	public function pinMediaAction() {
		
		$request = $this->getRequest();
		
		if( !JO_Session::get('user[user_id]') ) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login') );
		}
		
		$media_ids = JO_Session::get('instagram_media[media_id]');
		$board_id = JO_Session::get('instagram_media[board_id]');
		
		$data = array(
				'filter_user_id' => JO_Session::get('user[user_id]'),
				'media_id_in' => $media_ids,
				'limit' => 'none'
		);
			
		$meduas = Model_Instagram_Media::getUserMediasData($data);
		$this->view->medias = array();
		foreach($meduas AS $image) {
			$old_image = basename($image['media']);
			$new_image = str_replace($old_image, str_replace('_7', '_5', $old_image), $image['media']);
			$this->view->medias[] = array(
					'title' => $image['title'],
					'media_id' => $image['media_id'],
					'thumb' => $new_image
			);
		}
		
		$this->view->pin_media = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media&action=pinMediaCallback');
		$this->view->pin_media_fetch = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_instagram_media');
		
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
	public function pinMediaCallbackAction() {
		
		$request = $this->getRequest();
		
		if(JO_Session::get('user[user_id]')) {
			
			$media = Model_Instagram_Media::getMedia($request->getPost('media_id'));
			if($media) {
				if($media['user_id'] == JO_Session::get('user[user_id]')) {
					
					$result = new Model_Pins_Create(array(
						'title' => $media['title'],
						'from' => $media['from'],
						'image' => $media['media'],
						'description' => $media['title'],
						'board_id' => JO_Session::get('instagram_media[board_id]')
					));
					
					if($result->count()) {
						
						$result = $result->data;
						
						$this->view->pin_url = WM_Router::pinAction( $result['pin_id'] );

						Model_Instagram_Media::setPinMedia($media['media_id'], $result['pin_id']);
						
						///add history
						new Model_History_AddHistory(JO_Session::get('user[user_id]'), Model_History_Abstract::ADDPIN, $result['pin_id']);
						
						//send notification
						$users = new Model_Users_GroupBoardUsers(JO_Session::get('instagram_media[board_id]'));
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
													'pin_url' => WM_Router::pinAction($pin_info['pin_pin_id']),
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
						$this->view->ok = true;
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
					
				} else {
					$this->view->error = $this->translate('Private media!');
				}
			} else {
				$this->view->error = $this->translate('Media not found!');
			}
			
		} else {
			$this->view->location = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login');
		}
		
		echo $this->renderScript('json');
		
	}
	
	private function getMediaData($user_id, $timeout = 30, array $params) {
		$curl = new JO_Http();
		$curl->initialize(array(
				'target' => 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent',
				'method' => 'GET',
				'timeout' => $timeout,
				'params' => $params
		));
		$curl->useCurl(true);
		$curl->execute();
		return JO_Json::decode($curl->result, true);
	}
	
	private function checkDisabled($url) {
		$curl = new JO_Http();
		$curl->initialize(array(
				'target' => $url,
				'method' => 'GET',
				'timeout' => 10
		));
		$curl->useCurl(true);
		$curl->execute();
		return $curl->status == 200;
	}
	
}

?>