<?php

class Model_Boards_TotalInvates extends Model_Boards_Abstract {
	
	public $total = 0;
	
	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = $db->select()
					->from('users_boards', 'COUNT(board_id)');
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('user_id = ? AND is_author = 0 AND allow = 0', $data['filter_user_id']);
		} else {
			$query->where('users_boards.board_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query2 = $db->select()
						->from('boards', 'board_id');
			$query2 = self::filterFriend($query2);
			$query->where('users_boards.board_id IN (' . $query2 . ')');
		}

		$this->total = $db->fetchOne($query);
		
	}
}

?>