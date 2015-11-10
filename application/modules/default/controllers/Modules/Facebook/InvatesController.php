<?php

class Modules_Facebook_InvatesController extends Helper_Controller_Default {

	public function indexAction() {
		
		JO_Session::clear('redirect_fb_login');
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=users&action=login') );
		}
		
		$this->view->site_name = Helper_Config::get('site_name');
		
		$this->view->friends = array();
		
		$this->view->getfriends = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=modules_facebook_invates&action=getFriends' );
		
		//		$this->view->invate_href = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=register&user_id=' . JO_Session::get('user[user_id]') . '&code=' );
		$this->view->invate_href = WM_Router::create( $this->getRequest()->getBaseUrl() . '?invate_code=' );
		$this->view->add_to_invate = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=modules_facebook_invates&action=addInvate' );
		
		
		$this->view->baseUrl = $request->getBaseUrl();
		
		$this->view->user_avatars = Helper_Uploadimages::userAvatars(JO_Session::get('user'));
		
		$this->view->site_name = Helper_Config::get('site_name');
		$this->view->meta_description = Helper_Config::get('meta_description');
		
		$this->view->oauth_fb_key = Helper_Config::get('facebook_oauth_key');
		$this->view->fb_session = true;//$this->facebook->getSession();
		
		$this->view->facebook_connect2 = WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_invates&action=facebook_connect2' );
		
		//		var_dump($this->view->fb_session); exit;
		/**/
		
		//$facebook_friends = $this->getFriends();
		
		
		$facebookObject = new Model_Facebook_Login();
		//$follows = $facebookObject->getFacebookFriends();
		
		$this->view->friends = array();
		$this->view->friends_not_follow = array();
		$this->view->not_profile_users = array();
		/*if($facebook_friends) {
			$friends = $this->formatUsers($facebook_friends);
			if($friends) {
				$model_images = new Helper_Images();
				foreach($friends AS $friend) {
					if( array_key_exists($friend['id'], $follows) ) {
						$user_data = new Model_Users_User($follows[$friend['id']]);
						if($user_data->count()) {
							$avatar = Helper_Uploadimages::avatar($user_data, '_A');
							$user_data['avatar'] = $avatar['image'];
							$user_data['profile'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_data['user_id'] );
							$user_data['follow_user'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user_data['user_id'] );
							$this->view->friends[] = $user_data;
						}
					} else if( ($user_data = $facebookObject->getFacebookFriendsNotFollow($friend['id'])) !== false ) {
						if($user_data) {
							$avatar = Helper_Uploadimages::avatar($user_data, '_A');
							$user_data['avatar'] = $avatar['image'];
							$user_data['profile'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_data['user_id'] );
							$user_data['follow_user'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user_data['user_id'] );
							$this->view->friends_not_follow[] = $user_data;
						}
					} else {
						$this->view->not_profile_users[] = array(
								'avatar' => $friend['avatar'],
								'id' => $friend['id'],
								'name' => $friend['name'],
								'key' => $friend['key']
						);
					}
				}
			}
		}*/
		
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part',
				'methodsforinvates' => 'invates/methodsforinvates'
		);
		
		
	}
	public function addInvateAction(){
		$request = $this->getRequest();
		
		if($request->isXmlHttpRequest()) {
			if(JO_Session::get('user[user_id]')) {
				$facebookObject = new Model_Facebook_Login();
				$res = $facebookObject->addInvateFacebook($request->getPost('user_id'));
				if($res) {
					echo 'success';
				} else {
					echo $this->translate('There was a problem with the record. Please try again!');
				}
				exit;
				
			} else {
				exit;
			}
		} else {
			$this->forward('error','error404');
		}
		
	}
	
	private function getFriends() {
		static $results_array = null;
		if($results_array !== null) return $results_array;
		
		$request = $this->getRequest();
		
		$facebook = new Helper_Modules_Facebook();
		
		$me = $facebook->getUser();
		
		if(!$me) {
			return false;
		}
		
		/*if( !$me && !JO_Session::get('redirect_fb_login') ) {
			
			$redirect = $facebook->getLoginUrl(
					WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_invates' ),
					'modules_facebook_login'
			);
			JO_Session::set('redirect_fb_login',1);
			$this->redirect($redirect);
			
		}*/
		
		$limit = 1000;
		
		$fbData = null;
		if($me) {
			$fbData = $facebook->facebook->api('/me/friends?limit=' . $limit);
		}
		
		$results_array = array();
		if(isset($fbData['data']) && $fbData['data']) {
			$results_array = $fbData['data'];
		}
		
		if( count($results_array) >= $limit ) {
			$has_others = true;
			$pages = 1;
			while( $has_others ) {
				if($pages > 10) {$has_others=false; break; }
				if(isset($fbData['paging']['next'])) {
					//$results = @file_get_contents($fbData['paging']['next'] . '&access_token=' . $session['access_token']);
					$next = explode('/friends?',$fbData['paging']['next']);
					if(isset($next[1]) && $next[1]) {
						$fbData = $facebook->facebook->api('/me/friends?' . $next[1]);
						if(isset($fbData['data']) && $fbData['data']) {
							$results_array = array_merge($results_array, $fbData['data']);
						} else {
							$fbData = null;
							$has_others = false;
						}
					} else {
						$fbData = null;
						$has_others = false;
					}
				} else {
					$fbData = null;
					$has_others = false;
				}
			}
		}

		return $results_array;
	}
	/* END GET FRIENDS */
	
	public function getFriendsAction() {
		
		$request = $this->getRequest();
		
		$facebookObject = new Model_Facebook_Login();
		$follows = $facebookObject->getFacebookFriends();
		
		$facebook = new Helper_Modules_Facebook();
		
		$facebook_friends = $this->getFriends();
		
		if( $facebook_friends === false && (!JO_Session::get('redirect_fb_login') || (int)JO_Session::get('redirect_fb_login') < time() ) ) {
				
			$this->view->redirect = $facebook->getLoginUrl(
					WM_Router::create( $request->getBaseUrl() . '?controller=modules_facebook_invates' ),
					'modules_facebook_login'
			);
			JO_Session::set('redirect_fb_login',time() + 100);
			echo $this->renderScript('json');
			exit;
		}
		
		//$facebook_friends = $this->getFriends();
		
		$follow_friends_array = array();
		if($follows) { 
			$follow_friends = new Model_Users_UsersInId($follows);
			if($follow_friends->count()) {
				foreach($follow_friends AS $ff) {
					foreach($follows AS $fr_fb => $fr_pi) {
						if($fr_pi == $ff['user_id']) {
							$follow_friends_array[$fr_fb] = $ff;
						}
					}
				}
			}
		}

		$this->view->fb_friends = array();
		if($facebook_friends) {
			$friends = $this->formatUsers($facebook_friends);
			if($friends) {
				
				$facebook_ids = array();
				foreach($friends AS $fr) {
					$facebook_ids[] = $fr['id'];
				}
				
				$not_follow_users = $facebookObject->getFacebookFriendsNotFollowByIds($facebook_ids);
				if(!$not_follow_users) {
					$not_follow_users = array();
				}
				
				$model_images = new Helper_Images();
				foreach($friends AS $friend) {
					
					if( array_key_exists($friend['id'], $follow_friends_array) ) {
						$user_data = $follow_friends_array[$friend['id']];
						if($user_data) {
							$avatar = Helper_Uploadimages::avatar($user_data, '_A');
							$this->view->fb_friends['friends_unfollow'][] = array(
								'profile' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_data['user_id'] ),
								'follow_user' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user_data['user_id'] ),
								'avatar' => $avatar['image'],
								'name' => $user_data['fullname'],
								'user_id' => $user_data['user_id'],
								'template' => 'facebook/friends_unfollow',
								//texts
								'text_unfollow' => $this->translate('Unfollow'),
								'text_follow' => $this->translate('Follow')
							);
						}
					} else if( array_key_exists($friend['id'], $not_follow_users) ) {
						$user_data = $not_follow_users[$friend['id']];
						if($user_data) {
							$avatar = Helper_Uploadimages::avatar($user_data, '_A');
							$this->view->fb_friends['friends_follow'][] = array(
									'profile' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_data['user_id'] ),
									'follow_user' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user_data['user_id'] ),
									'avatar' => $avatar['image'],
									'name' => $user_data['fullname'],
									'user_id' => $user_data['user_id'],
									'template' => 'facebook/friends_follow',
									//texts
									'text_unfollow' => $this->translate('Unfollow'),
									'text_follow' => $this->translate('Follow')
							);
						}
					} else {
						if(!$facebookObject->checkInvateFacebookIDSelf($friend['id'], JO_Session::get('user[user_id]'))) {
							$this->view->fb_friends['friends_invate'][] = array(
									'profile' => 'http://facebook.com/' . $friend['id'],
									'key' => $friend['key'],
									'avatar' => $friend['avatar'],
									'id' => $friend['id'],
									'name' => $friend['name'],
									'template' => 'facebook/friends_invate',
									//texts
									'text_invate' => $this->translate('Invite')
							);
						}
					}
				}
			}
		}
		
		echo $this->renderScript('json');
	}
	
	private function formatUsers($data) {
		$friends = array();
		foreach($data AS $fr) {
			$friends[] = array(
				'id' => $fr['id'],
				'key' => md5($fr['id']),
				'name' => $fr['name'],
				'avatar' => 'http://graph.facebook.com/'.$fr['id'].'/picture'
			);
		}
		
		return $friends;
		
	}
	
}

?>