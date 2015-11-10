<?php

class Model_Boards_Follow {
	
	public $board_id;
	
	public $user_id;
	
	public $is_follow = null;
	
	public $is_follow_user = null;
	
	public function __construct($board_id) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$board_info = new Model_Boards_Board($board_id);
		
		if(!$board_info->count()) {
			return $this;
		}
		
		$this->board_id = $board_id;
		$this->user_id = $board_info['user_user_id'];
		
		if(!(string)JO_Session::get('user[user_id]') || (string)JO_Session::get('user[user_id]') == $board_info['user_user_id']) {
			return $this;
		}
		
		$query = $db->select()
						->from('users_following', 'COUNT(users_following_id)')
						->where('user_id = ?', (string)JO_Session::get('user[user_id]'))
						->where('board_id = ?', (string)$board_id)
						->limit(1);
			
		$is_follow = $db->fetchOne($query);
		
		if($is_follow) {
			$query = $db->select()
						->from('users_following_ignore', 'COUNT(users_following_id)')
						->where('user_id = ?', (string)JO_Session::get('user[user_id]'))
						->where('board_id = ?', (string)$board_id)
						->limit(1);
				
			$is_follow_disable = $db->fetchOne($query);
			if($is_follow_disable) {
				$is_follow = false;
			}
		}
		
		if(!$is_follow) {
			$is_follow_user = $this->isFollowUser($board_info['user_user_id']);
			if($is_follow_user) {
				$is_follow = true;
			}
		}
		
		$this->is_follow = $is_follow ? true : false;
	
	}
	
	public function followBoard() {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			Helper_Db::delete('users_following', array(
					'user_id = ?' => (string)JO_Session::get('user[user_id]'),
					'board_id = ?' => (string)$this->board_id
			));
			
			$uf_id = Helper_Db::insert('users_following', array(
					'user_id' => (string)JO_Session::get('user[user_id]'),
					'following_id' => (string)$this->user_id,
					'board_id' => (string)$this->board_id
			));
			
			if($uf_id) {
				Helper_Db::delete('users_following_ignore', array(
						'user_id = ?' => (string)JO_Session::get('user[user_id]'),
						'board_id = ?' => (string)$this->board_id
				));
				
			}
			
			
			
			$is_follow_user = new Model_Users_Follow($this->user_id);
			$this->is_follow_user = $is_follow_user->is_follow;
			
			$this->updateStat();
			
			$db->commit();
			return $uf_id ? true : false;
		} catch (JO_Exception $e) {
			$db->rollBack();
		}
		
		return null;
	}
	
	public function unfollowBoard() {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$row = Helper_Db::delete('users_following', array(
					'user_id = ?' => (string)JO_Session::get('user[user_id]'),
					'board_id = ?' => (string)$this->board_id
			));
			
			$is_fow = $this->isFollowUser($this->user_id);
			
			if($row || $is_fow) {
				
				if($is_fow) {
					$row = Helper_Db::insert('users_following_ignore', array(
							'user_id' => (string)JO_Session::get('user[user_id]'),
							'board_id' => (string)$this->board_id,
							'following_id' => (string)$this->user_id
					));
				}
			}
			
			$is_follow_user = new Model_Users_Follow($this->user_id);
			$this->is_follow_user = $is_follow_user->is_follow;
			
			$this->updateStat();
			
			$db->commit();
			return $row ? true : false;
		} catch (JO_Exception $e) {
			$db->rollBack();
		}
		
		return null;
	}
	
	public static function totalBoardFollow($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$sql = $db->select()
					->from('users_following', 'COUNT(users_following_id)')
					->where('user_id = ?', $user_id)
					->limit(1);
		return $db->fetchOne($sql);
	}
	
	public static function isFollowUser($user_id) {
		if(!(string)JO_Session::get('user[user_id]') || (string)JO_Session::get('user[user_id]') == $user_id) {
			return false;
		}
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('users_following_user', 'COUNT(ufu_id)')
					->where('user_id = ?', (string)JO_Session::get('user[user_id]'))
					->where('following_id = ?', (string)$user_id)
					->limit(1);
		
		return $db->fetchOne($query);
	}
	
	public function updateStat() {
		Helper_Db::update('users', array(
				'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)JO_Session::get('user[user_id]')));
		Helper_Db::update('users', array(
				'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
		), array('user_id = ?' => (string)$this->user_id));
		
		Helper_Db::update('boards', array(
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM users_following_ignore WHERE following_id = boards.user_id AND board_id = boards.board_id AND user_id != boards.user_id LIMIT 1) )')
		), array('board_id = ?' => (string)$this->board_id));
	}

}

?>