<?php

class Model_Boards_SearchAutocomplete extends Model_Boards_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListBoardsQueryLite();
		
		$query->where("(boards.user_id = ? OR boards.board_id IN (SELECT DISTINCT board_id FROM users_boards WHERE user_id = ? AND allow = 1))", (string)JO_Session::get('user[user_id]'));
		
		if(isset($data['filter_title']) && $data['filter_title']) {
			$data['filter_title'] = str_replace(' ', '%', $data['filter_title']);
			$data['filter_title'] = preg_replace('/([\%]{2,})/', '%', $data['filter_title']);
			$query->where('boards.title LIKE ?', '%' . (string)$data['filter_title'] . '%');
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