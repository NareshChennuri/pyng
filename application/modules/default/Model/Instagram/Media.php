<?php

class Model_Instagram_Media {
	
	public static function getMedia($media_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media')
					->where('media_id = ?', (string)$media_id)
					->limit(1);
		return $db->fetchRow($query);
	}
	
	public static function setPinMedia($media_id, $pin_id) {
		$db = JO_Db::getDefaultAdapter();
		return Helper_Db::update('instagram_media', array(
			'pin_id' => (string)$pin_id		
		), array('media_id = ?' => $media_id));
	}
	
	public static function addMedia($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$media_id = self::existMedia($data['md5key']);
		if($media_id) {
			return $media_id;
		}
		
		return Helper_Db::insert('instagram_media', array(
			'user_id' => $data['user_id'],
			'instagram_media_id' => $data['instagram_media_id'],
			'width' => $data['width'],
			'height' => $data['height'],
			'media' => $data['media'],
			'instagram_profile_id' => $data['instagram_profile_id'],
			'md5key' => $data['md5key'],
			'title' => $data['title'],
			'pin_id' => $data['pin_id'],
			'from' => $data['from']
		));
	}
	
	public static function existMedia($md5key) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media', 'media_id')
					->where('md5key = ?', $md5key)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function getMinMedia($instagram_profile_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media', 'instagram_media_id')
					->where('instagram_profile_id = ?', $instagram_profile_id)
					->order('instagram_media_id ASC')
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function getMaxMedia($instagram_profile_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media', 'instagram_media_id')
					->where('instagram_profile_id = ?', $instagram_profile_id)
					->order('instagram_media_id DESC')
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function getUserMediasInstagram($instagram_profile_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media')
					->where('instagram_profile_id = ?', $instagram_profile_id)
					->order('instagram_media_id DESC')
					/*->limit(300)*/;
		return $db->fetchAll($query);
	}
	
	public static function getUserMedias($user_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media')
					->where('user_id = ?', $user_id);
		return $db->fetchAll($query);
	}
	
	public static function getUserMediasData($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('instagram_media')
					->where('pin_id = 0')
					->order('instagram_media_id DESC');
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('user_id = ?', $data['filter_user_id']);
		}
		
		if(isset($data['media_id_not_in']) && is_array($data['media_id_not_in']) && count($data['media_id_not_in']) > 0) {
			$query->where('media_id NOT IN (?)', new JO_Db_Expr(trim(implode(',',$data['media_id_not_in']),',')));
		}
		
		if(isset($data['media_id_in']) && is_array($data['media_id_in']) && count($data['media_id_in']) > 0) {
			$query->where('media_id IN (?)', new JO_Db_Expr(trim(implode(',',$data['media_id_in']),',')));
		}
		
		if(isset($data['limit']) && (int)$data['limit']) {
			$query->limit((int)$data['limit']);
		} elseif(isset($data['limit']) && $data['limit'] == 'none') {
			
		} else {
			$query->limit(45);
		}
		
		return $db->fetchAll($query);
	}
	
}

?>