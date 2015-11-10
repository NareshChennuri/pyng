<?php

abstract class Model_Upload_Abstract extends JO_Model {
	
	abstract public static function getPinImage($pin, $size = null);
	abstract public static function deletePinImage($pin_info);
	abstract public static function uploadPin($image, $title = '', $id = 0);
	
	abstract public static function getUserImage($user, $prefix = null);
	abstract public static function deleteUserImage($user_info);
	abstract public static function uploadUserAvatar($avatar, $user_id = 0);
	
	public static function pinThumbSizes() {
		return array(
			'75x75' => '_A',
			'192x0' => '_B',
			'222x150' => '_C',
			'582x0' => '_D'
		);
	}
	
	public static function userThumbSizes() {
		return array(
			'50x50' => '_A',
			'180x0' => '_B',
			'200x200' => '_C'
		);
	}

	public static function formatImageSize($image, $prefix) {
		if(!$image) {
			return false;
		}
        
		$extension = strtolower(strrchr($image,"."));
		
        $fiulename = basename($image, $extension);
        return str_replace($fiulename.$extension,$fiulename.$prefix.$extension,$image);
	}
	
	public function recursiveDelete($directory) {
		if (is_dir($directory) && file_exists($directory)) {
			$handle = opendir($directory);
		} else {
			return FALSE;
		}
		
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				if (!is_dir($directory . '/' . $file)) {
					unlink($directory . '/' . $file);
				} else {
					$this->recursiveDelete($directory . '/' . $file);
				}
			}
		}
		
		closedir($handle);
		
		rmdir($directory);
		
		return TRUE;
	}
	
	public function clearImage($string) {
		$string = preg_replace('/[^a-z0-9а-яА-Я\-]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '-');
	}
	
	public function translateImage($string) {
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		$cir = array('/а/','/б/','/в/','/г/','/д/','/е/','/ж/','/з/','/и/','/й/','/к/',
				    '/л/','/м/','/н/','/о/','/п/','/р/','/с/','/т/','/у/','/ф/','/х/','/ц/','/ч/','/ш/','/щ/',
				    '/ъ/','/ь/','/ю/','/я/','/А/','/Б/','/В/','/Г/','/Д/','/Е/','/Ж/','/З/','/И/','/Й/','/К/',
				    '/Л/','/М/','/Н/','/О/','/П/','/Р/','/С/','/Т/','/У/','/Ф/','/Х/','/Ц/','/Ч/','/Ш/','/Щ/',
				    '/Ъ/','/Ь/','/Ю/','/Я/');
    
        $lat = array('a','b','v','g','d','e','zh','z','i','y','k',
				    'l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sht',
				    'a','y','yu','a','a','b','v','g','d','e','zh','z','i','y','k',
				    'l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sht',
				    'a','y','yu','a');
        
        $string = preg_replace($cir, $lat, $string);
        $string = $string ? $string : 'image';
        return strtolower(self::clearImage($string));
	}
	
	/* added for v2.0 */

	public static function pinImageExist($pin_id, $size, $gallery_id = 0) {
		static $db = null;
		if($db === null) { $db = JO_Db::getDefaultAdapter(); }
		$query = $db->select()
					->from('pins_images')
					->where('pin_id = ?', (string)$pin_id)
					->where('gallery_id = ?', (string)$gallery_id)
					->where('size = ?', $size)
					->limit(1);
		
		return $db->fetchRow($query);
	}

	public static function pinImageCreate($data) {
		static $db = null;
		if($db === null) { $db = JO_Db::getDefaultAdapter(); }
		
		if(self::pinImageExist($data['pin_id'], $data['size'], $data['gallery_id'])) {
			return false;
		}
		
		Helper_Db::insert('pins_images', array(
			'gallery_id' => $data['gallery_id'],
			'pin_id' => $data['pin_id'],
			'size' => $data['size'],
			'width' => $data['width'],
			'height' => $data['height'],
			'image' => $data['image'],
			'original' => $data['original'],
			'mime' => $data['mime']		
		));
	}
	
	//users
	public static function userAvatarExist($pin_id, $size) {
		static $db = null;
		if($db === null) { $db = JO_Db::getDefaultAdapter(); }
		$query = $db->select()
					->from('users_avatars')
					->where('user_id = ?', (string)$pin_id)
					->where('size = ?', $size)
					->limit(1);
		return $db->fetchRow($query);
	}

	public static function userAvatarCreate($data) {
		static $db = null;
		if($db === null) { $db = JO_Db::getDefaultAdapter(); }
		
		if(self::userAvatarExist($data['user_id'], $data['size'])) {
			return false;
		}
		
		Helper_Db::insert('users_avatars', array(
			'user_id' => $data['user_id'],
			'size' => $data['size'],
			'width' => $data['width'],
			'height' => $data['height'],
			'image' => $data['image'],
			'original' => $data['original'],
			'mime' => $data['mime']		
		));
	}
	
}

?>