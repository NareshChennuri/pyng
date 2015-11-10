<?php

class Model_History_Activity extends Model_History_Abstract {

	public function __construct($data, $row = 'to_user_id', $user_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!$user_id) {
			$user_id = (string)JO_Session::get('user[user_id]');
		}

		$rows_users = self::describeTable('users','user_');

		$rows_boards = self::describeTable('boards','board_');
		$rows_pins = self::describeTable('pins','pin_');
    	/////other rows
    	$rows_pins['pin_gift'] = new JO_Db_Expr('pins.price > 0.0000');
	
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$rows_users['user_fullname'] = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$rows_users['user_fullname'] = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$rows_users['user_fullname'] = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
    	if(JO_Session::get('user[user_id]')) {
    		$rows_pins['pin_is_liked'] = new JO_Db_Expr('('.$db->select()->from('pins_likes', 'COUNT(like_id)')->where('pin_id = pins.pin_id')->where('user_id = ?', JO_Session::get('user[user_id]'))->limit(1).')');
    	} else {
    		$rows_pins['pin_is_liked'] = new JO_Db_Expr("0");
    	}
    	$rows_boards['latest_pins'] = new JO_Db_Expr("SUBSTRING_INDEX((".$db->select()->from('pins','GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC)')->where('board_id = boards.board_id')->limit(15)."),',',15)");
    	
		$query = $db->select()
			->from('users_history')
			->joinLeft('users', 'users_history.'.($row == 'to_user_id'?'from':'to').'_user_id = users.user_id', $rows_users)
	    	->joinLeft('pins', 'users_history.pin_id = pins.pin_id', $rows_pins)
			->joinLeft('boards', 'IF(pins.board_id,pins.board_id,users_history.board_id) = boards.board_id', $rows_boards)
	    	->where($row . ' = ?', $user_id);
			
		if(isset($data['filter_history_action']) && (int)$data['filter_history_action']) {
			$query->where('history_action = ?', (int)$data['filter_history_action']);
		}
		
		if(JO_Session::get('user[user_id]')) {
			$query->columns(array('following_board'=>new JO_Db_Expr('('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = users_history.'.($row == 'to_user_id'?'from':'to').'_user_id')->where('board_id = users_history.board_id')->limit(1) .')')));
			$query->columns(array('following_user'=>new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = users_history.'.($row == 'to_user_id'?'from':'to').'_user_id')->limit(1).') + ('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = users_history.'.($row == 'to_user_id'?'from':'to').'_user_id')->where('board_id = users_history.board_id')->limit(1) .'))')));
		} else {
			$query->columns(array('following_board'=>new JO_Db_Expr("0")));
			$query->columns(array('following_user'=>new JO_Db_Expr("0")));
		}
			
		//sort and limit add to query from Model_History_Abstract
		$query = self::sortOrderLimit($query, $data);
		
		parent::__construct($db->fetchAll($query));

	}
	
	public static function getHistoryV2($data, $row = 'to_user_id', $user_id = 0) {
		$db = JO_Db::getDefaultAdapter();
	
		if(!$user_id) {
			$user_id = (string)JO_Session::get('user[user_id]');
		}
		
		$rows_history = self::describeTable('users_history','history_');
		$rows_users = self::describeTable('users','user_');
		
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$rows_users['user_fullname'] = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$rows_users['user_fullname'] = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$rows_users['user_fullname'] = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
		$query = $db->select()
			->from('users_history', $rows_history)
			->joinLeft('users', ($row=='to_user_id'?'users_history.from_user_id':'users_history.to_user_id').' = users.user_id', $rows_users)
			->where($row . ' = ?', $user_id);
			
		if(isset($data['filter_history_action']) && (int)$data['filter_history_action']) {
			$query->where('users_history.history_action = ?', (int)$data['filter_history_action']);
		}
			
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
	
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
	
		$allow_sort = array(
			'history_id'
		);
	
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('history_id' . $sort);
		}
	
		$results = $db->fetchAll($query); 
		$data = array();
		if($results) {
			foreach($results AS $result) {
				$result['history_text_type'] = self::getType($result['history_history_action']);
				if($result['history_text_type']) {
					$result['history_date_dif'] = array_shift( WM_Date::dateDiff($result['history_date_added'], time()) );
					$data[] = $result;
				}
			}
		}
		return $data;
	
	}
	
}

?>