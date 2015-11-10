<?php

class Model_Instagram_Login {
	
	public $row = 0;
	
	/**
	 * @var Helper_Modules_Facebook
	 */
	public $facebook;
	
	public function __construct($oauth_uid = null) {
		if($oauth_uid) {
			$db = JO_Db::getDefaultAdapter();
			$query = $db->select()
						->from('oauth_instagram')
						->where('oauth_uid = ?', $oauth_uid)
						->limit(1);
			
			$result = $db->fetchRow($query);
			if($result) {
				$this->row = $result;
			}
		}
	}
	
	public function update($data) {
		return Helper_Db::update('oauth_instagram', array(
				'username' => $data['username'],
				'access_token' => $data['access_token']
				), array('id = ?' => $this->row['id']));
	}
	
	public function insert($data) {
		return Helper_Db::insert('oauth_instagram', array(
				'username' => $data['username'],
				'user_id' => $data['user_id'],
				'oauth_uid' => $data['oauth_uid'],
				'access_token' => $data['access_token']
				));
	}
	
	/////////////////////////////
	public function getDataByUserId($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('oauth_instagram')
					->where('user_id = ?', (string)$user_id)
					->limit(1);
	
		return $db->fetchRow($query);
	}
	
	public function deleteDataByUserId($user_id) {
		$db = JO_Db::getDefaultAdapter();
		return Helper_Db::delete('oauth_instagram', array('user_id = ?' => (string)$user_id));
	}
	
}

?>