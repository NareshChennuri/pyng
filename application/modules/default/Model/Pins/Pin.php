<?php

class Model_Pins_Pin extends Model_Pins_Abstract {

	public function __construct($pin_id) {
		$db = JO_Db::getDefaultAdapter();
	
		$query = self::getListPinsQuery();
	
		//$query->columns(array('pin_next'=>new JO_Db_Expr('('.$db->select()->from(array('next'=>'pins'),'pin_id')->where('pin_id > ?', (string)$pin_id)->order('pin_id ASC')->limit(1).')')));
		//$query->columns(array('pin_prev'=>new JO_Db_Expr('('.$db->select()->from(array('prev'=>'pins'),'pin_id')->where('pin_id < ?', (string)$pin_id)->order('pin_id DESC')->limit(1).')')));
	
		if(JO_Session::get('user[user_id]')) {
			$query->columns(array('following_board'=>new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->limit(1).') + ('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->where('board_id = pins.board_id')->limit(1) .')-('.$db->select()->from('users_following_ignore','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->where('board_id = pins.board_id')->limit(1) .'))')));
			$query->columns(array('following_user'=>new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->limit(1).') + ('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->where('board_id = pins.board_id')->limit(1) .'))')));
			$query->columns(array('following_via'=>new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.via')->limit(1).') + ('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.via')->where('board_id = pins.board_id')->limit(1) .'))')));
		} else {
			$query->columns(array('following_board'=>new JO_Db_Expr("0")));
			$query->columns(array('following_user'=>new JO_Db_Expr("0")));
			$query->columns(array('following_via'=>new JO_Db_Expr("0")));
		}
		
		$rows_source = self::describeTable('pins_sources','source_');
		
		$query->joinLeft('pins_sources', 'pins.source_id = pins_sources.source_id', $rows_source);
	
		$query->where('pins.pin_id = ?', (string)$pin_id);
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}

		$response = $db->fetchRow($query);
		$response = is_array($response) ? $response : array();
		
		$this->data = $response;
// 		parent::__construct($response);
		
	}
	
}

?>