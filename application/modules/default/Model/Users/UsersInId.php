<?php

class Model_Users_UsersInId extends Model_Users_Abstract {

	public function __construct($data) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
		
		if(is_array($data) && count($data) > 0) {
			$query->where('users.user_id IN (?)', new JO_Db_Expr(implode(',',$data)) );
		} else {
			$query->where('users.user_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		parent::__construct($db->fetchAll($query));
		
	}
	
}

?>