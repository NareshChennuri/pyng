<?php

class Model_Allowips {
	
	public static function create($data) { 
		
		$data['ip_address'] = JO_Request_Server::encode_ip(trim($data['ip_address']));
		if(!self::is_exists($data['ip_address'])) {
			$db = JO_Db::getDefaultAdapter();
			$db->insert('users_ip_allow_admin', array(
				'ip_address' => $data['ip_address']
			));		
			return $db->lastInsertId();	
		}
		return false;
	}
	
	public static function edit($id, $data) {
		
		$data['ip_address'] = JO_Request_Server::encode_ip(trim($data['ip_address']));
		
		if(!self::is_exists($data['ip_address'], $id)) {
			$db = JO_Db::getDefaultAdapter();
			return $db->update('users_ip_allow_admin', array(
				'ip_address' => $data['ip_address']
			), array('id = ?' => $id));			
		}
		return false;
	}
	
	public static function is_exists($ip_address, $id=0) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db
					->select()
					->from('users_ip_allow_admin', 'id')
					->where('ip_address = ?', $ip_address )
					->limit(1);
		
		$id = $db->fetchOne($query);

		if($id && $id == $id) {
			return false;
		}
		
		return $id;
	}
	
	public static function getWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('users_ip_allow_admin');
	
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
			'id',
			'ip_address'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('ip_address' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filete_ip']) && $data['filete_ip']) {
			$query->where('ip_address LIKE ?', JO_Request_Server::encode_ip(trim($data['filete_ip'])));
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('users_ip_allow_admin', 'COUNT(id)')
					->limit(1);
		
		////////////filter
		
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filete_ip']) && $data['filete_ip']) {
			$query->where('ip_address LIKE ?', JO_Request_Server::encode_ip(trim($data['filete_ip'])));
		}
		
		return $db->fetchOne($query);
	}
	
	public static function getWord($id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('users_ip_allow_admin', 'ip_address')
					->where('id = ?', $id)
					->limit(1);
		
		return $db->fetchOne($query);
		
	}
	
	public function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('users_ip_allow_admin', array('id = ?' => (string)$id));
	}

	
	
}

?>