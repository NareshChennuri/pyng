<?php

class Model_Users_LikesPin extends Model_Users_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default users data
		$query = self::getListUsersQuery();
	
		if(isset($data['filter_like_pin_id']) && !is_null($data['filter_like_pin_id'])) {
			$query->where('users.user_id IN (' . new JO_Db_Expr($db->select()->from('pins_likes','user_id')->where('pin_id = ?')) . ')', (string)$data['filter_like_pin_id']);
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
	
		//sort and limit add to query from Model_Pins_Abstract
		$query = self::sortOrderLimit($query, $data);
	
		parent::__construct($db->fetchAll($query));
	
	}
	
}

?>