<?php

class Model_Pins {
	
	public static $searchWordLenght = 3;
	
	
	private function common() {
		
		static $data = null;
		
		if($data === null) {
			$db = JO_Db::getDefaultAdapter();
			$query = $db->select()
						->from('pins_ignore_dictionary', array('dic_id', 'word'));
			$data = $db->fetchPairs($query);
		}
		
		return $data;
		
	}
	
	
	public function getCurrencyBySimbol($simbol) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('currency', 'value')
					->where('TRIM(symbol_left) = ? OR TRIM(symbol_right) = ?', $simbol)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	
	
	public static function editPin($pin_id, $data) {
//		echo '<pre>';
//		var_dump($data); exit;

		$pin_info = self::getPin($pin_id);
		if(!$pin_info) {
			return;
		}
		
		$board_info = Model_Boards::getBoard($data['board_id']);
		
		$db = JO_Db::getDefaultAdapter();
		
		$date_modified = WM_Date::format(time(), 'yy-mm-dd H:i:s');
		
		$price = '';
		$source_id = Model_Source::getSourceByUrl($data['from']);
		
		/* price */
		$currencies = WM_Currency::getCurrencies();
		$price_left = '';
		$price_right = '';
		if($currencies) {
			foreach($currencies AS $currency) {
				if(trim($currency['symbol_left'])) {
					$price_left[] = preg_quote(trim($currency['symbol_left']));
				}
				if(trim($currency['symbol_right'])) {
					$price_right[] = preg_quote(trim($currency['symbol_right']));
				}
			}
			if($price_left) {
				if( preg_match('/(' . implode('|', $price_left) . ')([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/', $data['price'], $match) ) {
					$price_tmp = trim(str_replace(trim($match[1]), '', $match[0]));
					$currency = self::getCurrencyBySimbol(trim($match[1]));
					if($currency) {
						$price = round( $price_tmp / $currency, 4 );
					}
				}
			} 
			if(!$price && $price_right) {
				if( preg_match('/(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?(' . implode('|', $price_right) . ')/', $data['price'], $match) ) {
					$price_tmp = trim(str_replace(trim($match[2]), '', $match[0]));
					$currency = self::getCurrencyBySimbol(trim($match[2]));
					if($currency) {
						$price = round( $price_tmp / $currency, 4 );
					}
				}
			} 
		}
		/* end price */
		
		$data['is_video'] = 'false';
		$help_video = new Helper_AutoEmbed();
		if($help_video->parseUrl($data['from'])) {
			$data['is_video'] = 'true';
		}
		
		/* edit to db */
		$db->update('pins', array(
			'category_id' => (string)$board_info['category_id'],
			'board_id' => (string)$data['board_id'],
			'date_modified' => $date_modified,
			'description' => $data['description'],
			'price' => $price,
			'from' => $data['from'],
			'from_md5' => md5($data['from']),
			'is_video' => ($data['is_video'] == 'true' ? 1 : 0),
			'source_id' => isset($data['source_id']) ? $data['source_id'] : $source_id,
		), array('pin_id = ?' => (string)$pin_id));
		
		$spl = JO_Utf8::str_word_split( strip_tags( html_entity_decode($data['description'], ENT_QUOTES, 'utf-8') ) , self::$searchWordLenght);
		$words = array();
		foreach($spl AS $word) {
			$word = mb_strtolower($word, 'utf-8');
			if( !in_array($word, self::common()) && $word[0].$word[1] != '&#' ) {
				$words[$word] = $word;
			}
		}
		
		foreach($words AS $word => $data1) {
			$dic_id = $db->fetchOne( $db->select()->from('pins_dictionary', 'dic_id')->where('word = ?', $word) );
			if(!$dic_id) {
				$db->insert('pins_dictionary', array(
					'word' => $word
				));
				$dic_id = $db->lastInsertId();
			}
			if($dic_id) {
				$db->insert('pins_invert', array(
					'pin_id' => $pin_id,
					'dic_id' => $dic_id
				));
			}
		}
		
		if($pin_info['board_id'] != $data['board_id']) {
			//mahame i slagame ot stariq i v noviq/////
			Model_Boards::updateLatestPins($pin_info['board_id']);
			Model_Boards::updateLatestPins($data['board_id']);
			$board_info2 = Model_Boards::getBoard($pin_info['board_id']);
			if($board_info2['cover'] == $pin_id) {
				$db->update('boards', array(
					'cover' => 0
				), array('board_id = >' => $pin_info['board_id']));
			}
		}
		
		$db->update('pins', array(
			'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT user_id) FROM pins_likes WHERE pin_id = pins.pin_id)'),
			'comments' => new JO_Db_Expr('(SELECT COUNT(DISTINCT comment_id) FROM pins_comments WHERE pin_id = pins.pin_id)'),
		), array('pin_id = ?' => (string)$pin_id));
		
		Model_Users::updateLatestPins();
		
		self::rebuildCache(array($pin_id));
		
		return $pin_id;
		
	}
	
	public static function create($data) {
		$board_info = Model_Boards::getBoard($data['board_id']);
		
		$db = JO_Db::getDefaultAdapter();
		
		$date_added = WM_Date::format(time(), 'yy-mm-dd H:i:s');
		
		$image = '';
		$price = '';
		$source_id = Model_Source::getSourceByUrl($data['from']);
		
		/* price */
		$currencies = WM_Currency::getCurrencies();
		$price_left = '';
		$price_right = '';
		if($currencies) {
			foreach($currencies AS $currency) {
				if(trim($currency['symbol_left'])) {
					$price_left[] = preg_quote(trim($currency['symbol_left']));
				}
				if(trim($currency['symbol_right'])) {
					$price_right[] = preg_quote(trim($currency['symbol_right']));
				}
			}
			if($price_left) {
				if( preg_match('/(' . implode('|', $price_left) . ')([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/', $data['price'], $match) ) {
					$price_tmp = trim(str_replace(trim($match[1]), '', $match[0]));
					$currency = self::getCurrencyBySimbol(trim($match[1]));
					if($currency) {
						$price = round( $price_tmp / $currency, 4 );
					}
				}
			} 
			if(!$price && $price_right) {
				if( preg_match('/(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?(' . implode('|', $price_right) . ')/', $data['price'], $match) ) {
					$price_tmp = trim(str_replace(trim($match[2]), '', $match[0]));
					$currency = self::getCurrencyBySimbol(trim($match[2]));
					if($currency) {
						$price = round( $price_tmp / $currency, 4 );
					}
				}
			} 
		}
		/* end price */
		
		
		$from = isset($data['from'])?$data['from']:time();
		$is_video = (isset($data['is_video']) && $data['is_video'] == 'true' ? 1 : 0);
		if(!$is_video) {
			$auto = new Helper_AutoEmbed();
			if($auto->parseUrl($from)) {
				$is_video = 1;
			}
		}
		
		/* add to db */
		$db->insert('pins', array(
			'category_id' => (string)$board_info['category_id'],
			'board_id' => (string)$data['board_id'],
			'user_id' => (string)JO_Session::get('user[user_id]'),
			'date_added' => $date_added,
			'date_modified' => $date_added,
			'description' => $data['description'],
			'title' => isset($data['title']) ? $data['title'] : '',
			'price' => $price,
			'from' => $from,
			'from_md5' => md5($from),
			'is_video' => $is_video,
			'source_id' => isset($data['source_id']) ? $data['source_id'] : $source_id,
			'via' => isset($data['via']) ? $data['via'] : '',
			'repin_from' => isset($data['repin_from']) ? $data['repin_from'] : '',
			'public' => (int)$board_info['public'],
			'pinmarklet' => isset($data['pinmarklet']) ? 1 : 0,
			'from_repin' => isset($data['from_repin'])?$data['from_repin']:'',
			'store' => JO_Registry::get('default_upload_method') ? JO_Registry::get('default_upload_method') : 'locale'
		));
		
		$pin_id = $db->lastInsertId();
		
		if(!$pin_id) {
			return false;
		}
		
	
		///// upload images
		$front = JO_Front::getInstance();
		$request = JO_Request::getInstance();
		$upload_model = Helper_Pin::formatUploadModule(JO_Registry::get('default_upload_method'));
		$upload_model_file = $front->getModuleDirectoryWithDefault($request->getModule()) . '/' . $front->classToFilename($upload_model);
		if(!file_exists($upload_model_file)) {
			$upload_model = Helper_Pin::formatUploadModule('locale');
			$upload_model_file = $front->getModuleDirectoryWithDefault($request->getModule()) . '/' . $front->classToFilename($upload_model);
		}
		
		$image = false;
		if(file_exists($upload_model_file)) {
			$image = call_user_func(array($upload_model, 'uploadPin'), $data['image'], (isset($data['title']) && $data['title'] ? $data['title'] : null), $pin_id );
		}
		
// 		if(!$image && $upload_model != $front->formatModuleName('model_upload_locale')) {
// 			$image = call_user_func(array($upload_model, 'uploadPin'), $data['image'], (isset($data['title']) && $data['title'] ? $data['title'] : null), $pin_id );
// 		}
		
		
		if(!$image) {
			$db->delete('pins', array('pin_id = ?' => (string)$pin_id));
			return false;
		} else {
			$db->update('pins', array(
				'image' => $image['image'],
				'store' => $image['store'],
				'height' => $image['height'],
				'width' => $image['width'],
			), array('pin_id = ?' => (string)$pin_id));
		}
		
		//if($board_info['user_id'] == JO_Session::get('user[user_id]')) {
			Model_Boards::updateLatestPins($data['board_id'], $pin_id);
		//}
		
		Model_Users::updateLatestPins($pin_id);
		
		if(isset($data['repin_from']) && $data['repin_from']) {
			$pin_repin = self::getPin($data['repin_from']);
			if($pin_repin) {
				$db->update('pins', array(
					'repins' => ($pin_repin['repins'] + 1)
				), array('pin_id = ?' => $data['repin_from']));
			}
		}
		
		$spl = JO_Utf8::str_word_split( strip_tags( html_entity_decode($data['description'], ENT_QUOTES, 'utf-8') ) , self::$searchWordLenght);
		$words = array();
		foreach($spl AS $word) {
			$word = mb_strtolower($word, 'utf-8');
			if( !in_array($word, self::common()) && $word[0].$word[1] != '&#' ) {
				$words[$word] = $word;
			}
		}
		
		foreach($words AS $word => $data1) {
			$dic_id = $db->fetchOne( $db->select()->from('pins_dictionary', 'dic_id')->where('word = ?', $word) );
			if(!$dic_id) {
				$db->insert('pins_dictionary', array(
					'word' => $word
				));
				$dic_id = $db->lastInsertId();
			}
			if($dic_id) {
				$db->insert('pins_invert', array(
					'pin_id' => $pin_id,
					'dic_id' => $dic_id
				));
			}
		}
		
		
		if(JO_Session::get('user[facebook_connect]') && JO_Session::get('user[facebook_timeline]')) {
			
			try {
				$session = JO_Registry::get('facebookapi')->getUser();
					
				JO_Registry::get('facebookapi')->setExtendedAccessToken();
				
				if( JO_Registry::get('facebookapi')->api('/me') ) {
					$access_token = JO_Registry::get('facebookapi')->getAccessToken();
					$pin_url = WM_Router::create( JO_Request::getInstance()->getBaseUrl() . '?controller=pin&pin_id=' . $pin_id );
					$statusUpdate = JO_Registry::get('facebookapi')->api('/me/feed', 'post', array( 'link' => $pin_url, 'cb' => '' ));
				
					$og_namespace = trim(JO_Registry::get('og_namespace'));
					$og_recipe = trim(JO_Registry::get('og_recipe'));
					if(!$og_recipe) {
						$og_namespace = '';
					}
					
					if($og_namespace) {
	
						$params = array($og_recipe=>$pin_url,'access_token'=>$access_token);
						$response = JO_Registry::get('facebookapi')->api('/me/'.$og_namespace.':'.$og_recipe,'post',$params);
	
					}
					
				} 
			} catch (JO_Exception $e) {}
		} 
		
		self::rebuildCache(array($pin_id));
		
		if(isset($data['repin_from'])) {
			self::rebuildCache($data['repin_from']);
		}
		
		return $pin_id;
		
	}
	
	/**
	 * @param JO_Db_Select $query
	 * @param array $data
	 * @return JO_Db_Select
	 */
	private static function FilterBuilder(JO_Db_Select $query, $data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$ignore_in = false;
		
		$query->where('pins.store != ""');
		
		if(isset($data['filter_likes']) && $data['filter_likes'] ) {
			$ignore_in = true;
		}
		
//		if(isset($data['filter_likes']) && $data['filter_likes'] ) {
////			$query->where("pins.pin_id IN ( SELECT DISTINCT pin_id FROM pins_likes WHERE user_id = '".$data['filter_likes']."' )");
//			$query->joinLeft('pins_likes', 'pins.pin_id = pins_likes.pin_id', array())
//					->where('pins_likes.user_id = ?', $data['filter_likes'])
//					/*->group('pins.pin_id')*/;
//			$ignore_in = true;
//		}
		
		if(isset($data['filter_pin_id']) && $data['filter_pin_id']) {
			$query->where('pins.pin_id = ?', (string)$data['filter_pin_id']);
		}
		
		if(isset($data['filter_like_repin_comment']) && $data['filter_like_repin_comment'] === true) {
			$query->where('pins.likes > 0')
					->where('pins.repins > 0')
					->where('pins.comments > 0');
		}
		
		if(isset($data['delete_request']) && $data['delete_request'] === true) {
			$query->where('pins.delete_request = 1');
		}
		
		if(isset($data['filter_price_from']) && (int)$data['filter_price_from']) {
			$query->where('pins.price >= ?', (int)$data['filter_price_from']);
			$ignore_in = true;
		} elseif(isset($data['allow_gifts'])) {
			$query->where('pins.price > 0.0000');
			$ignore_in = true;
		}
		
		if(isset($data['filter_id_in']) && $data['filter_id_in']) {
			$query->where('pins.pin_id IN (?)', new JO_Db_Expr($data['filter_id_in']));
			$ignore_in = true;
		}
		
		if(isset($data['filter_id_not']) && $data['filter_id_not']) {
			$query->where('pins.pin_id NOT IN (?)', new JO_Db_Expr($data['filter_id_not']));
			$ignore_in = true;
		}
		
		if(isset($data['filter_price_to']) && (int)$data['filter_price_to']) {
			$query->where('pins.price <= ?', (int)$data['filter_price_to']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_marker']) && (string)$data['filter_marker']) {
			$query->where('pins.pin_id <= ?', (string)$data['filter_marker']);
			$ignore_in = true;
		}
					
		if(isset($data['filter_repin_from']) && !is_null($data['filter_repin_from'])) {
			$query->where('pins.repin_from = ?', (string)$data['filter_repin_from']);
			$ignore_in = true;
		}
					
		if(isset($data['filter_source_id']) && !is_null($data['filter_source_id'])) {
			$query->where('pins.source_id = ?', (string)$data['filter_source_id']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_from']) && !is_null($data['filter_from'])) {
			$query->where('pins.from = ?', $data['filter_from']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_from_md5']) && !is_null($data['filter_from_md5'])) {
			$query->where('pins.from_md5 = ?', $data['filter_from_md5']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_category_id']) && !is_null($data['filter_category_id'])) {
			$query->where('pins.category_id = ?', (string)$data['filter_category_id']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_is_video']) && !is_null($data['filter_is_video'])) {
			$query->where('pins.is_video = ?', (int)$data['filter_is_video']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_board_id']) && !is_null($data['filter_board_id'])) {
			$query->where('pins.board_id = ?', (string)$data['filter_board_id']);
			$ignore_in = true;
		}
		
//		if(isset($data['filter_ub_id']) && !is_null($data['filter_ub_id'])) {
//			$query->where('pins.ub_id = ?', (string)$data['filter_ub_id']);
//			$ignore_in = true;
//		}
		
		if(isset($data['filter_user_id']) && !is_null($data['filter_user_id'])) {
			$query->where('pins.user_id = ?', (string)$data['filter_user_id']);
			$ignore_in = true;
		}
		
		if(isset($data['filter_description'])) {
			$words = JO_Utf8::str_word_split( mb_strtolower($data['filter_description'], 'utf-8') , self::$searchWordLenght);
			
			if( count($words) > 0 ) {
				/*$sub = "SELECT `i`.`pin_id` FROM `pins_invert` `i`, `pins_dictionary` `d` WHERE `i`.`dic_id` = `d`.`dic_id` AND ( ";
				foreach($words AS $key => $word) {
					if($key) {
						$sub .= ' OR ';
					}
					$sub .= "`d`.`word` = " . $db->quote($word) . " OR MATCH(`d`.`word`) AGAINST (" . $db->quote($word) . ")";
				}
				$sub .= ')';
				
				$query->where('pins.pin_id IN (' . $sub . ')');
				
				$sub = "SELECT `dic_id`, `dic_id` FROM `pins_dictionary` `d` WHERE ( ";
				foreach($words AS $key => $word) {
					if($key) {
						$sub .= ' OR ';
					}
					$sub .= "`d`.`word` = " . $db->quote($word) . " OR MATCH(`d`.`word`) AGAINST (" . $db->quote($word) . ")";
				}
				$sub .= ')';
				
				$dicts = $db->fetchPairs($sub);
				
				$tmp_dic_ids = array();
				if(COUNT($dicts) > 0) { 
					
					$pins = $db->fetchPairs("SELECT `pin_id`, `pin_id` FROM `pins_invert` `i` WHERE `i`.`dic_id` IN (" . implode(',', $dicts) . ")");
					
					if(count($pins) > 0) {
						$query->where('pins.pin_id IN (' . implode(',', $pins) . ')');
					} else {
						$query->where('pins.pin_id = 0');
					}
				} else {
					$query->where('pins.pin_id = 0');
				}*/
				
				$sub = "SELECT `dic_id`, `dic_id` FROM `pins_dictionary` `d` WHERE ( ";
				foreach($words AS $key => $word) {
					if($key) {
						$sub .= ' OR ';
					}
					$sub .= "`d`.`word` = " . $db->quote($word) . " OR MATCH(`d`.`word`) AGAINST (" . $db->quote($word) . ")";
				}
				$sub .= ')';
				
				$dicts = $db->fetchPairs($sub);
				
				$tmp_dic_ids = array();
				if(COUNT($dicts) > 0) { 
					
//					$pins = $db->fetchPairs("SELECT `pin_id`, `pin_id` FROM `pins_invert` `i` WHERE `i`.`dic_id` IN (" . implode(',', $dicts) . ")");
					
					//$query->where('pins.pin_id IN ( SELECT DISTINCT `pin_id` FROM `pins_invert` `i` WHERE `i`.`dic_id` IN (' . implode(',', $dicts) . ') )');
				
					$query->joinLeft('pins_invert', 'pins.pin_id = pins_invert.pin_id', 'dic_id')
					->where('pins_invert.`dic_id` IN (' . implode(',', $dicts) . ')')
					->group('pins.pin_id');
					$ignore_in = true;
				
				} else {
					$query->where('pins.pin_id = 0');
				}
				
			} else {
				$query->where('pins.pin_id = 0');
			}
		}
		
		if(isset($data['following_users_from_user_id']) && (string)$data['following_users_from_user_id']) {
			if( JO_Session::get('user[following]') ) {
				$sql1 = "SELECT following_id FROM users_following_user WHERE user_id = ?";
				$sql = "SELECT p.pin_id FROM `users_following` a, pins p WHERE a.following_id NOT IN(" . $sql1 . ") AND a.`board_id` = p.`board_id`";
				$query->where("((pins.pin_id IN (" . $sql . ") OR pins.user_id IN (" . $sql1 . ")) AND (`board_id` NOT IN (SELECT board_id FROM `users_following_ignore` WHERE following_id IN(" . $sql1 . ") AND user_id = ?))) OR pins.user_id = ?", (string)JO_Session::get('user[user_id]'));
			}
			
			/*$sql1 = "SELECT following_id FROM users_following_user WHERE user_id = ?";
//			$sql = "SELECT p.pin_id FROM `users_following` a, `users_boards` b, pins p WHERE
//					a.following_id NOT IN(" . $sql1 . ") AND a.`board_id`=b.`board_id` AND b.`public`=1 AND a.`board_id` = p.`board_id` AND
//					(p.pin_id IN (SELECT pin_id FROM pins WHERE public = 1) OR p.user_id=".(string)JO_Session::get('user[user_id]').")";
//			$sql = "SELECT p.pin_id FROM `users_following` a, `users_following_ignore` c, `boards` b, pins p WHERE
//					a.following_id NOT IN(" . $sql1 . ") AND a.`board_id`=b.`board_id` AND b.`public`=1 AND a.`board_id` = p.`board_id` AND
//					(p.pin_id IN (SELECT pin_id FROM pins WHERE public = 1) OR p.user_id=?)";
			$sql = "SELECT p.pin_id FROM `users_following` a, pins p WHERE a.following_id NOT IN(" . $sql1 . ") AND a.`board_id` = p.`board_id`";
			$query->where("((pins.pin_id IN (" . $sql . ") OR pins.user_id IN (" . $sql1 . ")) AND (`board_id` NOT IN (SELECT board_id FROM `users_following_ignore` WHERE following_id IN(" . $sql1 . ") AND user_id = ?))) OR pins.user_id = ?", (string)JO_Session::get('user[user_id]'));
//			$query->where("pins.board_id IN (SELECT board_id FROM users_following WHERE user_id = '" . (string)$data['following_users_from_user_id'] . "')");
//echo $query; exit;
 			
			/*$sql = "SELECT board_id FROM users_boards WHERE (
					  user_id IN (
					    SELECT following_id FROM users_following_user WHERE user_id = ?
					  ) 
					OR
					  board_id IN (
					    SELECT board_id FROM users_following WHERE user_id = ?
					  )
					)
					AND
					board_id NOT IN (
					  SELECT board_id FROM users_following_ignore WHERE user_id = ?
					)";
			$query->where("pins.board_id IN (" . $sql . ") OR pins.user_id = ?", (string)JO_Session::get('user[user_id]'));
			*/
		} else {

//			if($ignore_in) {
//				if((string)JO_Session::get('user[user_id]')) {
//					$query->where("(pins.public = 1 OR user_id = ".(string)JO_Session::get('user[user_id]').")");
//				} else {
//					$query->where("pins.public = 1");
//				}
//			} else {
//				if((string)JO_Session::get('user[user_id]')) {
//					$query->where("(pins.pin_id IN (SELECT pin_id FROM pins WHERE public = 1)) OR (pins.user_id IN (SELECT DISTINCT user_id FROM pins WHERE user_id = ".(string)JO_Session::get('user[user_id]')."))");
//				} else {
//					$query->where("pins.pin_id IN (SELECT pin_id FROM pins WHERE public = 1)");
//				}
//			}
		}
//		echo $query; exit;
		return $query;
	} 
	
	public static function getPrevPin($pin_id, $fields = array('*')) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pins', array('pins.*', 'gift' => new JO_Db_Expr('pins.price > 0.0000')))
					->where('pins.pin_id < ?', (string)$pin_id)
					//->where("public = 1 OR user_id = ".(string)JO_Session::get('user[user_id]')."")
					->order('pin_id DESC')
					->limit(1);
		$query->where('pins.store != ""');
		
		$result = $db->fetchRow($query);
		if(!$result) {
			return false;
		}

		$userinfo = Model_Users::getUser($result['user_id'], false, $fields);
		
		if(!$userinfo) {
			return false;
		}
		
		return $result;
	}
	
	public static function getNextPin($pin_id, $fields = array('*')) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pins', array('pins.*', 'gift' => new JO_Db_Expr('pins.price > 0.0000')))
					->where('pins.pin_id > ?', (string)$pin_id)
					//->where("public = 1 OR user_id = ".(string)JO_Session::get('user[user_id]')."")
					->order('pin_id ASC')
					->limit(1);
		$query->where('pins.store != ""');
		
		$result = $db->fetchRow($query);
		if(!$result) {
			return false;
		}

		$userinfo = Model_Users::getUser($result['user_id'], false, $fields);
		
		if(!$userinfo) {
			return false;
		}
		
		return $result;
	}
	
	public static function getPin($pin_id, $fields = array('*')) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pins', array('pins.*', 'gift' => new JO_Db_Expr('pins.price > 0.0000')))
					->where('pins.pin_id = ?', (string)$pin_id)
					//->where("public = 1 OR user_id = ".(string)JO_Session::get('user[user_id]')."")
					->limit(1);
		$query->where('pins.store != ""');
		
		$result = $db->fetchRow($query); 
		if(!$result) {
			return false;
		}

		$userinfo = Model_Users::getUser($result['user_id'], false, $fields);
		
		if(!$userinfo) {
			return false;
		}
		
		$result['user_via'] = Model_Users::getUser($result['via'], false, $fields);
		$result['source'] = Model_Source::getSource($result['source_id']);
		$result['user'] = $userinfo;
		$result['board'] = Model_Boards::getBoardTitle($result['board_id']);
		$result['board_data'] = Model_Boards::getBoard($result['board_id']);
		$result['latest_comments'] = $result['comments'] ? Model_Comments::getComments(array(
			'filter_pin_id' => $pin_id
		)) : 0;
		$result['liked'] = $result['likes'] ? self::pinIsLiked($result['pin_id']) : 0;
		
		return $result;
	}
  	
	public function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	} 
	
	public static function pinIsExist($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins', 'pin_id')
					->where('pin_id = ?', $pin_id)
					->limit(1);
		return $db->fetchOne($query);
	}

	public static function getPins($data = array(), &$pin_ids = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$cache_live = (int)JO_Registry::get('config_cache_live');
		
		if($cache_live && isset($data['filter_rand']) && $data['filter_rand'] == true && isset($data['start']) && isset($data['limit'])) {
			$check = $db->select()
					->from('cache_index')
					->where('start_limit = ?', $data['start'].'.'.$data['limit'])
					->where('`date` >= ?', time())
					->limit(1);
			$cache = $db->fetchRow($check); 
			
			if($cache) {
				$results = JO_Json::decode($cache['data'], true);
				if($results && is_array($results)) {
					$return = array();
					foreach($results AS $result) {
						if(self::pinIsExist($result['pin_id'])) {
							$return[] = $result;
						}
					}
					return $return;
				}
			}
		} elseif($cache_live && isset($data['filter_like_repin_comment']) && $data['filter_like_repin_comment'] == true && isset($data['start']) && isset($data['limit'])) {
			
			$check = $db->select()
					->from('cache_popular')
					->where('start_limit = ?', $data['start'].'.'.$data['limit'])
					->where('`date` >= ?', time())
					->limit(1);
			$cache = $db->fetchRow($check); 
			
			if($cache) {
				$results = JO_Json::decode($cache['data'], true);
				if($results && is_array($results)) {
					$return = array();
					foreach($results AS $result) {
						if(self::pinIsExist($result['pin_id'])) {
							$return[] = $result;
						}
					}
					return $return;
				}
			}
		}

		
		if(isset($data['filter_likes']) && $data['filter_likes'] ) {
			$query = $db->select()
					->from('pins', array('pins.*', 'gift' => new JO_Db_Expr('pins.price > 0.0000')))
					->where('pins.pin_id IN (SELECT pins_likes.pin_id FROM pins_likes WHERE user_id = ?)', $data['filter_likes']);
					
		} else {
			$query = $db->select()
					->from('pins', array('pins.*', 'gift' => new JO_Db_Expr('pins.price > 0.0000')));
		}
					
		$query = self::FilterBuilder($query, $data);


		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		
		$allow_sort = array(
			'pins.pin_id',
			'pins.views'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} elseif(isset($data['order']) && $data['order'] instanceof JO_Db_Expr) {
			$query->order($data['order']);
		} else {
			$query->order('pins.pin_id' . $sort);
		}
		
		$start = microtime(true);
		
//echo $query.'<hr />';
		$results = $db->fetchAll($query);
		$results_array = array();
		if($results) {
			foreach($results AS $result) {
				$userinfo = Model_Users::getUser($result['user_id'], false, Model_Users::$allowed_fields);
				if($userinfo) {
					$result['user_via'] = $result['via']?Model_Users::getUser($result['via'], false, Model_Users::$allowed_fields):false;
					$result['user'] = $userinfo;
					$result['board'] = Model_Boards::getBoardTitle($result['board_id']);
					$result['latest_comments'] = $result['comments'] ? Model_Comments::getLatestComments($result['latest_comments']) : array();
					$result['liked'] = $result['likes'] ? self::pinIsLiked($result['pin_id']) : 0;
					$results_array[] = $result;
//					array_push($pin_ids, $result['pin_id']);
				}
			}
		}
		
//		var_dump( microtime(true)-JO_Registry::get('start_microtime') ); exit;
		
		return $results_array;
	}

	public static function getTotalPins($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pins', 'COUNT(DISTINCT pins.pin_id)')
					->limit(1);
		
		$query = self::FilterBuilder($query, $data)->reset(JO_Db_Select::GROUP);
		
		return $db->fetchOne($query);
	}

	public static function getTotalPinsLikes($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('pins', 'SUM(pins.likes)')
					->limit(1);
		
		$query = self::FilterBuilder($query, $data)->reset(JO_Db_Select::GROUP);
		
		return $db->fetchOne($query);
	}

	public static function getMaxPin($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
//					->from('pins', 'MAX(pins.pin_id)')
					->from('pins', 'pins.pin_id')
					->limit(1);
		
//		$query = self::FilterBuilder($query, $data)->reset(JO_Db_Select::GROUP); 
		$query = self::FilterBuilder($query, $data)->order('pins.pin_id DESC'); 
//		echo $query; exit;
		return $db->fetchOne($query);
	}
	
	public static function pinIsReported($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_reports', 'COUNT(pr_id)')
					->where('pin_id = ?', (string)$pin_id)
					->where('checked = 0')
					->limit(1);

		if((string)JO_Session::get('user[user_id]')) {
			$query->where("user_id = '" . (string)JO_Session::get('user[user_id]') . "' OR user_ip = '" . JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()) . "'");
		} else {
			$query->where("user_ip = ?", JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()));
		}
		
		return $db->fetchOne($query);
	}
	
	public static function commentIsReported($comment_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_reports_comments', 'COUNT(pr_id)')
					->where('comment_id = ?', (string)$comment_id)
					->where('checked = 0')
					->limit(1);

		if((string)JO_Session::get('user[user_id]')) {
			$query->where("user_id = '" . (string)JO_Session::get('user[user_id]') . "' OR user_ip = '" . JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()) . "'");
		} else {
			$query->where("user_ip = ?", JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()));
		}
		
		return $db->fetchOne($query);
	}
	
	public static function reportComment($comment_id, $prc_id, $message = '') {
		if(self::commentIsReported($comment_id)) {
			return false;
		}
		$db = JO_Db::getDefaultAdapter();
		
		$db->insert('pins_reports_comments', array(
			'prc_id' => (string)$prc_id,
			'user_id' => (string)JO_Session::get('user[user_id]'),
			'date_added' => new JO_Db_Expr('NOW()'),
			'comment_id' => (string)$comment_id,
			'user_ip' => JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()),
			'message' => (string)$message
		));
		
		return $db->lastInsertId();
	}
	
	public static function reportPin($pin_id, $prc_id, $message = '') {
		if(self::pinIsReported($pin_id)) {
			return false;
		}
		$db = JO_Db::getDefaultAdapter();
		
		$db->insert('pins_reports', array(
			'prc_id' => (string)$prc_id,
			'user_id' => (string)JO_Session::get('user[user_id]'),
			'date_added' => new JO_Db_Expr('NOW()'),
			'pin_id' => (string)$pin_id,
			'user_ip' => JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()),
			'message' => (string)$message
		));
		
		return $db->lastInsertId();
	}
	
	public static function pinIsLiked($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!(string)JO_Session::get('user[user_id]')) {
			return false;
		}
		
		$query = $db->select()
					->from('pins_likes', 'COUNT(like_id)')
					->where('user_id = ?', (string)JO_Session::get('user[user_id]'))
					->where('pin_id = ?', (string)$pin_id)
					->limit(1);
		
		return $db->fetchOne($query);
	}
	
	public static function likePin($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!(string)JO_Session::get('user[user_id]')) {
			return false;
		}
		
		$db->insert('pins_likes', array(
			'pin_id' => (string)$pin_id,
			'user_id' => (string)JO_Session::get('user[user_id]')
		));
		
		$row = $db->lastInsertId();
		
		if($row) { 
			$db->update('pins', array(
				'likes' => new JO_Db_Expr("(SELECT COUNT(like_id) FROM pins_likes WHERE pin_id = '".$pin_id."')")
			), array('pin_id = ?' => (string)$pin_id));
			
			$db->update('users', array(
				'likes' => new JO_Db_Expr("(SELECT COUNT(like_id) FROM pins_likes WHERE user_id = '".(string)JO_Session::get('user[user_id]')."')")
			), array('user_id = ?' => (string)JO_Session::get('user[user_id]')));
			
			self::rebuildCache($pin_id);
		}
		
		return $row;
	}
	
	public static function unlikePin($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!(string)JO_Session::get('user[user_id]')) {
			return false;
		}
		
		$row = $db->delete('pins_likes', array(
			'pin_id = ?' => (string)$pin_id,
			'user_id = ?' => (string)JO_Session::get('user[user_id]')
		));
		
		if($row) {
			$db->update('pins', array(
				'likes' => new JO_Db_Expr("(SELECT COUNT(like_id) FROM pins_likes WHERE pin_id = '".$pin_id."')")
			), array('pin_id = ?' => (string)$pin_id));
			
			$db->update('users', array(
				'likes' => new JO_Db_Expr("(SELECT COUNT(like_id) FROM pins_likes WHERE user_id = '".(string)JO_Session::get('user[user_id]')."')")
			), array('user_id = ?' => (string)JO_Session::get('user[user_id]')));
		
			self::rebuildCache($pin_id);
		}
		
		return $row;
	}
	
	public static function getPinReportCategories() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_reports_categories', array('prc_id', 'title'))
					->order('sort_order ASC');
		return $db->fetchPairs($query);
	}
	
	public static function getCommentReportCategories() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_comment_reports_categories', array('prc_id', 'title'))
					->order('sort_order ASC');
		return $db->fetchPairs($query);
	}
	
	public static function addComment($data, $latest_comments, $fields = array('*')) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('pins_comments', array(
			'pin_id' => (string)$data['pin_id'],
			'user_id' => (string)JO_Session::get('user[user_id]'),
			'comment' => $data['write_comment'],
			'date_added' => new JO_Db_Expr('NOW()')
		));
		
		$com_id = $db->lastInsertId();
		if(!$com_id) {
			return false;
		}
		
		$query = $db->select()
					->from('pins_comments')
					->where('comment_id = ?', $com_id)
					->limit('1');
		$result = $db->fetchRow($query);
		if(!$result) {
			return false;
		}

		
		$db->update('pins', array(
			'comments' => new JO_Db_Expr("(SELECT COUNT(comment_id) FROM pins_comments WHERE pin_id = '".(string)$data['pin_id']."')"),
			'latest_comments' => new JO_Db_Expr("(SELECT GROUP_CONCAT(comment_id ORDER BY comment_id ASC) FROM (SELECT comment_id FROM pins_comments WHERE pin_id = '" . (string)$data['pin_id'] . "' ORDER BY comment_id ASC LIMIT 4) AS tmp)")
		), array('pin_id = ?' => (string)$data['pin_id']));
		
		$userdata = Model_Users::getUser(JO_Session::get('user[user_id]'), false, $fields);
		if(!$userdata) {
			$userdata = array('fullname' => '', 'avatar' => '');
		}
		
		self::rebuildCache($data['pin_id']);
		
		$result['user'] = $userdata;
		return $result;
	}
	
	public static function updateViewed($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!self::isViewedPin($pin_id)) {
			$db->update('pins', array(
				'views' => new JO_Db_Expr('views+1')
			), array('pin_id = ?' => (string)$pin_id));
			
			$db->insert('pins_views', array(
				'user_id' => (string)JO_Session::get('user[user_id]'),
				'date_added' => new JO_Db_Expr('NOW()'),
				'pin_id' => (string)$pin_id,
				'user_ip' => JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp())
			));
		}
		
		$db->update('pins', array(
			'total_views' => new JO_Db_Expr('total_views+1')
		), array('pin_id = ?' => (string)$pin_id));
		
	}
	
	public static function isViewedPin($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_views', 'COUNT(pv_id)')
					->where('pin_id = ?', (string)$pin_id)
					->limit(1);

		if((string)JO_Session::get('user[user_id]')) {
			$query->where("user_id = '" . (string)JO_Session::get('user[user_id]') . "' OR user_ip = '" . JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()) . "'");
		} else {
			$query->where("user_ip = ?", JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()));
		}
		
		return $db->fetchOne($query);
	}
	
	public static function getComment($com_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_comments')
					->where('comment_id = ?', $com_id)
					->limit('1');
		return $db->fetchRow($query);
	}
	
	/*public static function deleteComment($com_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getComment($com_id);
		$results = false;
		if($info) {
			$results = $db->delete('pins_comments', array('comment_id = ?' => $com_id));
			$db->delete('pins_reports_comments', array('comment_id = ?' => $com_id));
			
	
			$db->update('pins', array(
				'comments' => new JO_Db_Expr("(SELECT COUNT(comment_id) FROM pins_comments WHERE pin_id = '".(string)$info['pin_id']."')"),
//				'latest_comments' => (string)implode(',',$fcm)
				'latest_comments' => new JO_Db_Expr("(SELECT GROUP_CONCAT(comment_id ORDER BY comment_id ASC) FROM (SELECT comment_id FROM pins_comments WHERE pin_id = '" . (string)$info['pin_id'] . "' ORDER BY comment_id ASC LIMIT 4) AS tmp)")
			), array('pin_id = ?' => (string)$info['pin_id']));
			
			self::rebuildCache($info['pin_id']);
			
		}
		return $results;
	}*/
	
	public static function deleteFromServer($image) {
		if(JO_Registry::get('enable_amazon')) {
			$s3 = new JO_Api_Amazon(JO_Registry::get('awsAccessKey'), JO_Registry::get('awsSecretKey'));
			$s3->putBucket(JO_Registry::get('bucklet'), JO_Api_Amazon::ACL_PUBLIC_READ);
			if($s3->getBucketLogging(JO_Registry::get('bucklet'))) {
				$s3->deleteObject(JO_Registry::get('bucklet'), $image);
			}
		}
	}
	
	
	public static function delete($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		$pin_info = self::getPin($pin_id);
		if(!$pin_info) {
			return false;
		}

		call_user_func(array(Helper_Pin::formatUploadModule($pin_info['store']), 'deletePinImage'), $pin_info );
		

		if($pin_info['latest_comments']) {
			foreach($pin_info['latest_comments'] AS $c) {
				self::deleteComment($c['comment_id']);
			}
		}
		
		self::deleteCache($pin_info);
		
		$del = $db->delete('pins', array('pin_id = ?' => $pin_id));
		if(!$del) {
			return false;
		} else {
			
			$latest_pins = array();
			$pins_query = $db->select()
								->from('pins', array('pin_id','pin_id'))
								->where('user_id = ?', $pin_info['user_id'])
								->order('pin_id DESC')
								->limit(15);
			
			$latest = $db->fetchPairs($pins_query);
			if($latest) {
				$latest_pins = $latest;
			}
			
			$db->delete('pins_invert', array('pin_id = ?' => $pin_id));
			$db->delete('pins_likes', array('pin_id = ?' => $pin_id));
			$db->delete('pins_reports', array('pin_id = ?' => $pin_id));
			$db->delete('pins_views', array('pin_id = ?' => $pin_id));
			$db->delete('users_history', array('pin_id = ?' => $pin_id));
			$db->delete('pins_images', array('pin_id = ?' => $pin_id));
			
			$update = array(
				'pins' => new JO_Db_Expr("(SELECT COUNT(pin_id) FROM pins WHERE user_id=users.user_id)"),
				'latest_pins' => implode(',', $latest_pins)
			);
			$update['likes'] = new JO_Db_Expr('likes-'.(int)$db->fetchOne($db->select()->from('pins_likes','COUNT(like_id)')->where('pin_id = ?', $pin_id)));
			$db->update('users', $update, array('user_id=?'=>$pin_info['user_id']));
			
			$latest_pins = array();
			$pins_query = $db->select()
								->from('pins', array('pin_id','pin_id'))
								->where('board_id = ?', $pin_info['board_id'])
								->order('pin_id DESC')
								->limit(15);
			
			$latest = $db->fetchPairs($pins_query);
			if($latest) {
				$latest_pins = $latest;
			}
			$update = array(
				'pins' => new JO_Db_Expr('(SELECT COUNT(pin_id) FROM pins WHERE board_id=boards.board_id)'),
				'latest_pins' => implode(',', $latest_pins)
			);
			$update['latest_pins'] = implode(',', $latest_pins);
			
			$db->update('boards', $update, array('board_id=?'=>$pin_info['board_id']));
			
			
			
			return true;
		}
		
	}
	
	/////// index
	public static function Cmd() {
		$db = JO_Db::getDefaultAdapter();
		
// 		$cache_live = (int)JO_Registry::get('config_cache_live');
// 		if(!$cache_live) {
// 			return;
// 		}
		
		$file = BASE_PATH . '/cache/cache_index.lock';
		
		if(file_exists($file)) { 
			if( filemtime($file) > (time()-(100)) ) {
				@unlink($file);
			} else {
				return;
			}
		}
		
		$query = $db->select()
					->from('pins', array('max' => 'MAX(pin_id)', 'min' => 'MIN(pin_id)', 'total' => 'COUNT(pin_id)'))
					->limit(1);
		$max_min  = $db->fetchRow($query);
		
		file_put_contents($file, '');
		$pins_array = array();
		$pp = JO_Registry::get('config_front_limit');
		
		$loop = 50;
		if($max_min['total'] <= $pp) {
			$loop = 1;
			$pp = $max_min['total'];
		} else if($max_min['total'] <= $pp * $loop) {
			$loop = floor($max_min['total']/$pp);
		}
		
		for( $i = 1; $i <= $loop; $i++) {
			$start = ( $pp * $i ) - $pp;
// 			if(self::checkCache($start.'.'.$pp)) {
// 				continue;
// 			}
			
			$pins = array();
			while ( COUNT($pins) < $pp ) {
				$pin_id = mt_rand($max_min['min'], $max_min['max']);
				$pin_exist_query = $db->select()->from('pins','pin_id')->joinLeft('users', 'pins.user_id=users.user_id','')->joinLeft('boards', 'pins.board_id=boards.board_id','')->where('pin_id = ?', $pin_id);
				if($db->fetchOne($pin_exist_query) && !isset($pins_array[$pin_id])) {
					$pins[] = $pin_id;
					$pins_array[$pin_id] = true;
				}
			}
			
			self::setCache(array(
				'pins' => ($pins?implode(',',$pins): ''),
				'start_limit' => $start.'.'.$pp
			));
			
		}
		
		@unlink($file);

	}
	
	private function checkCache($start_limit) {
		$db = JO_Db::getDefaultAdapter();
		$check = $db->select()
					->from('cache_index', 'COUNT(`date`)')
					->where('start_limit = ?', $start_limit)
					->where('`date` >= ?', time())
					->limit(1);
		return $db->fetchOne($check);
	}
	
	private function setCache($data) {
		
// 		$cache_live = (int)JO_Registry::get('config_cache_live');
// 		if(!$cache_live) {
// 			return;
// 		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->delete('cache_index', array(
			'start_limit = ?' => $data['start_limit']
		));
		$db->insert('cache_index',array(
			'start_limit' => $data['start_limit'],
			//'date' => (time()+$cache_live),
			'data' => $data['pins']
		));
	}
	
	///// popular
	public static function CmdPopular() {
		$db = JO_Db::getDefaultAdapter();
		
		$cache_live = (int)JO_Registry::get('config_cache_live');
		if(!$cache_live) {
			return;
		}
		
		$file = BASE_PATH . '/cache/cache_popular_index.lock';
		
		if(file_exists($file)) { 
			if( filemtime($file) > (time()-($cache_live*10)) ) {
				@unlink($file);
			} else {
				return;
			}
		}
		
		$query = $db->select()
					->from('pins', array('pin_id','pin_id'))
					->where('pins.likes > ? AND pins.repins > ? AND pins.comments > ?', 0)
					->order('pins.views DESC')
					->limit(0, 3000);
		
		$max_min  = $db->fetchPairs($query);
		
		$total = count($max_min);
		
		file_put_contents($file, '');
		$pins_array = array();
		$pp = JO_Registry::get('config_front_limit');
		
		$loop = 20;
		if($total <= $pp) {
			$loop = 1;
			$pp = $total;
		} else if($total <= $pp * $loop) {
			$loop = floor($max_min['total']/$pp);
		}
		
		for( $i = 1; $i <= $loop; $i++) {
			$start = ( $pp * $i ) - $pp;
			if(self::checkCache($start.'.'.$pp)) {
				continue;
			}
			
			$pins = array();
			while ( COUNT($pins) < $pp ) {
				$pin_id = array_rand($max_min, 1);
				$pin_exist_query = $db->select()->from('pins','pin_id')->where('pin_id = ?', $pin_id);
				if($db->fetchOne($pin_exist_query) && !isset($pins_array[$pin_id])) {
					$pins[] = $pin_id;
					$pins_array[$pin_id] = true;
				}
			}
			
			self::setPopularCache(array(
				'pins' => ($pins?self::getPins(array('filter_id_in'=>implode(',',$pins))): array()),
				'start_limit' => $start.'.'.$pp
			));
			
		}
		
		@unlink($file);

	}
	
	private function checkPopularCache($start_limit) {
		$db = JO_Db::getDefaultAdapter();
		$check = $db->select()
					->from('cache_popular', 'COUNT(`date`)')
					->where('start_limit = ?', $start_limit)
					->where('`date` >= ?', time())
					->limit(1);
		return $db->fetchOne($check);
	}
	
	private function setPopularCache($data) {
		
		$cache_live = (int)JO_Registry::get('config_cache_live');
		if(!$cache_live) {
			return;
		}
		
		$db = JO_Db::getDefaultAdapter();
		$db->delete('cache_popular', array(
			'start_limit = ?' => $data['start_limit']
		));
		$db->insert('cache_popular',array(
			'start_limit' => $data['start_limit'],
			'date' => (time()+$cache_live),
			'data' => JO_Json::encode($data['pins'])
		));
	}
	
	/* CACHE */
	
	public static function generateCachePatch($pin) {
		try {
			$path = BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/');
			$file = false;
			if(!file_exists($path) || !is_dir($path)) {
				@mkdir($path, 0777, true);
			} else {
				$file = $path . $pin['pin_id'] . '.cache';
			}
			
			if(!$file) {
				return false;
			}
			
			$db = new JO_Db_Adapter_Pdo_Sqlite(array("dbname"=> $file));
			
			$db->query("CREATE TABLE IF NOT EXISTS pins_cache (row_id INTEGER PRIMARY KEY ASC, pin_id INTEGER KEY ASC, user_id INTEGER KEY ASC, html TEXT, template VARCHAR KEY ASC, date_added INTEGER KEY ASC);");
			
			return $file;
		} catch (JO_Exception $e) {
			return false;
		}
	}
	
	public static function generateCache($cache_file, $html) {
		
		if(file_exists($cache_file) && !self::getCache($cache_file)) {
			$pin_id = basename($cache_file, '.cache');
			try {
				$minifi = new JO_Minify_Html();
				$db = new JO_Db_Adapter_Pdo_Sqlite(array("dbname"=> $cache_file));
				$db->delete('pins_cache', array(
					'user_id' => (string)JO_Session::get('user[user_id]')
				));
				$db->insert('pins_cache', array(
					'pin_id' => (string)$pin_id,
					'user_id' => (string)JO_Session::get('user[user_id]'),
					'html' => (string)$minifi->minify( $html ),
					'template' => JO_Registry::get('template'),
					'date_added' => time()
				));
			} catch (JO_Exception $e) { }
		}
	}
	
	public static function getCache($cache_file) {
		if(file_exists($cache_file)) {
			$pin_id = basename($cache_file, '.cache');
			try {
				$db = new JO_Db_Adapter_Pdo_Sqlite(array("dbname"=> $cache_file));
				$query = $db->select()
							->from('pins_cache', array('html', 'date_added'))
							->where('pin_id = ?', (string)$pin_id)
							->where('user_id = ?', (string)JO_Session::get('user[user_id]'))
							->where('template = ?', JO_Registry::get('template'))
							->limit(1); 
				return $db->fetchRow($query);
			} catch (JO_Exception $e) {}
		} 
		return false;
	} 
	
	public static function rebuildCache($pins) { 
		if(!$pins) {
			return;
		}
		if(!is_array($pins)) {
			$pins = Model_Pins::getPins(array(
				'filter_pin_id' => $pins
			));  
		} else {
			$pins = Model_Pins::getPins(array(
				'filter_id_in' => implode(',',$pins)
			));  	
		}
		
		if($pins) {
			foreach($pins AS $pin) {
				self::deleteCache($pin);
				Helper_Pin::returnHtml($pin, true);
			}
		}
	}
	
	public static function deleteCache($pin) {
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . $pin['pin_id'] . '.cache');
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . 'author/' . $pin['pin_id'] . '.cache');
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . 'viewer/' . $pin['pin_id'] . '.cache');
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . 'not_loged/' . $pin['pin_id'] . '.cache');
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . 'activity/author/' . $pin['pin_id'] . '.cache');
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . 'activity/viewer/' . $pin['pin_id'] . '.cache');
		@unlink(BASE_PATH . '/cache/data/pins/' . WM_Date::format($pin['date_added'], 'yy/mm/dd/') . 'activity/not_loged/' . $pin['pin_id'] . '.cache');
	}
	
	
	//////////////////////////////////////////// v2 ////////////////////////////////////////////
    
    /**
     * @param string $table
     * @return array 
     */
    public static function describeTable($table, $row = '') {
        $db = JO_Db::getDefaultAdapter();
        $result = $db->describeTable($table);
        $data = array();
        foreach($result AS $res) {
            $data[$row . $res['COLUMN_NAME']] = $res['COLUMN_NAME'];
        }
        return $data;
    }
	
	
	public static function getPinV2($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = self::getListPinsQuery();
		
		$query->columns(array('pin_next'=>new JO_Db_Expr('('.$db->select()->from(array('next'=>'pins'),'pin_id')->where('pin_id > ?', (string)$pin_id)->order('pin_id ASC')->limit(1).')')));
		$query->columns(array('pin_prev'=>new JO_Db_Expr('('.$db->select()->from(array('prev'=>'pins'),'pin_id')->where('pin_id < ?', (string)$pin_id)->order('pin_id DESC')->limit(1).')')));
		
		if(JO_Session::get('user[user_id]')) {
			$query->columns(array('following_board'=>new JO_Db_Expr('('.$db->select()->from('users_following','COUNT(users_following_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->where('board_id = pins.board_id')->limit(1) .')')));
			$query->columns(array('following_user'=>new JO_Db_Expr('('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('user_id = ?', JO_Session::get('user[user_id]'))->where('following_id = pins.user_id')->limit(1).')')));
		} else {
			$query->columns(array('following_board'=>new JO_Db_Expr("'login'")));
			$query->columns(array('following_user'=>new JO_Db_Expr("'login'")));
		}
		
		$query->where('pins.pin_id = ?', (string)$pin_id);
		
		return $db->fetchRow($query);
	}
    
	/* new cache generate */
	public static function Cmd2() {
		$db = JO_Db::getDefaultAdapter();
	
		$file = BASE_PATH . '/cache/cache_index.lock';
	
		if(file_exists($file)) {
			if( filemtime($file) > (time()-(100)) ) {
				@unlink($file);
			} else {
				return;
			}
		}
	
		$query = $db->select()
			->from('pins', array('max' => 'MAX(pin_id)', 'min' => 'MIN(pin_id)', 'total' => 'COUNT(pin_id)'))
			->limit(1);
		$max_min  = $db->fetchRow($query);
	
		file_put_contents($file, '');
		$pins_array = array();
		$pp = Helper_Config::get('config_front_limit');
	
		$loop = 50*$pp;
		if($max_min['total'] <= $loop) {
			$loop = $max_min['total'];
		}
		
		$pins = array();
		while ( COUNT($pins) < $loop ) {
			$pin_id = mt_rand($max_min['min'], $max_min['max']);
			$pin_exist_query = $db->select()->from('pins','pin_id')->where('pin_id = ?', $pin_id);
			if($db->fetchOne($pin_exist_query) && !isset($pins_array[$pin_id])) {
				$pins[] = $pin_id;
				$pins_array[$pin_id] = true;
			}
		}
		
		self::setCache(array(
				'pins' => ($pins?implode(',',$pins): ''),
				'start_limit' => 'home'
		));
	
		@unlink($file);
	
	}
	
	///// popular
	public static function CmdPopular2() {
		$db = JO_Db::getDefaultAdapter();
		
		$file = BASE_PATH . '/cache/cache_popular_index.lock';
		
		if(file_exists($file)) { 
			if( filemtime($file) > (time()-(100)) ) {
				@unlink($file);
			} else {
				return;
			}
		}
		
		$query = $db->select()
					->from('pins', array('pin_id','pin_id'))
					->where('pins.likes > ? AND pins.repins > ? AND pins.comments > ?', 0)
					->order('pins.views DESC')
					->limit(3000);
		
		$max_min  = $db->fetchPairs($query);
		
		$total = count($max_min);
		
		file_put_contents($file, '');
		$pins_array = array();
		$pp = Helper_Config::get('config_front_limit');
		
		$loop = 50*$pp;
		if($total <= $loop) {
			$loop = $total;
		}
		
		$pins = array();
		while ( COUNT($pins) < $loop ) {
			$pin_id = array_rand($max_min, 1);
			$pin_exist_query = $db->select()->from('pins','pin_id')->where('pin_id = ?', $pin_id);
			if($db->fetchOne($pin_exist_query) && !isset($pins_array[$pin_id])) {
				$pins[] = $pin_id;
				$pins_array[$pin_id] = true;
			}
		}
		
		self::setCache(array(
				'pins' => ($pins?implode(',',$pins): ''),
				'start_limit' => 'popular'
		));
		
		@unlink($file);

	}
	
}

?>