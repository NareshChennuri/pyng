<?php

class Model_Pages {
	
	public function __construct() {}
	
	public static function createPage($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('pages', array(
			'date_added' => new JO_Db_Expr('NOW()'),
			'date_modified' => new JO_Db_Expr('NOW()'),
			'parent_id' => (int)(isset($data['parent_id']) ? $data['parent_id'] : 0),
			'status' => (int)$data['status'],
			'in_footer' => (int)$data['in_footer'],
			'title' => $data['title'],
			'description' => $data['description'],
			'meta_title' => $data['meta_title'],
			'meta_description' => $data['meta_description'],
			'meta_keywords' => $data['meta_keywords']
		));
		
		$page_id = $db->lastInsertId();
		
		if(isset($data['keyword']) && $data['keyword']) {
			self::generatePage($page_id, $data['keyword']);
		} else {
			self::generatePage($page_id);
		}
		
		$temporary_images = JO_Session::get('temporary_images');
		if($temporary_images && is_array($temporary_images)) {
			$page_info = self::getPage($page_id);
			if($page_info) {
				$gallery_path = '/gallery/' . date("Y/m/", strtotime($page_info['date_added']));
				$upload_folder  = realpath(BASE_PATH . '/uploads');
				$upload_folder .= $gallery_path;
				foreach($temporary_images AS $image) {
					$image_name = basename($image['image']);
					if(!file_exists($upload_folder) || !is_dir($upload_folder)) {
						mkdir($upload_folder, 0777, true);
					}
					if(copy( BASE_PATH . '/uploads/' . $image['image'], $upload_folder . $image_name )) {
						$image_id = Model_Gallery::createImage(array(
							'gallery_id' => $page_id,
							'controller' => 'pages',
							'image' => $gallery_path . $image_name
						));
						if($image_id && isset($image['title'])) {
							Model_Gallery::updateImageInfo($image_id, $image['title']);
						}
					}
					$mi = new Helper_Images();
					$mi->deleteImages($image['image']);
				}
			}
			JO_Session::clear('temporary_images');
		}
		
		return $page_id;
	}
	
	public static function editePage($page_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pages', array(
			'date_modified' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'in_footer' => (int)$data['in_footer'],
			'title' => $data['title'],
			'description' => $data['description'],
			'meta_title' => $data['meta_title'],
			'meta_description' => $data['meta_description'],
			'meta_keywords' => $data['meta_keywords']
		), array('page_id = ?' => (int)$page_id));
		
		if(isset($data['keyword']) && $data['keyword']) {
			self::generatePage($page_id, $data['keyword']);
		} else {
			self::generatePage($page_id);
		}
		
		return $page_id;
	}
	
	public static function getPages($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages')
					->order('pages.sort_order ASC');
					
		if(isset($data['parent_id'])) {
			$query->where('pages.parent_id = ?', (int)$data['parent_id']);
		}
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}

		$data_info = $db->fetchAll($query);
		$result = array();
		if($data_info) {
			return $data_info;
		}
		
		return $result;
	}
	
	public static function getTotalPages($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages', 'COUNT(pages.page_id)')
					->limit(1);
					
		if(isset($data['parent_id'])) {
			$query->where('pages.parent_id = ?', (int)$data['parent_id']);
		} 
		return $db->fetchOne($query);
	}
	
	public static function changeSortOrder($post_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pages', array(
			'sort_order' => $sort_order
		), array('page_id = ?' => (int)$post_id));
	}
	
	public static function getPage($page_id) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages', array('*', new JO_Db_Expr("(SELECT keyword FROM url_alias WHERE query='page_id=".(int)$page_id."' LIMIT 1) AS keyword")))
					->where('pages.page_id = ?', (int)$page_id);

		return $db->fetchRow($query);
	}
	
	public static function changeStatus($page_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pages', array(
			'status' => new JO_Db_Expr('IF(status = 1, 0, 1)')
		), array('page_id = ?' => (int)$page_id));
	}
	
	public static function deletePage($page_id) {
		$db = JO_Db::getDefaultAdapter();
		$pages = self::getPages(array('parent_id' => $page_id));
		if($pages) {
			foreach($pages AS $page) {
				self::deletePage($page['page_id']);
			}
		}
		
		$images = Model_Gallery::getGalleryImages($page_id, 'pages');
		if($images) {
			foreach($images AS $image) {
				Model_Gallery::deleteImage($image['image_id']);
			}
		}
		
		$db->delete('pages', array('page_id = ?' => (int)$page_id));
		$db->query("DELETE FROM url_alias WHERE query = 'page_id=" . (int)$page_id . "'");
	}

	public static function getPagesFromParent($parent_id) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages')
					->where('pages.parent_id = ?', (int)$parent_id)
					->order('pages.title ASC');

		$data_info = $db->fetchAll($query);
		$result = array();
		if($data_info) {
			foreach($data_info AS $info) {
				$children = self::getPagesFromParent($info['page_id']);
				$info['title'] = self::getPath($info['page_id']);
				$info['children'] = ($children ? true : false);
				$result[] = $info;
				
				$result = array_merge($result, $children);
			}
		}
		
		return $result;
	}
	
	public function getPath($page_id) {
		$result = self::getPage($page_id);
		
		if($result && $result['parent_id']) {
			return self::getPath($result['parent_id']) . ' >> ' . $result['title'];
		} else {
			return $result['title'];
		}
	}
	
	public static function getPagesMenu($menu_id = 0) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pages_to_menu')
					->where('pages_to_menu.menu_id = ?', (int)$menu_id)
					->order('sort_order ASC');

		return $db->fetchAll($query);
	}
	
	public static function updatePagesMenu($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('pages_to_menu');
		if(is_array($data)) {
			foreach($data AS $row => $page_id) {
				$db->insert('pages_to_menu', array(
					'menu_id' => 0,
					'sort_order' => $row,
					'page_id' => $page_id		
				));
			}
		}
	}
	
	/************************************************** BEGIN PAGES AUTOSEO **************************************************/
	public static function generatePage($page_id, $keyword = '') {
		$db = JO_Db::getDefaultAdapter();
		$info = self::getPage($page_id);
		
		if(!$info) {
			return;
		}
		
		if(trim($keyword)) {
			$slug = $uniqueSlug = Model_AutoSeo::translate($keyword);
		} elseif(trim($info['title'])) {
			$slug = $uniqueSlug = Model_AutoSeo::translate($info['title']);
		} else {
			$slug = $uniqueSlug = 'page';
		}
		
		$index = 1;
		
		$db->query("DELETE FROM url_alias WHERE query = 'page_id=" . (int)$page_id . "'");
		while (Model_AutoSeo::getTotalKey($uniqueSlug)) {
			$uniqueSlug = $slug . '-' . $index ++;
		}
		
		$db->insert('url_alias', array(
			'query' => 'page_id=' . (int)$page_id,
			'keyword' => $uniqueSlug,
			'route' => 'pages/read'
		));
	
		$last_inser_id = $db->lastInsertId();
		if($last_inser_id) {
			$db->update('url_alias', array(
				'path' => self::getPagePath($page_id)
			), array('url_alias_id = ?' => $last_inser_id));
		}
		
	}
	
	public function getPagePath($page_id, $language_id) {
		$result = self::getPage($page_id);
		$keyword = self::getPageKeyword($page_id);
		if($result && $result['parent_id']) {
			return self::getPagePath($result['parent_id']) . '/' . $keyword;
		} else {
			return $keyword;
		}
	}
	
	private function getPageKeyword($page_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('url_alias', 'keyword')
					->where("query = ?", new JO_Db_Expr("'page_id=" . (int)$page_id . "'"));
		return $db->fetchOne($query);
	}
	/************************************************** END PAGES AUTOSEO **************************************************/
	
}

?>