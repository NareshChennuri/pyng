<?php

class Helper_Uploadimages {
	
	public static function pin($pin, $size = null) {
		if(!isset($pin['pin_store']) || !$pin['pin_store']) {
			$pin['pin_store'] = 'Model_Upload_Locale';
		}
		if(!isset($pin['image']) && isset($pin['pin_image'])) {
			$pin['image'] = $pin['pin_image'];
		} elseif(isset($pin['image']) && !isset($pin['pin_image'])) {
			$pin['pin_image'] = $pin['image'];
		}
		return call_user_func(array($pin['pin_store'], 'getPinImage'), $pin, $size);
	}
	
	public static function avatar($user, $size = null) {
		if(!isset($user['store']) || !$user['store']) {
			$user['store'] = 'Model_Upload_Locale';
		}
		$image = call_user_func(array($user['store'], 'getUserImage'), $user, $size);
		if(!$image) {
			$user['avatar'] = Helper_Config::get('no_avatar');
			$image = call_user_func(array('Model_Upload_Locale', 'getUserImage'), $user, $size);
		}
		return $image;
	}
	
	public static function pinThumbs($pin) {
		$thumb_sizes = Model_Upload_Abstract::pinThumbSizes();
		$thumbs = array();
		foreach($thumb_sizes AS $size => $prefix) {
			if( isset($pin['pin_thumb' . strtolower($prefix)]) && $pin['pin_thumb' . strtolower($prefix)] && count($thumb = explode('|||',$pin['pin_thumb' . strtolower($prefix)])) == 5 ) {
				$thumbs['thumb_image' . strtolower($prefix)] = $thumb[0];
				$thumbs['thumb_original' . strtolower($prefix)] = $thumb[3];
				$thumbs['thumb_width' . strtolower($prefix)] = $thumb[1];
				$thumbs['thumb_height' . strtolower($prefix)] = $thumb[2];
				$thumbs['thumb_mime' . strtolower($prefix)] = $thumb[4];
				$thumbs['thumb_size' . strtolower($prefix)] = $prefix;
				$thumbs['thumb_pin_id' . strtolower($prefix)] = $pin['pin_pin_id'];
			} else {
				$image = Helper_Uploadimages::pin($pin, $prefix);
				if($image) {
					foreach($image AS $k=>$v) {
						$thumbs['thumb_' . $k . strtolower($prefix)] = $v;
					}
				} else {
			
				}
			}
		}
		return $thumbs;
	}
	
	public static function userAvatars($user) {
		$thumb_sizes = Model_Upload_Abstract::userThumbSizes();
		$avatars = array();
		foreach($thumb_sizes AS $size => $prefix) {
			if( isset($user['user_avatar' . strtolower($prefix)]) && $user['user_avatar' . strtolower($prefix)] && count($thumb = explode('|||',$user['user_avatar' . strtolower($prefix)])) == 5 ) {
				$avatars['avatar_image' . strtolower($prefix)] = $thumb[0];
				$avatars['avatar_original' . strtolower($prefix)] = $thumb[3];
				$avatars['avatar_width' . strtolower($prefix)] = $thumb[1];
				$avatars['avatar_height' . strtolower($prefix)] = $thumb[2];
				$avatars['avatar_mime' . strtolower($prefix)] = $thumb[4];
				$avatars['avatar_size' . strtolower($prefix)] = $prefix;
				$avatars['avatar_user_id' . strtolower($prefix)] = $user['user_id'];
			} else {
				$avatar = Helper_Uploadimages::avatar($user, $prefix);
				if($avatar) {
					foreach($avatar AS $k=>$v) {
						$avatars['avatar_' . $k . strtolower($prefix)] = $v;
					}
				} else {
			
				}
			}
		}
		return $avatars;
	}

}

?>