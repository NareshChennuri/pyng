<?php

class Model_Users {
	
	private static $thumb_sizes = array(
		'50x50' => '_A',
		'180x0' => '_B'
	);

	
	public static function editeUser($user_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array( );
		
		if(trim($data['password'])) {
			$insert['password'] = (md5($data['password']));
		}
		
		if(isset($data['groups']) && is_array($data['groups'])) {
			$groups = $data['groups'];
		} else {
			$groups = array();
		}
		
		if(isset($data['email'])) {
		    if(!self::isExistEmail($data['email'])) {
		        $insert['email'] = $insert['new_email'] = $data['email'];
		    }
		}
		
		if(isset($data['is_admin'])) {
		    $insert['is_admin'] = $data['is_admin'];
		}
		
		if(isset($data['is_developer']) and JO_Session::get('user[is_developer]')) {
		    $insert['is_developer'] = $data['is_developer'];
		}
		
		if(isset($data['username'])) {
		    if(!self::isExistUsername($data['username'])) {
		        $insert['username'] = $data['username'];
		        $db->delete('url_alias', array('query = ?' => 'user_id='.(string)$user_id));
		        $db->insert('url_alias', array(
        			'query' => 'user_id=' . (string)$user_id,
        			'keyword' => $data['username'],
        			'path' => $data['username'],
        			'route' => 'users/profile'
        		));
		    }
		}
		
		if(isset($data['firstname'])) {
		    $insert['firstname'] = $data['firstname'];
		}
		
		if(isset($data['lastname'])) {
		    $insert['lastname'] = $data['lastname'];
		}
		
		
		
		$insert['groups'] = serialize($groups);
        $insert['status'] = $data['status'];

		$db->update('users', $insert, array('user_id = ?' => (string)$user_id));
		
		return $user_id;
	}
	
    public static function createUser($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$insert = array( );
		
		if(trim($data['password'])) {
			$insert['password'] = (md5($data['password']));
		}
		
		if(isset($data['groups']) && is_array($data['groups'])) {
			$groups = $data['groups'];
		} else {
			$groups = array();
		}
		
		if(isset($data['email'])) {
		    if(!self::isExistEmail($data['email'])) {
		        $insert['email'] = $insert['new_email'] = $data['email'];
		    }
		}
		
		if(isset($data['is_admin'])) {
		    $insert['is_admin'] = $data['is_admin'];
		}
		
		if(isset($data['is_developer']) and JO_Session::get('user[is_developer]')) {
		    $insert['is_developer'] = $data['is_developer'];
		}
		
		if(isset($data['username'])) {
		    if(!self::isExistUsername($data['username'])) {
		        $insert['username'] = $data['username'];
		    }
		}
		
		if(isset($data['firstname'])) {
		    $insert['firstname'] = $data['firstname'];
		}
		
		if(isset($data['lastname'])) {
		    $insert['lastname'] = $data['lastname'];
		}
		
		
		
		$insert['groups'] = serialize($groups);
        $insert['status'] = $data['status'];

		$db->insert('users', $insert);
		$user_id = $db->lastInsertId();
        if(isset($data['username'])) {
		    $db->insert('url_alias', array(
        		'query' => 'user_id=' . (string)$user_id,
        		'keyword' => $data['username'],
        		'path' => $data['username'],
        		'route' => 'users/profile'
        	));
		}
		return true;
	}
	
	public static function deleteFromServer($image) {
		if(JO_Registry::get('enable_amazon')) {
			$s3 = new JO_Api_Amazon(JO_Registry::get('awsAccessKey'), JO_Registry::get('awsSecretKey'));
			$s3->putBucket(JO_Registry::get('bucklet'), JO_Api_Amazon::ACL_PUBLIC_READ);
			if($s3->getBucketLogging(JO_Registry::get('bucklet'))) {
				$s3->deleteObject(JO_Registry::get('bucklet'), $image);
			}
		}
	}
	
	public static function deleteImagesAmazon($image) {
		if(!$image) {
			return;
		}
		
		$ext = strtolower(strrchr($image,"."));
		
		$thumbs = array($image);
		foreach(self::$thumb_sizes AS $size => $key) {
			$thumbs[] = preg_replace('/'.$ext.'$/i',$key.$ext,$image);
		}
		
		foreach($thumbs AS $thumb) {
			self::deleteFromServer($thumb);
		}
	}
	
	public static function deleteUser($user_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$user_info = self::getUser($user_id);
		if(!$user_info) {
			return;
		}
		
		/*if($user_info['store'] == 's3') {
			self::deleteImagesAmazon($user_info['avatar']);
		} else {
			if($user_info['avatar']) {
				$model_image = new Helper_Images();
				$model_image->deleteImages($user_info['avatar']);
			}
		}*/
		
		$query = $db->select()
					->from('boards')
					->where('user_id = ?', (string)$user_id);
		$rows = $db->fetchAll($query);
		if($rows) {
			foreach($rows AS $row) {
				Model_Boards::delete($row['board_id']);
			}
		}
		
		$ext = Model_Extensions_Install::getByMethod('user_login');
		$front = JO_Front::getInstance();
		foreach($ext AS $login) {
			$model = $front->formatModuleName('model_' . $login . '_users');
			$instance = new $model();
			$instance->deleteByUserId($user_id);
		}
		
        $db->query("DELETE FROM url_alias WHERE query = 'user_id=" . (string)$user_id . "'");
        $db->delete('users_boards', array('user_id = ?' => (string)$user_id));
        $db->delete('shared_content', array('user_id = ?' => (string)$user_id));
        $db->delete('users_following', array('user_id = ?' => (string)$user_id));
        $db->delete('users_following', array('following_id = ?' => (string)$user_id));
        $db->delete('users_following_ignore', array('user_id = ?' => (string)$user_id));
        $db->delete('users_following_ignore', array('following_id = ?' => (string)$user_id));
        $db->delete('users_following_user', array('user_id = ?' => (string)$user_id));
        $db->delete('users_following_user', array('following_id = ?' => (string)$user_id));
        $db->delete('users_history', array('from_user_id = ?' => (string)$user_id));
        $db->delete('users_history', array('to_user_id = ?' => (string)$user_id));
		$db->delete('users', array('user_id = ?' => (string)$user_id));
        
	}
	
	public static function getUser($user_id) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('users', array('*', 'fullname' => "CONCAT(firstname,' ',lastname)"))
							->where('user_id = ?', (string)$user_id)
							->limit(1,0);
		return $db->fetchRow($query);
	}
	
	public static function getUsers($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		
		$query = $db
					->select()
					->from(array('u' => 'users'));
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'desc') {
			$sort = ' DESC';
		} else {
			$sort = ' ASC';
		}
		
		$allow_sort = array(
			'u.user_id',
			'u.username',
			'u.email',
			'u.firstname',
			'u.status',
			'u.pins',
            'u.boards',
		    'u.likes',
			'u.delete_account_date'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('u.user_id' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('u.user_id = ?', (int)$data['filter_user_id']);
		}
		
		if(isset($data['filter_delete_account']) && !is_null($data['filter_delete_account'])) {
			$query->where('u.delete_account = ?', (int)$data['filter_delete_account']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('u.firstname LIKE ? OR u.lastname LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
    	if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
    	if(isset($data['filter_email']) && $data['filter_email']) {
			$query->where('u.email LIKE ?', '%' . $data['filter_email'] . '%');
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalUsers($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db
					->select()
					->from(array('u' => 'users'), 'COUNT(user_id)');
		
		////////////filter
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('u.user_id = ?', (string)$data['filter_user_id']);
		}
		
		if(isset($data['filter_delete_account']) && !is_null($data['filter_delete_account'])) {
			$query->where('u.delete_account = ?', (int)$data['filter_delete_account']);
		}
		
    	if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('u.firstname LIKE ? OR u.lastname LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.firstname LIKE ? OR u.lastname LIKE ?', '%' . $data['filter_username'] . '%');
		}
		
    	if(isset($data['filter_email']) && $data['filter_email']) {
			$query->where('u.email LIKE ?', '%' . $data['filter_email'] . '%');
		}
		
		return $db->fetchOne($query);
	}
	
	
	
	public function checkLogin($username, $password) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('users')
							->where('email = ? OR username = ?', (string)$username)
							->where('password = ?', (string)md5($password))
							->limit(1,0);
		$user_data = $db->fetchRow($query);
		
		if($user_data) {
			$groups = unserialize($user_data['groups']);
	    	if(is_array($groups) && count($groups) > 0) {
//	    		unset($user_data['groups']);
	    		$query_group = $db->select()
	    							->from('user_groups')
	    							->where("ug_id IN (?)", new JO_Db_Expr(implode(',', array_keys($groups))));
	    		$fetch_all = $db->fetchAll($query_group);
	    		$user_data['access'] = array();
	    		if($fetch_all) {
	    			foreach($fetch_all AS $row) {
	    				$modules = unserialize($row['rights']);
	    				if(is_array($modules)) {
	    				    foreach($modules AS $module => $ison) {
	    					    foreach($ison AS $m => $on) {
	    						    $user_data['access'][$module][$m] = $m;
	    					    }
	    					}
	    				}
	    			}
	    		}
	    	}
//	    	if(isset($user_data['access']) && count($user_data['access'])) {
//    	    	$user_data['is_admin'] = true;
//	    	}
		}
    	
		return $user_data;
	}
	
    public function isExistEmail($email) {
	        
	      $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('users', new JO_Db_Expr('COUNT(user_id)'))
					->where('email = ?', $email);
		
		return $db->fetchOne($query)>0 ? true : false;	
	}
	
    public function isExistUsername($username) {
	        
	      $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('users', new JO_Db_Expr('COUNT(user_id)'))
					->where('username = ?', $username);
		
		return $db->fetchOne($query)>0 ? true : false;	
	}
	
    public static function getWaiting($data = array()) {
        
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db
					->select()
					->from(array('u' => 'shared_content'));
//					->where('u.user_id =?', '-1')
//					->where('u.send !=?', '2');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'desc') {
			$sort = ' DESC';
		} else {
			$sort = ' ASC';
		}
		
		$allow_sort = array(
			'u.sc_id',
			'u.email'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('u.sc_id' . $sort);
		}
	
		////////////filter
		
		if(isset($data['filter_email']) && $data['filter_email']) {
			$query->where('u.email = ?', (string)$data['filter_email']);
		}
        if(isset($data['filter_sent']) && ($data['filter_sent']>-1 and $data['filter_sent']<4)) {
			$query->where('u.send = ?', (int)$data['filter_sent']);
		}
		return $db->fetchAll($query);
	}
	
	public static function getTotalWaiting($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db
					->select()
					->from(array('u' => 'shared_content'), 'COUNT(user_id)');
//					->where('u.user_id =?', '-1')
//					->where('u.send !=?', '2');
		
		////////////filter
		
		if(isset($data['filter_email']) && $data['filter_email']) {
			$query->where('u.email = ?', (string)$data['filter_email']);
		}
		
	if(isset($data['filter_sent']) && ($data['filter_sent']>-1 and $data['filter_sent']<4)) {
			$query->where('u.send = ?', (int)$data['filter_sent']);
		}
		
		return $db->fetchOne($query);
	}
	
    public static function deleteWait($sc_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$db->delete('shared_content', array('sc_id = ?' => (int)$sc_id));
        
	}

	public static function getWait($sc_id) {
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('shared_content')
							->where('sc_id = ?', (int)$sc_id)
							->limit(1,0);
		return $db->fetchRow($query);
	}
	
    public static function invite($sc_id) {
		$db = JO_Db::getDefaultAdapter();	
		$db->update('shared_content', array('send'=>'2'), array('sc_id = ?' => (int)$sc_id));
	}
	
	public static function updateLatestPins($user_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		$board_info = self::getUser( $user_id );
		
		if($board_info) {
			$pins = Model_Pins::getPins(array(
				'filter_user_id' => $board_info['user_id'],
				'sort' => 'DESC',
				'order' => 'pins.pin_id',
				'start' => 0,
				'limit' => 15
			));
			$latest_add = array();
			if($pins) {
				foreach($pins AS $p) {
					$latest_add[] = $p['pin_id'];
				}
			}
			$db->update('users', array(
				'latest_pins' => implode(',',$latest_add),
				'pins' => new JO_Db_Expr("(SELECT COUNT(pin_id) FROM pins WHERE user_id = '".$board_info['user_id']."')")
			), array('user_id = ?' => (string)JO_Session::get('user[user_id]')));
			
		}
	}
	
}

?>