<?php

class Model_Banners extends JO_Db_Table {
	
	public static function create($data) {		
		$db = JO_Db::getDefaultAdapter();
		$db->insert('banners', array(
			'name' => (string)$data['name'],
			'html' => (string)$data['html'],
			'height' => (string)$data['height'],
			'width' => (string)$data['width'],
			'position' => (string)$data['position'],
			'controller' => (string)$data['controller_set'],
			'status' => (string)$data['status'],
			'status_in_js_mode' => (string)$data['status_in_js_mode'],
			'category_id' => isset($data['category_id'])?$data['category_id']:0
		));		
		return $db->lastInsertId();
		
	}
	
	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		return $db->update('banners', array(
			'name' => (string)$data['name'],
			'html' => (string)$data['html'],
			'height' => (string)$data['height'],
			'width' => (string)$data['width'],
			'position' => (string)$data['position'],
			'controller' => (string)$data['controller_set'],
			'status' => (string)$data['status'],
			'status_in_js_mode' => (string)$data['status_in_js_mode'],
			'category_id' => isset($data['category_id'])?$data['category_id']:0
		), array('id = ?' => $id));
	}
	
	public static function getBanners($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('banners');
	
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
			'name'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('id' . $sort);
		}
		
		////////////filter
		
		$query = self::filter($query, $data);
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalBanners($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('banners', 'COUNT(id)')
					->limit(1);
		
		////////////filter
		
		$query = self::filter($query, $data);
		
		return $db->fetchOne($query);
	}
	
	private function filter(JO_Db_Select $query, $data) {
		if(isset($data['filter_id']) && $data['filter_id']) {
			$query->where('id = ?', (int)$data['filter_id']);
		}
		
		if(isset($data['filter_name']) && $data['filter_name']) {
			$query->where('name LIKE ?', '%' . $data['filter_name'] . '%');
		}
		
		return $query;
	}
	
	public static function getBanner($id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db
					->select()
					->from('banners')
					->where('id = ?', $id)
					->limit(1);
		
		return $db->fetchRow($query);
		
	}
	
	public function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('banners', array('id = ?' => (string)$id));
	}

	
	
}

?>