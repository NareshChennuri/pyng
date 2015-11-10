<?php

class Model_Boards_PopularBoards extends Model_Boards_Abstract {

	public function __construct($data) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListBoardsQuery();
		
		if(isset($data['where']) && $data['where'] instanceof JO_Db_Expr) {
			$query->where($data['where']);
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