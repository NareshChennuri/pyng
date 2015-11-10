<?php

class Model_Users_Search extends Model_Users_Abstract {

	public function __construct($data) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
	
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('users.firstname LIKE ? OR users.lastname LIKE ? OR users.username LIKE ?', '%' . str_replace(' ', '%', $data['filter_username']) . '%');
		} else {
			$query->where('users.user_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
	
		//sort and limit add to query from Model_Users_Abstract
		$query = self::sortOrderLimit($query, $data);
		
		parent::__construct($db->fetchAll($query));
		
	}
	
}

?>