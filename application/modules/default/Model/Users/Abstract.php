<?php

class Model_Users_Abstract extends ArrayObject {
	
    /**
     * @return Ambigous <JO_Db_Select, JO_Db_Select>
     */
    public static function getListUsersQueryLite() {
    	$db = JO_Db::getDefaultAdapter();
    	
    	//$rows_users = self::describeTable('users','user_');
    
    	$rows_users = array('*');
    
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
    	
    	$thumbs = Model_Upload_Abstract::userThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_users['user_avatar'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('users_avatars','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('user_id = users.user_id')->where('size = ?', $prefix)->limit(1).')');
    	}
    	
    	$query = $db->select()
	    	->from('users', $rows_users);
    	
    	return $query;
    }
	
    /**
     * @return Ambigous <JO_Db_Select, JO_Db_Select>
     */
    public static function getListUsersQuery() {
    	$db = JO_Db::getDefaultAdapter();
    	
    	//$rows_users = self::describeTable('users','user_');
    
    	$rows_users = array('*');
    
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
    	
    	$thumbs = Model_Upload_Abstract::userThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_users['user_avatar'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('users_avatars','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('user_id = users.user_id')->where('size = ?', $prefix)->limit(1).')');
    	}
    	
    	if(JO_Session::get('user[user_id]')) {
    		$rows_users['following_user'] = new JO_Db_Expr('(('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = users.user_id')->limit(1).') + ('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = users.user_id')->limit(1) .'))');
    	} else {
    		$rows_users['following_user'] = new JO_Db_Expr("0");
    	}
    	
    	/*if(JO_Session::get('user[user_id]')) {
    		$rows_pins['pin_is_liked'] = new JO_Db_Expr('('.$db->select()->from('pins_likes', 'COUNT(like_id)')->where('pin_id = pins.pin_id')->where('user_id = ?', JO_Session::get('user[user_id]'))->limit(1).')');
    	} else {
    		$rows_pins['pin_is_liked'] = new JO_Db_Expr("'login'");
    	}
    	
    	$thumbs = Model_Upload_Abstract::pinThumbSizes();
    	foreach($thumbs AS $size => $prefix) {
    		$rows_pins['pin_thumb'.strtolower($prefix)] = new JO_Db_Expr('('.$db->select()->from('pins_images','CONCAT_WS(\'|||\',image,width,height,original,mime)')->where('pin_id = pins.pin_id')->where('size = ?', $prefix)->limit(1).')');
    	}*/
    	
    	$rows_users['latest_pins'] = new JO_Db_Expr("SUBSTRING_INDEX((".$db->select()->from('pins','GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC)')->where('user_id = users.user_id')->limit(15)."),',',15)");
    	
    	$query = $db->select()
	    	->from('users', $rows_users);
    	
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
    			'users.user_id'
    	);
    	
    	if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
    		$query->order($data['order'] . $sort);
    	} elseif(isset($data['order']) && $data['order'] instanceof JO_Db_Expr) {
    		$query->order($data['order']);
    	} else {
    		$query->order('users.user_id' . $sort);
    	}
    	
    	if(isset($data['start']) && isset($data['limit'])) {
    		if($data['start'] < 0) {
    			$data['start'] = 0;
    		}
    		$query->limit($data['limit'], $data['start']);
    	}
    	return $query;
    }
	
	/* v2.2 */
	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	protected static function filterFriend(JO_Db_Select $query) {
		$db = JO_Db::getDefaultAdapter();
		if(JO_Session::get('user[user_id]')) {
			$has_pins = 'users.user_id = ? OR users.public = 1';
			if(JO_Session::get('user[followers]')) {
				$has_pins .= ' OR users.user_id IN (' . $db->select()->from('users_following','user_id')->where('following_id = ?') . ')';
				$has_pins .= ' OR users.user_id IN (' . $db->select()->from('users_following_user','user_id')->where('following_id = ?') . ')';
			}
			$query->where(new JO_Db_Expr($has_pins), JO_Session::get('user[user_id]'));
		} else {
			$query->where('users.public = 1');
		}
		return $query;
	}
	/* end mod v2.2 */

}

?>