<?php

class Model_Users_UserByEmail extends Model_Users_Abstract {

	public function __construct($email) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
	
		$query->where('users.email = ?', (string)$email)
			->limit(1);
		
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