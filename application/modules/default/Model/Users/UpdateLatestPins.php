<?php

class Model_Users_UpdateLatestPins extends Model_Users_Abstract {

	public function __construct($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$user_info = new Model_Users_User($user_id);
		if($user_info->count()) {
			
			$config_private_boards = Helper_Config::get('config_private_boards');
			
			Helper_Db::update('users', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND (public = 1 OR user_id = users.user_id)' : '').')'),
					'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND (public = 1 OR user_id = users.user_id)' : '').')'),
					'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
					'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )'),
					'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE user_id = users.user_id), \',\', 15 ) )')
			), array('user_id = ?' => $user_id));
			
		}
	}
	
}

?>