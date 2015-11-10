<?php

class Model_Upload_Rackspace extends Model_Upload_Abstract {
	
	public static $error = false;
	public static $connect;
	public static $contaners = array();
	
	public static function init() {
		$auth = new JO_Api_Rackspace_Authentication(Helper_Config::get('rackspace_api_username'), Helper_Config::get('rackspace_api_key'), (Helper_Config::get('rackspace_account_name') ? Helper_Config::get('rackspace_account_name') : NULL), (Helper_Config::get('rackspace_authentication_service_uri') == 'UK' ? JO_Api_Rackspace_Authentication::UK_AUTHURL : JO_Api_Rackspace_Authentication::US_AUTHURL));
		if(!$auth->authenticate()) {
			self::$error = self::translate('Unable to connect to upload server!');
			return false;
		}
		try {
			self::$connect = new JO_Api_Rackspace_Connection($auth);
			//self::$contaners = self::$connect->list_public_containers();
		} catch (JO_Exception $e) {
			self::$error = $e->getMessage();
			return false;
		}
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
		
		if( ( $img_size = @getimagesize($img) ) !== false ) {
			$data = array(
				'image' => $img,
				'original' => $pin['image'],
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
					'original' => $pin['image'],
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
		
		try {
			
			$images = self::$connect->get_container(Helper_Config::get('rackspace_pins_contaners'));
			
			$pin_info['image'] = basename($pin_info['image']);
			$ext = strtolower(strrchr($pin_info['image'],"."));
			$thumbs = array( $pin_info['image'] );
			$sizes = self::pinThumbSizes();
			if($sizes) {
				foreach($sizes AS $size => $key) {
					$get = $images->exists_object(preg_replace('/'.$ext.'$/i',$key.$ext,$pin_info['image']));
					if($get && $get->content_length) {
						$images->delete_object(preg_replace('/'.$ext.'$/i',$key.$ext,$pin_info['image']));
					}
				}
			}
			
			$get = $images->exists_object($pin_info['image']);
			if($get && $get->content_length) {
				$images->delete_object($pin_info['image']);
			}
			
		} catch (JO_Exception $e) {
			
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
						
						$container = Helper_Config::get('rackspace_pins_contaners');// 'pins_'.date('Y_m');
						
						/*if(!in_array($container, self::$contaners)) {
							try {
								self::$connect->create_container($container);
								$contaners[] = $container;
							} catch (JO_Exception $e) {
								self::$error = $e->getMessage();
								return false;
							}
						}*/
						
						//if(in_array($container, self::$contaners)) {
							
						try {
							$images = self::$connect->get_container($container);
							//$images->make_public(86400*365);
							//$images = self::$connect->get_container($container);
							//if(!$images->cdn_uri) {
							//	self::$error = self::translate("Authentication response did not indicate CDN availability");
							//	return false;
							//}
							
							$object = $images->create_object($name);
							$object->load_from_filename(BASE_PATH . '/uploads/' . $thumb_a1[1]);
							$image_info = $images->get_object($name);
							if(!$image_info->name) {
								self::$error = self::translate('Unable to upload to the server!');
								return false;
							}
							
							foreach($pin_sizes AS $s) {
				
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
									$object = $images->create_object($s[2]);
									$object->load_from_filename(BASE_PATH . '/uploads/' . $thumb_b1[1]);
									$image_info1 = $images->get_object($s[2]);
									if(!$image_info1->name) {
										self::$error = self::translate('Unable to upload to the server!');
										return false;
									}
								} else {
									self::$error = self::translate('Unable to upload to the local server!');
									return false;
								}
							}
							
							return array(
									'store' 	=> 'Model_Upload_Rackspace',
									'image' 	=> trim(Helper_Config::get('rackspace_pins_contaners_cdn'),'/') . '/' . $image_info->name,
									'width'		=> 0,
									'height' 	=> 0
							);
							
						} catch (JO_Exception $e) {
							self::$error = $e->getMessage();
							return false;
						}
							
						/*} else {
							self::$error = self::translate('Unable to upload to the server!');
							return false;
						}*/
						
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
						
						$container = Helper_Config::get('rackspace_users_contaners');
						
						try {
							
							$images = self::$connect->get_container($container);
							//$images->make_public(86400*365);
							//$images = self::$connect->get_container($container);
							//if(!$images->cdn_uri) {
							//	self::$error = self::translate("Authentication response did not indicate CDN availability");
							//	return false;
							//}
								
							$object = $images->create_object($name);
							$object->load_from_filename(BASE_PATH . '/uploads/' . $thumb_a1[1]);
							$image_info = $images->get_object($name);
							if(!$image_info->name) {
								self::$error = self::translate('Unable to upload to the server!');
								return false;
							}
								
							foreach($pin_sizes AS $s) {
							
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
									$object = $images->create_object($s[2]);
									$object->load_from_filename(BASE_PATH . '/uploads/' . $thumb_b1[1]);
									$image_info1 = $images->get_object($s[2]);
									if(!$image_info1->name) {
										self::$error = self::translate('Unable to upload to the server!');
										return false;
									}
								} else {
									self::$error = self::translate('Unable to upload to the local server!');
									return false;
								}
							}
								
							return array(
									'store' 	=> 'Model_Upload_Rackspace',
									'image' 	=> trim(Helper_Config::get('rackspace_users_contaners_cdn'),'/') . '/' . $image_info->name,
									'width'		=> 0,
									'height' 	=> 0
							);
							
						} catch (JO_Exception $e) {
							self::$error = $e->getMessage();
							return false;
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
	
	public static function deleteUserImage($pin_info) {
		
		self::init();
			
		if(self::$error) {
			return false;
		}
		
		try {
			
			$images = self::$connect->get_container(Helper_Config::get('rackspace_users_contaners'));
			
			$user_info['avatar'] = basename($user_info['avatar']);
			$ext = strtolower(strrchr($user_info['avatar'],"."));
			$thumbs = array( $user_info['avatar'] );
			$sizes = self::pinThumbSizes();
			if($sizes) {
				foreach($sizes AS $size => $key) {
					$get = $images->exists_object(preg_replace('/'.$ext.'$/i',$key.$ext,$pin_info['image']));
					if($get && $get->content_length) {
						$images->delete_object(preg_replace('/'.$ext.'$/i',$key.$ext,$pin_info['image']));
					}
				}
			}
			
			$get = $images->exists_object($user_info['avatar']);
			if($get && $get->content_length) {
				$images->delete_object($user_info['avatar']);
			}
			
		} catch (JO_Exception $e) {
			
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
		
		if( ( $img_size = @getimagesize($img) ) !== false ) {
			$data = array(
					'image' => $img,
					'original' => $user['avatar'],
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
		return self::$error ? 'Rackspace: ' . self::$error : false;
	}
	
}

?>