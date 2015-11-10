<?php

class Model_Upload_Amazons3 extends Model_Upload_Abstract {
	
	public static $error = false;
	public static $connect;
	
	public static function init() {
		self::$connect = new JO_Api_Amazon(Helper_Config::get('amazons3_access_key'), Helper_Config::get('amazons3_secret_key'));
		if(!self::$connect->getBucketLogging(Helper_Config::get('amazons3_bucklet'))) {
			self::$error = self::translate('Unable to connect to upload server!');
			return false;
		}
		self::$connect->putBucket(Helper_Config::get('amazons3_bucklet'), JO_Api_Amazon::ACL_PUBLIC_READ);
	}
	
	public static function upload($image, $image_server) {
		if( !self::$error ) {
			if ( !self::$connect->putObjectFile($image, Helper_Config::get('amazons3_bucklet'), $image_server, JO_Api_Amazon::ACL_PUBLIC_READ, array(), JO_File_Ext::getMimeFromFile($image)) ) {
				self::$error = self::translate('Unable to upload to the server!');
			}
		}
	}
	
	public static function delete($image) {
		return self::$connect->deleteObject(Helper_Config::get('amazons3_bucklet'), $image);
	}
	
	public static function getPinImage($pin, $prefix = null) {
		if(!$pin['image']) {
			$pin['image'] = Helper_Config::get('no_image');
		}
		
		$pin['pin_id'] = $pin['pin_pin_id'] = $pin['pin_pin_id'] ? $pin['pin_pin_id'] : -1;
		
		$gallery_id = isset($pin['gallery_id'])?$pin['gallery_id']:0;
		
		$image_stored = self::pinImageExist($pin['pin_id'], $prefix, $gallery_id);
		if($image_stored) {
			return $image_stored;
		}
		
		if($prefix) {
			$img = self::formatImageSize($pin['image'], $prefix);
			if(!$img) {
				return false;
			}
		} else {
			$img = $pin['image'];
		}
		
		$img = trim(Helper_Config::get('amazons3_bucklet_location'),'/') . '/' . $img;
		if( ( $img_size = @getimagesize($img) ) !== false ) {
			$data = array(
				'image' => $img,
				'original' => trim(Helper_Config::get('amazons3_bucklet_location'),'/') . '/' . $pin['image'],
				'width' => $img_size[0],
				'height' => $img_size[1],
				'mime' => $img_size['mime'],
				'size' => $prefix,
				'pin_id' => $pin['pin_id'],
				'gallery_id' => $gallery_id
			);
			
			self::pinImageCreate($data);
			
			return $data;
		} else {
			$data = array(
					'image' => '',
					'original' => trim(Helper_Config::get('amazons3_bucklet_location'),'/') . '/' . $pin['image'],
					'width' => '',
					'height' => '',
					'mime' => '',
					'size' => $prefix,
					'pin_id' => $pin['pin_id'],
					'gallery_id' => $gallery_id
			);
				
			self::pinImageCreate($data);
		}
		
		return false;
	}
	
	public static function deletePinImage($pin_info) {
		
		self::init();
		
		if(self::$error) {
			return false;
		}
		
		$ext = strtolower(strrchr($pin_info['image'],"."));
		$thumbs = array( $pin_info['image'] );
		$sizes = self::pinThumbSizes();
		if($sizes) {
			foreach($sizes AS $size => $key) {
				self::delete(preg_replace('/'.$ext.'$/i',$key.$ext,$pin_info['image']));
			}
		}
	}
	
	public static function uploadPin($image, $title = '', $id = 0) {
		
		if( ( $imageinfo = getimagesize($image) ) !== false ) {
			self::init();
			
			if(self::$error) {
				return false;
			}
			
			if($title && mb_strlen($title, 'utf-8') > 60) {
				$title = JO_Utf8::splitText($title, 60, '');
			}
			if($title && mb_strlen($title, 'utf-8') > 60) {
				$title = mb_substr($title, 0, 60, 'utf8');
			}
			
			$date_added = WM_Date::format(time(), 'yy-mm-dd H:i:s');
		
			$ext = strtolower(strrchr($image,"."));
			if(!$ext) {
				$mime_ext = explode('/', $imageinfo['mime']);
				if(isset($mime_ext[1])) {
					$ext = '.' . $mime_ext[1];
				}
			}
			
			if( $title ) {
				$name = self::translateImage($title) . '_' . md5($image) . '_' . $id . $ext;
			} else {
				$name = md5($image) . '_' . $id . $ext;
			}
			
			$image_path = 'pins/' . WM_Date::format($date_added, 'yy/mm/');
			
			if(!file_exists(BASE_PATH . '/uploads/cache_pins/' . $image_path) || !is_dir(BASE_PATH . '/uploads/cache_pins/' . $image_path)) {
				@mkdir(BASE_PATH . '/uploads/cache_pins/' . $image_path, 0777, true);
			}
			
			$user_agent = ini_get('user_agent');
			ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
			
			if(!@copy($image, BASE_PATH . '/uploads/cache_pins/' . $image_path . $name) ) {
				self::$error = self::translate('Unable to upload to the local server!');
				return false;
			}
			
			ini_set('user_agent', $user_agent);

			$max_width = 0;
			$sizes = self::pinThumbSizes();
			$pin_sizes = array();
			if($sizes) {
				$model_images = new Helper_Images();
				foreach($sizes AS $size => $prefix) {
					$sizes = explode('x', $size);
					$pin_sizes[] = array(
						(int)isset($sizes[0])?$sizes[0]:0,
						(int)isset($sizes[1])?$sizes[1]:0,
						basename($name, $ext) . $prefix . $ext
					);
					$max_width = max($max_width, (int)isset($sizes[0])?$sizes[0]:0);
				}
				if($max_width) {
					$thumb_a = $model_images->resizeWidth('/cache_pins/' . $image_path . $name, $max_width);
					$thumb_a1 = explode('/uploads/', $thumb_a);
					if($thumb_a1 && isset($thumb_a1[1]) && $thumb_a1[1]) {
						self::upload(BASE_PATH . '/uploads/' . $thumb_a1[1], $image_path . $name);
						if(self::$error) {
							return false;
						}
						
						foreach($pin_sizes AS $s) {
							if(self::$error) {
								return false;
							}
							$thumb_b = '';
							if($s[0] && $s[1]) {
								$thumb_b = $model_images->resize('/' . $thumb_a1[1], $s[0], $s[1], true);
							} else if($s[0] && !$s[1]) {
								$thumb_b = $model_images->resizeWidth('/' . $thumb_a1[1], $s[0]);
							} else if(!$s[0] && $s[1]) {
								$thumb_b = $model_images->resizeHeight('/' . $thumb_a1[1], $s[1]);
							}
							
							$thumb_b1 = explode('/uploads/', $thumb_b);
							if($thumb_b1 && isset($thumb_b1[1]) && $thumb_b1[1]) {
								self::upload(BASE_PATH . '/uploads/' . $thumb_b1[1], $image_path . $s[2]);
								if(self::$error) {
									return false;
								}
							} else {
								self::$error = self::translate('Unable to upload to the local server!');
								return false;
							}
						}
						
						$model_images->deleteImages('/' . $thumb_a1[1]);
						$model_images->deleteImages('/cache_avatars/' . $image_path . $name);
						
						return array(
								'store' 	=> 'Model_Upload_Amazons3',
								'image' 	=> $image_path . $name,
								'width'		=> 0,
								'height' 	=> 0
						);
						
					} else {
						self::$error = self::translate('Unable to upload to the local server!');
						return false;
					}
				} else {
					self::$error = self::translate('Missing images sizes!');
					return false;
				}
			} else {
				self::$error = self::translate('Missing images sizes!');
				return false;
			}
		} else {
			self::$error = self::translate('Image format is not valid!');
			return false;
		}
		
		self::$error = self::translate('Unknown server error!');
		return false;
	}
	
	
	/////////// user avatar
	
	public static function uploadUserAvatar($image, $user_id = 0) {
		
		if( ( $imageinfo = getimagesize($image) ) !== false ) {
			
			self::init();
				
			if(self::$error) {
				return false;
			}
			
			$added_date = time();
			$username = md5($user_id);
			$user_info = new Model_Users_User($user_id);
			if($user_info->count()) {
				$added_date = $user_info['date_added'];
				$username = $user_info['username'];
			}
			
			$ext = strtolower(strrchr($image,"."));
			if(!$ext) {
				$mime_ext = explode('/', $imageinfo['mime']);
				if(isset($mime_ext[1])) {
					$ext = '.' . $mime_ext[1];
				}
			}
						
			$name = $username . '_' . $user_id . $ext;
				
			$image_path = 'avatars/' . WM_Date::format($added_date, 'yy/mm/');
			
			if(!file_exists(BASE_PATH . '/uploads/cache_avatars/' . $image_path) || !is_dir(BASE_PATH . '/uploads/cache_avatars/' . $image_path)) {
				@mkdir(BASE_PATH . '/uploads/cache_avatars/' . $image_path, 0777, true);
			}
			
			$user_agent = ini_get('user_agent');
			ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
				
			if(!@copy($image, BASE_PATH . '/uploads/cache_avatars/' . $image_path . $name) ) {
				self::$error = self::translate('Unable to upload to the local server!');
				return false;
			}
				
			ini_set('user_agent', $user_agent);
			
			$max_width = 0;
			$sizes = self::userThumbSizes();
			
			$pin_sizes = array();
			if($sizes) {
				$model_images = new Helper_Images();
				foreach($sizes AS $size => $prefix) {
					$sizes = explode('x', $size);
					$pin_sizes[] = array(
							(int)isset($sizes[0])?$sizes[0]:0,
							(int)isset($sizes[1])?$sizes[1]:0,
							basename($name, $ext) . $prefix . $ext
					);
					$max_width = max($max_width, (int)isset($sizes[0])?$sizes[0]:0);
				} 
				if($max_width) {
					$thumb_a = $model_images->resizeWidth('/cache_avatars/' . $image_path . $name, $max_width);
					$thumb_a1 = explode('/uploads/', $thumb_a);
					if($thumb_a1 && isset($thumb_a1[1]) && $thumb_a1[1]) {
						self::upload(BASE_PATH . '/uploads/' . $thumb_a1[1], $image_path . $name);
						if(self::$error) {
							return false;
						}
			
						foreach($pin_sizes AS $s) {
							if(self::$error) {
								return false;
							}
							$thumb_b = '';
							if($s[0] && $s[1]) {
								$thumb_b = $model_images->resize('/' . $thumb_a1[1], $s[0], $s[1], true);
							} else if($s[0] && !$s[1]) {
								$thumb_b = $model_images->resizeWidth('/' . $thumb_a1[1], $s[0]);
							} else if(!$s[0] && $s[1]) {
								$thumb_b = $model_images->resizeHeight('/' . $thumb_a1[1], $s[1]);
							}
								
							$thumb_b1 = explode('/uploads/', $thumb_b);
							if($thumb_b1 && isset($thumb_b1[1]) && $thumb_b1[1]) {
								self::upload(BASE_PATH . '/uploads/' . $thumb_b1[1], $image_path . $s[2]);
								if(self::$error) {
									return false;
								}
							} else {
								self::$error = self::translate('Unable to upload to the local server!');
								return false;
							}
						}
						
						$model_images->deleteImages('/' . $thumb_a1[1]);
						$model_images->deleteImages('/cache_avatars/' . $image_path . $name);
			
						return array(
								'store' 	=> 'Model_Upload_Amazons3',
								'image' 	=> $image_path . $name,
								'width'		=> 0,
								'height' 	=> 0
						);
			
					} else {
						self::$error = self::translate('Unable to upload to the local server!');
						return false;
					}
				} else {
					self::$error = self::translate('Missing images sizes!');
					return false;
				}
			} else {
				self::$error = self::translate('Missing images sizes!');
				return false;
			}
			
			
		} else {
			self::$error = self::translate('Image format is not valid!');
			return false;
		}
		
		self::$error = self::translate('Unknown server error!');
		return false;

	}
	
	public static function deleteUserImage($user_info) {
		
		self::init();
		if(self::$error) {
			return false;
		}
		
		$thumbs = array( $user_info['avatar'] );
		$sizes = self::userThumbSizes();
		if($sizes) {
			$ext = strtolower(strrchr($user_info['avatar'],"."));
			foreach($sizes AS $size => $key) {
				self::delete(preg_replace('/'.$ext.'$/i',$key.$ext,$user_info['avatar']));
			}
		}
	}
	
	public static function getUserImage($user, $prefix = null) {
		if(!$user['avatar']) {
			$user['avatar'] = Helper_Config::get('no_avatar');
		}
		
		$user['user_id'] = $user['user_id'] ? $user['user_id'] : -1;
		
		$image_stored = self::userAvatarExist($user['user_id'], $prefix);
		if($image_stored) {
			return $image_stored;
		}
		
		if($prefix) {
			$img = self::formatImageSize($user['avatar'], $prefix);
			if(!$img) {
				return false;
			}
		} else {
			$img = $user['avatar'];
		}
		
		$img = trim(Helper_Config::get('amazons3_bucklet_location'),'/') . '/' . $img;
		if( ( $img_size = @getimagesize($img) ) !== false ) {
			$data = array(
				'image' => $img,
				'original' => trim(Helper_Config::get('amazons3_bucklet_location'),'/') . '/' . $user['avatar'],
				'width' => $img_size[0],
				'height' => $img_size[1],
				'mime' => $img_size['mime'],
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