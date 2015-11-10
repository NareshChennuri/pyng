<?php

class Model_Users_Followers extends Model_Users_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default users data
		$query = self::getListUsersQuery();
	
		if(isset($data['filter_followers_user_id']) && $data['filter_followers_user_id']) {
			$query->where('user_id IN (SELECT user_id FROM users_following_user WHERE following_id = ?) OR user_id IN (SELECT user_id FROM users_following WHERE following_id = ?)', (string)$data['filter_followers_user_id']);
			//$query->where('user_id IN (SELECT following_id FROM users_following_user WHERE user_id = ?) OR user_id IN (SELECT following_id FROM users_following WHERE user_id = ?)', (string)$data['filter_followers_user_id']);
		} else {
			$query->where('user_id = 0');
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