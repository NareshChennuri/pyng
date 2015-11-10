<?php

class Model_Facebook_Login {
	
	public $row = 0;
	
	/**
	 * @var Helper_Modules_Facebook
	 */
	public $facebook;
	
	public function __construct($oauth_uid = null) {
		if($oauth_uid) {
			$db = JO_Db::getDefaultAdapter();
			$query = $db->select()
						->from('oauth_facebook')
						->where('oauth_uid = ?', $oauth_uid)
						->limit(1);
			
			$result = $db->fetchRow($query);
			if($result) {
				$this->row = $result;
			}
		}
	}
	
	public function update($data) {
		return Helper_Db::update('oauth_facebook', array(
				'access_token' => $this->facebook->getAccessToken()
				), array('id = ?' => $this->row['id']));
	}
	
	public function updateTimelineByUserId($user_id,$timeline) {
		return Helper_Db::update('oauth_facebook', array(
				'access_token' => $this->facebook->getAccessToken(),
				'timeline' => (int)$timeline
				), array('user_id = ?' => (string)$user_id));
	}
	
	public function insert($data) {
		return Helper_Db::insert('oauth_facebook', array(
				'email' => $data['email'],
				'user_id' => $data['user_id'],
				'oauth_uid' => $data['oauth_uid'],
				'access_token' => $data['access_token']
				));
	}
	
	///////////////////////////////////
	public function checkInvateFacebookIDSelf($facebook_id, $user_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('invate_facebook')
					->where('`facebook_id` = ?', (string)$facebook_id)
					->where('user_id = ?', (string)$user_id)
					->limit(1);
	
		return $db->fetchRow($query);
	}
	
	public function checkInvateFacebookID($facebook_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('invate_facebook')
					->where('`facebook_id` = ?', (string)$facebook_id)
					->limit(1);
	
		return $db->fetchRow($query);
	}
	
	public static function checkInvateFacebook($key) {
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('invate_facebook')
					->where('`code` = ?', (string)$key)
					->limit(1);
		
		return $db->fetchRow($query);
	}
	
	public function setInvate($data) {
		if(isset($data['if_id'])) {
			Helper_Db::delete('invate_facebook', array('if_id => ?' => $data['if_id']));
		}
		if(isset($data['self_id']) && isset($data['user_id'])) {
			$follow = new Model_Users_Follow($data['self_id'],$data['user_id']);
			if(!$follow->is_follow) {
				$follow->followUser();
			}
			$follow = new Model_Users_Follow($data['user_id'],$data['self_id']);
			if(!$follow->is_follow) {
				$follow->followUser();
			}
		}
	}
	
	public static function addInvateFacebook($user_id) {
		return Helper_Db::insert('invate_facebook', array(
			'user_id' => (string)JO_Session::get('user[user_id]'),
			'code' => md5($user_id),
			'facebook_id' => (string)$user_id
		));
	}
	
	public static function checkIsInvateFacebookFriend() {
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('invate_facebook', array('facebook_id', 'facebook_id'))
					->where('user_id = ?', (string)JO_Session::get('user[user_id]'));
		
		return $db->fetchPairs($query);
	}
	
	/////////////////////////////
	public function getDataByUserId($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('oauth_facebook')
					->where('user_id = ?', (string)$user_id)
					->limit(1);
	
		return $db->fetchRow($query);
	}
	
	public function deleteDataByUserId($user_id) {
		$db = JO_Db::getDefaultAdapter();
		return Helper_Db::delete('oauth_facebook', array('user_id = ?' => (string)$user_id));
	}
	
	public function getFacebookFriends() {
		$db = JO_Db::getDefaultAdapter();
		
		static $results = null;
		if($results !== null) return $results;
		
		$query = $db->select()
					->from('users_following_user', '')
					->joinLeft('users', 'users_following_user.following_id = users.user_id','')
					->where('users_following_user.user_id = ?', (string)JO_Session::get('user[user_id]'))
					->where('(SELECT COUNT(oauth_uid) FROM oauth_facebook WHERE user_id = users.user_id) > 0')
					->columns(array('(SELECT oauth_uid FROM oauth_facebook WHERE user_id = users.user_id LIMIT 1)', 'users_following_user.following_id'));
		
		$results = $db->fetchPairs($query);
		return $results;
	}
	
	public static function getFacebookFriendsNotFollow($facebook_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$rows_users = array('users.*');
		switch (Helper_Config::get('config_user_view')) {

			case 'username':

				$rows_users['fullname'] = new JO_Db_Expr('users.username');

				break;

			case 'firstname':

				$rows_users['fullname'] = new JO_Db_Expr('users.firstname');

				break;

			case 'fullname':

			default:

				$rows_users['fullname'] = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');

				break;

		}
		
		$query = $db->select()
					->from('users', $rows_users)
					->where('(SELECT COUNT(oauth_uid) FROM oauth_facebook WHERE oauth_uid = ? AND user_id = users.user_id)', (string)$facebook_id)
					->where('users.user_id NOT IN (SELECT following_id FROM users_following_user WHERE user_id = ?) AND users.user_id NOT IN (SELECT following_id FROM users_following WHERE user_id = ?)', (string)JO_Session::get('user[user_id]'))
					->limit(1);
		
		$result = $db->fetchRow($query);
		if(!$result) {
			return false;
		}
					
		return $result;
	}
	
	public static function getFacebookFriendsNotFollowByIds($facebook_ids) {
		$db = JO_Db::getDefaultAdapter();
		
		$rows_users = array('users.*');
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$rows_users['fullname'] = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$rows_users['fullname'] = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$rows_users['fullname'] = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
		/*$query = $db->select()
					->from('users', $rows_users)
					->where('(SELECT COUNT(oauth_uid) FROM oauth_facebook WHERE oauth_uid = ? AND user_id = users.user_id)', (string)$facebook_id)
					->where('users.user_id NOT IN (SELECT following_id FROM users_following_user WHERE user_id = ?) AND users.user_id NOT IN (SELECT following_id FROM users_following WHERE user_id = ?)', (string)JO_Session::get('user[user_id]'))
					->limit(1);*/
		
		if(!is_array($facebook_ids) || !count($facebook_ids)) {
			return false;
		}
		
		$query = $db->select()
					->from('oauth_facebook', 'oauth_uid')
					->joinLeft('users', 'oauth_facebook.user_id = users.user_id')
					->where('oauth_facebook.oauth_uid IN (?)', new JO_Db_Expr(implode(',',$facebook_ids)))
					->where('users.user_id NOT IN (SELECT following_id FROM users_following_user WHERE user_id = ?) AND users.user_id NOT IN (SELECT following_id FROM users_following WHERE user_id = ?)', (string)JO_Session::get('user[user_id]'));
		
		$result = $db->fetchAll($query);
		if(!$result) {
			return false;
		}

		$return = array();
		foreach($result AS $r) {
			$return[$r['oauth_uid']] = $r;
		}
		
		return $return;
	}
	
}

?>