<?php

class Model_Twitter_Login {
	
	public $row = 0;
	
	public function __construct($oauth_uid = null) {
		if($oauth_uid) {
			$db = JO_Db::getDefaultAdapter();
			$query = $db->select()
						->from('oauth_twitter')
						->where('oauth_uid = ?', $oauth_uid)
						->limit(1);
			
			$result = $db->fetchRow($query);
			if($result) {
				$this->row = $result;
			}
		}
	}
	
	public function update($data) {
		return Helper_Db::update('oauth_twitter', $data, array('id = ?' => $this->row['id']));
	}
	
	public function updateTwitByUserId($user_id,$data) {
		return Helper_Db::update('oauth_twitter', array(
				'twitter_oauth_token' => $data['twitter_oauth_token'],
				'twitter_oauth_token_secret' => $data['twitter_oauth_token_secret'],
				'twit' => (int)$data['twit'],
				'username' => $data['username']
				), array('user_id = ?' => (string)$user_id));
	}
	
	public function insert($data) {
		return Helper_Db::insert('oauth_twitter', array(
				'username' => $data['username'],
				'user_id' => $data['user_id'],
				'oauth_uid' => $data['oauth_uid'],
				'twitter_oauth_token' => $data['twitter_oauth_token'],
				'twitter_oauth_token_secret' => $data['twitter_oauth_token_secret']
				));
	}
	
	/////////////////////////////
	public function getDataByUserId($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('oauth_twitter')
					->where('user_id = ?', (string)$user_id)
					->limit(1);
	
		return $db->fetchRow($query);
	}
	
	public function deleteDataByUserId($user_id) {
		$db = JO_Db::getDefaultAdapter();
		return Helper_Db::delete('oauth_twitter', array('user_id = ?' => (string)$user_id));
	}

}

?>