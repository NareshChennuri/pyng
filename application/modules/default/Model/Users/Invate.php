<?php

class Model_Users_Invate extends Model_Users_Abstract {
	
	public $is_user = false;
	public $key = false;
	
	public function __construct($email = null) {
		if($email) {
			$db = JO_Db::getDefaultAdapter();
			$query = $db
				->select()
				->from('users')
				->where('email = ?', (string)$email)
				->limit(1);
			$this->is_user = $db->fetchRow($query);
			
			if($this->is_user) {
				return $this;
			}
			
			$query = $db
				->select()
				->from('shared_content')
				->where('email = ?', (string)$email)
				->limit(1);
			$key = $db->fetchRow($query);
			
			if($key) {
				$this->key = $key['key'];
				return $this;
			}
			
			$key = md5( time() . mt_rand() );
			
			$last = Helper_Db::insert('shared_content', array(
					'user_id' => JO_Session::get('user[user_id]'),
					'date_added' => new JO_Db_Expr('NOW()'),
					'key' => $key,
					'email' => $email,
					'send' => 1
			));
			
			if($last) {
				$this->key = $key;
				return $this;
			}
		}
	}
	
	public function isInvated($key, $user_id) {
		$db = JO_Db::getDefaultAdapter();
		
		//select default pin data
		$query = $db
					->select()
					->from('shared_content');
		
		$query->where('`key` = ?', (string)$key)
				->where('user_id = ? OR -1 = ?', (string)$user_id)
		->limit(1);
		
		return $db->fetchRow($query);
	}
	
	public function toArray() {
		$data = array();
		foreach($this AS $key => $value) {
			$data[$key] = $value;
		}
		return $data;
	}
	
	public static function isInvatedByEmail($email) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
					->select()
					->from('users')
					->where('email = ?', (string)$email)
					->limit(1);
		$user_data = $db->fetchRow($query);
		if($user_data) {
			return 1;
		}
		
		$query = $db
					->select()
					->from('shared_content')
					->where('email = ?', (string)$email)
					->limit(1);
		$user_data = $db->fetchRow($query);
		if($user_data) {
			return 2;
		}
		
		return false;
	}

}

?>