<?php

class Model_Users_Edit {
	
	public $affected_rows = null;

	public function __construct($user_id, $data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$user_info_get = new Model_Users_User($user_id);
			if(!$user_info_get) {
				return $this;
			}
			
			$rows = Helper_Db::describeTable('users');
			
			$update = array();
			$avatar = '';
			foreach($rows AS $row => $def) {
				if(isset($data[$row])) {
					if(in_array($row, array('password','new_password'))) {
						if($data[$row]) {
							if($data[$row] instanceof JO_Db_Expr) {
								$update[$row] = $data[$row];
							} else {
								$update[$row] = md5($data[$row]);
							}
						} else {
							$update[$row] = '';
						}
					} elseif($row == 'avatar') {
						$avatar = $data[$row];
						unset($data[$row]);
					} else {
						$update[$row] = $data[$row];
					}
				}
			}
			
			if(count($update) == 0 && $avatar == '') {
				return $this;
			}
			
			$result = Helper_Db::update('users', $update, array('user_id = ?' => (string)$user_id));
			
			//add user avatar
			if($avatar) {
				$image = false;
				$method_for_upload = Helper_Config::get('file_upload_method');
				if($method_for_upload) {
					$image = call_user_func(array($method_for_upload, 'uploadUserAvatar'), $avatar, $user_id );
				}
				
				$error = call_user_func(array($method_for_upload, 'getError') );
				
				if(!$error && $image && isset($image['image']) && $image['image']) {
					$res = Helper_Db::update('users', array(
							'avatar' => $image['image'],
							'store' => $image['store'],
							'height' => $image['height'],
							'width' => $image['width'],
							'last_action_datetime' => new JO_Db_Expr('NOW()')
					), array('user_id = ?' => (string)$user_id));
					if(!$result) { $result = $res; }	
					
					Helper_Db::delete('users_avatars', array('user_id = ?' => (string)$user_id));
				
					if($user_info_get && $user_info_get['avatar']) {
						if($user_info_get['avatar'] != $image['image']) {
							call_user_func(array($user_info_get['store'], 'deleteUserImage'), $user_info_get );
						}
					}
				
				}
			}
			
			if(isset($data['username'])) {

				$result = new Model_Users_Autoseo($user_id);
				
				if(!$result->affected_rows) { $result = $res; }	
			}
			
			$config_private_boards = Helper_Config::get('config_private_boards');
			
			$res = Helper_Db::update('users', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND (public = 1 OR user_id = users.user_id)' : '').')'),
					'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND (public = 1 OR user_id = users.user_id)' : '').')'),
					'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
					'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )'),
					'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE user_id = users.user_id), \',\', 15 ) )')
			), array('user_id = ?' => (string)$user_id));
			
			if(!$result) { $result = $res; }
			
			$this->affected_rows = $result;
			
			$db->commit();
			
		} catch ( JO_Exception $e ) {
			$db->rollBack();
		}
		
	}

}

?>