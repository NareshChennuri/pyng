<?php

class Model_Users_Follow {
	
	public $user_id;
	public $user_id2;
	
	public $is_follow = null;
	
	public function __construct($user_id, $user_id2 = null) {
		
		if($user_id2 === null) {
			if(!(string)JO_Session::get('user[user_id]') || (string)JO_Session::get('user[user_id]') == $user_id) {
				return $this;
			}
			$user_id2 = JO_Session::get('user[user_id]');
		}
		
		$this->user_id2 = $user_id2;
		
		$user_info = new Model_Users_User($user_id);
		if(!$user_info->count()) {
			return $this;
		}
		
		$this->user_id = $user_id;
		
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('users_following_user', 'COUNT(ufu_id)')
					->where('user_id = ?', (string)$this->user_id2)
					->where('following_id = ?', (string)$user_id)
					->limit(1);
		
		$is_follow = $db->fetchOne($query);
		
		if(!$is_follow) {
			$query = $db->select()
						->from('users_following', 'COUNT(users_following_id)')
						->where('user_id = ?', (string)$this->user_id2)
						->where('following_id = ?', (string)$user_id)
						->limit(1);
			
			$is_follow = $db->fetchOne($query);
		}
		
		$this->is_follow = $is_follow ? true : false;
	
	}
	
	public function followUser() {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$uf_id = Helper_Db::insert('users_following_user', array(
					'user_id' => (string)$this->user_id2,
					'following_id' => (string)$this->user_id
			));
			
			if($uf_id) {
				Helper_Db::delete('users_following_ignore', array(
						'user_id = ?' => (string)$this->user_id2,
						'following_id = ?' => (string)$this->user_id
				));
				
			}
			
			$this->updateStat();
			
			$db->commit();
			return $uf_id ? true : false;
		} catch (JO_Exception $e) {
			$db->rollBack();
		}
		
		return null;
	}
	
	public function unfollowUser() {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			$row = Helper_Db::delete('users_following_user', array(
					'user_id = ?' => (string)$this->user_id2,
					'following_id = ?' => (string)$this->user_id
			));
			
			if(!$row) {
				$row = Helper_Db::delete('users_following', array(
						'user_id = ?' => (string)$this->user_id2,
						'following_id = ?' => (string)$this->user_id
				));
			}
			
			if($row) {
				Helper_Db::delete('users_following_ignore', array(
						'user_id = ?' => (string)$this->user_id2,
						'following_id = ?' => (string)$this->user_id
				));
				Helper_Db::delete('users_following', array(
						'user_id = ?' => (string)$this->user_id2,
						'following_id = ?' => (string)$this->user_id
				));
				
			}
			
			$this->updateStat();
			
			$db->commit();
			return $row ? true : false;
		} catch (JO_Exception $e) {
			$db->rollBack();
		}
		
		return null;
	}
	
	public function updateStat() {
		/*Helper_Db::update('users', array(
				'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)$this->user_id2));
		Helper_Db::update('users', array(
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)$this->user_id));*/
		Helper_Db::update('users', array(
				'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)$this->user_id2));
		Helper_Db::update('users', array(
				'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)$this->user_id));
		Helper_Db::update('boards', array(
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM users_following_ignore WHERE following_id = boards.user_id AND board_id = boards.board_id AND user_id != boards.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)$this->user_id));
	}

}

?>