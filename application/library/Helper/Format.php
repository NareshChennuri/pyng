<?php

class Helper_Format extends JO_Model {
	
	public function fromatUserFollow($user) {
		
		$request = $this->getRequest();
		
		$loged = JO_Session::get('user[user_id]');
		
		$user_data = array();
		foreach($user AS $k => $v) {
			if(strpos($k, 'user_') === 0) {
				$user_data[substr($k, 5)] = $v;
			}
		}
		
		$user_href = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_user_id'] );
		
		if($user['history_action'] == Model_History_Abstract::FOLLOW_USER) {
			$title = sprintf($this->translate('Followed %s.'), '<a href="'.$user_href.'">'.$user['user_fullname'].'</a>');
		} elseif($user['history_action'] == Model_History_Abstract::UNFOLLOW_USER) {
			$title = sprintf($this->translate('Unfollowed %s.'), '<a href="'.$user_href.'">'.$user['user_fullname'].'</a>');
		} else {
			$title = '';
		}
		
		$date_dif = array_shift( WM_Date::dateDiff($user['date_added'], time()) );
		
		/* v2.2 */
		$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
		if($config_enable_follow_private_profile) {
			$user_enable_follow = $user['user_enable_follow'];
		} else {
			$user_enable_follow = true;
		}
		/* v2.2 */
		
		return array(
			'template' => 'activity_follow_user',
			'history_id' => $user['history_id'],
			'title' => $title,
			'loged' => $loged,
			'user_id' => $user['user_user_id'],
			'avatars' => Helper_Uploadimages::userAvatars($user_data),
			'date_dif' => sprintf($this->translate('%d %s ago'), $date_dif['value'], $this->translate($date_dif['key'])),
			//follow
			'following_user' => $user['following_user'],
			/* v2.2 mod */
			'enable_follow_user' => ($loged ? $user['user_user_id'] != $loged && $user_enable_follow : false),
			/* v2.2 mod */
			//links
			'user_follow_href' => $loged && $user['user_user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user['user_user_id'] ) : false,
			'user_href' => $user_href,
			//texts
			'text_follow' => $this->translate('Follow'),
			'text_unfollow' => $this->translate('Unfollow')
		);
	}
	
	public function fromatListUserFollowing($user) {
		
		$request = $this->getRequest();
		
		$limit_thumbs = 8;
		
		$pins_filter = array();
		$latest = explode(',',$user['latest_pins']);
		if($latest) {
			foreach($latest AS $l) {
				if($l) {
					$pins_filter[] = $l;
				}
			}
		}
		
		$pins = $pins_filter ? new Model_Pins_PinsThumbsForFollowersAndFollowing(array(
			'pins' => $pins_filter,
			'start' => 0,
			'limit' => $limit_thumbs
		)) : new ArrayObject();
		
		$thumbs = array();
		
		$total_pins = $pins->count();
		for( $i = 0; $i < min( $limit_thumbs, $total_pins ); $i++ ) {
			if(isset($pins->data[$i])) {
				$pin = $pins->data[$i];
				$thumbs[] = array(
					'images' => Helper_Uploadimages::pinThumbs($pin),
					'href' => WM_Router::pinAction($pin['pin_pin_id'])
				);
			}
		}
		
		$loged = JO_Session::get('user[user_id]');
		
		/* v2.2 */
		$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
		if($config_enable_follow_private_profile) {
			$user_enable_follow = isset($user['enable_follow'])?$user['enable_follow']:$user['user_enable_follow'];
		} else {
			$user_enable_follow = true;
		}
		/* v2.2 */
		
		return array(
			'template' => 'user_followers',
			'row' => isset($user['row']) ? ($user['row']%2==0) : 0,
			'loged' => $loged,
			'user_id' => $user['user_id'],
			'avatars' => Helper_Uploadimages::userAvatars($user),
			'fullname' => $user['fullname'],
			'location' => $user['location'],
			'latest_pins' => $thumbs,
			'pins' => $user['pins'],
			'boards' => $user['boards'],
			//follow
			'following_user' => $user['following_user'],
			/* v2.2 mod */
			'enable_follow_user' => ($loged ? $user['user_id'] != $loged && $user_enable_follow : false),
			/* v2.2 mod */
			//links
			'user_follow_href' => $loged && $user['user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user['user_id'] ) : false,
			'user_href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'] ),
			'pins_href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=pins&user_id=' . $user['user_id'] ),
			//texts
			'text_pin_s' => $user['pins'] == 1 ? $this->translate('Pin') : $this->translate('Pins'),
			'text_board_s' => $user['boards'] == 1 ? $this->translate('Board') : $this->translate('Boards'),
			'text_follow' => $this->translate('Follow'),
			'text_unfollow' => $this->translate('Unfollow')
			
		);
	}
	
	public function fromatListUserFollowers($user) {
		
		$request = $this->getRequest();
		
		$limit_thumbs = 8;
		
		$pins_filter = array();
		$latest = explode(',',$user['latest_pins']);
		if($latest) {
			foreach($latest AS $l) {
				if($l) {
					$pins_filter[] = $l;
				}
			}
		}
		
		$pins = $pins_filter ? new Model_Pins_PinsThumbsForFollowersAndFollowing(array(
			'pins' => $pins_filter,
			'start' => 0,
			'limit' => $limit_thumbs
		)) : new ArrayObject();
		
		$thumbs = array();
		
		$total_pins = $pins->count();
		for( $i = 0; $i < min( $limit_thumbs, $total_pins ); $i++ ) {
			if(isset($pins->data[$i])) {
				$pin = $pins->data[$i];
				$thumbs[] = array(
					'images' => Helper_Uploadimages::pinThumbs($pin),
					'href' => WM_Router::pinAction($pin['pin_pin_id'])
				);
			}
		}
		
		$loged = JO_Session::get('user[user_id]');
		
		/* v2.2 */
		$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
		if($config_enable_follow_private_profile) {
			$user_enable_follow = isset($user['enable_follow'])?$user['enable_follow']:$user['user_enable_follow'];
		} else {
			$user_enable_follow = true;
		} 
		/* v2.2 */
		
		return array(
			'template' => 'user_followers',
			'row' => isset($user['row']) ? ($user['row']%2==0) : 0,
			'loged' => $loged,
			'user_id' => $user['user_id'],
			'avatars' => Helper_Uploadimages::userAvatars($user),
			'fullname' => $user['fullname'],
			'location' => $user['location'],
			'latest_pins' => $thumbs,
			'pins' => $user['pins'],
			'boards' => $user['boards'],
			//follow
			'following_user' => $user['following_user'],
			/* v2.2 mod */
			'enable_follow_user' => ($loged ? $user['user_id'] != $loged && $user_enable_follow : false),
			/* v2.2 mod */
			//links
			'user_follow_href' => $loged && $user['user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $user['user_id'] ) : false,
			'user_href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'] ),
			'pins_href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=pins&user_id=' . $user['user_id'] ),
			//texts
			'text_pin_s' => $user['pins'] == 1 ? $this->translate('Pin') : $this->translate('Pins'),
			'text_board_s' => $user['boards'] == 1 ? $this->translate('Board') : $this->translate('Boards'),
			'text_follow' => $this->translate('Follow'),
			'text_unfollow' => $this->translate('Unfollow')
			
		);
	}
	
	public function fromatListBoard($board) {
		
		$request = $this->getRequest();
		
		$limit_thumbs = 5;
		
		$pins_filter = array();
		$latest = explode(',',$board['latest_pins']);
		if($latest) {
			foreach($latest AS $l) {
				if($l) {
					$pins_filter[] = $l;
				}
			}
		}
		
		$pins = $pins_filter ? new Model_Pins_PinsThumbsForBoard(array(
			'cover' => $board['board_cover'],
			'pins' => $pins_filter,
			'start' => 0,
			'limit' => $limit_thumbs
		)) : new ArrayObject();
		
		
		
		$thumbs = array();
		
		$total_pins = $pins->count();
		for( $i = 0; $i < min( $limit_thumbs, max($total_pins, $limit_thumbs) ); $i++ ) {
			if(isset($pins->data[$i])) {
				$pin_data = $pins->data[$i];
				foreach($pin_data AS $key => $ppd) {
					if(strpos($key, 'pin_') === 0) {
						$pin_data[substr($key, 4)] = $ppd;
					}
				}
				$thumbs[] = Helper_Uploadimages::pinThumbs($pin_data);
			} else {
				$thumbs[] = false;
			}
		} 
		
		$loged = JO_Session::get('user[user_id]');
		
		$user_data = array();
		foreach($board AS $k => $v) {
			if(strpos($k, 'user_') === 0) {
				$user_data[substr($k, 5)] = $v;
			}
		}

		/* v2.2 */
		$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
		if($config_enable_follow_private_profile) {
			$user_enable_follow = $board['user_enable_follow'];
		} else {
			$user_enable_follow = true;
		}
		/* v2.2 */
		
		return array(
			'template' => 'boards',
			'loged' => $loged,
			'board_id' => $board['board_board_id'],
			'autor_id' => $board['board_user_id'],
			'history_id' => (isset($board['history_id'])&&$board['history_id']?$board['history_id']:0),
			'title' => $board['board_title'],
			'fullname' => $board['user_fullname'],
			'following_board' => $board['following_board'],
			'following_user' => $board['following_user'],
			'thumbs' => $thumbs,
			'avatars' => Helper_Uploadimages::userAvatars($user_data),
			'pins' => $board['board_pins'],
			'enable_sort' => isset($board['enable_sort'])&& $board['enable_sort']?$board['enable_sort']:false,
			//follow
			/* v2.2 mod */
			'enable_follow_board' => ($loged ? $board['board_user_id'] != $loged && $user_enable_follow : false),
			'enable_follow_user' => ($loged ? $board['board_user_id'] != $loged && $user_enable_follow : false),
			/* v2.2 mod */
			'enable_follow_user1' => array($loged!=$board['board_user_id'],$loged,$board['board_user_id'],$loged,false),
			//links
			'user_href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $board['board_user_id'] ),
			'user_follow_href' => $loged && $board['board_user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $board['board_user_id'] ) : false,
			'board_follow_href' => $loged && $board['board_user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=follow&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'] ) : false,
			'edit_url' => $loged && $board['board_user_id'] == $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=edit&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'] ) : false,
			'board_url' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'] ),
			'board_cover' => $loged && $board['board_user_id'] == $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=cover&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'] . (isset($board['history_id'])&&$board['history_id']?'&hid='.$board['history_id']:'') ) : false,
			'board_accept' => $loged && $board['board_user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=allowInvate&type=accept&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'] ) : false,
			'board_decline' => $loged && $board['board_user_id'] != $loged ? WM_Router::create( $request->getBaseUrl() . '?controller=users&action=allowInvate&type=decline&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'] ) : false,
			//texts
			'text_pin_s' => $board['board_pins'] == 1 ? $this->translate('Pin') : $this->translate('Pins'),
			'text_follow' => $this->translate('Follow'),
			'text_unfollow' => $this->translate('Unfollow'),
			'text_edit' => $this->translate('Edit'),
			'text_edit_board_cover' => $this->translate('Edit Board cover'),
			'text_accept' => $this->translate('Accept'),
			'text_decline' => $this->translate('No thanks'),
			'text_invited_you' => $this->translate('invited you to this board')
				
		);
	}
	
	///////////////////////////////////// Format Listing Data for pins /////////////////////////////////////
	public function fromatList($pin) {
		
		static $extensions = null;
		if($extensions === null) {
			$extensions = Model_Extensions::getByMethod('pin_list');
		}
		
		$pin = array_merge($pin, Helper_Pin::formatPinData($pin));
		
		$pin_data = array(
				'template' => 'pins',
				'pin_id' => $pin['pin_id'],
				'pin_row' => $pin['pin_pin_row'],
				'category_id' => $pin['board_category_id'],
				'loged' => JO_Session::get('user[user_id]') ? true : false,
				'is_liked' => $pin['pin_is_liked'],
				'from' => $pin['pin_from'],
				'vip' => $pin['pin_vip'],
				'gift' => $pin['pin_gift'],
				'price' => $pin['pin_price_formated'],
				'date_added' => $pin['pin_date_added'],
				'is_video' => $pin['pin_is_video'],
				'description' => str_replace('&amp;', '&', $pin['pin_description']),
				'likes' => $pin['pin_likes'],
				'comments' => $pin['pin_comments'],
				'repins' => $pin['pin_repins'],
				'set_activity_title' => isset($pin['set_activity_title']) ? $pin['set_activity_title'] : false,
				//via
				'via_profile' => $pin['via_profile']?$pin['via_profile']:false,
				//self
				'self_profile' => $pin['self_profile'],
				//author
				'author_profile' => array(
					'avatars' => $pin['user_avatars'],
					'fullname' => $pin['user_fullname'],
					'user_id' => $pin['user_user_id'],
					'href' => $pin['user_href']
				),
				//links
				'url_repin' => $pin['pin_url_repin'],
				'url_edit' => $pin['pin_edit'],
				'url_like' => $pin['pin_url_like'],
				'url_comment' => $pin['pin_url_comment'],
				'pin_url' => $pin['pin_href'],
				'board_url' => $pin['pin_onto_href'],
				//comments
				'pin_comments_data' => $pin['pin_latest_comments']?$pin['pin_latest_comments']:false,
				//images
				'images' => $pin['pin_thumbs'],
				//'gallery' => $pin['gallery'],
				//text's
				'text_vip' => $this->translate('VIP'),
				'text_add_comment' => $this->translate('Add a comment...'),
				'text_delete_comment' => $this->translate('Delete Comment'),
				'text_via' => $pin['via_profile']?$this->translate('via'):'',
				'text_onto' => $this->translate('onto'),
				'text_repin' => $this->translate('Repin'),
				'text_edit' => $this->translate('Edit'),
				'text_like_unlike' => $pin['pin_is_liked']?$this->translate('Unlike'):$this->translate('Like'),
				'text_comment' => $this->translate('Comment'),
				'text_total_likes' => sprintf( $this->translate('%d like' . ($pin['pin_likes'] == 1 ? '' : 's')), $pin['pin_likes'] ),
				'text_total_comments' => sprintf( $this->translate('%d comment' . ($pin['pin_comments'] == 1 ? '' : 's')), $pin['pin_comments'] ),
				'text_total_repins' => sprintf( $this->translate('%d repin' . ($pin['pin_repins'] == 1 ? '' : 's')), $pin['pin_repins'] ),
				'text_date_dif' => sprintf($this->translate('%d %s ago'), $pin['pin_date_dif']['value'], $this->translate($pin['pin_date_dif']['key'])),
				'text_board' => $pin['board_title'],
				'text_all_comments' => $pin['pin_comments'] && $pin['pin_comments'] > (int)Helper_Config::get('config_comments_list') ? sprintf($this->translate('All %d comments...'), $pin['pin_comments']) : false
			);
		
		if($extensions) {
			$front = JO_Front::getInstance();
			foreach($extensions AS $id => $ext) {
				$pin_data_ext = call_user_func(array($front->formatModuleName('model_' . $ext . '_pin'), 'listing'), $pin, $pin_data);
				if($pin_data_ext && is_array($pin_data_ext)) {
					$pin_data = array_merge($pin_data, $pin_data_ext);
				}
			}
		}
		
		return $pin_data;
		
	}
	
	public function fromatListBanners($banners) {
		$banners_result = array();
		if($banners) {
			foreach($banners AS $banner) {
				$banners_result[] = array(
						'width' => $banner['width'],
						'height' => $banner['height'],
						'html' => html_entity_decode($banner['html'], ENT_QUOTES, 'utf-8')
				);
			}
			if($banners_result) {
				return array(
						'template' => 'pin_banner',
						'banners' => $banners_result
				);
			}
		}
		return false;
	}
	
	public function fromatListNoResults($message = '') {
		return array(
				'template' => 'no_results',
				'message' => $message
		);
	}
	
	public function responseJsonCallback($return) {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$callback = $request->getRequest('callback');
		if(!preg_match('/^([a-z0-9_.]{1,})$/i', $callback)) {
			$callback = false;
		}
		
		if($callback) {
			$return = $callback . '(' . JO_Json::encode( $return ) . ')';
		} else {
			$response->addHeader('Cache-Control: no-cache, must-revalidate');
			$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			$response->addHeader('Content-type: application/json');
			$return = JO_Json::encode( $return );
		}
		
		$response->appendBody($return);
		
	}

}

?>