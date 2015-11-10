<?php

class Model_Users_User extends Model_Users_Abstract {

	public function __construct($user_id) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
	
		$query->where('users.user_id = ?', (string)$user_id)
			->limit(1);
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		$response = $db->fetchRow($query);
		$response = is_array($response) ? $response : array();
		parent::__construct($response);
		
	}
	
	public function toArray() {
		$data = array();
		foreach($this AS $key => $value) {
			$data[$key] = $value;
		}
		return $data;
	}
	
}

?>