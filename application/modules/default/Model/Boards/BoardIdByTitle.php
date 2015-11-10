<?php

class Model_Boards_BoardIdByTitle extends Model_Boards_Abstract {

	public function __construct($title, $user_id = 0, $category_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		
		$user_id = $user_id ? $user_id : JO_Session::get('user[user_id]');
		
		//select default pin data
		$query = self::getListBoardsQuery();
	
		$query->where('boards.title LIKE ?', $title)
			  ->where('boards.user_id = ?', $user_id);
		$query->limit(1);
		
		$response = $db->fetchRow($query);
		if(!$response) {
			$result = new Model_Boards_Create(array(
				'title' => $title,
				'category_id' => $category_id,
				'user_id' => $user_id		
			));
			if($result->board_id) {
				$board_data = new Model_Boards_Board($result->board_id);
				if($board_data) {
					$response = $board_data->toArray();
				}
			}
		}
		$response = is_array($response) ? $response : array();
		parent::__construct($response);
		
	}
	
}

?>