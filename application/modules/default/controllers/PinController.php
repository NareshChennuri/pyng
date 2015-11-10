<?php

class PinController extends Helper_Controller_Default {

	/************************* V2 ********************************/
	
	public function header_metasAction($pin_array = array()) {
		if($pin_array) {
			$pin_array = array_merge((array)$pin_array, Helper_Pin::formatPinData((array)$pin_array, true));
			$params = array(
				'min_word_occur' => 2,
				'min_2words_phrase_occur' => 2
			);
			$params['content'] = strip_tags(html_entity_decode($pin_array['pin_description'] . ' ' . $pin_array['board_title'], ENT_QUOTES, 'UTF-8')); //page content
			$keywords = new WM_Keywords($params);
			
			$this->view->site_name = Helper_Config::get('site_name');
			
			$this->view->pin = array(
				'title' => $pin_array['board_title'] . ' - ' . htmlspecialchars(strip_tags( html_entity_decode($pin_array['pin_description']) )),
				'description' => htmlspecialchars(strip_tags( html_entity_decode($pin_array['pin_description']) )),
				'keywords' => htmlspecialchars($keywords->get_keywords()),
				'images' => $pin_array['pin_thumbs'],
				'pin_url' => $pin_array['pin_href'],
				'is_video' => $pin_array['pin_is_video'],
				'from' => $pin_array['pin_from']
			);
			
			Helper_Config::set('extra_metatags', array('pin' => $this->view->pin));
			
			//var_dump($this->view->pin); exit;
		} else {
			$this->noViewRenderer(true);
		}
		$this->noLayout(true);
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		if($request->getPost('send_comment') == 1 ) {
			if(JO_Session::get('user[user_id]')) {
				$this->forward('pin', 'v2addComment');
			} else {
				exit;
			}
		}
		
		$pin_id = $request->getRequest('pin_id');
		
		if( JO_Registry::get('isMobile') ) {
			
			if($request->isXmlHttpRequest()) {
				$this->noViewRenderer(true);
				$this->indexHelper();
			}
			
			$this->view->children = array(
					'header_part' 	=> 'layout/header_part',
					'footer_part' 	=> 'layout/footer_part'
			);
			
		} else {
		
			if(Helper_Config::get('config_disable_js')) {
				
				$return = $this->indexHelper(true);
				if($return['template'] == 'error') {
					$this->forward('error', 'error404');
				}
				
				foreach($return AS $key => $val) {
					$this->view->{$key} = $val;
				}
				
				$template = new Helper_Tmpl($return['template'], $return);
				$this->view->result_data = $template->render($return['template']);
				
				$this->view->children = array(
						'header_part' 	=> 'layout/header_part',
						'footer_part' 	=> 'layout/footer_part'
				);
				
			} else {
				//if get url or F5 load index pin's and open box
				if(!$request->isXmlHttpRequest()) {
					$request->setParams('open_from_pin_detail_page', $pin_id);
					$this->forward('index', 'index');
				} elseif($request->getParam('callback') == 'Pins.getPins') {
					$this->forward('index', 'index');
				}
				$this->indexHelper();
			}
		
		}
		
	}
	
	public function indexHelper($return_data = false) {
		
		$request = $this->getRequest();
		
		//if(JO_Session::get('user[user_id]') && $request->getPost('send_comment') == 1 ) {
		//	$this->forward('pin', 'v2addComment');
		//}
		
		$pin_id = $request->getRequest('pin_id');
		
		

		$pin_array2 = new Model_Pins_Pin($pin_id);
		
		$return = array();
		if($pin_array2->count() < 1) {
			$return = array(
				'template' => 'error',
				'message' => $this->translate('The page you\'re looking for could not be found.')
			);
		} else {
			
			$pin_array = $pin_array2->data;
			
			//metas
			if($return_data) {
				JO_Layout::getInstance()->meta_title = $pin_array['board_title'] . ' - ' . htmlspecialchars(strip_tags( html_entity_decode($pin_array['pin_description']) ));
				JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('pin/header_metas', $pin_array));
			}
			
			$pin = array();
			foreach($pin_array AS $key => $value) {
				$pin[$key] = $value;
			}
			
			$loged = JO_Session::get('user[user_id]');
			
			$banners = Model_Banners::getBanners(
					new JO_Db_Expr("`controller` = '".$request->getController()."'")
			);
			
			$tmp_banners = array();
			foreach($banners AS $pos => $bannersd) {
				foreach($bannersd AS $k=>$d) {
					$bannersd[$k]['html'] = html_entity_decode($d['html']);
				}
				$tmp_banners = array_merge($tmp_banners, $bannersd);
			}
			
			$extra_metas = '';
			if(!Helper_Config::get('config_disable_js')) {
				$this->header_metasAction($pin_array);
				$extra_metas_get = Model_Extensions::getByMethod('extra_metas');
				foreach($extra_metas_get AS $id => $mod) {
					$extra_metas .= $this->view->callChildren('modules_' . $mod . '_extrametas');
				}
			} 
			
			$pin = array_merge((array)$pin, Helper_Pin::formatPinData((array)$pin, true));
			
			/* v2.2 */
			$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
			if($config_enable_follow_private_profile) {
				$user_enable_follow = ($pin['via_user_id']?$pin['via_enable_follow']:$pin['user_enable_follow']);
			} else {
				$user_enable_follow = true;
			}
			/* v2.2 */
			
			$return = array(
					'template' => 'pinDetail',
					'pin_id' => (string)$pin['pin_id'],
					'is_reported' => $pin['pin_is_reported'],
					'loged' => $loged,
					'is_liked' => $pin['pin_is_liked'],
					'vip' => $pin['pin_vip'],
					'gift' => $pin['pin_gift'],
					'price' => $pin['pin_price_formated'],
					'is_video' => $pin['pin_is_video'],
					'description' => str_replace('&amp;','&',$pin['pin_description']),
					'likes' => $pin['pin_likes'],
					'comments' => $pin['pin_comments'],
					'repins' => $pin['pin_repins'],
					'video_code' => $pin['pin_video_code'],
					'from' => $pin['pin_from'],
					'source' => $pin['pin_source'],
					'target_repin' => $pin['pin_target_repin'],
                    'offerStartsOn' => $pin['offerStartsOn'],
                    'offerExpiresOn' => $pin['offerExpiresOn'],
                    'cityName' => $pin['cityName'],
                    'placeName' => $pin['placeName'],
                    'storeName' => $pin['storeName'],
					'banners' => $tmp_banners,
					'extra_metas' => $extra_metas,
					'meta_title' => $pin['board_title'] . ' - ' . htmlspecialchars(strip_tags( html_entity_decode($pin['pin_description']) )),
					//follow
					/* v2.2 mod */
					'enable_follow_board' => ($loged ? $pin['board_user_id'] != $loged && $user_enable_follow : false),
					'enable_follow_user' => ($loged ? $pin['user_user_id'] != $loged && $user_enable_follow : false),
					'enable_follow_via' => ($loged ? ($pin['via_user_id']?$pin['via_user_id']:$pin['user_user_id']) != $loged && $user_enable_follow : false),
					/* v2.2 mod*/
					'board_is_follow' => $pin['following_board'],
					'user_is_follow' => $pin['following_user'],
					'via_is_follow' => ($pin['via_user_id']?$pin['following_via']:$pin['following_user']),
					//via
					'via_profile' => $pin['via_profile']?$pin['via_profile']:false,
					//self
					'self_profile' => $pin['self_profile']?$pin['self_profile']:false,
					//author
					'author_profile' => array(
							'avatars' => $pin['user_avatars'],
							'fullname' => $pin['user_fullname'],
							'user_id' => $pin['user_user_id'],
							'href' => $pin['user_href']
					),
					//links
					'url_repin' => $pin['pin_url_repin'],
					'url_edit' => $pin['pin_edit'],
					'url_like' => $pin['pin_url_like'],
					'url_comment' => $pin['pin_url_comment'],
					'url_embed' => $pin['pin_url_embed'],
					'url_report' => $pin['pin_url_report'],
					'url_email' => $pin['pin_url_email'],
					'url_login' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ),
					'pin_url' => $pin['pin_href'],
					'board_url' => $pin['pin_onto_href'],
					'pin_next_href' => $pin['pin_next_href'],
					'pin_prev_href' => $pin['pin_prev_href'],
					'user_follow_href' => $pin['user_follow_href'],
					'board_follow_href' => $pin['board_follow_href'],
					'via_follow_href' => $pin['via_follow_href'],
					'mobile_upload' => $pin['mobile_upload'],
					//comments
					'pin_comments' => $pin['pin_latest_comments']?$pin['pin_latest_comments']:false,
					//images
					'images' => $pin['pin_thumbs'],
					'gallery' => $pin['pin_gallery'],
					//text's
					'text_mobile' => $this->translate('Mobile'),
					'text_vip' => $this->translate('VIP'),
					'text_from' => $this->translate('From'),
					'text_uploaded_by' => $this->translate('Uploaded by'),
					'text_delete_comment' => $this->translate('Delete Comment'),
					'text_via' => $this->translate('via'),
					'text_onto' => $this->translate('onto'),
					'text_repin' => $this->translate('Repin'),
					'text_edit' => $this->translate('Edit'),
					'text_email' => $this->translate('Email'),
					'text_like_unlike' => $pin['pin_is_liked']?$this->translate('Unlike'):$this->translate('Like'),
					'text_comment' => $this->translate('Comment'),
					'text_add_comment' => $this->translate('Add a comment...'),
					'text_post_comment' => $this->translate('Post comment'),
					'text_post_comment_help' => $this->translate('Type @ to recommend this pin to another Pinner'),
					'text_login_comment' => $this->translate('Login to Comment'),
					'text_total_likes' => sprintf( $this->translate('%d like' . ($pin['pin_likes'] == 1 ? '' : 's')), $pin['pin_likes'] ),
					'text_total_comments' => sprintf( $this->translate('%d comment' . ($pin['pin_comments'] == 1 ? '' : 's')), $pin['pin_comments'] ),
					'text_total_repins' => sprintf( $this->translate('%d repin' . ($pin['pin_repins'] == 1 ? '' : 's')), $pin['pin_repins'] ),
					'text_date_dif' => sprintf($this->translate('%d %s ago'), $pin['pin_date_dif']['value'], $this->translate($pin['pin_date_dif']['key'])),
					'text_pinned_date_dif' => sprintf($this->translate(($pin['pin_repin_from'] ? 'Repinned' : 'Pinned').' %d %s ago'), $pin['pin_date_dif']['value'], $this->translate($pin['pin_date_dif']['key'])),
					'text_pinned_date_dif_from' => sprintf($this->translate(($pin['pin_repin_from'] ? 'Repinned' : 'Pinned').' %d %s ago from'), $pin['pin_date_dif']['value'], $this->translate($pin['pin_date_dif']['key'])),
					'text_uploads_date_dif' => sprintf($this->translate('Uploaded %d %s ago'), $pin['pin_date_dif']['value'], $this->translate($pin['pin_date_dif']['key'])),
					'text_uploads_date_dif_from' => sprintf($this->translate('Uploaded %d %s ago from'), $pin['pin_date_dif']['value'], $this->translate($pin['pin_date_dif']['key'])),
					'text_board' => $pin['board_title'],
					'text_all_comments' => $pin['pin_comments'] >= (int)Helper_Config::get('config_comments_list') ? sprintf($this->translate('All %d comments...'), $pin['pin_comments']) : false,
					'text_tweet' => $this->translate('Tweet'),
					'text_embed' => $this->translate('Embed'),
					'text_report_pin' => $this->translate('Report Pin'),
					'text_next' => $this->translate('Next'),
					'text_prev' => $this->translate('Previous'),
					'text_follow' => $this->translate('Follow'),
					'text_unfollow' => $this->translate('Unfollow')
			);
			
			Model_Pins::updateViewed($pin['pin_id']);
		}
		
		$extensions = Model_Extensions::getByMethod('pin_view');
		if($extensions) {
			$front = JO_Front::getInstance();
			foreach($extensions AS $id => $ext) {
				$pin_data_ext = call_user_func(array($front->formatModuleName('model_' . $ext . '_pin'), 'preview'), $pin, $return);
				if($pin_data_ext && is_array($pin_data_ext)) {
					$return = array_merge($return, $pin_data_ext);
				}
			}
		}
		
		if($return_data) {
			return $return;
		}
		
		//format response data
		$formatObject = new Helper_Format();
		$formatObject->responseJsonCallback($return);
		
		$this->noViewRenderer(true);
	}
	
	public function getCommentsAction() {
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$pin_id = $request->getRequest('pin_id');
		
		$return = array();
		
		$comments = Model_Comments::getComments2(array(
			'filter_pin_id' => $pin_id
		));
		
		$user_id = JO_Session::get('user[user_id]');
		$is_admin = JO_Session::get('user[is_admin]');
		$is_developer = JO_Session::get('user[is_developer]');
		
		foreach($comments AS $key => $comment) {
		
			$user_avatars = Helper_Uploadimages::userAvatars(array(
					'avatar' => $comment['avatar'],
					'store' => $comment['store'],
					'user_id' => $comment['user_id']
			));
			
			$reported = false;
			if($user_id && !$comment['is_reported']) {
				$reported = WM_Router::pinAction($pin_id, 'reportComment', 'comment_id=' . $comment['comment_id']);
			}
			
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
				$delete = WM_Router::pinAction($pin_id, 'deleteComment', 'comment_id=' . $comment['comment_id']);
			}
			$return[] = array(
					'user' => array(
							'profile' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $comment['user_id'] ),
							'avatars' => $user_avatars,
							'fullname' => $comment['fullname']
					),
					'comment' => $comment['comment'],
					//urls
					'url_delete' => $delete,
					'url_report' => $reported,
					//texts
					'text_delete' => $this->translate('Delete Comment'),
					'text_report' => $this->translate('Report Comment')
			);
		}
		
		//format response data
		$formatObject = new Helper_Format();
		$formatObject->responseJsonCallback($return);
		
		$this->noViewRenderer(true);
	}
	
	public function getOtherDataAction() {
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin_info = new Model_Pins_Pin($pin_id);
		
		$return = array();
		
		$formatObject = new Helper_Format();
		
		if($pin_info->count()) {
			
			$pin_info = $pin_info->data;
			
			$pin_info = array_merge((array)$pin_info, Helper_Pin::formatPinData((array)$pin_info));
			
			$loged = JO_Session::get('user[user_id]');
			
			/* v2.2 */
			$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
			if($config_enable_follow_private_profile) {
				$user_enable_follow = ($pin_info['via_user_id']?$pin_info['via_enable_follow']:$pin_info['user_enable_follow']);
			} else {
				$user_enable_follow = true;
			}
			/* v2.2 */
			
			$return = array(
				'loged' => $loged,
				/* v2.2 mod */
				'enable_follow_board' => ($loged ? $pin_info['board_user_id'] != $loged && $user_enable_follow : false),
				'enable_follow_user' => ($loged ? $pin_info['user_user_id'] != $loged && $user_enable_follow : false),
				'enable_follow_via' => ($loged ? ($pin_info['via_user_id']?$pin_info['via_user_id']:$pin_info['user_user_id']) != $loged && $user_enable_follow : false),
				/* v2.2 mod */
				'board_is_follow' => $pin_info['following_board'],
				'via_is_follow' => ($pin_info['via_user_id']?$pin_info['following_via']:$pin_info['following_user']),
				'pin_likes' => $pin_info['pin_likes'],
				'pin_likes_total' => ($pin_info['pin_likes'] > 20 ? ($pin_info['pin_likes'] - 20) : 0),
				//urls
				'user_follow_href' => $pin_info['user_follow_href'],
				'board_follow_href' => $pin_info['board_follow_href'],
				'via_follow_href' => $pin_info['via_follow_href'],
				//texts
				'text_unfollow' => $this->translate('Unfollow'),
				'text_follow' => $this->translate('Follow'),
				'text_likes' => $this->translate('Likes'),
				'text_repins' => $this->translate('Repins'),
				'text_onto_board' => $this->translate('Pinned onto the board'),
				'text_onto' => $this->translate('onto'),
				'text_originally_pinned' => $this->translate('Originally pinned by'),
				'text_pined_via' => sprintf($this->translate('Pinned via %s from'), Helper_Config::get('site_name')),
				'pin_likes_total_text' => sprintf($this->translate('+%d more likes'), ($pin_info['pin_likes'] - 20))
			);
			
			//other pins from board
			$return['onto_board'] = false;
			$pins = new Model_Pins_Boards(array(
				'start' => 0,
				'limit' => 12,
				'filter_board_id' => $pin_info['pin_board_id']
			));
			if($pins->count()) {
				
				$board_info = new Model_Boards_Board($pin_info['pin_board_id']);
				if($board_info->count()) {
					$href = WM_Router::create($request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_info['pin_user_id'] . '&board_id=' . $pin_info['pin_board_id']);
					$return['onto_board'] = array(
							'pins' => array(),
							'url' => $href,
							'name' => $pin_info['board_title'],
							'user_id' => $board_info['user_user_id']
					);
					$total = $pins->count();
					foreach($pins->data AS $row => $pin) {
						$pin['pin_id'] = $pin['pin_pin_id'];
						$key = false;
						if($row == 0) {
							$key = 'first';
						} else if($row == ($total-1)) {
							$key = 'last';
						}
						$return['onto_board']['pins'][] = array(
							'images' => Helper_Uploadimages::pinThumbs($pin),
							'href' => $href,
							'key' => $key
						);
					}
				}
			}
			
			// others pin from via or user
			$return['originally_pinned'] = false;
			$pins = new Model_Pins_Users(array(
				'start' => 0,
				'limit' => 6,
				'filter_user_id' => ($pin_info['via_user_id']?$pin_info['via_user_id']:$pin_info['pin_user_id'])
			));
			if($pins->count()) {
				$href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . ($pin_info['via_user_id']?$pin_info['via_user_id']:$pin_info['pin_user_id']));
				$return['originally_pinned'] = array(
						'pins' => array(),
						'url' => $href,
						'name' => ($pin_info['via_user_id']?$pin_info['via_fullname']:$pin_info['user_fullname']),
						'via' => ($pin_info['via_user_id']?true:false),
						'user_id' => ($pin_info['via_user_id']?$pin_info['via_user_id']:$pin_info['pin_user_id'])
				);
				$total = $pins->count();
				foreach($pins->data AS $row => $pin) {
					$pin['pin_id'] = $pin['pin_pin_id'];
					$key = false;
					if($row == 0) {
						$key = 'first';
					} else if($row == ($total-1)) {
						$key = 'last';
					}
					$return['originally_pinned']['pins'][] = array(
							'images' => Helper_Uploadimages::pinThumbs($pin),
							'href' => $href,
							'key' => $key
					);
				}
			}
			
			// others pin from source
			$return['source'] = false;
			
			if($pin_info['pin_source_id']) {
				$pins = new Model_Pins_Source(array(
					'start' => 0,
					'limit' => 5,
					'filter_source_id' => $pin_info['pin_source_id']
				));
				if($pins->count()) {
					$href = WM_Router::create($request->getBaseUrl() . '?controller=source&source_id=' . ($pin_info['pin_source_id']));
					
					$return['source'] = array(
							'pins' => array(),
							'url' => $href,
							'name' => $pin_info['source_source']
					);
					$total = $pins->count();
					foreach($pins->data AS $row => $pin) {
						$pin['pin_id'] = $pin['pin_pin_id'];
						$key = false;
						if($row == 0) {
							$key = 'first';
						} else if($row == ($total-1)) {
							$key = 'last';
						}
						$return['source']['pins'][] = array(
								'images' => Helper_Uploadimages::pinThumbs($pin),
								'href' => $href,
								'key' => $key
						);
					}
				}
			}
			
			// others pin likes
			$return['users_likes'] = false;
			if($pin_info['pin_likes']) {
				$users = new Model_Users_LikesPin(array(
					'start' => 0,
					'limit' => 20,
					'filter_like_pin_id' => $pin_info['pin_id']
				));
				if($users->count()) {
					$total = $users->count();
					foreach($users AS $row => $user) {
						$user['pin_id'] = $pin['pin_pin_id'];
						$key = false;
						if($row == 0) {
							$key = 'first';
						} else if($row == ($total-1)) {
							$key = 'last';
						}
						$return['users_likes'][] = array(
								'avatars' => Helper_Uploadimages::userAvatars($user),
								'href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id'] ),
								'fullname' => $user['fullname'],
								'key' => $key
						);
					}
				}
			}
			
			// others pin from source
			$return['users_repins'] = false;
			if($pin_info['pin_repins']) {
				$pins = new Model_Pins_Repins(array(
					'start' => 0,
					'limit' => 6,
					'filter_like_pin_id' => $pin_info['pin_id']
				));
				if($pins->count()) {
					$total = $pins->count();
					foreach($pins->data AS $row => $pin) {
						$pin['pin_id'] = $pin['pin_pin_id'];
						$key = false;
						if($row == 0) {
							$key = 'first';
						} else if($row == ($total-1)) {
							$key = 'last';
						}
						$return['users_repins'][$pin['user_user_id']] = array(
								'user' => array(
									'fullname' => $pin['user_fullname'],
									'avatars' => Helper_Uploadimages::userAvatars(array(
										'avatar' => $pin['user_avatar'],
										'store' => $pin['user_store'],
										'user_id' => $pin['user_user_id']
									)),
									'href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_user_id'] )
								),
								'board' => array(
									'name' => $pin['board_title'],
									'href' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin['user_user_id'] . '&board_id=' . $pin['board_board_id'] )
								),
								'key' => $key
						);
					}
				}
			}
			
			
		
		}
		
		//format response data
		$formatObject = new Helper_Format();
		$formatObject->responseJsonCallback($return);
		
		$this->noViewRenderer(true);
	}
	
	public function v2addCommentAction() {
		
		$request = $this->getRequest();
		
		$response = array();
		
		if(JO_Session::get('user[user_id]')) {
			
			$key_com = md5(serialize($request->getPost())) . '_' . date('i');
			if(JO_Session::get('post_comments_data') == $key_com) {
				exit;
			}
			
			$pin_info = new Model_Pins_Pin($request->getRequest('pin_id'));
			if($pin_info->count()) {
				
				$pin_info = $pin_info->data;
				
				$data = array(
					'pin_id' => $pin_info['pin_pin_id'],
					'user_id' => JO_Session::get('user[user_id]'),
					'comment' => $request->getPost('write_comment'),
					'date_added' => WM_Date::format(null, 'yy-mm-dd H:i:s')
				);
				
				if($request->issetPost('friends') && is_array($request->getPost('friends'))) {
					foreach($request->getPost('friends') AS $user_id => $fullname) { 
						if( Model_Users::isFriendUser($user_id, JO_Session::get('user[user_id]')) ) {
							$profile = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_id );
							$data['comment'] = preg_replace('/'.$fullname.'/i',' <a class="link comment-user-profile" href="'.$profile.'">'.$fullname.'</a> ',$data['comment']);
						}
					}
				}
				
				$result = new Model_Pins_AddComment($data);
				
				if($result->comment_id) {
					
					JO_Session::set('post_comments_data',$key_com);
					
					$pin_info = new Model_Pins_Pin($request->getRequest('pin_id'));
					
					$pin_info = $pin_info->data;
					
					new Model_History_AddHistory(
							$pin_info['user_user_id'], 
							Model_History_Abstract::COMMENTPIN, 
							$pin_info['pin_pin_id'],
							0,
							$request->getPost('write_comment'));
					
					
					$response = array(
						'ok' => true,
						'user' => array(
									'profile' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]')),
									'avatars' => Helper_Uploadimages::userAvatars(JO_Session::get('user')),
									'fullname' => JO_Session::get('user[fullname]')
								),
						'total_comments' => $pin_info ? $pin_info['pin_comments'] : 0,
						'comment' => $data['comment'],
						'delete_comment' => WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=deleteComment&comment_id=' . $result->comment_id ),
						'url_delete' => WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=deleteComment&comment_id=' . $result->comment_id ),
						'text_delete_comment' => $this->translate('Delete Comment'),
						'stats' => $this->getPinStat($request->getRequest('pin_id')),
						'url_report' => false,
						//texts
						'text_delete' => $this->translate('Delete Comment'),
						'text_report' => $this->translate('Report Comment')
					);
					
					//send email for comment pin
					if($pin_info['user_user_id'] != JO_Session::get('user[user_id]')) {
						if($pin_info && $pin_info['user_email_interval'] == 1 && $pin_info['user_comments_email']) {
							
							$template = Model_Notification::getTemplate('comment_pin');
							if($template) {
								$template_data = array(
									'user_id' => $pin_info['user_user_id'],
									'user_firstname' => $pin_info['user_firstname'],
									'user_lastname' => $pin_info['user_lastname'],
									'user_fullname' => $pin_info['user_fullname'],
									'user_username' => $pin_info['user_username'],
									'author_url' => $response['user']['profile'],
									'author_fullname' => $response['user']['fullname'],
									'pin_url' => WM_Router::pinAction($pin_info['pin_pin_id']),
									'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
								);
								
								if(!$template['title']) {
									$template['title'] = '${author_fullname} ' . $this->translate('comment your pin');
								}
								
								$template['title'] = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
								$template['template'] = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
								
								Model_Email::send(
										$pin_info['user_email'],
										Helper_Config::get('noreply_mail'),
										$template['title'],
										$template['template']
								);
							}
						}
					}
					
				} else {
					$response['error'] = $this->translate('There was a problem with the record. Please try again!');
				}
				
			} else {
				$response['error'] = $this->translate('There was a problem with the record. Please try again!');
			}
		} else {
			$response['location'] = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		
		$this->noViewRenderer(true);
		echo JO_Json::encode($response);
	}
	
	public function deleteCommentAction() {
		
		$request = $this->getRequest();
		$comment_id = $request->getRequest('comment_id');
		$comment_info = new Model_Pins_GetComment($comment_id);
		
		$response = array();
		
		if($comment_info->count()) {
		
			$comment_info = $comment_info->data;
			$enable_delete = false;
			if( JO_Session::get('user[is_admin]') ) {
				$enable_delete = true;
			} elseif( JO_Session::get('user[is_developer]') ) {
				$enable_delete = true;
			} elseif( JO_Session::get('user[user_id]') == $comment_info['user_id'] ) {
				$enable_delete = true;
			} else {
				$pin_info = new Model_Pins_Pin($comment_info['pin_id']);
				if($pin_info->count()) {
					$pin_info = $pin_info->data;
					if($pin_info['user_user_id'] == JO_Session::get('user[user_id]')) {
						$enable_delete = true;
					}
				}
			}
			
			if($enable_delete) {
				$deleted = new Model_Pins_DeleteComment($comment_id);
				if($deleted->affected_rows) {
					$response = array(
							'ok' => true,
							'total_comments' => (int)$pin_info['pin_comments'],
							'stats' => $this->getPinStat($comment_info['pin_id'])
					);
				} else {
					$response['error'] = $this->translate('There was a problem with the record. Please try again!');
				}
			} else {
				$response['error'] = $this->translate('You do not have permission to delete this comment!');
			}
			
		} else {
			$response['error'] = $this->translate('There was a problem with the record. Please try again!');
		}
		
		$this->noViewRenderer(true);
		echo JO_Json::encode($response);

	}
	
	public function createpinAction() {
		
		$request = $this->getRequest();
		if( JO_Session::get('user[user_id]') ) {
			if( $request->isPost() ) {
	
				$validate = new Helper_Validate();
				$validate->_set_rules($request->getPost('description'), $this->translate('Description'), 'not_empty;min_length[1];max_length[500]');
				$validate->_set_rules($request->getPost('board_id'), $this->translate('Board'), 'not_empty;');
					
				if($validate->_valid_form()) {
					//if validate post
					$result = new Model_Pins_Create($request->getPost());
					
					if($result->count()) {
						$result = $result->data;
						$this->view->pin_url = WM_Router::pinAction( $result['pin_id'] );
						
						if(JO_Session::get('user[first_login]')) {
							$this->view->callChildren('index/sendWelcome');
						}
						
						///add history
						new Model_History_AddHistory(JO_Session::get('user[user_id]'), Model_History_Abstract::ADDPIN, $result['pin_id']);
						
						//send notification
						if($request->getPost('repin_from')) {
							$pin_info = new Model_Pins_Pin($request->getPost('repin_from'));
							if($pin_info->count()) {
								$pin_info = $pin_info->data;
								$template = Model_Notification::getTemplate('repin_pin');
								if($template) {
									$template_data = array(
											'user_id' => $pin_info['user_user_id'],
											'user_firstname' => $pin_info['user_firstname'],
											'user_lastname' => $pin_info['user_lastname'],
											'user_fullname' => $pin_info['user_fullname'],
											'user_username' => $pin_info['user_username'],
											'author_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') ),
											'author_fullname' => JO_Session::get('user[fullname]'),
											'pin_url' => WM_Router::pinAction($pin_info['pin_pin_id']),
											'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
									);
										
									if(!$template['title']) {
										$template['title'] = '${author_fullname} ' . $this->translate('repin your PIN');
									}
										
									$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
									$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
									
									Model_Email::send(
											$pin_info['user_email'],
											Helper_Config::get('noreply_mail'),
											$title,
											$body
									);
								}
								
								$this->view->repin_from = $pin_info['pin_pin_id'];
								$this->view->stats = $this->getPinStat($pin_info['pin_pin_id']);
								
							}
							
						}
						$users = new Model_Users_GroupBoardUsers($request->getPost('board_id'));
						if($users->count()) {
							
							$template = Model_Notification::getTemplate('group_board');
							if($template) {
								$pin_info = new Model_Pins_Pin($result['pin_id']);
								if($pin_info->count()) {
									$pin_info = $pin_info->data;
									$mail_footer = html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8');
									foreach($users AS $user) {
										if($user['email_interval'] == 1 && $user['groups_pin_email']) {
											
											$template_data = array(
													'user_id' => $user['user_id'],
													'user_firstname' => $user['firstname'],
													'user_lastname' => $user['lastname'],
													'user_fullname' => $user['fullname'],
													'user_username' => $user['username'],
													'author_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin_info['user_user_id'] ),
													'author_fullname' => $pin_info['user_fullname'],
													'board_url' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_info['user_user_id'] . '&board_id=' . $pin_info['board_board_id'] ),
													'board_name' => $pin_info['board_title'],
													'pin_url' => WM_Router::pinAction($pin_info['pin_pin_id']),
													'mail_footer' => $mail_footer
											);
							
											if(!$template['title']) {
												$template['title'] = '${author_fullname} ' . $this->translate('added new pin to a group board');
											}
											
											$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
											$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
												
											Model_Email::send(
													$user['email'],
													Helper_Config::get('noreply_mail'),
													$title,
													$body
											);
											
										}
									}
								}
							}
						}
					} else {
						if($result->error) {
							$this->view->error = $result->error;
						} else {
							$this->view->error = $this->translate('There was a problem with the record. Please try again!');
						}
					}
				} else {
					$this->view->error = $validate->_get_error_messages();
				}
			}
		} else {
			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
			$this->view->error = $this->translate('You must login or register to pinit!');;
		}
		
		echo $this->renderScript('json');
		
	}
	
	public function repinAction(){
		
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin_info = new Model_Pins_Pin($pin_id);
		
		
		if(!$pin_info->count()) {
			$this->forward('error', 'error404');
		}
		
		$pin_info = $pin_info->data;
		
		$model_images = new Helper_Images();
		
		$this->view->title = $pin_info['pin_title'];
		$this->view->price = $pin_info['pin_price'];
		$pin_info['pin_id'] = $pin_info['pin_pin_id'];
		
		$this->view->images = Helper_Uploadimages::pinThumbs($pin_info);
		
		$this->view->pin_gallery = array();
		$gallery = new Model_Pins_Gallery($pin_info['pin_pin_id']);
		if($gallery->count()) {
			$this->view->pin_gallery[] = $this->view->images;
			foreach($gallery->data AS $gal) {
				$this->view->pin_gallery[] = Helper_Uploadimages::pinThumbs(array(
						'pin_pin_id' => $gal['pin_id'],
						'pin_store'	=> $gal['store'],
						'gallery_id' => $gal['gallery_id'],
						'pin_image' => $gal['image']
				));
			}
		}
		
		$this->view->is_video = $pin_info['pin_is_video'] ? 'true' : 'false';
// 		$this->view->from = $pin_info['pin_from'];
		$this->view->from = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_info['board_user_id'] . '&board_id=' . $pin_info['board_board_id'] );
		$this->view->from_repin = $pin_info['pin_from'];
		$this->view->description = $pin_info['pin_description'];
		$this->view->via = $pin_info['pin_user_id'];
		$this->view->pin_id = $pin_info['pin_pin_id'];
		if($pin_info['pin_gift']) {
			$this->view->formated_price = WM_Currency::format($pin_info['pin_price']);
		}
		
		//$this->view->from_url = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=repin&pin_id=' . $pin_id );
		$this->view->from_url = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=createpin' );
		$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory' );
		
		$boards = new Model_Boards_BoardsWithShared(array(
			'filter_user_id' => JO_Session::get('user[user_id]')		
		));
		$this->view->boards = array();
		if($boards->count()) {
			foreach($boards AS $board) {
				$this->view->boards[] = array(
						'board_id' => $board['board_board_id'],
						'title' => $board['board_title']
				);
			}
		}
				
		///////////////// Extension on create //////////////////
		$this->view->form_extensions = array();
		$extensions = Model_Extensions::getByMethod('pin_onrepinform');
		if($extensions) {
			$front = JO_Front::getInstance();
			foreach($extensions AS $id => $ext) {
				$this->view->form_extensions[] = array(
						'html' => $this->view->callChildren('modules_' . $ext . '_onrepinform'),
						'view' => $this->view->callChildrenView('modules_' . $ext . '_onrepinform'),
						'key' => $ext
				);
			}
		}
		
		
		$this->noLayout(true);
		
	}
	
	public function likeAction() {
		
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin = new Model_Pins_Pin($pin_id);
		
		if(!$pin->count()) {
			$this->view->error = $this->translate('There was a problem with the record. Please try again!');
		} else {
			$pin = $pin->data;
			if(!(int)JO_Session::get('user[user_id]')) {
				$this->view->location = WM_Router::create( $request->getBaseUrl() . '?controller=landing' );
			} else {
				
				$likes = new Model_Pins_IsLiked($pin_id);
				if($likes->total) {
					if($likes->unlike()) {
						$this->view->ok = true;
						$this->view->stats = $this->getPinStat($pin_id);
						$this->view->text = $this->translate('Like');
						$this->view->disabled = false;
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
				} else {
					if($likes->like()) {
						$this->view->ok = true;
						$this->view->stats = $this->getPinStat($pin_id);
						$this->view->text = $this->translate('Unlike');
						$this->view->disabled = true;
						
						$template = Model_Notification::getTemplate('like_pin');
						if($template && $likes->pin && $likes->pin) {
							
							if($likes->pin['user_user_id'] != JO_Session::get('user[user_id]') && $likes->pin['user_email_interval'] && $likes->pin['user_repins_email']) {
								
								$template_data = array(
										'user_id' => $likes->pin['user_user_id'],
										'user_firstname' => $likes->pin['user_firstname'],
										'user_lastname' => $likes->pin['user_lastname'],
										'user_fullname' => $likes->pin['user_fullname'],
										'user_username' => $likes->pin['user_username'],
										'author_url' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . JO_Session::get('user[user_id]') ),
										'author_fullname' => JO_Session::get('user[fullname]'),
										'pin_url' => WM_Router::pinAction($likes->pin['pin_pin_id']),
										'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
								);
							
								if(!$template['title']) {
									$template['title'] = '${author_fullname} ' . $this->translate('like your PIN');
								}
								
								$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
								$template = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
									
								Model_Email::send(
										$likes->pin['user_email'],
										Helper_Config::get('noreply_mail'),
										$title,
										$template
								);
								
							}
						}
						
					} else {
						$this->view->error = $this->translate('There was a problem with the record. Please try again!');
					}
				}
			}
		}
		
		echo $this->renderScript('json');
		
	}
	
	public function embedAction() {
		
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin = new Model_Pins_Pin($pin_id);
		
		if(!$pin->count()) {
			$this->forward('error', 'error404');
		}
		
		$pin = $pin->data;
		
		$pin = array_merge((array)$pin, Helper_Pin::formatPinData((array)$pin, true));
		
		$max_width = $max_height = 0;
		$image_original = $image_thumb = $size_pref = '';
		if(isset($pin['pin_thumbs']) && is_array($pin['pin_thumbs'])) {
			foreach($pin['pin_thumbs'] AS $key => $data) {
				if(strpos($key, 'thumb_width_')!==false && $data) {
					$max_width = max($max_width, $data);
					if($data > 150 && !$size_pref) {
						$size_pref = str_replace('thumb_width_', '', $key);
					}
				}
				if(strpos($key, 'thumb_height_')!==false && $data) {
					$max_height = max($max_height, $data);
				}
				if(strpos($key, 'thumb_original_')!==false && $data) {
					$image_original = $data;
				}
				if(strpos($key, 'thumb_image_')!==false && $data) {
					$image_thumb = $data;
				}
			}
		}
		
		if(!$size_pref) {
			$this->forward('error', 'error404');
		}
		
		$pin['thumb'] = $image_thumb;
		$pin['thumb_width'] = $pin['pin_thumbs']['thumb_width_' . $size_pref];
		$pin['thumb_height'] = $pin['pin_thumbs']['thumb_height_' . $size_pref];
		$pin['thumb_width_max'] = $max_width;
		$pin['thumb_height_max'] = $max_height;
		$pin['original'] = $image_original;
		
		
		$this->view->pin = $pin;
		
		$this->noLayout(true);

	}
	
	public function emailAction() {
		
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin_info = new Model_Pins_Pin($pin_id);
		
		if(!$pin_info->count()) {
			$this->forward('error', 'error404');
		}
		
		$pin_info = $pin_info->data;
		
		$this->view->pin_id = $pin_id;
	
		$this->view->pin_href = WM_Router::create( $request->getBaseUrl() . '?controller=pin&pin_id=' . $pin_id );
		$this->view->url_form = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=email&pin_id=' . $pin_id );
		
		
		if($request->issetPost('name')) {
			$this->view->Recipient_name = $request->getPost('name');
		} else {
			$this->view->Recipient_name = '';
		}
		if($request->issetPost('email')) {
			$this->view->Recipient_email = $request->getPost('email');
		} else {
			$this->view->Recipient_email = '';
		}
		if($request->issetPost('message')) {
			$this->view->Recipient_message = $request->getPost('message');
		} else {
			$this->view->Recipient_message = '';
		}
		
		$this->view->pins_details = $this->view->render('email','pin');
		
		
		$this->view->error = '';
		if($request->isPost()) {
			
			$validate = new Helper_Validate(); 
			$validate->_set_rules($request->getPost('name'), $this->translate('Recipient Name'), 'not_empty;min_length[3];max_length[100]');
			$validate->_set_rules($request->getPost('email'), $this->translate('Recipient Email'), 'not_empty;min_length[5];max_length[100];email');
//			$validate->_set_rules($request->getPost('message'), $this->translate('Message'), 'not_empty;min_length[15]');
			
			if($validate->_valid_form()) {
			
				$this->view->is_posted = true;
				
				$shared_content = new Model_Users_Invate($request->getPost('email'));
				
				$shared_content_url = $request->getBaseUrl();
				if(!$shared_content->is_user) {
					if($shared_content->key) {
						$shared_content_url = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=register&user_id=' . JO_Session::get('user[user_id]') . '&key=' . $shared_content->key);
					}
				}
				
				$template = Model_Notification::getTemplate('send_pin');
				if($template) {
					
					$template_data = array(
							'user_id' => JO_Session::get('user[user_id]'),
							'user_firstname' => JO_Session::get('user[firstname]'),
							'user_lastname' => JO_Session::get('user[lastname]'),
							'user_fullname' => JO_Session::get('user[fullname]'),
							'user_username' => JO_Session::get('user[username]'),
							'recipient_name' => $request->getPost('name'),
							'site_url' => $request->getBaseUrl(),
							'site_name' => Helper_Config::get('site_name'),
							'invate_url' => $shared_content_url,
							'pin_url' => WM_Router::pinAction($pin_id),
							'user_message' => nl2br($request->getPost('message')),
							'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
					);
												
					if(!$template['title']) {
						$template['title'] = $this->translate('Shared content from') . ' ${user_firstname}';
					}
						
					$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
					$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
					
					$result = Model_Email::send(
							$request->getPost('email'),
							Helper_Config::get('noreply_mail'),
							$title,
							$body
					);
					
					if($result) {
						$this->setViewChange('message_email');
					} else {
						$this->view->error = $this->translate('There was an error. Please try again later!');
					}
					
				} else {
					$this->view->error = $this->translate('There was an error. Please try again later!');
				}
			
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
			
		}
		
		if($this->view->error) {
			$this->setViewChange('email');
		}
		
		$this->noLayout(true);
		
	}
	
	public function reportAction() {
	
		$request = $this->getRequest();
	
		$pin_id = $request->getRequest('pin_id');
	
		$pin_info = new Model_Pins_Pin($pin_id);
	
		if(!$pin_info->count()) {
			$this->forward('error', 'error404');
		}
		
		$pin_info = $pin_info->data;
	
	
		$this->view->url_form = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=report&pin_id=' . $pin_id );
		$this->view->intellectual_property = WM_Router::create( $request->getBaseUrl() . '?controller=about&action=copyright&pin_id=' . $pin_id );
		$this->view->pin_id = $pin_id;
	
		$this->view->pin_href = WM_Router::create( $request->getBaseUrl() . '?controller=pin&pin_id=' . $pin_id );
	
		$reportcategories = new Model_Pins_PinReportCategories();
		$this->view->reportcategories = $reportcategories->toArray();
		
		if($request->issetPost('report_category')) {
			$this->view->report_category = $request->getPost('report_category');
		} else {
			if($this->view->reportcategories) {
				list($firstKey) = array_keys($this->view->reportcategories);
				$this->view->report_category = $firstKey;
			} else {
				$this->view->report_category = 0;
			}
		}
		
		$this->view->report_message = $request->getPost('report_message');
	
		if($request->isPost()) {
			$this->view->is_posted = true;
				
			if(Model_Pins::pinIsReported($request->getRequest('pin_id'))) {
				$this->view->error = $this->translate('You are already reported this pin!');
			} else {
					
				$result = Model_Pins::reportPin( $request->getRequest('pin_id'), $request->getPost('report_category'), $request->getPost('report_message') );
				if(!$result) {
					$this->view->error = $this->translate('Error reporting experience. Try again!');
				} else {
					if(Helper_Config::get('not_rp')) {
						Model_Email::send(
								Helper_Config::get('report_mail'),
								Helper_Config::get('noreply_mail'),
								$this->translate('New reported pin'),
								$this->translate('Hello, there is new reported pin in ').' '.Helper_Config::get('site_name')
						);
					}
					$terms = Model_Pages::getPage( Helper_Config::get('page_terms') );
					if($terms) {
						$this->view->terms = $terms['title'];
					}
						
					$this->view->pin_oppener = $request->getRequest('pin_oppener');
					$this->view->terms_href = WM_Router::create( $request->getBaseUrl() . '?controller=about&action=terms' );

					$this->setViewChange('message_report');
				}
					
			}
		}
	
		$this->noLayout(true);

	}
	
	public function reportCommentAction(){
		$request = $this->getRequest();
		$comment_id = $request->getRequest('comment_id');
		$comment_info = new Model_Pins_GetComment($comment_id);
		
		if(!$comment_info->count()) {
			$this->forward('error', 'error404');
		}
		
		$comment_info = $comment_info->data;
		
		$reportcategories = new Model_Pins_CommentReportCategories();
		$this->view->reportcategories = $reportcategories->toArray();
		
		$this->view->url_form = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=reportComment&comment_id=' . $comment_id );
		$this->view->comment_id = $comment_id;
	
		$this->view->pin_href = WM_Router::create( $request->getBaseUrl() . '?controller=pin&pin_id=' . $comment_info['pin_id'] );
		
		if($request->issetPost('report_category')) {
			$this->view->report_category = $request->getPost('report_category');
		} else {
			if($this->view->reportcategories) {
				list($firstKey) = array_keys($this->view->reportcategories);
				$this->view->report_category = $firstKey;
			} else {
				$this->view->report_category = 0;
			}
		}
		
		$this->view->comment_is = true;
		
		$this->view->pin_id = $comment_info['pin_id'];
		
		$this->setViewChange('report');
		
		if($request->isPost()) {
			$this->view->is_posted = true;
			
			if(Model_Pins::commentIsReported($comment_id)) {
				$this->view->error = $this->translate('You are already reported this comment!');
			} else {
			
				$result = Model_Pins::reportComment( $comment_id, $request->getPost('report_category'), $request->getPost('report_message') );
				if(!$result) {
					$this->view->error = $this->translate('Error reporting experience. Try again!');
				} else {
    				if(Helper_Config::get('not_rc')) {
    		    			Model_Email::send(
    				    	  	Helper_Config::get('report_mail'),
    				    	 	Helper_Config::get('noreply_mail'),
    				    	   	$this->translate('New reported comment'),
    				    	  	$this->translate('Hello, there is new reported comment in ').' '.Helper_Config::get('site_name')
    				    	 );
		    			}
					$terms = Model_Pages::getPage( Helper_Config::get('page_terms') );
					if($terms) {
						$this->view->terms = $terms['title'];
					}
					
					$this->view->terms_href = WM_Router::create( $request->getBaseUrl() . '?controller=about&action=terms' );
					
					$this->setViewChange('message_report');
				}
			
			}
		}
		
		$this->noLayout(true);
	}
	
	
	
	private function getPinStat($pin_id) {
		$pin_info = new Model_Pins_Pin($pin_id);
		$pin_info_count = $pin_info->count();
		$pin_info= $pin_info->data;
		$stats = array();
		if($pin_info_count && $pin_info['pin_likes']) {
			$stats['likes'] = sprintf( $this->translate('%d like' . ($pin_info['pin_likes'] == 1 ? '' : 's')), $pin_info['pin_likes'] );
		} else {
			$stats['likes'] = sprintf( $this->translate('%d likes'), 0 );
		}
		if($pin_info_count && $pin_info['pin_repins']) {
			$stats['repins'] = sprintf( $this->translate('%d repin' . ($pin_info['pin_repins'] == 1 ? '' : 's')), $pin_info['pin_repins'] );
		} else {
			$stats['repins'] = sprintf( $this->translate('%d repins'), 0 );
		}
		if($pin_info_count && $pin_info['pin_comments']) {
			$stats['comments'] = sprintf( $this->translate('%d comment' . ($pin_info['pin_comments'] == 1 ? '' : 's')), $pin_info['pin_comments'] );
			$stats['all_comments'] = sprintf($this->translate('All %d comments...'), $pin_info['pin_comments']);
			$stats['all_comments_href'] = WM_Router::pinAction( $pin_info['pin_pin_id'] );
		} else {
			$stats['comments'] = sprintf( $this->translate('%d comments'), 0 );
		}
		return $stats;
	}
	
	public function deleteAction(){
		
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin_info = new Model_Pins_Pin($pin_id);
		
		if(!$pin_info->count()) {
			$this->forward('error','error404');
		}
		
		$pin_info = $pin_info->data;
		
		if($pin_info['user_user_id'] != JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::pinAction( $pin_info['pin_pin_id'] ) );
		} else {
			$delete = new Model_Pins_Delete($pin_id);
			if($delete->affected_rows) {
				$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_info['pin_user_id'].'&board_id=' . $pin_info['pin_board_id']) );
			} else {
				$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=pin&action=edit&pin_id=' . $pin_info['pin_pin_id']) );
			}
		}
	}
	
	public function editAction(){
//		var_dump( htmlspecialchars('⚐') );exit;
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
// 		$pin_info = Model_Pins::getPin($pin_id);
		$pin_info = new Model_Pins_Pin($pin_id);
		
		if(!$pin_info->count() || $pin_info->data['pin_user_id'] != JO_Session::get('user[user_id]')) {
			$this->forward('error', 'error404');
		}
		
		$pin_info = $pin_info->data;
		
		if( $request->isPost() ) {
			
			$validate = new Helper_Validate();
			if($pin_info['pin_from']) {
				$validate->_set_rules($request->getPost('from'), $this->translate('Link'), 'not_empty;min_length[3];domain');
			} else if(trim($request->getPost('from'))) {
				$validate->_set_rules($request->getPost('from'), $this->translate('Link'), 'not_empty;min_length[3];domain');
			}
			
			$data = $request->getPost();
			
			if($validate->_valid_form()) {
				
				$edit = new Model_Pins_Edit($pin_id, $request->getPost());
				
				$this->redirect( WM_Router::pinAction( $pin_info['pin_pin_id'] ) );
				
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
			
			foreach($data AS $k=>$v) {
				if(isset($pin_info['pin_' . $k])) {
					$pin_info['pin_' . $k] = $v;
				}
			}
			
		}
		
		$pin_info['images'] = Helper_Uploadimages::pinThumbs($pin_info);
		
		if($pin_info['pin_gift']) {
			$pin_info['price_formated'] = WM_Currency::format($pin_info['pin_price']);	
		} else {
			$pin_info['price_formated'] = '';
			$pin_info['pin_price'] = 0;
		}
		
		$pin_info['href'] = WM_Router::pinAction( $pin_info['pin_pin_id'] );
		
		$this->view->pin_info = $pin_info;
		
		$view->get_user_friends = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=friends' );
		
		$boards = new Model_Boards_BoardsWithShared(array(
			'filter_user_id' => JO_Session::get('user[user_id]')		
		));
		$this->view->boards = array();
		if($boards->count()) {
			foreach($boards AS $board) {
				$this->view->boards[] = array(
						'board_id' => $board['board_board_id'],
						'title' => $board['board_title']
				);
			}
		}
				
		///////////////// Extension on edit //////////////////
		$this->view->form_extensions = array();
		$extensions = Model_Extensions::getByMethod('pin_oneditform');
		if($extensions) {
			$front = JO_Front::getInstance();
			foreach($extensions AS $id => $ext) {
				$this->view->form_extensions[] = array(
						'html' => $this->view->callChildren('modules_' . $ext . '_oneditform'),
						'view' => $this->view->callChildrenView('modules_' . $ext . '_oneditform'),
						'key' => $ext
				);
			}
		}
		
		$this->view->pin_delete = WM_Router::pinAction($pin_id, 'delete');
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
	}
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	public function left_partAction(){
		/*$request = $this->getRequest();
		
		$pin_id = $request->getRequest('pin_id');
		
		$pin_info = new Model_Pins_Pin($pin_id);
		
		//other pins from board
		$return['onto_board'] = false;
		$pins = new Model_Pins_Boards(array(
				'start' => 0,
				'limit' => 12,
				'filter_board_id' => $pin_info['pin_board_id']
		));
		if($pins->count()) {
			$href = WM_Router::create($request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_info['pin_user_id'] . '&board_id=' . $pin_info['pin_board_id']);
			$return['onto_board'] = array(
					'pins' => array(),
					'url' => $href,
					'name' => $pin_info['board_title']
			);
			$total = $pins->count();
			foreach($pins AS $row => $pin) {
				$pin['pin_id'] = $pin['pin_pin_id'];
				$key = false;
				if($row == 0) {
					$key = 'first';
				} else if($row == ($total-1)) {
					$key = 'last';
				}
				$return['onto_board']['pins'][] = array(
						'images' => Helper_Uploadimages::pinThumbs($pin),
						'href' => $href,
						'key' => $key
				);
			}
		}
		
		
		// others pin from source
		$return['source'] = false;
		if($pin_info['pin_source_id']) {
			$pins = new Model_Pins_Source(array(
					'start' => 0,
					'limit' => 5,
					'filter_source_id' => $pin_info['pin_source_id']
			));
			if($pins->count()) {
				$href = WM_Router::create($request->getBaseUrl() . '?controller=source&source_id=' . ($pin_info['pin_source_id']));
					
				$return['source'] = array(
						'pins' => array(),
						'url' => $href,
						'name' => $pin_info['source_source']
				);
				$total = $pins->count();
				foreach($pins AS $row => $pin) {
					$pin['pin_id'] = $pin['pin_pin_id'];
					$key = false;
					if($row == 0) {
						$key = 'first';
					} else if($row == ($total-1)) {
						$key = 'last';
					}
					$return['source']['pins'][] = array(
							'images' => Helper_Uploadimages::pinThumbs($pin),
							'href' => $href,
							'key' => $key
					);
				}
			}
		}
		
		// others pin repins
		$return['users_repins'] = false;
		if(!$return['source']) {
			
			if($pin_info['pin_repins']) {
				$pins = new Model_Pins_Repins(array(
						'start' => 0,
						'limit' => 6,
						'filter_like_pin_id' => $pin_info['pin_id']
				));
				if($pins->count()) {
					$total = $pins->count();
					foreach($pins AS $row => $pin) {
						$user['pin_id'] = $pin['pin_pin_id'];
						$key = false;
						if($row == 0) {
							$key = 'first';
						} else if($row == ($total-1)) {
							$key = 'last';
						}
						$return['users_repins'][] = array(
								'user' => array(
										'fullname' => $pin['user_fullname'],
										'avatars' => Helper_Uploadimages::userAvatars(array(
												'avatar' => $pin['user_avatar'],
												'store' => $pin['userstore'],
												'user_id' => $pin['user_user_id']
										)),
										'href' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_user_id'] )
								),
								'board' => array(
										'name' => $pin['board_title'],
										'href' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin['user_user_id'] . '&board_id=' . $pin['board_board_id'] )
								),
								'key' => $key
						);
					}
				}
			}
		}
		
		//var_dump(var_export($return['onto_board'],true)); exit;
		
		
		
		
		
		
		
		/*$this->view->source = Model_Source::getSource(Helper_Config::getArray('pin_info[source_id]'));
		
		if($this->view->source) {
			$this->view->source_pins = Helper_Pin::getSourcePins(Helper_Config::getArray('pin_info[source_id]'), 6, 75);
			$this->view->pin['from'] = WM_Router::create($request->getBaseUrl() . '?controller=source&source_id=' . $this->view->pin['source_id']);
		} else if(Helper_Config::getArray('pin_info[repin_from]')) {
			$pin_repin = Model_Pins::getPin(Helper_Config::getArray('pin_info[repin_from]'));
			if($pin_repin) {
				$this->view->source['source'] = $pin_repin['board'];
				$this->view->pin['from'] = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $pin_repin['user_id'] . '&board_id=' . $pin_repin['board_id'] );
				$this->view->source_pins = Helper_Pin::getBoardPins( $pin_repin['board_id'], 9, 75 );
			}
		}
		
		$this->view->boardIsFollow = Model_Users::isFollow(array(
			'board_id' => Helper_Config::getArray('pin_info[board_id]')
		));
		
		$this->view->follow = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=follow&user_id=' . $this->view->pin['user_id'] . '&board_id=' . $this->view->pin['board_id'] );
		
		$this->view->loged = JO_Session::get('user[user_id]');
		
		$this->view->pin['userFollowIgnore'] = ($this->view->pin['via'] ? $this->view->pin['via'] : $this->view->pin['user_id']) == JO_Session::get('user[user_id]');
		
//		var_dump($this->view->onto_board);
		
		Helper_Config::set('pin_info', array());*/
	}
	
	
	
	
}

?>