<?php

class Helper_Pin {
	
	public function parse_urls($text, $maxurl_len = 35, $target = '_self') {
	    if (preg_match_all('/((ht|f)tps?:\/\/([\w\.]+\.)?[\w-]+(\.[a-zA-Z]{2,4})?[^\s\r\n\(\)"\'<>\,]+)/si', $text, $urls)) {
	        $offset1 = ceil(0.65 * $maxurl_len) - 2;
	        $offset2 = ceil(0.30 * $maxurl_len) - 1;
	        
	        foreach (array_unique($urls[1]) AS $url) {
	            if ($maxurl_len AND strlen($url) > $maxurl_len) {
	                $urltext = substr($url, 0, $offset1) . '...' . substr($url, -$offset2);
	            } else {
	                $urltext = $url;
	            }
	            
	            $text = str_replace($url, '<a class="link" href="'. $url .'" onclick="target=\''. $target .'\'" title="'. $url .'">'. $urltext .'</a>', $text);
	        }
	    }
	
	    return $text;
	}  
	
	public static function descriptionFix($description) {
		//$description = html_entity_decode($description, ENT_QUOTES, 'utf-8');
		//$description = preg_replace('/(<!--|-->)/Uis','',$description);
		$description = self::parse_urls(str_replace('&amp;', '&', $description), 35, '_blank');
		return $description;
	}
	
	public static function getPinLikes($pin_id) {
		static $result = array(), $model_images = null, $request = null;
		if(isset($result[$pin_id])) return $result[$pin_id];
		if($model_images === null) { $model_images = new Helper_Images(); }
		if($request === null) { $request = JO_Request::getInstance(); }
		$users = Model_Users::getUsers(array(
			'filter_like_pin_id' => $pin_id,
			'start' => 0,
			'limit' => 20,
			'order' => 'pins_likes.like_id',
			'sort' => 'DESC'
		));
		$data = array();
		if($users) { 
			foreach($users AS $user) {
				
				$avatar = Helper_Uploadimages::avatar($user, '_A');
				$user['avatar'] = $avatar['image'];
				
				$data[] = array(
					'avatar' => $user['avatar'],
					'fullname' => $user['fullname'],
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'])
				);
			}
		}
		$result[$pin_id] = array(
			'data' => $data,
			'total' => (Model_Users::getTotalUsers(array(
				'filter_like_pin_id' => $pin_id
			)) - count($data))
		);
		return $result[$pin_id];
	}
	
	public static function getRePins($pin_id) {
		static $result = array(), $model_images = null, $request = null;
		if(isset($result[$pin_id])) return $result[$pin_id];
		if($model_images === null) { $model_images = new Helper_Images(); }
		if($request === null) { $request = JO_Request::getInstance(); }
		$pins = Model_Pins::getPins(array(
			'filter_repin_from' => $pin_id,
			'start' => 0,
			'limit' => 6
		)); 
		$data = array();
		if($pins) {
			foreach($pins AS $pin) {

				$img = Helper_Uploadimages::pin($pin, '_A');
				$image = $img['image'];
				
				$avatar = Helper_Uploadimages::avatar($pin['user'], '_A');
				$pin['user']['avatar'] = $avatar['image'];
				
				$pin['user']['profile'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_id'] );
		
				$data[] = array(
					'board' => $pin['board'],
					'user' => $pin['user'],
					'onto_href' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin['user_id'] . '&board_id=' . $pin['board_id'] ),
					'thumb' => $image,
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=pin&pin_id=' . $pin['pin_id'] )
				);
			}
		}
		$result[$pin_id] = $data;
		return $data;
	}
	
	public static function getBoardPins($board_id, $limit = 12, $thumb = 75) {
		static $result = array(), $model_images = null, $request = null;
		if(isset($result[$board_id])) return $result[$board_id];
		if($model_images === null) { $model_images = new Helper_Images(); }
		if($request === null) { $request = JO_Request::getInstance(); }
		$pins = Model_Pins::getPins(array(
			'filter_board_id' => $board_id,
			'start' => 0,
			'limit' => $limit
		));
		$data = array();
		if($pins) {
			foreach($pins AS $pin) {
				$image = Helper_Uploadimages::pin($pin, '_A');
				if($image) {
					$data[] = array(
							'board' => Model_Boards::getBoardWithoutUser($board_id),
							'thumb' => $image['image'],
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin['user_id'] . '&board_id=' . $pin['board_id'])
					);
				}
			}
		}
		$result[$board_id] = $data;
		return $data;
	}
	
	public static function getOriginallyPinned($user_id) {
		static $result = array(), $model_images = null, $request = null;
		if(isset($result[$user_id])) return $result[$user_id];
		if($model_images === null) { $model_images = new Helper_Images(); }
		if($request === null) { $request = JO_Request::getInstance(); }
		$pins = Model_Pins::getPins(array(
			'filter_user_id' => $user_id,
			'start' => 0,
			'limit' => 6
		));
		$data = array();
		if($pins) {
			foreach($pins AS $pin) {
				$image = Helper_Uploadimages::pin($pin, '_A');
				if($image) {
					$data[] = array(
							'user' => Model_Users::getUser($user_id),
							'thumb' => $image['image'],
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_id'])
					);
				}
			}
		}
		$result[$user_id] = $data;
		return $data;
	}
	
	public static function getSourcePins($source_id, $limit = 5, $thumb = 75) {
		static $result = array(), $model_images = null, $request = null;
		if(isset($result[$source_id])) return $result[$source_id];
		if($model_images === null) { $model_images = new Helper_Images(); }
		if($request === null) { $request = JO_Request::getInstance(); }
		$pins = Model_Pins::getPins(array(
			'filter_source_id' => $source_id,
			'start' => 0,
			'limit' => $limit
		));
		$data = array();
		if($pins) {
			foreach($pins AS $pin) {
				$image = Helper_Uploadimages::pin($pin, '_A');
				if($image) {
					$data[] = array(
							'thumb' => $image['image'],
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=source&source_id=' . $pin['source_id'])
					);
				}
			}
		}
		$result[$source_id] = $data;
		return $data;
	}

	
	public static function formatUploadModule($store) {
		static $front = null, $request = null, $upload_store = array();
		if($request === null) { $request = JO_Request::getInstance(); }
		if($front === null) { $front = JO_Front::getInstance(); }
		
		if($store == 'local' || $store == '') { $store = 'locale'; }
		
		if(isset($upload_store[$store])) {
			return $upload_store[$store];
		} else {
			$upload_model = 'model_upload_' . $store;
			$upload_model = $front->formatModuleName($upload_model);
			$upload_store[$store] = $upload_model;
			return $upload_model;
		}
	}
	

	//////////////////////////////////////////// v2 ////////////////////////////////////////////

	

	public static function formatPinData($pin, $detail = false) { 
	
		static $model_images = null, $request = null;
		if($model_images === null) { $model_images = new Helper_Images(); }
		if($request === null) { $request = JO_Request::getInstance(); }

		///////////////////////////////////// PIN INFO /////////////////////////////////////
		
		$pin['pin_id'] = $pin['pin_pin_id'];
		$pin['image'] = $pin['pin_image'];
		
		//url's
		$pin['pin_url_embed'] = WM_Router::pinAction( $pin['pin_pin_id'], 'embed' );
		if(!JO_Session::get('user[user_id]')) {
			
			$login_url = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
			if(Helper_Config::get('enable_free_registration')) {
				//$login_url = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=register' );
			}
			
			$pin['pin_url_email'] = 
			$pin['pin_url_report'] = 
			$pin['pin_url_like'] = 
			$pin['pin_url_repin'] = 
			$pin['pin_url_comment'] = $login_url;
			$pin['pin_edit'] = false;
		} else { 
			$pin['pin_url_email'] = WM_Router::pinAction( $pin['pin_pin_id'], 'email' );
			$pin['pin_url_report'] = WM_Router::pinAction( $pin['pin_pin_id'], 'report' );
			$pin['pin_url_like'] = WM_Router::pinAction( $pin['pin_pin_id'], 'like' );
			$pin['pin_url_repin'] = WM_Router::pinAction( $pin['pin_pin_id'], 'repin' );
			$pin['pin_url_comment'] = WM_Router::pinAction( $pin['pin_pin_id'] );
			$pin['pin_edit'] = JO_Session::get('user[user_id]') == $pin['pin_user_id'] ? WM_Router::pinAction( $pin['pin_pin_id'], 'edit' ) : false;
		}
		
		//$pin['pin_description'] = self::descriptionFix($pin['pin_description']);
		$pin['pin_href'] = WM_Router::pinAction( $pin['pin_pin_id'] );
		
		if($pin['pin_gift']) {
			$pin['pin_price_formated'] = WM_Currency::format($pin['pin_price']);
		} else {
			$pin['pin_price_formated'] = 0;
		}
		
		
		
		//return all image sizes
		$pin['pin_thumbs'] = Helper_Uploadimages::pinThumbs($pin);//array_merge($pin, Helper_Uploadimages::pinThumbs($pin));
		

		
		$date_dif = array_shift( WM_Date::dateDiff($pin['pin_date_added'], time()) );
		$pin['pin_date_dif'] = $date_dif;
		
		/* URL'S*/
		$pin['pin_onto_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin['board_user_id'] . '&board_id=' . $pin['board_board_id'] );
		////follow urls
		$pin['board_follow_href'] = 
		$pin['user_follow_href'] = 
		$pin['via_follow_href'] = false;
		if(JO_Session::get('user[user_id]') ? $pin['board_user_id'] != JO_Session::get('user[user_id]') : false) {
			$pin['board_follow_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=follow&user_id=' . $pin['board_user_id'] . '&board_id=' . $pin['board_board_id'] );
		}
		if(JO_Session::get('user[user_id]') ? $pin['user_user_id'] != JO_Session::get('user[user_id]') : false) {
			$pin['user_follow_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $pin['user_user_id'] );
		}
		if($pin['via_user_id']) {
			if(JO_Session::get('user[user_id]') ? $pin['via_user_id'] != JO_Session::get('user[user_id]') : false) {
				$pin['via_follow_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=follow&user_id=' . $pin['via_user_id'] );
			}
		} else {
			if(JO_Session::get('user[user_id]') ? $pin['user_user_id'] != JO_Session::get('user[user_id]') : false) {
				$pin['via_follow_href'] = $pin['user_follow_href'];
			}
		}
		
		if($detail) {
			$pin['pin_next_href'] = '';
			if(isset($pin['pin_next']) && $pin['pin_next']) {
				$pin['pin_next_href'] = WM_Router::pinAction( $pin['pin_next'] );
			}
			$pin['pin_prev_href'] = '';
			if(isset($pin['pin_prev']) && $pin['pin_prev']) {
				$pin['pin_prev_href'] = WM_Router::pinAction( $pin['pin_prev'] );
			}
		}
		
		if(Helper_Config::get('config_comments_list') && !$detail && $pin['pin_comments']) {
			$latest_comments = Model_Comments::getLatestComments2($pin['pin_latest_comments']);
			$pin['pin_latest_comments'] = array();
			
			$user_id = JO_Session::get('user[user_id]');
			$is_admin = JO_Session::get('user[is_admin]');
			$is_developer = JO_Session::get('user[is_developer]');
			
			foreach($latest_comments AS $key => $comment) {
				
				$user_avatars = Helper_Uploadimages::userAvatars(array(
						'avatar' => $comment['avatar'],
						'store' => $comment['store'],
						'user_id' => $comment['user_id']
				));
		
				$enable_delete = false;
				if( $is_admin ) {
					$enable_delete = true;
				} elseif( $is_developer ) {
					$enable_delete = true;
				} elseif( $user_id == $comment['user_id'] ) {
					$enable_delete = true;
				} elseif($comment['pin_user_id'] == $user_id) {
					$enable_delete = true;
				}
				
				$delete = false;
				if( $enable_delete ) {
					$delete = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=deleteComment&comment_id=' . $comment['comment_id'] );
				}
				$pin['pin_latest_comments'][] = array(
					'user' => array(
									'profile' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $comment['user_id'] ),
									'avatars' => $user_avatars,
									'fullname' => $comment['fullname']
								),
					'comment' => $comment['comment'],
					'delete' => $delete
				);
			}
			
		} else {
			$pin['pin_latest_comments'] = array();
		}
		
		$pin['pin_video_code'] = false;
		if($detail) {
			if($pin['pin_is_video']) {
				$auto = new Helper_AutoEmbed();
				if( $pin['pin_repin_from'] && $auto->parseUrl($pin['pin_from_repin']) ) {
					$auto->setWidth('100%');
					$auto->setHeight('350');
					$pin['pin_video_code'] = $auto->getEmbedCode();
				} else {
					if( $auto->parseUrl($pin['pin_from']) ) {
						$auto->setWidth('100%');
						$auto->setHeight('350');
						$pin['pin_video_code'] = $auto->getEmbedCode();
					} else {
						$pin['pin_is_video'] = false;
					}
				}
				
				
			} else {
				$auto = new Helper_AutoEmbed();
				if( $pin['pin_repin_from'] && $auto->parseUrl($pin['pin_from_repin']) ) {
					$auto->setWidth('100%');
					$auto->setHeight('350');
					$pin['pin_video_code'] = $auto->getEmbedCode();
					$pin['pin_is_video'] = true;
				} else if( $auto->parseUrl($pin['pin_from']) ) {
					$auto->setWidth('100%');
					$auto->setHeight('350');
					$pin['pin_video_code'] = $auto->getEmbedCode();
					$pin['pin_is_video'] = true;
				} else {
					$pin['pin_is_video'] = false;
				}
			}
			
			$pin['pin_gallery'] = array();
			$gallery = new Model_Pins_Gallery($pin['pin_pin_id']);
			if($gallery->count()) {
				foreach($gallery->data AS $gal) {
					$pin['pin_gallery'][] = Helper_Uploadimages::pinThumbs(array(
						'pin_pin_id' => $gal['pin_id'],
						'pin_store'	=> $gal['store'],
						'gallery_id' => $gal['gallery_id'],
						'pin_image' => $gal['image']
					));
				}
			}
			
		}
		
		///////////////////////////////////// AUTHOR INFO /////////////////////////////////////
		//return author all images
		
		$user_data = array(
			'avatar' => $pin['user_avatar'],
			'store' => $pin['user_store'],
			'user_id' => $pin['user_user_id']
		);
		foreach($pin AS $k => $v) {
			if(strpos($k, 'user_avatar_') === 0) {
				$user_data[$k] = $v;
			}
		}
		
		$pin['user_avatars'] = Helper_Uploadimages::userAvatars($user_data);
		
		$pin['user_href'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_user_id'] );
		
		///////////////////////////////////// VIA INFO /////////////////////////////////////
		
		$pin['via_profile'] = array();
		if($pin['via_user_id']) {
			$via_avatars = Helper_Uploadimages::userAvatars(array(
					'avatar' => $pin['via_avatar'],
					'store' => $pin['via_store'],
					'user_id' => $pin['via_user_id']
			));
			$pin['via_profile'] = array(
					'avatars' => $via_avatars,
					'profile' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['via_user_id'] ),
					'fullname' => $pin['via_fullname']
			);
		}
		
		///////////////////////////////////// SELF INFO /////////////////////////////////////
		$pin['self_profile'] = false;
		if(JO_Session::get('user[user_id]')) {	
			$user_avatars = Helper_Uploadimages::userAvatars(array(
					'avatar' => JO_Session::get('user[avatar]'),
					'store' => JO_Session::get('user[store]'),
					'user_id' => JO_Session::get('user[user_id]')
			));
			$pin['self_profile']['avatars'] = $user_avatars;
			$pin['self_profile']['fullname'] = JO_Session::get('user[fullname]');
			$pin['self_profile']['profile'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') );
		}
		
		///////////////////////////////////// SOURCE INFO /////////////////////////////////////
		$pin['pin_source'] = array();
		$pin['pin_target_repin'] = false;
		if($detail) {
			if($pin['pin_repin_from']) {
				$pin_repin = new Model_Pins_Pin($pin['pin_repin_from']);
				if($pin_repin->count()) {
					$pin_repin = $pin_repin->data;
					$pin['pin_source']['source'] = $pin_repin['board_title'];
					$pin['pin_from'] = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_repin['user_user_id'] . '&board_id=' . $pin_repin['board_board_id'] );
					$pin['pin_target_repin'] = true;
				} else {
					$source = new Model_Sources_Source($pin['pin_source_id']);
					if($source->count()) {
						$pin['pin_source']['source'] = $source['source'];
					}
				}
			} else {
				$source = new Model_Sources_Source($pin['pin_source_id']);
				if($source->count()) {
					$pin['pin_source']['source'] = $source['source'];
				}
			}
		}
		
		///for mobile
		$pin['mobile_upload'] = false;
		if($pin['pin_from'] == 'Mobile') {
			$pin['pin_from'] = '';
			$pin['mobile_upload'] = true;
		}
		
		return $pin;
	}
	
}

?>
