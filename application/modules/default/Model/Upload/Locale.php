<?php

class Model_Upload_Locale extends Model_Upload_Abstract {
	
	public static $error = false;
	
	public static function getPinImage($pin, $size = null) {
		/*if(!$pin['image']) {
			$pin['image'] = Helper_Config::get('no_image');
		}*/
		
		$sizes = self::pinThumbSizes();
		$format_size = false;
		if($sizes) {
			foreach($sizes AS $val => $key) {
				if($key == $size) {
					$format_size = $val;
					break;
				}
			}
		}
		if(!$format_size) {
			return false;
		}
		
		$pin['pin_id'] = $pin['pin_pin_id'] = $pin['pin_pin_id'] ? $pin['pin_pin_id'] : -1;
		
		$gallery_id = isset($pin['gallery_id'])?$pin['gallery_id']:0;
		
		$image_stored = self::pinImageExist($pin['pin_pin_id'], $size, $gallery_id);
		if($image_stored) {
			return $image_stored;
		}
		
		$model_images = new Helper_Images();
		
		$sizes = explode('x', $format_size);
		$width = (int)isset($sizes[0])?$sizes[0]:0;
		$height = (int)isset($sizes[1])?$sizes[1]:0;
		
		if($width && $height) {
			$img = $model_images->resize($pin['pin_image'], $width, $height, true);
		} else if($width && !$height) {
			$img = $model_images->resizeWidth($pin['pin_image'], $width);
		} else if($height && !$width) {
			$img = $model_images->resizeHeight($pin['pin_image'], $height);
		} 
		
		if( $img ) {
			$data = array(
					'image' => $img,
					'original' => $model_images->original($pin['pin_image']),
					'width' => $model_images->getSizes('width'),
					'height' => $model_images->getSizes('height'),
					'mime' => JO_File_Ext::getMimeFromFile($img),
					'size' => $size,
					'pin_id' => $pin['pin_pin_id'],
					'gallery_id' => $gallery_id
			);
			
			self::pinImageCreate($data);
			
			return $data;
		} else {
			$data = array(
					'image' => '',
					'original' => '',
					'width' => '',
					'height' => '',
					'mime' => '',
					'size' => $size,
					'pin_id' => $pin['pin_pin_id'],
					'gallery_id' => 0
			);
				
			self::pinImageCreate($data);
		}
		
		return false;

	}
	
	public static function deletePinImage($pin_info) {
		$model_image = new Helper_Images();
		$model_image->deleteImages($pin_info['image'], true);
	}
	
	public static function uploadPin($image, $title = '', $id = 0) {
		try {
			
			if($title && mb_strlen($title, 'utf-8') > 60) {
				$title = JO_Utf8::splitText($title, 60, '');
			}
			if($title && mb_strlen($title, 'utf-8') > 60) {
				$title = mb_substr($title, 0, 60, 'utf8');
			}
			
			$date_added = WM_Date::format(time(), 'yy-mm-dd H:i:s');
			$user_agent = ini_get('user_agent');
			ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
			
			if( ( $imageinfo = @getimagesize($image) ) !== false ) {
				$ext = JO_File_Ext::getExtFromMime($imageinfo['mime']);
				$ext = '.'.$ext;
				if( $title ) {
					$name = self::translateImage($title) . '_' . md5($image) . '_' . $id . $ext;
				} else {
					$name = md5($image) . '_' . $id . $ext;
				}
				
				$image_path = '/pins/' . WM_Date::format($date_added, 'yy/mm/');
				if(!file_exists( BASE_PATH . '/uploads' . $image_path ) || !is_dir(BASE_PATH . '/uploads' . $image_path)) {
					@mkdir(BASE_PATH . '/uploads' . $image_path, 0777, true);
				}
				
				$name = self::rename_if_exists($image_path, $name);
			
				// 				Helper_Images::copyFromUrl($image, BASE_PATH . '/uploads' . $image_path . $name);
				if(@copy($image, BASE_PATH . '/uploads' . $image_path . $name )) {
			
					ini_set('user_agent', $user_agent);
				
					if( file_exists( BASE_PATH . '/uploads' . $image_path . $name ) ) {
						return array(
								'store' 	=> 'Model_Upload_Locale',
								'image' => $image_path . $name,
								'width'	=> 0,
								'height' => 0
						);
					} else {
						self::$error = 'File not found';
						return false;
					}
				} else {
					self::$error = 'Error upload';
					return false;
				}
			} else {
				self::$error = 'Not valid image';
				return false;
			}
		} catch (JO_Exception $e) {
			self::$error = $e->getMessage();
			return false;
		}
		return false;
	}
	
	private function rename_if_exists($dir, $filename) {
	    $ext = strtolower(strrchr($filename, '.'));
	    $prefix = substr($filename, 0, -strlen($ext));
	    $i = 0;
	    while(file_exists($dir . $filename)) { // If file exists, add a number to it.
	        $filename = $prefix . '[' .++$i . ']' . $ext;
	    }
	    return $filename;
	}
	
	
	/////////// user avatar
	
	public static function uploadUserAvatar($avatar, $user_id = 0) {
		try {
			$added_date = time();
			$username = md5($user_id);
			$user_info = new Model_Users_User($user_id);
			if($user_info->count()) {
				$added_date = $user_info['date_added'];
				$username = $user_info['username'];
			}
			
			if( ( $imageinfo = @getimagesize($avatar) ) !== false ) {
				$ext = JO_File_Ext::getExtFromMime($imageinfo['mime']);
				
				$name = $username . '_' . $user_id . '.' . $ext;
			
				$image_path = '/users/' . WM_Date::format($added_date, 'yy/mm/');
				//$name = self::rename_if_exists($image_path, $name);
			
				if(!file_exists( BASE_PATH . '/uploads' . $image_path ) || !is_dir(BASE_PATH . '/uploads' . $image_path)) {
					mkdir(BASE_PATH . '/uploads' . $image_path, 0777, true);
				}
			
				if(copy($avatar, BASE_PATH . '/uploads' . $image_path . $name )) {
					if( file_exists( BASE_PATH . '/uploads' . $image_path . $name ) ) {
						return array(
								'store' 	=> 'Model_Upload_Locale',
								'image' => $image_path . $name,
								'width'	=> 0,
								'height' => 0
						);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} catch (JO_Exception $e) {
			self::$error = $e->getMessage();
			return false;
		}
		return false;
	}
	
	public static function deleteUserImage($user_info) {
		$model_image = new Helper_Images();
		$model_image->deleteImages($user_info['avatar'], true);
	}

	public static function getUserImage($user, $prefix = null) {
		
		if(!$user['avatar']) {
			$user['avatar'] = Helper_Config::get('no_avatar');
		}
		
		$sizes = self::userThumbSizes();
		$format_size = false;
		if($sizes) {
			foreach($sizes AS $val => $key) {
				if($key == $prefix) {
					$format_size = $val;
					break;
				}
			}
		}
		if(!$format_size) {
			return false;
		}
		
		$user['user_id'] = $user['user_id'] ? $user['user_id'] : -1;
		
		$image_stored = self::userAvatarExist($user['user_id'], $prefix);
		if($image_stored) {
			return $image_stored;
		}
		
		$model_images = new Helper_Images();
		
		$sizes = explode('x', $format_size);
		$width = (int)isset($sizes[0])?$sizes[0]:0;
		$height = (int)isset($sizes[1])?$sizes[1]:0;
		
		if($width && $height) {
			$img = $model_images->resize($user['avatar'], $width, $height, true);
		} else if($width && !$height) {
			$img = $model_images->resizeWidth($user['avatar'], $width);
		} else if($height && !$width) {
			$img = $model_images->resizeHeight($user['avatar'], $height);
		}
		
		if( $img ) {
			$data = array(
					'image' => $img,
					'original' => $model_images->original($user['avatar']),
					'width' => $model_images->getSizes('width'),
					'height' => $model_images->getSizes('height'),
					'mime' => JO_File_Ext::getMimeFromFile($img),
					'size' => $prefix,
					'user_id' => $user['user_id']
			);
			
			self::userAvatarCreate($data);
			
			return $data;
		}
		
		return false;

	}
	
	public static function getError() {
		return self::$error;
	}
	
}

?>