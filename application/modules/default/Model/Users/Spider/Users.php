<?php

class Model_Users_Spider_Users extends JO_Model {
	
	public function getUserByName($username, $namers, $avatar) {
		$db = JO_Db::getDefaultAdapter();
		if(self::isExistUsername($username)) {
			return $db->fetchOne($db->select()->from('users', 'user_id')->where('username=?',(string)$username)->limit(1));
		} else {
			$exp = explode(' ',$namers);
			$firsname = array_shift($exp);
			$last = implode(' ', $exp);
			
			Helper_Config::set('default_boards', array());
			
			$response = new Model_Users_Create(array(
				'username' => (string)$username,
				'firstname' => (string)$firsname,
				'lastname' => (string)$last,
				'avatar' => (string)$avatar,
				'email' => $username . '@spider-imports',
				'first_login' => 0
			));
			
			return $response->user_id;
		}
	}
	
	public static function isExistUsername($username, $old_username=FALSE) {
	    if($username==$old_username) {
			return false;
	    }
	        
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('users', new JO_Db_Expr('COUNT(user_id)'))
					->where('username = ?', $username);
		
		return $db->fetchOne($query)>0 ? true : false;
	}
	
}

?>