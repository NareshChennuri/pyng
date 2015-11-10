<?php

class Search_PeopleController extends Helper_Controller_Default {

	public function config() {
		$request = $this->getRequest();
		return array(
			'sort' => 3,
			'title' => $this->translate('People'),
			'active' => in_array($request->getAction(), array('people')),
			'href' => WM_Router::create($request->getBaseUrl() . '?controller=search&action=people&q=' . urlencode($request->getQuery('q')))
		);
	}
	
	public function getSearchResultAction($return_data = false) {
	
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$query = $request->getRequest('q');
	
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
				'filter_username' => $query
		);
	
		$return = array();
	
	
		/* set board count */
		$has_people = true;
		if(!trim($query)) {
			$has_people = false;
		}
	
		// pins data
		$peoples = $has_people ? new Model_Users_Search($data) : new ArrayObject();
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_people && $peoples->count()) {
			$loged = JO_Session::get('user[user_id]');
			/* v2.2 */
			$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
			/* v2.2 */
			foreach($peoples AS $row => $user) {
				//pins
				$return[] = array(
					'template' => 'people_search',
					'user_id' => $user['user_id'],
					'loged' => $loged,
					'avatars' => Helper_Uploadimages::userAvatars($user),
					'fullname' => $user['fullname'],
					'location' => $user['location'],
					//follow
					'user_is_follow' => $user['following_user'],
					/* v2.2 mod */
					'enable_follow_user' => $loged && $loged != $user['user_id'] && ($config_enable_follow_private_profile ? $user['enable_follow'] : true),
					/* v2.2 mod */
					//links
					'user_href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'] ),
					'user_follow_href' => $loged && $loged != $user['user_id'] ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user['user_id'] ) : false,
					//text
					'text_follow' => $this->translate('Follow'),
					'text_unfollow' => $this->translate('Unfollow'),
				);
			}
				
		} else {
			if($page == 1) {
				$message = $this->translate('No users!');
			} else {
				$message = $this->translate('No more users!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
	
	}
	
	///////////////autocomplete/
	
	public function autocomplete($query) {
		$request = $this->getRequest();
		
		$result = array();
		
		$has_friends = (int)JO_Session::get('user[following]') + (int)JO_Session::get('user[followers]');
		
		$users = $has_friends ? new Model_Users_SearchAutocomplete(array(
			'filter_username' => $query,
			'start' => 0,
			'limit' => 100
		)) : new ArrayObject();
		
		if($has_friends && $users->count()) {
			foreach($users AS $user) {
				$result[] = array(
						'template' => 'user',
						'avatars' => Helper_Uploadimages::userAvatars($user),
						'fullname' => $user['fullname'],
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'])
				);
			}
		}
		
		return $result;
	}
	
	
	
}

?>