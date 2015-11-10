<?php

class Model_Categories {
	
	public function createCategory($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('category', array(
			'date_added' => new JO_Db_Expr('NOW()'),
			'date_modified' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'title' => $data['title'],
			'meta_title' => $data['meta_title'],
			'meta_description' => $data['meta_description'],
			'meta_keywords' => $data['meta_keywords'],
			'image' => $data['image']
		));
		
		$category_id = $db->lastInsertId();
		
		if(isset($data['keyword']) && $data['keyword']) {
			self::generateCategory($category_id, $data['keyword']);
		} else {
			self::generateCategory($category_id);
		}
		
		return $category_id;
	}
	
	public function editeCategory($category_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('category', array(
			'date_modified' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'title' => $data['title'],
			'meta_title' => $data['meta_title'],
			'meta_description' => $data['meta_description'],
			'meta_keywords' => $data['meta_keywords'],
			'image' => $data['image']
		), array('category_id = ?' => (int)$category_id));
		
		if(isset($data['keyword']) && $data['keyword']) {
			self::generateCategory($category_id, $data['keyword']);
		} else {
			self::generateCategory($category_id);
		}
		
		return $category_id;
	}
	
	public function changeSortOrder($category_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('category', array(
			'sort_order' => $sort_order
		), array('category_id = ?' => (int)$category_id));
	}
	
	public function changeStatus($category_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('category', array(
			'status' => new JO_Db_Expr('IF(status = 1, 0, 1)')
		), array('category_id = ?' => (int)$category_id));
	}
	
	public function deleteCategory($category_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('category', array('category_id = ?' => (int)$category_id));
		$db->query("DELETE FROM url_alias WHERE query = 'category_id=" . (int)$category_id . "'");
	}
	
	public function getCategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('category', array('*', '(SELECT COUNT(board_id) FROM boards WHERE category_id = category.category_id) AS boards'))
					->order('category.sort_order ASC');
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		$data_info = $db->fetchAll($query);
		$result = array();
		if($data_info) { 
			$result = $data_info;
		}
		
		return $result;
	}
	
	public function getCategory($category_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('category', array('*', new JO_Db_Expr("(SELECT keyword FROM url_alias WHERE query='category_id=".(int)$category_id."' LIMIT 1) AS keyword")))
					->where('category.category_id = ? ', (int)$category_id);
		
		return $db->fetchRow($query);
		
	}
	
	/************************************************** BEGIN CATEGORIES AUTOSEO **************************************************/
	public static function generateCategory($category_id, $keyword = '') {
		$db = JO_Db::getDefaultAdapter();
		$info = self::getCategory($category_id);
		
		if(!$info) {
			return;
		}
		
		if(trim($keyword)) {
			$slug = $uniqueSlug = Model_AutoSeo::translate($keyword);
		} elseif(trim($info['title'])) {
			$slug = $uniqueSlug = Model_AutoSeo::translate($info['title']);
		} else {
			$slug = $uniqueSlug = 'category';
		}
		
		$slug = mb_strtolower($slug, 'utf-8');
		$uniqueSlug = mb_strtolower($uniqueSlug, 'utf-8');
		
		$index = 1;
		
		$db->query("DELETE FROM url_alias WHERE query = 'category_id=" . (int)$category_id . "'");
		while (Model_AutoSeo::getTotalKey($uniqueSlug)) {
			$uniqueSlug = $slug . '-' . $index ++;
		}
		
		$db->insert('url_alias', array(
			'query' => 'category_id=' . (int)$category_id,
			'keyword' => $uniqueSlug,
			'route' => 'category/index',
			'path' => $uniqueSlug
		));
	}
	/************************************************** END CATEGORIES AUTOSEO **************************************************/
	

}

?>