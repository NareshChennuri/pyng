<?php

class Model_Gallery {

	public static function getMainImage($gallery_id, $controller) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('gallery_images')
					->where('gallery_images.gallery_id = ?', (int)$gallery_id)
					->where('controller = ?', $controller)
					->order('gallery_images.sort_order ASC')
					->limit(1);
		return $db->fetchRow($query);
	}
	
	public static function getGalleryImages($gallery_id, $controller = '') {
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
	
}

?>