<?php

class Model_History_AddHistory extends Model_History_Abstract {
	
	public function __construct($to, $type, $pin_id = 0, $board_id = 0, $comment = '') {
		
		if($to == JO_Session::get('user[user_id]')) {
			return;
		} else if(!JO_Session::get('user[user_id]')) {
			return;
		}
		
		$history_id = Helper_Db::insert('users_history', array(
			'date_added' => new JO_Db_Expr('NOW()'),
			'from_user_id' => (string)JO_Session::get('user[user_id]'),
			'to_user_id' => (string)$to,
			'history_action' => (int)$type,
			'pin_id' => (string)$pin_id,
			'board_id' => (string)$board_id,
			'comment' => $comment
		));
		
		if($history_id) {
			if(self::FOLLOW == $type) {
				Helper_Db::delete('users_history', array('to_user_id = ?' => (string)$to,'from_user_id = ?' => (string)JO_Session::get('user[user_id]'), 'history_action = ?' => self::UNFOLLOW, 'board_id = ?' => (string)$board_id));
			} elseif(self::UNFOLLOW == $type) {
				Helper_Db::delete('users_history', array('to_user_id = ?' => (string)$to,'from_user_id = ?' => (string)JO_Session::get('user[user_id]'), 'history_action = ?' => self::FOLLOW, 'board_id = ?' => (string)$board_id));
			} elseif(self::FOLLOW_USER == $type) {
				Helper_Db::delete('users_history', array('to_user_id = ?' => (string)$to,'from_user_id = ?' => (string)JO_Session::get('user[user_id]'), 'history_action = ?' => self::UNFOLLOW_USER));
			} elseif(self::UNFOLLOW_USER == $type) {
				Helper_Db::delete('users_history', array('to_user_id = ?' => (string)$to,'from_user_id = ?' => (string)JO_Session::get('user[user_id]'), 'history_action = ?' => self::FOLLOW_USER));
			} elseif(self::LIKEPIN == $type) {
				Helper_Db::delete('users_history', array('to_user_id = ?' => (string)$to,'from_user_id = ?' => (string)JO_Session::get('user[user_id]'), 'history_action = ?' => self::UNLIKEPIN, 'pin_id = ?' => (string)$pin_id));
			} elseif(self::UNLIKEPIN == $type) {
				Helper_Db::delete('users_history', array('to_user_id = ?' => (string)$to,'from_user_id = ?' => (string)JO_Session::get('user[user_id]'), 'history_action = ?' => self::LIKEPIN, 'pin_id = ?' => (string)$pin_id));
			}
		}
	}
	
}

?>