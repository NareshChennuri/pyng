<?php

class Model_Boards_Abstract extends ArrayObject {

	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	public static function getListBoardsQueryLite() {
		$db = JO_Db::getDefaultAdapter();

		$rows_boards = self::describeTable('boards','board_');
		/////other rows
		//$rows_boards['board_url'] = new JO_Db_Expr('('.$db->select()->from('url_alias', 'IF(`path`,`path`,`keyword`)')->where('query = CONCAT(\'board_id=\',boards.board_id)')->limit(1).')');
		$rows_boards['board_users_all'] =  new JO_Db_Expr('('.$db->select()->from('users_boards', 'GROUP_CONCAT(user_id)')->where("board_id = boards.board_id AND user_id != boards.user_id")->limit(1).')');
		$rows_boards['board_users_allow'] =  new JO_Db_Expr('('.$db->select()->from('users_boards', 'GROUP_CONCAT(user_id)')->where("board_id = boards.board_id AND user_id != boards.user_id AND allow = 1")->limit(1).')');
		$rows_boards['board_users_not_allow'] =  new JO_Db_Expr('('.$db->select()->from('users_boards', 'GROUP_CONCAT(user_id)')->where("board_id = boards.board_id AND user_id != boards.user_id AND allow = 0")->limit(1).')');
		
		$query = $db->select()
			->from('boards', $rows_boards);
		
		//for public boards
    	if(Helper_Config::get('config_private_boards')) {
    		if(JO_Session::get('user[user_id]')) {
    			$query->where('boards.user_id = ? OR IF(boards.user_id = ?, 1, boards.public)  = 1', JO_Session::get('user[user_id]'));
    		} else {
    			$query->where('boards.public = 1');
    		}
    	}
		
		return $query;
	}

	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	public static function getListBoardsQuery() {
		$db = JO_Db::getDefaultAdapter();

		$rows_users = self::describeTable('users','user_');
		$rows_boards = self::describeTable('boards','board_');
		/////other rows
		//$rows_boards['board_url'] = new JO_Db_Expr('('.$db->select()->from('url_alias', 'IF(`path`,`path`,`keyword`)')->where('query = CONCAT(\'board_id=\',boards.board_id)')->limit(1).')');
		$rows_boards['board_users_all'] =  new JO_Db_Expr('('.$db->select()->from('users_boards', 'GROUP_CONCAT(user_id)')->where("board_id = boards.board_id AND user_id != boards.user_id")->limit(1).')');
		$rows_boards['board_users_allow'] =  new JO_Db_Expr('('.$db->select()->from('users_boards', 'GROUP_CONCAT(user_id)')->where("board_id = boards.board_id AND user_id != boards.user_id AND allow = 1")->limit(1).')');
		$rows_boards['board_users_not_allow'] =  new JO_Db_Expr('('.$db->select()->from('users_boards', 'GROUP_CONCAT(user_id)')->where("board_id = boards.board_id AND user_id != boards.user_id AND allow = 0")->limit(1).')');

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
			$rows_boards['following_board'] = new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = boards.user_id')->limit(1).')+('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = boards.user_id')->where('board_id = boards.board_id')->limit(1) .') - ('.$db->select()->from('users_following_ignore','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = boards.user_id')->where('board_id = boards.board_id')->limit(1) .'))');
			$rows_boards['following_user'] = new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = boards.user_id')->limit(1).')+('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = boards.user_id')->where('board_id = boards.board_id')->limit(1) .'))');
		} else {
			$rows_boards['following_board'] = new JO_Db_Expr("0");
			$rows_boards['following_user'] = new JO_Db_Expr("0");
		}
		
    	$thumbs = Model_Upload_Abstract::userThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_users['user_avatar'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('users_avatars','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('user_id = users.user_id')->where('size = ?', $prefix)->limit(1).')');
    	}
		
    	$rows_boards['latest_pins'] = new JO_Db_Expr("SUBSTRING_INDEX((".$db->select()->from('pins','GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC)')->where('board_id = boards.board_id')->limit(15)."),',',15)");
    	
		$query = $db->select()
			->from('boards', $rows_boards)
			->joinLeft('users', 'boards.user_id = users.user_id', $rows_users);
		
		//for public boards
    	if(Helper_Config::get('config_private_boards')) {
    		if(JO_Session::get('user[user_id]')) {
    			$query->where('boards.user_id = ? OR IF(boards.user_id = ?, 1, boards.public)  = 1', JO_Session::get('user[user_id]'));
    		} else {
    			$query->where('boards.public = 1');
    		}
    	}
		
		return $query;
	}
	
	/**
	 * @param string $table
	 * @return array
	 */
	public static function describeTable($table, $row = '') {
		$db = JO_Db::getDefaultAdapter();
		$result = $db->describeTable($table);
		$data = array();
		foreach($result AS $res) {
			$data[$row . $res['COLUMN_NAME']] = $res['COLUMN_NAME'];
		}
		return $data;
	}
	
	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	public static function sortOrderLimit($query, $data = array()) {
		 
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		 
		$allow_sort = array(
			'boards.board_id',
			'boards.title',
			'boards.sort_order',
			'boards.total_views',
			'users_boards.sort_order'
		);
		 
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} elseif(isset($data['order']) && $data['order'] instanceof JO_Db_Expr) {
			$query->order($data['order']);
		} else {
			$query->order('boards.board_id' . $sort);
		}
		 
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		return $query;
	}
	
	public function toArray() {
		$tmp = array();
		foreach($this AS $key => $data) {
			$tmp[$key] = $data;
		}
		return $tmp;
	}
	
	/* v2.2 */
	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	protected static function filterFriend(JO_Db_Select $query) {
		$db = JO_Db::getDefaultAdapter();
		if(JO_Session::get('user[user_id]')) {
			$has_pins = 'boards.user_id = ? OR boards.user_id IN (SELECT user_id FROM users WHERE public = 1)';
			if(JO_Session::get('user[followers]')) {
				$has_pins .= ' OR boards.user_id IN (' . $db->select()->from('users_following','user_id')->where('following_id = ?')->where('users_following.board_id = boards.board_id') . ')';
				$has_pins .= ' OR boards.user_id IN (' . $db->select()->from('users_following_user','user_id')->where('following_id = ?') . ')';
			}
			$query->where(new JO_Db_Expr($has_pins), JO_Session::get('user[user_id]'));
			if(JO_Session::get('user[followers]')) {
				$query->where('boards.board_id NOT IN (SELECT board_id FROM `users_following_ignore` WHERE following_id = ?)', JO_Session::get('user[user_id]'));
			}
		} else {
			$query->where('boards.user_id IN (SELECT user_id FROM users WHERE public = 1)');
		}
		return $query;
	}
	/* end mod v2.2 */
	
}

?>