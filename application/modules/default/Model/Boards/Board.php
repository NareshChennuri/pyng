<?php

class Model_Boards_Board extends Model_Boards_Abstract {

	public function __construct($board_id) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListBoardsQuery();
	
		$query->where('boards.board_id = ?', (string)$board_id);
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		$query->limit(1);
		
		$response = $db->fetchRow($query); 

		$response = is_array($response) ? $response : array();
		parent::__construct($response);
		
	}
	
}

?>