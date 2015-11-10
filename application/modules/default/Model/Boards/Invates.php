<?php

class Model_Boards_Invates extends Model_Boards_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListBoardsQuery();
	
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('boards.board_id IN (SELECT board_id FROM users_boards WHERE user_id = ? AND is_author = 0 AND allow = 0)', (string)$data['filter_user_id']);
		} else {
			$query->where('boards.board_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		$query = self::sortOrderLimit($query, $data);
		
		parent::__construct($db->fetchAll($query));
		
	}

	
	
}

?>