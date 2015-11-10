<?php

class Model_Boards_UpdateLatestPins extends Model_Boards_Abstract {

	public function __construct($board_id) {
		$db = JO_Db::getDefaultAdapter();
		$board_info = new Model_Boards_Board($board_id);
		if($board_info->count()) {
			
			$config_private_boards = Helper_Config::get('config_private_boards');
			
			$db->update('boards', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE board_id = boards.board_id '.($config_private_boards ? ' AND (public = 1 OR board_id = boards.board_id)' : '').')'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM users_following_ignore WHERE following_id = boards.user_id AND board_id = boards.board_id AND user_id != boards.user_id LIMIT 1) )'),
					'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE board_id = boards.board_id), \',\', 15 ) )')
			), array('board_id = ?' => (string)$board_id));
			
		}
	}
	
}

?>