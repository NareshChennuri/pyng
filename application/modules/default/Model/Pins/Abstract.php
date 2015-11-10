<?php

class Model_Pins_Abstract /*extends ArrayObject*/ {
	
	public static $searchWordLenght = 3;
	
	public $data = array();
	
	/* (non-PHPdoc)
	 * @see ArrayObject::count()
	 */
	public function count() {
		return is_array($this->data) ? count($this->data) : 0;
	}
	
    /**
     * @return Ambigous <JO_Db_Select, JO_Db_Select>
     */
    public static function getListPinsQueryLite() {
    	$db = JO_Db::getDefaultAdapter();
    	
    	$rows_pins = self::describeTable('pins','pin_');

    	$thumbs = Model_Upload_Abstract::pinThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_pins['pin_thumb'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('pins_images','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('pin_id = pins.pin_id')->where('size = ?', $prefix)->limit(1).')');
    	}
    	
   		$query = $db->select()
	    	->from('pins', $rows_pins);
    	
    	//for public boards
    	if(Helper_Config::get('config_private_boards')) {
    		if(JO_Session::get('user[user_id]')) {
    			$query->where('pins.user_id = ? OR IF(pins.user_id = ?, 1, pins.public)  = 1', JO_Session::get('user[user_id]'));
    		} else {
    			$query->where('pins.public = 1');
    		}
    	}
    	
    	return $query;
    }
	
    /**
     * @return Ambigous <JO_Db_Select, JO_Db_Select>
     */
    public static function getListPinsQuery() {
    	$db = JO_Db::getDefaultAdapter();
    	
    	$rows_pins = self::describeTable('pins','pin_');
    	$rows_users = self::describeTable('users','user_');
    	$rows_via = self::describeTable('users','via_');
    	$rows_boards = self::describeTable('boards','board_');
    	/////other rows
    	$rows_pins['pin_gift'] = new JO_Db_Expr('pins.price > 0.0000');
    	
    	//$rows_boards['board_url'] = new JO_Db_Expr('('.$db->select()->from('url_alias', 'IF(`path`,`path`,`keyword`)')->where('query = CONCAT(\'board_id=\',boards.board_id)')->limit(1).')');
    	
    	switch (Helper_Config::get('config_user_view')) {
    		case 'username':
    			$rows_users['user_fullname'] = new JO_Db_Expr('users.username'); 
    			$rows_via['via_fullname'] = new JO_Db_Expr('via.username');
    		break;
    		case 'firstname':
    			$rows_users['user_fullname'] = new JO_Db_Expr('users.firstname'); 
    			$rows_via['via_fullname'] = new JO_Db_Expr('via.firstname');
    		break;
    		case 'fullname':
    		default:
    			$rows_users['user_fullname'] = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)'); 
    			$rows_via['via_fullname'] = new JO_Db_Expr('CONCAT(via.firstname, " ", via.lastname)');
    		break;
    	}
    	
    	
    	/*if(JO_Session::get('user[user_id]')) {
    	 $rows_pins['following_board'] = new JO_Db_Expr('('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->where('board_id = pins.board_id')->limit(1) .')');
    	$rows_pins['following_user'] = new JO_Db_Expr('('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->limit(1).')');
    	} else {
    	$rows_pins['following_board'] = new JO_Db_Expr("'login'");
    	$rows_pins['following_user'] = new JO_Db_Expr("'login'");
    	}*/
    	
    	if(JO_Session::get('user[user_id]')) {
    		$rows_pins['pin_is_liked'] = new JO_Db_Expr('('.$db->select()->from('pins_likes', 'COUNT(like_id)')->where('pin_id = pins.pin_id')->where('user_id = ?', JO_Session::get('user[user_id]'))->limit(1).')');
    	} else {
    		$rows_pins['pin_is_liked'] = new JO_Db_Expr("0");
    	}
    	
    	/* is reported */
    	$query_is_reported = $db->select()
    	->from('pins_reports', 'COUNT(pr_id)')
    	->where('pin_id = pins.pin_id')
    	->where('checked = 0')
    	->limit(1);
    	 
    	if((string)JO_Session::get('user[user_id]')) {
    		$query_is_reported->where("user_id = '" . (string)JO_Session::get('user[user_id]') . "' OR user_ip = '" . JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()) . "'");
    	} else {
    		$query_is_reported->where("user_ip = ?", JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()));
    	}
    	
    	$rows_pins['pin_is_reported'] = new JO_Db_Expr("(".$query_is_reported.")");
    	
    	$thumbs = Model_Upload_Abstract::pinThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_pins['pin_thumb'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('pins_images','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('pin_id = pins.pin_id')->where('size = ?', $prefix)->limit(1).')');
    	}
    	
    	$thumbs = Model_Upload_Abstract::userThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_users['user_avatar'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('users_avatars','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('user_id = users.user_id')->where('size = ?', $prefix)->limit(1).')');
    	}
    	
    	
    	$query = $db->select()
	    	->from('pins', $rows_pins)
	    	->joinLeft('users', 'pins.user_id = users.user_id', $rows_users)
	    	->joinLeft('boards', 'pins.board_id = boards.board_id', $rows_boards)
	    	->joinLeft(array('via' => 'users'), 'pins.via = via.user_id', $rows_via);
    	
    	//for public boards
    	if(Helper_Config::get('config_private_boards')) {
    		if(JO_Session::get('user[user_id]')) {
    			$query->where('pins.user_id = ? OR IF(pins.user_id = ?, 1, pins.public)  = 1', JO_Session::get('user[user_id]'));
    		} else {
    			$query->where('pins.public = 1');
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
    			'pins.pin_id',
    			'pins.views'
    	);
    	
    	if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
    		$query->order(new JO_Db_Expr('pins.vip DESC,' . $data['order'] . $sort));
    	} elseif(isset($data['order']) && $data['order'] instanceof JO_Db_Expr) {
    		$query->order(new JO_Db_Expr('pins.vip DESC,' . $data['order']));
    	} else {
    		$query->order(new JO_Db_Expr('pins.vip DESC, pins.pin_id' . $sort));
    	}
    	
    	if(isset($data['start']) && isset($data['limit'])) {
    		if($data['start'] < 0) {
    			$data['start'] = 0;
    		}
    		$query->limit($data['limit'], $data['start']);
    	}
		
		/* rows */
		if(isset($data['start']) && isset($data['limit'])) {
			$query->join(array('row' => new JO_Db_Expr('(SELECT @curRow := ' . ((int)$data['start']-1) . ')')), '');
		} else {
			$query->join(array('row' => new JO_Db_Expr('(SELECT @curRow := -1)')), '');
		}
		$query->columns(array('pin_pin_row' => new JO_Db_Expr('@curRow := @curRow + 1')));
		
    	return $query;
    }
    
    /**
     * @return Ambigous <NULL, multitype:, multitype:mixed >
     */
    public static function blackWordsDictionary() {
    	static $data = null;
    	
    	if($data === null) {
    		$db = JO_Db::getDefaultAdapter();
    		$query = $db->select()
    		->from('pins_ignore_dictionary', array('dic_id', 'word'));
    		$data = $db->fetchPairs($query);
    	}
    	
    	return $data;
    }
    
    
	public function getCurrencyBySimbol($simbol) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('currency', 'value')
					->where('TRIM(symbol_left) = ? OR TRIM(symbol_right) = ?', $simbol)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	
	/* v2.2 */
	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	protected static function filterFriend(JO_Db_Select $query) {
		$db = JO_Db::getDefaultAdapter();
		if(JO_Session::get('user[user_id]')) {
			$has_pins = 'pins.user_id = ? OR pins.user_id IN (SELECT user_id FROM users WHERE public = 1)';
			if(JO_Session::get('user[followers]')) {
				$has_pins .= ' OR pins.user_id IN (' . $db->select()->from('users_following','user_id')->where('following_id = ?')->where('users_following.board_id = pins.board_id') . ')';
				$has_pins .= ' OR pins.user_id IN (' . $db->select()->from('users_following_user','user_id')->where('following_id = ?') . ')';
			}
			$query->where(new JO_Db_Expr($has_pins), JO_Session::get('user[user_id]'));
			if(JO_Session::get('user[followers]')) {
				$query->where('pins.board_id NOT IN (SELECT board_id FROM `users_following_ignore` WHERE following_id = ?)', JO_Session::get('user[user_id]'));
			}
		} else {
			$query->where('pins.user_id IN (SELECT user_id FROM users WHERE public = 1)');
		}
		return $query;
	}
	/* end mod v2.2 */
	
}

?>