<?php

class Model_Categories {
	
	public static function getCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('category', array('category_id', 'title', 'image'))
					->order('category.sort_order ASC');
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['filter_status'])) {
			$query->where('category.status = ?', (int)$data['filter_status']);
		}
		
		if(isset($data['where']) && $data['where'] instanceof JO_Db_Expr) {
			$query->where($data['where']);
		}
		
		$data_info = $db->fetchAll($query);
		$result = array();
		if($data_info) { 
			$result = $data_info;
		}
		
		return $result;
	}
	
	public static function getTotalCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('category', 'COUNT(category.category_id)');
		
		if(isset($data['filter_status'])) {
			$query->where('category.status = ?', (int)$data['filter_status']);
		}
		
		return $db->fetchOne($query);
	}
	
	public static function getCategory($category_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('category')
					->where('category.category_id = ? ', (int)$category_id)
					->limit(1);
		
		return $db->fetchRow($query);
		
	}

}

?>