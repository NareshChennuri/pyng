<?php

class Model_Gallery {
	
	public function createImage($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('gallery_images', new JO_Db_Expr('MAX(sort_order)'))
					->where('gallery_id = ?', (int)$data['gallery_id'])
					->where('controller = ?', $data['controller']);
					
		$max_sort_order = ((int)$db->fetchOne($query) + 1);
		
		$db->insert('gallery_images', array(
			'gallery_id' => (int)$data['gallery_id'],
			'image' => $data['image'],
			'sort_order' => $max_sort_order,
			'controller' => $data['controller']
		));
		
		return $db->lastInsertId();
	}
	
	public function getGalleryImages($gallery_id, $controller = '') {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('gallery_images')
					->where('gallery_images.gallery_id = ?', (int)$gallery_id)
					->order('gallery_images.sort_order ASC');
		
		if($controller) {
			$query->where('controller = ?', $controller);
		}
					
		return $db->fetchAll($query);
					
	}
	
	public function sortOrderImages($image_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('gallery_images', array(
			'sort_order' => (int)$sort_order
		), array('image_id = ?' => (int)$image_id));
	}
	
	public function deleteImage($image_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('gallery_images', array('image'))
					->where('image_id = ?', (int)$image_id);
		
		$result = $db->fetchOne($query);
		if(!$result) {
			return 'error';
		} else {
			$mi = new Helper_Images();
			$mi->deleteImages($result);
			$db->delete('gallery_images', array('image_id = ?' => (int)$image_id));
			return 'ok';
		}
	}
	
	public function getImage($image_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('gallery_images')
					->where('image_id = ?', (int)$image_id);
		
		return $db->fetchRow($query);
	}
	
	public function updateImageInfo($image_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('gallery_images', array(
			'title' => $data['title'],
			'description' => $data['description']
		), array('image_id = ?' => (int)$image_id));
	}
}

?>