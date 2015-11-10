<?php

class WM_Users {
	
	public static function allow(/*read,create,edit,delete*/$type, $name) {
		if( !(string)JO_Session::get('user[user_id]') ) { 
			return false;
		} elseif( (string)JO_Session::get('user[is_developer]') ) { 
			return true;
		} else {
			if( is_array(JO_Session::get('user[access]['.$type.']')) && JO_Session::get('user[access]['.$type.']['.$name.']') == $name ) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	public static function initSession($user_id) {
		$db = JO_Db::getDefaultAdapter();	
		
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$user_fullname = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$user_fullname = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$user_fullname = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
		$query = $db
							->select()
							->from('users', array('*', 'fullname' => $user_fullname))
							->where('user_id = ?', (string)$user_id)
							->limit(1);
		$user_data = $db->fetchRow($query);
		
		if($user_data && $user_data['status']) {
			$groups = unserialize($user_data['groups']);
	    	if(is_array($groups) && count($groups) > 0) {
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
	    	
	    	if(!JO_Request::getInstance()->isXmlHttpRequest()) {
	    	
		    	$db->update('users', array(
		    		'last_action_datetime' => new JO_Db_Expr('NOW()'),
		    		'ip_address' => JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp())
		    	), array('user_id = ?' => (string)$user_id));
	    	
	    	}
	    	
		}
		
//		foreach($user_data AS $key => $data) {
//			self::{$key} = $data;
//		}
		
		JO_Session::set(array('user' => $user_data));

		return $user_data;
		
	}

	public static function initSessionCookie($cookie) {
		$db = JO_Db::getDefaultAdapter();	
		
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$user_fullname = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$user_fullname = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$user_fullname = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
		$query = $db
							->select()
							->from('users', array('*', 'fullname' => $user_fullname))
							->where("MD5(CONCAT(user_id,'".JO_Request::getInstance()->getDomain()."',date_added)) = ?", (string)$cookie)
							->limit(1);
		$user_data = $db->fetchRow($query);
		
		if($user_data && $user_data['status']) {
			$groups = unserialize($user_data['groups']);
	    	if(is_array($groups) && count($groups) > 0) {
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
	    	
	    	$db->update('users', array(
	    		'last_action_datetime' => new JO_Db_Expr('NOW()'),
	    		'ip_address' => JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp())
	    	), array('user_id = ?' => (string)$user_data['user_id']));
	    	
		}
		
//		foreach($user_data AS $key => $data) {
//			self::{$key} = $data;
//		}
		
		JO_Session::set(array('user' => $user_data));

		return $user_data;
		
	}
	
	public static function initPermision() {
	
		$request = JO_Request::getInstance();
		if($request->getModule() == 'admin' && JO_Session::get('user[is_admin]')) {
			$files = glob(APPLICATION_PATH . '/modules/' . $request->getModule() . '/controllers/*.php');
			$temporary_for_menu = array();
			$temporary_for_permision = array();
			$sort_order = $sort_order2 = array();
			if($files) { 
				foreach($files AS $d => $file) {
					$name = basename($file, '.php');
					if(!class_exists($name)) {
						JO_Loader::loadFile($file);
					}
					if(method_exists($name, 'config')) { 
						$data = call_user_func(array($name, 'config'));
						
						if(!isset($data['has_permision']) || !$data['has_permision']) { continue; }
						
						if(!$data['in_menu'] || !WM_Users::allow('read', $data['permision_key'])) { continue; } 
						
						if(isset($sort_order2[$data['menu']])) {
							$sort_order2[$data['menu']] = min($sort_order[$data['menu']],(int)(isset($data['sort_order']) ? $data['sort_order'] : 0));
						} else {
							$sort_order2[$data['menu']] = (int)(isset($data['sort_order']) ? $data['sort_order'] : 0);
						} 
						
						$sort_order[$data['menu']][$d] = (int)(isset($data['sort_order']) ? $data['sort_order'] : 0);

						$temporary_for_menu[$data['menu']][$d] = array(
							'name' => $data['name'],
							'key' => $data['permision_key'],
							'has_permision' => $data['has_permision'],
							'menu' => $data['menu'],
							'href' => $request->getBaseUrl() . $request->getModule() . '/' . (strtolower($name)!='indexcontroller'?str_replace('controller','',strtolower($name)) . '/': '')
						);
					}
				}
			}
			
			array_multisort($sort_order2, SORT_ASC, $temporary_for_menu);
			foreach($temporary_for_menu AS $k=>$t) {
				array_multisort($sort_order[$k], SORT_ASC, $temporary_for_menu[$k]);
			} 
			
			return $temporary_for_menu;
		}
	}
	
	public static function checkLoginFacebookTwitter($id, $type = 'facebook', $session = false, $session2 = false, $row = 'id') { 
		$db = JO_Db::getDefaultAdapter();	
		
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$user_fullname = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$user_fullname = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$user_fullname = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
		$query = $db
							->select()
							->from('users', array('*', 'fullname' => $user_fullname))
							->limit(1);  
							
		if($row == 'id') {
			$query->where($type.'_id = ?',  (string)$id );
		} else {
			$query->where($row.' = ?',  (string)$id );
		}
							
		$user_data = $db->fetchRow($query);
		
		if($user_data) {
			
			if($type == 'instagram_profile') {
				$type = 'instagram';
			}
			
			if(!$user_data[$type . '_connect']) {
				return false;
			}
			
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
	    						$user_data['access'][$module] = $module;
	    					}
	    				}
	    			}
	    		}
	    	}
	    	
			if($session) {
				$update = array(
					'facebook_session' => serialize($session)
				);
				if($row != 'id' && isset($session['uid'])) {
					$update['facebook_id'] = $session['uid'];
				}
				$db->update('users', $update, array('user_id = ?' => (string)$user_data['user_id']));
			}
	    	
		}
    	
		return $user_data;
	}
	
	public static function deleteFacebookSession($user_id) {
		$db = JO_Db::getDefaultAdapter();	
		$db->delete('users_facebook_session', array('user_id = ?' => (string)$user_id));
	}
	
	public static function edit2($user_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$rows = self::describeTable('users');
		
		$user_info_get = self::getUser($user_id);
		
		$date_added = WM_Date::format($user_info_get['date_added'], 'yy-mm-dd H:i:s');
		
		$update = array();
		$avatar = '';
		foreach($rows AS $row) {
			if( array_key_exists($row, $data) ) {
		
				if($row == 'avatar') {
					if($data[$row]) {
						JO_Session::clear('upload_avatar');
						$avatar = $data[$row];
					} else {
						//$update[$row] = $data[$row];
					}
				} /* end avatar */ elseif($row == 'password' || $row == 'new_password') {
					$update[$row] = md5($data[$row]);
				} else {
					$update[$row] = $data[$row];
				}
			}
		}
		
		if(!$update) {
			return false;
		}
		
		$rebuild = $result = $db->update('users', $update, array('user_id = ?' => (string)$user_id));
	}
    
    public static function describeTable($table) {
        $db = JO_Db::getDefaultAdapter();
        $result = $db->describeTable($table);
        $data = array();
        foreach($result AS $res) {
            $data[] = $res['COLUMN_NAME'];
        }
        return $data;
    }
	
	public static function getUser($user_id, $reset = false, array $fields = array('*')) {
		static $result = array();
		
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$user_fullname = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$user_fullname = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$user_fullname = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
		
		$fields = array_merge(array('fullname' => $user_fullname), $fields);
		
		if(!$reset && isset($result[$user_id . serialize($fields)])) { return $result[$user_id . serialize($fields)]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('users', $fields)
							->where('user_id = ?', (string)$user_id)
							->limit(1); 
		$result[$user_id . serialize($fields)] = $db->fetchRow($query);
		return $result[$user_id . serialize($fields)];
	}
	
}

?>