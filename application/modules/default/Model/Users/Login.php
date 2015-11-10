<?php

class Model_Users_Login extends Model_Users_Abstract {

	public function __construct($username, $password) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQuery();
	
		$query->where('email = ? OR username = ?', (string)$username);
		$query->where('password = ?', (string)md5($password))
			->limit(1);
		
		$response = $db->fetchRow($query);
		$response = is_array($response) ? $response : array();
		if($response) {
			$groups = unserialize($response['groups']);
			if(is_array($groups) && count($groups) > 0) {
				$query_group = $db->select()
					->from('user_groups')
					->where("ug_id IN (?)", new JO_Db_Expr(implode(',', array_keys($groups))));
				$fetch_all = $db->fetchAll($query_group);
				$response['access'] = array();
				if($fetch_all) {
					foreach($fetch_all AS $row) {
						$modules = unserialize($row['rights']);
						if(is_array($modules)) {
							foreach($modules AS $module => $ison) {
								foreach($ison AS $m => $on) {
									$response['access'][$module][$m] = $m;
								}
							}
						}
					}
				}
			}
			parent::__construct($response);
			new Model_Users_Edit($response['user_id'], array(
				'last_login' => new JO_Db_Expr('NOW()')		
			));
		}
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