<?php

class Model_Boards_BoardsWithShared extends Model_Boards_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListBoardsQuery();
	
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			if(JO_Session::get('user[user_id]') && JO_Session::get('user[user_id]') == (string)$data['filter_user_id']) {
				$query->where('boards.user_id = ? OR boards.board_id IN (SELECT board_id FROM users_boards WHERE user_id = ? AND allow = 1 AND (SELECT user_id FROM users WHERE user_id = ? AND public = 1))', (string)JO_Session::get('user[user_id]'));
			} else {
				$query->where('(boards.user_id = ? AND users.public = 1) OR boards.board_id IN (SELECT board_id FROM users_boards WHERE user_id = ? AND allow = 1 AND (SELECT user_id FROM users WHERE user_id = ? AND public = 1))', (string)$data['filter_user_id']);
			}
		} else {
			$query->where('boards.user_id = ? OR boards.board_id IN (SELECT board_id FROM users_boards WHERE user_id = ? AND allow = 1)', (string)$data['filter_user_id']);
		}

		$query = self::sortOrderLimit($query, $data);
		
		parent::__construct($db->fetchAll($query));
		
	}
	
}

?>