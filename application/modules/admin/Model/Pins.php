<?php

class Model_Pins {
	
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
	
	public static function changeStatus($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$pin_info = self::getPin($pin_id);
		self::deleteCache($pin_info);
		
		return $db->update('pins', array(
			'vip' => new JO_Db_Expr('IF(vip = 1, 0, 1)')
		), array('pin_id = ?' => (string)$pin_id));
		
	}
	
	public static $searchWordLenght = 3;
	
	private static $thumb_sizes = array(
		'75x75' => '_A',
		'194x0' => '_B',
		'223x150' => '_C',
		'582x0' => '_D'
	);
	
	public static function edit($pin_id, $data) {

		$pin_info = self::getPin($pin_id);
		if(!$pin_info) {
			return;
		}
		
		$db = JO_Db::getDefaultAdapter();
		
		$date_modified = WM_Date::format(time(), 'yy-mm-dd H:i:s');
		
		$board_info = Model_Boards::getBoard($data['board_id']);
		$source_id = Model_Source::getSourceByUrl($data['from']);
		
		/* price */
		$price = $pin_info['price'];
		/*if( preg_match('/(\$|\£|\€|\¥|\₪|zł|\฿)([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/', $data['price'], $match) ) {
			$price_tmp = trim(str_replace(trim($match[1]), '', $match[0]));
			$currency = self::getCurrencyBySimbol(trim($match[1]));
			if($currency) {
				$price = round( $price_tmp / $currency, 4 );
			}
		}*/
		
		if(isset($pin_info['price']) && $pin_info['price']) {
			$currencies = WM_Currency::getCurrencies();
			$price_left = array();
			$price_right = array();
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
				if(!$pin_info['price'] && $price_right) {
					if( preg_match('/(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?(' . implode('|', $price_right) . ')/', $data['price'], $match) ) {
						$price_tmp = trim(str_replace(trim($match[2]), '', $match[0]));
						$currency = self::getCurrencyBySimbol(trim($match[2]));
						if($currency) {
							$price = round( $price_tmp / $currency, 4 );
						}
					}
				}
			}
		}
		
		$data['is_video'] = 'false';
		$help_video = new Helper_AutoEmbed();
		if($help_video->parseUrl($data['from'])) {
			$data['is_video'] = 'true';
		}
		
		$is_update = $db->update('pins', array(
			'category_id' => (string)$board_info['category_id'],
			'board_id' => $data['board_id'],
			'date_modified' => $date_modified,
			'from' => $data['from'],
			'from_md5' => md5($data['from']),
			'description' => $data['description'],
			'price' => $price,
			'is_video' => ($data['is_video'] == 'true' ? 1 : 0),
			'source_id' => isset($data['source_id']) ? $data['source_id'] : $source_id,
			'date_modified' => new JO_Db_Expr('NOW()')
		), array('pin_id = ?' => $pin_id));	
		
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
		
		Model_Users::updateLatestPins($pin_info['user_id']);
		
		self::deleteCache($pin_info);
	}
	
	/**
	 * @param JO_Db_Select $query
	 * @param array $data
	 * @return JO_Db_Select
	 */
	private static function FilterBuilder(JO_Db_Select $query, $data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		if(isset($data['filter_pin_id']) && $data['filter_pin_id']) {
			$query->where('p.pin_id = ?', (string)$data['filter_pin_id']);
		}
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('p.user_id = ?', (string)$data['filter_user_id']);
		}
		
		if(isset($data['filter_fullname']) && $data['filter_fullname']) {
			$query->where('u.firstname LIKE ? OR u.lastname LIKE ?', '%'.$data['filter_fullname'].'%');
		}
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('u.username LIKE ?', '%'.$data['filter_username'].'%');
		}
		
		if(isset($data['filter_board']) && $data['filter_board']) {
			$query->where('b.title LIKE ?', '%'.$data['filter_board'].'%');
		}
		
		if(isset($data['filter_board_id']) && !is_null($data['filter_board_id'])) {
			$query->where('p.board_id = ?', (string)$data['filter_board_id']);
		}
		
		if(isset($data['filter_description']) && $data['filter_description']) {
			$words = JO_Utf8::str_word_split( mb_strtolower($data['filter_description'], 'utf-8') , self::$searchWordLenght);
			
			if( count($words) > 0 ) {
				
				$sub = "SELECT `dic_id`, `dic_id` FROM `pins_dictionary` `d` WHERE ( ";
				foreach($words AS $key => $word) {
					if($key) {
						$sub .= ' OR ';
					}
					$sub .= "`d`.`word` = " . $db->quote($word) . "";
				}
				$sub .= ')';
				
				$dicts = $db->fetchPairs($sub);
				
				$tmp_dic_ids = array();
				if(COUNT($dicts) > 0) { 
					$query->joinLeft('pins_invert', 'p.pin_id = pins_invert.pin_id', 'dic_id')
					->where('pins_invert.`dic_id` IN (' . implode(',', $dicts) . ')')
					->group('p.pin_id');
				} else {
					$query->where('p.pin_id = 0');
				}
				
			} else {
				$query->where('p.pin_id = 0');
			}
		}
		
		return $query;
	}
	
	public static function getPins($data = array()) {
		$db = JO_Db::getDefaultAdapter();
    
		if( (isset($data['filter_fullname']) && $data['filter_fullname']) || (isset($data['filter_username']) && $data['filter_username']) ) {
			
			$query = $db
					->select()
					->from(array('u' => 'users'), array('fullname' => "CONCAT(firstname,' ', lastname)",'u.username'))
					->joinRight(array('p' => 'pins'), 'p.user_id = u.user_id')
					->joinLeft(array('b' => 'boards'), 'p.board_id = b.board_id', array('board_title' => 'title'));
	
		} elseif(isset($data['filter_board']) && $data['filter_board']) {
			
			$query = $db
					->select()
					->from(array('b' => 'boards'), array('board_title' => 'title'))
					->joinRight(array('p' => 'pins'), 'p.board_id = b.board_id')
					->joinLeft(array('u' => 'users'), 'p.user_id = u.user_id', array('fullname' => "CONCAT(firstname,' ', lastname)",'u.username'));
	
		} else {
			
			$query = $db
					->select()
					->from(array('p' => 'pins'))
					->joinLeft(array('u' => 'users'), 'p.user_id = u.user_id', array('fullname' => "CONCAT(firstname,' ', lastname)",'u.username'))
					->joinLeft(array('b' => 'boards'), 'p.board_id = b.board_id', array('board_title' => 'title'));
	
		}
		
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
			'p.pin_id',
			'p.price',
			'p.likes',
			'p.comments',
			'p.vip'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('p.pin_id' . $sort);
		}
		
		////////////filter
		
		$query = self::FilterBuilder($query, $data);
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalPins($data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
        
		if( (isset($data['filter_fullname']) && $data['filter_fullname']) || (isset($data['filter_username']) && $data['filter_username']) ) {
			
			$query = $db
					->select()
					->from(array('u' => 'users'),'')
					->joinRight(array('p' => 'pins'), 'p.user_id = u.user_id', 'COUNT(p.pin_id)')
					->joinLeft(array('b' => 'boards'), 'p.board_id = b.board_id', '');
	
		} elseif(isset($data['filter_board']) && $data['filter_board']) {
			
			$query = $db
					->select()
					->from(array('b' => 'boards'), '')
					->joinRight(array('p' => 'pins'), 'p.board_id = b.board_id', 'COUNT(p.pin_id)')
					->joinLeft(array('u' => 'users'), 'p.user_id = u.user_id', '');
	
		} else {
			
			$query = $db
					->select()
					->from(array('p' => 'pins'), 'COUNT(p.pin_id)')
					->joinLeft(array('u' => 'users'), 'p.user_id = u.user_id', '')
					->joinLeft(array('b' => 'boards'), 'p.board_id = b.board_id', '');
	
		}
		
//		$query = $db
//					->select()
//					->from(array('p' => 'pins'), 'COUNT(p.pin_id)')
//					->limit(1);
		
		////////////filter
		
		$query = self::FilterBuilder($query, $data);
		
		return $db->fetchOne($query);
	}
	
	public static function getPin($pin_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins')
					->where('pin_id = ?', $pin_id)
					->limit(1);
		
		return $db->fetchRow($query);
		
	}
	
	public static function deleteFromServer($image) {
		if(JO_Registry::get('enable_amazon')) {
			$s3 = new JO_Api_Amazon(JO_Registry::get('awsAccessKey'), JO_Registry::get('awsSecretKey'));
			$s3->putBucket(JO_Registry::get('bucklet'), JO_Api_Amazon::ACL_PUBLIC_READ);
			if($s3->getBucketLogging(JO_Registry::get('bucklet'))) {
				$s3->deleteObject(JO_Registry::get('bucklet'), $image);
			}
		}
	}
	
	public static function deleteImagesAmazon($image) {
		if(!$image) {
			return;
		}
		
		$ext = strtolower(strrchr($image,"."));
		
		$thumbs = array($image);
		foreach(self::$thumb_sizes AS $size => $key) {
			$thumbs[] = preg_replace('/'.$ext.'$/i',$key.$ext,$image);
		}
		
		foreach($thumbs AS $thumb) {
			self::deleteFromServer($thumb);
		}
	}
	
	public static function delete($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		$pin_info = self::getPin($pin_id);
		if(!$pin_info) {
			return false;
		}

		if($pin_info['image']) {
			$res = Helper_Db::create('pins_images_for_delete', array(
					'pin_id' => $pin_info['pin_id'],
					'date_added' => $pin_info['date_added'],
					'image' => $pin_info['image'],
					'store' => $pin_info['store']
			));
		}
		
		$comments = Model_Comments::getComments(array(
			'filter_pin_id' => $pin_id,
		));
		
		if($comments) {
			foreach($comments AS $comment) {
				$db->delete('pins_comments', array('comment_id = ?' => $comment['comment_id']));
				$db->delete('pins_reports_comments', array('comment_id = ?' => $comment['comment_id']));
			}
		}

		$del = $db->delete('pins', array('pin_id = ?' => $pin_id));
		if(!$del) {
			return false;
		} else {
			
			
			$db->query("INSERT INTO `pins_images_for_delete`(`pin_id`, `gallery_id`, `date_added`, `image`, `store`) SELECT `pin_id`, `gallery_id`, '".$pin_info['date_added']."', `image`, `store` FROM `pins_gallery` WHERE `pin_id` = '".$pin_id."'");
				
			$db->delete('pins_invert', array('pin_id = ?' => $pin_id));
			$db->delete('pins_likes', array('pin_id = ?' => $pin_id));
			$db->delete('pins_reports', array('pin_id = ?' => $pin_id));
			$db->delete('pins_views', array('pin_id = ?' => $pin_id));
			$db->delete('users_history', array('pin_id = ?' => $pin_id));
			$db->delete('pins_gallery', array('pin_id = ?' => $pin_id));
			$db->delete('pins_images', array('pin_id = ?' => $pin_id));
			
			
			$config_private_boards = Helper_Config::get('config_private_boards');
			
			Helper_Db::update('users', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
					'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
					'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
					'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )'),
					'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE user_id = users.user_id), \',\', 15 ) )')
			), array('user_id=?'=>$pin_info['user_id']));
			
			
			Helper_Db::update('boards', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE board_id = boards.board_id '.($config_private_boards ? ' AND public = 1' : '').')'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM users_following_ignore WHERE following_id = boards.user_id AND board_id = boards.board_id AND user_id != boards.user_id LIMIT 1) )'),
					'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE board_id = boards.board_id), \',\', 15 ) )')
			), array('board_id=?'=>$pin_info['board_id']));
			
			self::deleteCache($pin_info);
			
			return true;
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
	
    public static function getPinsR($data = array()) {
		$db = JO_Db::getDefaultAdapter();
    
		
			$query = $db
					->select()
					->from(array('rp' => 'pins_reports'))
					->joinLeft(array('prc' => 'pins_reports_categories'), 'rp.prc_id = prc.prc_id', array('category_title' => 'title'))
					->joinLeft(array('p' => 'pins'), 'p.pin_id = rp.pin_id')
					->joinLeft(array('u' => 'users'), 'rp.user_id = u.user_id', array('fullname' => "CONCAT(firstname,' ', lastname)",'u.username'))
					->joinLeft(array('b' => 'boards'), 'p.board_id = b.board_id', array('board_title' => 'title'));
	
		
		
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
			'p.pin_id',
		    'rp.date_added'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('rp.date_added' . $sort);
		}
		
        if(isset($data['filter_pr_id']) && $data['filter_pr_id']) {
			$query->where('pr_id = ?', (int)$data['filter_pr_id']);
		}
		
        if(isset($data['filter_prc_id']) && $data['filter_prc_id']) {
			$query->where('rp.prc_id = ?', (int)$data['filter_prc_id']);
		}
		

		return $db->fetchAll($query);
	}
	
	public static function getTotalPinsR($data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
        
		
			$query = $db
					->select()
					->from(array('p' => 'pins_reports'), 'COUNT(p.pin_id)');

		
//		$query = $db
//					->select()
//					->from(array('p' => 'pins'), 'COUNT(p.pin_id)')
//					->limit(1);
		
		////////////filter
		
    	if(isset($data['filter_pr_id']) && $data['filter_pr_id']) {
			$query->where('pr_id = ?', (int)$data['filter_pr_id']);
		}
		
		return $db->fetchOne($query);
	}
	
    public static function deleteR($report_id) {
		$db = JO_Db::getDefaultAdapter();
			$db->delete('pins_reports', array('pr_id = ?' => $report_id));
			
			return true;
		
	}
	
    public static function getRPin($report_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_reports')
					->where('pr_id = ?', $report_id)
					->limit(1);
		
		return $db->fetchRow($query);
		
	}
	
    public static function deleteRP($report_id) {
    		$rp = self::getRPin($report_id);
    		self::delete($rp['pin_id']);
			
			return true;
		
	}
	
    public static function getPinReportCategories() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_reports_categories', array('prc_id', 'title'))
					->order('sort_order ASC');
		return $db->fetchAll($query);
	}

	
	
}

?>