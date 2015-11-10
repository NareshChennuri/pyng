<?php

class Model_Boards_Search extends Model_Boards_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListBoardsQuery();
		
		$query->where('boards.pins > 0');
		
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