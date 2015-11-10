<?php

class Model_Comments {

	public static function getLatestComments($in) {
		
		$temp = array();
		foreach(explode(',',$in) AS $k) {
			if($k) {
				$temp[] = $k;
			}
		}
		$in = implode(',',$temp);
		
		if(trim($in)) {
			$db = JO_Db::getDefaultAdapter();	
			$query = $db
								->select()
								->from('pins_comments')
								->where('comment_id IN (?)', new JO_Db_Expr($in))
								->order('comment_id ASC')
								->limit(5);
								
			$results = $db->fetchAll($query);
			$data = array();
			if($results) {
				foreach($results AS $result) {
					$userdata = Model_Users::getUser($result['user_id'], false, Model_Users::$allowed_fields);
					if(!$userdata) {
						$userdata = array('fullname' => '', 'avatar' => '', 'store' => 'local');
					}
					$result['user'] = $userdata;
					$data[] = $result;
				}
			}
			return $data;
		}
		return array();
	}

	public static function getComments($data) {

		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('pins_comments');
							
		if(isset($data['filter_pin_id'])) {
			$query->where('pins_comments.pin_id = ?', (string)$data['filter_pin_id']);
		}
		
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
			'pins_comments.comment_id'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('pins_comments.comment_id' . $sort);
		}
							
		$results = $db->fetchAll($query);
		$response = array();
		if($results) {
			foreach($results AS $result) {
				$userdata = Model_Users::getUser($result['user_id'], false, Model_Users::$allowed_fields);
				if(!$userdata) {
					$userdata = array('fullname' => '', 'avatar' => '', 'store' => 'local');
				}
				$result['user'] = $userdata;
				$response[] = $result;
			}
		}
		return $response;

	}

	public static function getTotalComments($pin_id) {

		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('pins_comments', 'COUNT(comment_id)')
							->where('pin_id = ?', $pin_id);
		
		return $db->fetchOne($query);

	}
	
	
	
	
	
	//////////////////////////////////////////// v2 ////////////////////////////////////////////

	public static function getLatestComments2($in) {
		
		$temp = array();
		foreach(explode(',',$in) AS $k) {
			if($k) {
				$temp[] = $k;
			}
		}
		$in = implode(',',$temp);
		
		if(trim($in)) {
			
			$limit = (int)Helper_Config::get('config_comments_list');
			if($limit < 1) { $limit = 4; } 
			
			$db = JO_Db::getDefaultAdapter();
			
			$pin_user_id = new JO_Db_Expr('('.$db->select()->from('pins','user_id')->where('pin_id = pins_comments.pin_id')->limit(1).')');
							
			
			switch (Helper_Config::get('config_user_view')) {
				case 'username':
					$user_seo_url = new JO_Db_Expr('users.username');
					break;
				case 'firstname':
					$user_seo_url = new JO_Db_Expr('users.firstname');
					break;
				case 'fullname':
				default:
					$user_seo_url = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
					break;
			}
				
			$query = $db
								->select()
								->from('pins_comments')
								->joinLeft('users', 'pins_comments.user_id = users.user_id', array('firstname','lastname','avatar','store','username','fullname' => $user_seo_url, 'pin_user_id' => $pin_user_id))
								->where('pins_comments.comment_id IN (?)', new JO_Db_Expr($in))
								->order('pins_comments.comment_id ASC')
								->limit($limit);
								
			return $db->fetchAll($query);
		}
		return array();
	}

	public static function getComments2($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$is_reported = $db->select()
							->from('pins_reports_comments', 'COUNT(pr_id)')
							->where('pins_reports_comments.comment_id = pins_comments.comment_id')
							->where('checked = 0')
							->limit(1);
							
							if((string)JO_Session::get('user[user_id]')) {
								$is_reported->where("user_id = '" . (string)JO_Session::get('user[user_id]') . "' OR user_ip = '" . JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()) . "'");
							} else {
								$is_reported->where("user_ip = ?", JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()));
							}
							
		$pin_user_id = new JO_Db_Expr('('.$db->select()->from('pins','user_id')->where('pin_id = pins_comments.pin_id')->limit(1).')');
							
		switch (Helper_Config::get('config_user_view')) {
			case 'username':
				$user_seo_url = new JO_Db_Expr('users.username');
				break;
			case 'firstname':
				$user_seo_url = new JO_Db_Expr('users.firstname');
				break;
			case 'fullname':
			default:
				$user_seo_url = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
				break;
		}
			
		$query = $db->select()
					->from('pins_comments', array('*', 'is_reported' => new JO_Db_Expr('('.$is_reported.')')))
					->joinLeft('users', 'pins_comments.user_id = users.user_id', array('firstname','lastname','avatar','store','username','fullname' => $user_seo_url, 'pin_user_id' => $pin_user_id));
						
		if(isset($data['filter_pin_id'])) {
			$query->where('pins_comments.pin_id = ?', (string)$data['filter_pin_id']);
		}
		
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
			'pins_comments.comment_id'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('pins_comments.comment_id' . $sort);
		}
							
		return $db->fetchAll($query);

	}
	
}

?>