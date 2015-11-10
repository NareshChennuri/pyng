<?php

class Model_Users_UsersIds extends Model_Users_Abstract {

	public function __construct($data) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		//sort and limit add to query from Model_Users_Abstract
		$query = self::sortOrderLimit($query, $data);
		$query->where('users.pins > 0');
		parent::__construct($db->fetchPairs($query));
		
	}
	
}

?>