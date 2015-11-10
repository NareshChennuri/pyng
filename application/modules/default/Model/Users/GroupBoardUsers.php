<?php

class Model_Users_GroupBoardUsers extends Model_Users_Abstract {

	public function __construct($board_id) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
		
		$query->where('users.user_id IN (?)', new JO_Db_Expr( '('.$db->select()->from('users_boards', 'user_id')->where('board_id = ?', (string)$board_id)->where('allow = 1').')' ));
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		parent::__construct($db->fetchAll($query));
		
	}
	
}

?>