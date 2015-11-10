<?php

class Model_Users_Create {
	
	public $user_id = null;
	
	public $error = array();
	
	public function __construct($data) {
		
		$db = JO_Db::getDefaultAdapter();
		try {
			
			$db->beginTransaction();
			
			$date_added = WM_Date::format(time(), 'yy-mm-dd H:i:s');
			$data['date_added'] = $date_added;
			$data['last_login'] = $date_added;
			$data['status'] = 1;
			$data['last_action_datetime'] = $date_added;
			$data['ip_address'] = JO_Request_Server::encode_ip( JO_Request::getInstance()->getClientIp() );
			
			$data['new_email'] = $data['email'];
			$data['store'] = JO_Registry::get('default_upload_method');
			if(!$data['store']) {
				$data['store'] = 'Model_Upload_Locale';
			}
			
			/*$avatar = '';
			if(isset($data['avatar']) && $data['avatar']) {
				$avatar = $data['avatar'];
				$data['avatar'] = '';
			}*/
			
			$rows = Helper_Db::describeTable('users');
			
			$insert = array();
			$avatar = '';
			foreach($rows AS $row => $def) {
				if(isset($data[$row])) {
					if(in_array($row, array('password','new_password'))) {
						if($data[$row]) {
							if($data[$row] instanceof JO_Db_Expr) {
								$insert[$row] = $data[$row];
							} else {
								$insert[$row] = md5($data[$row]);
							}
						} else {
							$insert[$row] = '';
						}
					} elseif($row == 'avatar') {
						$avatar = $data[$row];
						$data[$row] = '';
					} else {
						$insert[$row] = $data[$row];
					}
				} else {
					$insert[$row] = $def;
				}
			}
			
			//create user
			$user_id = Helper_Db::create('users', $insert);
			
			if(!$user_id) {
				return $this;
			}
			
			//upload avatar
			if($avatar) {
				$method_for_upload = Helper_Config::get('file_upload_method');
				if($method_for_upload) {
					$image = call_user_func(array($method_for_upload, 'uploadUserAvatar'), $avatar, $user_id);
					$error = call_user_func(array($method_for_upload, 'getError'));
					if($error) {
						$this->error[] = $error;
					}
					
					if($image && isset($image['image']) && $image['image']) {
						Helper_Db::update('users', array(
							'avatar' => $image['image'],
							'store' => $image['store'],
							'height' => $image['height'],
							'width' => $image['width'],
						), array('user_id = ?' => (string)$user_id));
					}
				}
			}
			
			//create user alias
			new Model_Users_Autoseo($user_id);
			/*Helper_Db::insert('url_alias', array(
					'query' => 'user_id=' . (string)$user_id,
					'keyword' => $data['username'],
					'path' => $data['username'],
					'route' => 'users/profile'
			));*/
			
			//add default boards
			if( is_array(Helper_Config::get('default_boards')) ) {
				foreach(Helper_Config::get('default_boards') AS $def) {
					new Model_Boards_Create(array(
							'category_id' => $def['category_id'],
							'title' => $def['title'],
							'user_id' => (string)$user_id
					));
				}
			}
			
			//set following
			
			$config_private_boards = Helper_Config::get('config_private_boards');
			
			if( isset($data['following_user']) && $data['following_user'] && $data['following_user'] != -1 ) {
				Helper_Db::insert('users_following_user', array(
						'user_id' => (string)$user_id,
						'following_id' => (string)$data['following_user']
				));
				Helper_Db::insert('users_following_user', array(
						'user_id' => (string)$data['following_user'],
						'following_id' => (string)$user_id
				));
				//update following user info
				Helper_Db::update('users', array(
						'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
						'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
						'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
						'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
						'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )'),
						//'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE user_id = users.user_id), ',', 15 ) )')
				), array('user_id = ?' => (string)$data['following_user']));
				
			}
			
			//update user info
			Helper_Db::update('users', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
					'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
					'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
					'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )'),
					//'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE user_id = users.user_id), ',', 15 ) )')
			), array('user_id = ?' => (string)$user_id));
			
			$this->user_id = $user_id;
			
			$db->commit();
			
		} catch (JO_Exception $e) {
			$this->error[] = $e->getMessage();
			$db->rollBack();
		}
		
	}

}

?>