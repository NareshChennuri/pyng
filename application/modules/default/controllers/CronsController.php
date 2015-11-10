<?php

class CronsController extends Helper_Controller_Default {
	
	private $now = null;
	
	public function init() {
		$request = $this->getRequest();
		$request->setBaseUrl( trim(Helper_Config::get('config_base_domain'),'/') . '/' );
		$this->noViewRenderer();
		ignore_user_abort(true);
		set_time_limit(0);
		$this->now = time();
	}

	public function generateCacheAction() {
		new Model_Crons_Index();
	}

	public function generatePopularCacheAction() {
		new Model_Crons_Popular();
	}

	public function generateStatAction() {
		Model_Crons_All::stats();
	}

	public function updateStatAction() {
		Model_Crons_All::updateStats();
	}
	
	public function deletePinImagesAction() {
		Model_Crons_All::deletePinImagesFromStorage();
	}
	
	public function sendDailyAction() {
		
		$request = $this->getRequest();
		
		$view = JO_View::resetInstance();
		
		$view->base_href = $request->getBaseUrl();
		$view->site_name = Helper_Config::get('site_name');
		$view->on_facebook = Helper_Config::get('config_on_facebook');
		
		$view->site_logo = $view->base_href . 'data/images/logo.png';
		if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
		    $view->site_logo = $view->base_href . 'uploads' . Helper_Config::get('site_logo'); 
		}

		$view->settings = WM_Router::create( $view->base_href . '?controller=prefs' );
		
		$goodies = Model_Pages::getPage( Helper_Config::get('page_goodies') );
		if($goodies) {
			$view->pin_it = WM_Router::create($view->base_href . '?controller=pages&action=read&page_id=' . $goodies['page_id']);
		}
		
		$view->pages = array();
		$page = Model_Pages::getPage( Helper_Config::get('page_privacy_policy') );
		if($page) {
			$view->pages[] = array(
				'title' => $page['title'],
				'href' => WM_Router::create($view->base_href . '?controller=pages&action=read&page_id=' . $page['page_id'])
			);
		}
		
		$page = Model_Pages::getPage( Helper_Config::get('page_terms') );
		if($page) {
			$view->pages[] = array(
				'title' => $page['title'],
				'href' => WM_Router::create($view->base_href . '?controller=pages&action=read&page_id=' . $page['page_id'])
			);
		}
		
		$histories = new Model_History_Today(array(
			'today' => WM_Date::format($this->now, JO_Date::SQL_FULL)
		));
		
		$no_avatar = Helper_Config::get('no_avatar');
		
		if($histories->count()) {
			$model_images = new Helper_Images();
			foreach($histories AS $history) {
				
				if(strpos($history['email'], '@spider-imports') !== false) {
					continue;
				}
				
				$history['avatars'] = Helper_Uploadimages::userAvatars(array(
					'store' => $history['store'],
					'avatar' => $history['avatar'],
					'user_id' => $history['user_id']
				));
				
				
				$history['user_followers'] = WM_Router::create( $view->base_href . '?controller=users&action=followers&user_id=' . $history['user_id']  );
				
				$history['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $history['user_id'] );
				
				$history['history_comments_total'] = count($history['history_comments']);
				$history['history_follow_total'] = count($history['history_follow']);
				$history['history_like_total'] = count($history['history_like']);
				$history['history_repin_total'] = count($history['history_repin']);
				
				/////comments
				if($history['history_comments_total']) {
					foreach($history['history_comments'] AS $k => $v) {
						$history['history_comments'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
							'store' => $v['store'],
							'avatar' => $v['avatar'],
							'user_id' => $v['user_id']
						));
						$history['history_comments'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				/////follow
				if($history['history_follow_total']) {
					foreach($history['history_follow'] AS $k => $v) {
						$history['history_follow'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
								'store' => $v['store'],
								'avatar' => $v['avatar'],
								'user_id' => $v['user_id']
						));
						$history['history_follow'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				/////like
				if($history['history_like_total']) {
					foreach($history['history_like'] AS $k => $v) {
						$history['history_like'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
								'store' => $v['store'],
								'avatar' => $v['avatar'],
								'user_id' => $v['user_id']
						));
						$history['history_like'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				/////repin
				if($history['history_repin_total']) {
					foreach($history['history_repin'] AS $k => $v) {
						$history['history_repin'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
								'store' => $v['store'],
								'avatar' => $v['avatar'],
								'user_id' => $v['user_id']
						));
						$history['history_repin'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				
				$view->history = $history;
				
				$html = $view->render('sendDaily','crons');
				
				Model_Email::send(
					$history['email'],
					Helper_Config::get('noreply_mail'),
					sprintf($this->translate('Daily %s'), $view->site_name),
					$html,
					false
				);
				
			}
		}
		
		
		
	}
	
	public function sendWeeklyAction() {
		
		$request = $this->getRequest();
		
		$view = JO_View::resetInstance();
		
		$view->base_href = $request->getBaseUrl();
		$view->site_name = Helper_Config::get('site_name');
		$view->on_facebook = Helper_Config::get('config_on_facebook');
		
		$view->site_logo = $view->base_href . 'data/images/logo.png';
		if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
		    $view->site_logo = $view->base_href . 'uploads' . Helper_Config::get('site_logo'); 
		}

		$view->settings = WM_Router::create( $view->base_href . '?controller=prefs' );
		
		$goodies = Model_Pages::getPage( Helper_Config::get('page_goodies') );
		if($goodies) {
			$view->pin_it = WM_Router::create($view->base_href . '?controller=pages&action=read&page_id=' . $goodies['page_id']);
		}
		
		$view->pages = array();
		$page = Model_Pages::getPage( Helper_Config::get('page_privacy_policy') );
		if($page) {
			$view->pages[] = array(
				'title' => $page['title'],
				'href' => WM_Router::create($view->base_href . '?controller=pages&action=read&page_id=' . $page['page_id'])
			);
		}
		
		$page = Model_Pages::getPage( Helper_Config::get('page_terms') );
		if($page) {
			$view->pages[] = array(
				'title' => $page['title'],
				'href' => WM_Router::create($view->base_href . '?controller=pages&action=read&page_id=' . $page['page_id'])
			);
		}
		
		$histories = new Model_History_Today(array(
			'week_range' => WM_Date::x_week_range($this->now)
		));
		
		$no_avatar = Helper_Config::get('no_avatar');
		
		if($histories->count()) {
			$model_images = new Helper_Images();
			
			/* BOARDS */
			$view->popular_bards = array();
			$populars = new Model_Boards_PopularBoards(array(
				'start' => 0,
				'limit' => 6,
				'sort' => 'DESC',
				'order' => 'boards.total_views',
				'where' => new JO_Db_Expr('boards.pins > 4')
			));
			
			if($populars->count()) {
				$formatObject = new Helper_Format();
				foreach($populars AS $board) {
					$view->popular_bards[] = $formatObject->fromatListBoard($board);
				}
			}
			
			/* VIDEO */
//			$view->video = array();
//			$video = Model_Pins::getPins(array(
//				'start' => 0,
//				'limit' => 1,
//				'filter_is_video' => 1
//			));
//			
//			if($video) {
//				foreach($video AS $pin) {
//					$pin['thumb'] = $model_images->resizeWidth($pin['image'], 194);
//					$pin['thumb_width'] = $model_images->getSizes('width');
//					$pin['thumb_height'] = $model_images->getSizes('height');
//					$pin['description'] = Helper_Pin::descriptionFix($pin['description']);
//					$pin['href'] = WM_Router::create( $request->getBaseUrl() . '?controller=pin&pin_id=' . $pin['pin_id'] );
//								
//				}
//			}
			
			
			/* HISTORY */
			foreach($histories AS $history) {
				
				if(strpos($history['email'], '@spider-imports') !== false) {
					continue;
				}
			
				$history['avatars'] = Helper_Uploadimages::userAvatars(array(
					'store' => $history['store'],
					'avatar' => $history['avatar'],
					'user_id' => $history['user_id']
				));

				
				$history['user_followers'] = WM_Router::create( $view->base_href . '?controller=users&action=followers&user_id=' . $history['user_id']  );
				
				$history['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $history['user_id'] );
				
				$history['history_comments_total'] = count($history['history_comments']);
				$history['history_follow_total'] = count($history['history_follow']);
				$history['history_like_total'] = count($history['history_like']);
				$history['history_repin_total'] = count($history['history_repin']);
				
				/////comments
				if($history['history_comments_total']) {
					foreach($history['history_comments'] AS $k => $v) {
						$history['history_comments'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
							'store' => $v['store'],
							'avatar' => $v['avatar'],
							'user_id' => $v['user_id']
						));
						$history['history_comments'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				/////follow
				if($history['history_follow_total']) {
					foreach($history['history_follow'] AS $k => $v) {
						$history['history_follow'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
								'store' => $v['store'],
								'avatar' => $v['avatar'],
								'user_id' => $v['user_id']
						));
						$history['history_follow'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				/////like
				if($history['history_like_total']) {
					foreach($history['history_like'] AS $k => $v) {
						$history['history_like'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
								'store' => $v['store'],
								'avatar' => $v['avatar'],
								'user_id' => $v['user_id']
						));
						$history['history_like'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
				/////repin
				if($history['history_repin_total']) {
					foreach($history['history_repin'] AS $k => $v) {
						$history['history_repin'][$k]['avatars'] = Helper_Uploadimages::userAvatars(array(
								'store' => $v['store'],
								'avatar' => $v['avatar'],
								'user_id' => $v['user_id']
						));
						$history['history_repin'][$k]['profile'] = WM_Router::create( $view->base_href . '?controller=users&action=profile&user_id=' . $v['user_id'] );
					}
				}
			
				/* PINS */
				$likes = new Model_History_Activity(array(
					'history_action' => Model_History_Abstract::LIKEPIN,
					'start' => 0,
					'limit' => 30
				), 'from_user_id', $history['user_id']);
				
				$history['pins_likes'] = array();
				if($likes->count()) {
					$temp = array();
					foreach($likes AS $like) {
						$temp[$like['pin_id']] = $like['pin_id'];
					}
					
					if($temp) { 
						
						$pins = new Model_Pins_PinsInId(array(
							'start' => 0,
							'limit' => 9,
							'pins' => $temp
						));
						
						
						if($pins->count()) {
							$formatObject = new Helper_Format();
							foreach($pins->data AS $pin) {
								
								$history['pins_likes'][] = $formatObject->fromatList($pin);

							}
						}
					}
				}
				
				$view->history = $history;
				
				$html = $view->render('sendWeekly','crons');
				
				Model_Email::send(
					$history['email'],
					Helper_Config::get('noreply_mail'),
					sprintf($this->translate('Weekly %s'), $view->site_name),
					$html,
					false
				);
				
			}
		}
	}
	
}

?>