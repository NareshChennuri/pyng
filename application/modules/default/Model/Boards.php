<?php

class Model_Boards {
	
	public static function isViewedBoard($board_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('boards_views', 'COUNT(bv_id)')
					->where('board_id = ?', (string)$board_id)
					->limit(1);

		if((string)JO_Session::get('user[user_id]')) {
			$query->where("user_id = '" . (string)JO_Session::get('user[user_id]') . "' OR user_ip = '" . JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()) . "'");
		} else {
			$query->where("user_ip = ?", JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp()));
		}
		
		return $db->fetchOne($query);
	}
	
	public static function updateViewed($board_id) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!self::isViewedBoard($board_id)) {
			$db->update('boards', array(
				'views' => new JO_Db_Expr('views+1')
			), array('board_id = ?' => (string)$board_id));
			
			$db->insert('boards_views', array(
				'user_id' => (string)JO_Session::get('user[user_id]'),
				'date_added' => new JO_Db_Expr('NOW()'),
				'board_id' => (string)$board_id,
				'user_ip' => JO_Request_Server::encode_ip(JO_Request::getInstance()->getClientIp())
			));
		}
		
		$db->update('boards', array(
			'total_views' => new JO_Db_Expr('total_views+1')
		), array('board_id = ?' => (string)$board_id));
		
	}
	
	public static function isGroupBoard($board_id) {
		if(!$board_id) {
			return false;
		}
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('users_boards', new JO_Db_Expr('GROUP_CONCAT(DISTINCT user_id)'))
					->where('board_id = ?', $board_id)
					->group('board_id')
					->having('COUNT(user_id) > 1')
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function createBoard($data) {
		
		if(!isset($data['category_id']) || !$data['category_id']) {
			$data['category_id'] = JO_Registry::get('default_category_id');
		}
		
		$db = JO_Db::getDefaultAdapter();
		
		$count_followers = $db->select()
							->from('users_following_user', 'COUNT(ufu_id)')
							->where('following_id = ?', JO_Session::get('user[user_id]'));
		
		$db->insert('boards', array(
			'user_id' => isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]'),
			'title' => $data['title'],
			'date_added' => new JO_Db_Expr('NOW()'),
			'date_modified' => new JO_Db_Expr('NOW()'),
			'public' => 1,
			'category_id' => $data['category_id'],
			'followers' => new JO_Db_Expr('('.$count_followers.')')
		));
		
		$board_id = $db->lastInsertId();
		
		if(!$board_id) {
			return false;
		}
		
		$ins = $db->insert('users_boards', array(
			'user_id' => isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]'),
			'board_id' => $board_id,
			'is_author' => 1
		));
		
		if(isset($data['friends'])) {
			foreach($data['friends'] AS $fr) {
				$db->insert('users_boards', array(
					'user_id' => $fr,
					'board_id' => $board_id
				));
			}
		}
		
		if(!$ins) {
			$db->delete('boards', array(
				'board_id' => $board_id
			));
			$db->delete('users_boards', array(
				'board_id' => $board_id
			));
			return false;
		}
		
		$db->update('users', array(
			'boards' => new JO_Db_Expr("(SELECT COUNT(board_id) FROM boards WHERE user_id = '".( isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]') )."')")
		), array('user_id = ?' => isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]')));
		
		self::generateBoardQuery($board_id, array('title' => $data['title']));
		return array(
			'board_id' => $board_id,
			'title' => $data['title']
		);
		
	}
	
	public static function editBoard($board_id, $data) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$board_info = self::getBoard($board_id);
		if(!$board_info) {
			return;
		}
		
		$update = array(
			'date_modified' => new JO_Db_Expr('NOW()')
		);
		if(isset($data['title'])) {
			$update['title'] = (string)$data['title'];
		}
		if(isset($data['category_id'])) {
			$update['category_id'] = (string)$data['category_id'];
		}
		
		$result = $db->update('boards', $update, array('board_id = ?' => (string)$board_id));
		
		$usrd = $db->select()
				->from('users_boards')
				->where('board_id = ?',(string)$board_id);
		$usd = $db->fetchAll($usrd);
		$tmp = array();
		if($usd) {
			foreach($usd AS $e) {
				$tmp[$e['user_id']] = array(
					'allow' => $e['allow'],
					'sort_order' => $e['sort_order']
				);
			}
		}
		
		$db->delete('users_boards', array('board_id = ?' => (string)$board_id));
		
		$ins = $db->insert('users_boards', array(
			'user_id' => $board_info['user_id'],
			'board_id' => $board_id,
			'is_author' => 1,
			'sort_order' => (int)(isset($tmp[$board_info['user_id']]['sort_order'])?$tmp[$board_info['user_id']]['sort_order']:0)
		));
		
		if(isset($data['friends'])) {
			foreach($data['friends'] AS $fr) {
				$db->insert('users_boards', array(
					'user_id' => $fr,
					'board_id' => $board_id,
					'allow' => (int)(isset($tmp[$fr]['allow'])?$tmp[$fr]['allow']:0),
					'sort_order' => (int)(isset($tmp[$fr]['sort_order'])?$tmp[$fr]['sort_order']:0)
				));
			}
		}
		
		if($result && isset($data['category_id']) && $board_info['category_id'] != $data['category_id']) {
			$db->update('pins', array(
				'category_id' => $data['category_id'],
				'date_modified' => WM_Date::format(time(), 'yy-mm-dd H:i:s')
			), array('board_id = ?'=>$board_id));
		} else {
			$db->update('pins', array(
					'date_modified' => WM_Date::format(time(), 'yy-mm-dd H:i:s')
			), array('board_id = ?' => $board_id));
		}
		
		$db->update('boards', array(
			'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE board_id = boards.board_id)'),
			'followers' => new JO_Db_Expr('(SELECT COUNT(DISTINCT users_following_id) FROM users_following WHERE board_id = boards.board_id)')
		), array('board_id = ?' => $board_id));

		
		self::generateBoardQuery($board_id, array('title' => $data['title']));
		return true;
		
	}
	
//	public static function updateLatestPins($board_id, $pin_id) {
//		$db = JO_Db::getDefaultAdapter();
//		$board_info = self::getBoard($board_id);
//		
//		if($board_info) {
//			$latest = explode(',',$board_info['latest_pins']);
//			$latest_add = array($pin_id);
//			for($i=0; $i<min(15, count($latest)); $i++) {
//				if(isset($latest[$i]) && $latest[$i]) {
//					$latest_add[] = $latest[$i];
//				}
//			} 
//			$db->update('boards', array(
//				'latest_pins' => implode(',',$latest_add),
//				'pins' => new JO_Db_Expr('pins + 1')
//			), array('board_id = ?' => (string)$board_id));
//			
//		}
//	}
	
	public static function updateLatestPins($board_id, $pin_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		$board_info = self::getBoard($board_id);
		
		if($board_info) {
			$pins = Model_Pins::getPins(array(
				'filter_board_id' => $board_id,
				//'filter_user_id' => $board_info['user_id'],
				'sort' => 'DESC',
				'order' => 'pins.pin_id',
				'start' => 0,
				'limit' => 15
			));
			$latest_add = array();
			if($pins) {
				foreach($pins AS $p) {
					$latest_add[] = $p['pin_id'];
				}
			}
			
			$db->update('boards', array(
				'latest_pins' => implode(',',$latest_add),
				'pins' => new JO_Db_Expr("(SELECT COUNT(pin_id) FROM pins WHERE board_id = '".$board_id."')") // AND user_id = '".$board_info['user_id']."'
			), array('board_id = ?' => (string)$board_id));
			
		}
	}

	public static function getBoardId($title) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('boards', 'board_id')
					->where('title LIKE ?', $title)
					->where('user_id = ?', JO_Session::get('user[user_id]'));
		$board_id = $db->fetchOne($query);
		if(!$board_id) {
			$board_id = self::createBoard(array(
				'title' => (string)$title
			));
			$board_id = $board_id['board_id'];
		}
		return $board_id;
	}
	
	public static function getBoard($board_id/*, $user_id*/, $allow = false) {
		
		static $result = array();
		if(isset($result[$board_id/* . '_' . $user_id*/])) { return $result[$board_id/* . '_' . $user_id*/]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('boards')
							/*->joinLeft('users_boards', 'boards.board_id = users_boards.board_id')*/
							->where('boards.board_id = ?', (int)$board_id)
							/*->where('users_boards.user_id = ?', (int)$user_id)*/
							->limit(1);

		$data = $db->fetchRow($query);
		if(!$data) {
			return false;
		}
		
		$data['pins_array'] = array();
		if(trim($data['latest_pins'])) {
			$data['pins_array'] = $db->fetchAll($db->select()->from('pins')->where("pin_id IN ('?')", new JO_Db_Expr(implode("','", explode(',',$data['latest_pins']))))->order( ($data['cover'] ? 'FIELD(pin_id, "'.$data['cover'].'") DESC, pin_id DESC' : 'pin_id DESC') )->limit(15));
		}
		
		$data['board_users'] = array();
		$boards_users = $db->fetchAll($db->select()->from('users_boards')->where("board_id = ?", (string)$board_id)->where('user_id != ?', $data['user_id'])->where($allow?'allow = 1':'1=1'));
		if($boards_users) {
			foreach($boards_users AS $u) {
				$ud = Model_Users::getUser($u['user_id'], true);
				if($ud) {
					$data['board_users'][] = $ud;
				}
			}
		}
		
		$result[$board_id/* . '_' . $user_id*/] = $data;
		
		return $result[$board_id/* . '_' . $user_id*/];
	}
	
	public static function getBoardWithoutUser($board_id) {
		
		static $result = array();
		if(isset($result[$board_id])) { return $result[$board_id]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('boards')
							->where('boards.board_id = ?', (int)$board_id)
							->limit(1);
		$result[$board_id] = $db->fetchRow($query);
		return $result[$board_id];
	}
	
	public static function getBoards($data = array()) {
		
		$key = md5(serialize($data));
		
		static $result = array();
//		if(isset($result[$key])) { return $result[$key]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('boards');
		
		/*if(isset($data['friendly']) && $data['friendly']) {
			$query->joinLeft('users_boards', 'boards.board_id=users_boards.board_id AND is_author = 1', '')
			->group('boards.board_id');
		} elseif(isset($data['filter_user_id']) && !is_null($data['filter_user_id'])) {
			
		} else {
			$query->joinLeft('users_boards', 'boards.board_id=users_boards.board_id AND is_author = 1', '')
			->group('boards.board_id');
		}*/
	
		
		if(isset($data['where']) && $data['where'] instanceof JO_Db_Expr) {
			$query->where($data['where']);
		}
		
		if(isset($data['delete_request']) && $data['delete_request'] === true) {
			$query->where('boards.delete_request = 1');
		}
		
		if(isset($data['filter_id_in']) && $data['filter_id_in']) {
			$query->where('board_id = ?', (string)$data['filter_id_in']);
		}
		
		if(isset($data['filter_title']) && $data['filter_title']) {
			$query->where('boards.title LIKE ?', (string)$data['filter_title'] . '%');
		}
		
//		if(isset($data['friendly']) && $data['friendly']) {
//			if(isset($data['filter_user_id']) && !is_null($data['filter_user_id'])) {
//				$query->where("(user_id = '".(string)$data['filter_user_id']."' OR board_id IN (SELECT DISTINCT board_id FROM users_boards WHERE user_id = ? AND allow = 1))", (string)$data['friendly']);
//			} else {
//				$query->where('board_id IN (SELECT DISTINCT board_id FROM users_boards WHERE user_id = ? AND allow = 1)', (string)$data['friendly']);
//			}
//		} 
		
		if(isset($data['friendly']) && $data['friendly']) {
			if(isset($data['filter_user_id']) && !is_null($data['filter_user_id'])) {
				$query->where("(boards.user_id = '".(string)$data['filter_user_id']."' OR boards.board_id IN (SELECT DISTINCT board_id FROM users_boards WHERE user_id = ? AND allow = 1))", (string)$data['friendly']);
			} else {
				$query->where('boards.board_id IN (SELECT DISTINCT board_id FROM users_boards WHERE user_id = ? AND allow = 1)', (string)$data['friendly']);
			}
		} elseif(isset($data['filter_user_id']) && !is_null($data['filter_user_id'])) {
			if(isset($data['friendly']) && $data['friendly']) {
				$query->where("(boards.user_id = '".(string)$data['filter_user_id']."' OR boards.board_id IN (SELECT DISTINCT board_id FROM users_boards WHERE user_id = ? AND allow = 1))", (string)$data['friendly']);
			} else {
				$query->where('boards.user_id = ?', (string)$data['filter_user_id']);
			}
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
			'boards.board_id',
			'boards.title',
			'boards.sort_order',
			'boards.total_views',
			'users_boards.sort_order'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('boards.board_id' . $sort);
		}

		$results = $db->fetchAll($query);
		$result[$key] = array();
		if($results) {
			foreach($results AS $data) {
				$data['pins_array'] = array();
				if(trim($data['latest_pins'])) {
					$data['pins_array'] = $db->fetchAll($db->select()->from('pins')->where("pin_id IN ('?')", new JO_Db_Expr(implode("','", explode(',',$data['latest_pins']))))->order(($data['cover'] ? 'FIELD(pin_id, "'.$data['cover'].'") DESC, pin_id DESC' : 'pin_id DESC'))->limit(15));
				}
				
				$result[$key][] = $data;
			}
		}
		
		return $result[$key];
	}
	
	public static function allowEdit($board_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('users_boards', 'COUNT(ub_id)')
					->where('board_id = ?', (string)$board_id)
					->where('user_id = ?', (string)JO_Session::get('user[user_id]'))
					->where('allow = 1')
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function getBoardTitle($board_id) {
		
		static $result = array();
		if(isset($result[$board_id])) { return $result[$board_id]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('boards', 'title')
							->where('board_id = ?', (string)$board_id)
							->limit(1);
		$result[$board_id] = $db->fetchOne($query);
		return $result[$board_id];
	}
	
	/* SEO */
	
	public static function generateBoardQuery($board_id, $info = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!$info) {
			$info = self::getBoard($board_id);
		}
		
		if(!$info) {
			return;
		}
		
		if( ($cleared = trim(self::clear($info['title']))) != '' ) {
			$slug = $uniqueSlug = $cleared;
		} else {
			$slug = $uniqueSlug = 'board';
		}
		
		$index = 1;
		$db->query("DELETE FROM url_alias WHERE query = 'board_id=" . $board_id . "'");
		/*while (self::getTotalKey($uniqueSlug)) {
			$uniqueSlug = $slug . '-' . $index ++;
		}*/
		
		$uniqueSlug = self::renameIfExist($uniqueSlug);
		
		$db->insert('url_alias', array(
			'query' => 'board_id=' . (int)$board_id,
			'keyword' => $uniqueSlug,
			'path' => $uniqueSlug,
			'route' => 'boards/view'
		));
		
	}
	
	function renameIfExist($uniqueSlug) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('url_alias', array('keyword', 'keyword'))
					->where('keyword = ?', $uniqueSlug)
					->orWhere('keyword LIKE ?', $uniqueSlug . '-%');
		$array = $db->fetchPairs($query);
		foreach(WM_Modules::getControllers() AS $controller) {
			$array[$controller] = $controller;
		}
		$array['admin'] = 'admin';
		$array['default'] = 'default';
		
		$array = JO_Utf8::array_change_key_case_unicode($array);
		
		return self::rename_if_exists($array, mb_strtolower($uniqueSlug, 'utf-8'));
	}
	
	function rename_if_exists($array, $query) {
		$i = 0;
		
		$uniqueSlug = $query;
		while(isset($array[$uniqueSlug])) {
			$uniqueSlug = $query . '-' .++$i;
		}
		
		return $uniqueSlug;
	}
	
	public function clear($string) {
// 		$string = self::translateCirilic($string);
// 		$string = preg_replace('/[^a-z0-9\-\.]+/ium','-', $string);
		$string = preg_replace('/[\/\#\!\@\\\\)\(\?\'\"\:\;\>\<\$\,\.\&\%\*\=\|\{\}\[\]\^\`\~\+\ ]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '-');
	}
	
	public function translateCirilic($string) {
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
        return $string;
	}
	
	public function getTotalKey($keyword) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('url_alias', new JO_Db_Expr('COUNT(url_alias_id)'))
					->where("keyword = ?", (string)$keyword);
		return $db->fetchOne($query);
	}
	
	public function sort_order($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('boards', array(
			'sort_order' => (int)$sort_order
		), array('board_id = ?' => (string)$id, 'user_id = ?' => JO_Session::get('user[user_id]')));
	}
	
	public static function delete($board_id) {
		
    	$board_info = self::getBoard($board_id);
    	if(!$board_info) {
    		return;
    	}

		$deleted_pins = 0;
		
		$db = JO_Db::getDefaultAdapter();
		$pins_query = $db->select()
							->from('pins')
							->where('board_id = ?', $board_id);
		$pins = $db->fetchAll($pins_query);
		if($pins) {
			foreach($pins AS $pin) {
				$deleted = Model_Pins::delete($pin['pin_id']);
				if($deleted) {
					$deleted_pins++;
				}
			}
		}
		
		$db->delete('users_following', array('board_id = ?' => $board_id));
		$db->delete('users_following_ignore', array('board_id = ?' => $board_id));
		$del_boards = $db->delete('boards', array('board_id = ?' => $board_id));
		$db->delete('users_boards', array('board_id = ?' => $board_id));
		$db->delete('users_history', array('board_id = ?' => $board_id));
		$db->query("DELETE FROM url_alias WHERE query = 'board_id=" . $board_id . "'");
		
		$update = array(
			'boards' => new JO_Db_Expr("(SELECT COUNT(board_id) FROM boards WHERE user_id = '".$board_info['user_id']."')")
		);
		
		$db->update('users', $update, array('user_id=?'=>$board_info['user_id']));
		
		Model_Users::updateLatestPins($board_info['user_id']);

		return $del_boards;
		
	}
	
    public static function getInvBoards($data = array()) {
		
		$key = md5(serialize($data));
		
		static $result = array();
//		if(isset($result[$key])) { return $result[$key]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('users_boards')
							->joinLeft('boards', 'users_boards.board_id = boards.board_id')
							->joinLeft('users', 'boards.user_id = users.user_id', array('store AS ustore','user_id as uuser_id', 'firstname', 'lastname', 'username', 'avatar'))
							->where('users_boards.user_id = ?', (int)JO_Session::get('user[user_id]'))
							->where('users_boards.is_author = ?', '0')
							->where('users_boards.allow = ?', '0');

	
		
		if(isset($data['where']) && $data['where'] instanceof JO_Db_Expr) {
			$query->where($data['where']);
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
			'boards.board_id',
			'boards.title',
			'boards.sort_order',
			'boards.total_views',
		    'users_boards.ub_id'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('users_boards.ub_id' . $sort);
		}
		

		$results = $db->fetchAll($query);
		
		$result[$key] = array();
		if($results) {
			foreach($results AS $data) {
				$data['pins_array'] = array();
				if(trim($data['latest_pins'])) {
					$data['pins_array'] = $db->fetchAll($db->select()->from('pins')->where("pin_id IN ('?')", new JO_Db_Expr(implode("','", explode(',',$data['latest_pins']))))->order(($data['cover'] ? 'FIELD(pin_id, "'.$data['cover'].'") DESC, pin_id DESC' : 'pin_id DESC'))->limit(15));
				}
				
				$result[$key][] = $data;
			}
		}
		
		return $result[$key];
	}
	
    public static function getUsersBoard($data) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('users_boards')
					->where('ub_id = ?', (int)$data['ub_id'])
					->where('user_id =?', JO_Session::get('user[user_id]'))
					->where('allow = ?', '0')
					->where('board_id =?', (int)$data['board_id'])
					->limit(1);
		
		return $db->fetchRow($query);
	}
	
	
	//////////////////////////////////// V2 //////////////////////////////////////////////////
    public static function deleteUsersBoard($board_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$db->delete('users_boards', array('board_id = ?' => $board_id, 'user_id = ?' => JO_Session::get('user[user_id]')));
		
		return true;
	}
	
	public static function acceptUsersBoard($board_id) {
	    $db = JO_Db::getDefaultAdapter();
			$db->update('users_boards', array(
				'allow' => '1'
			), array('board_id = ?' => $board_id, 'user_id = ?' => JO_Session::get('user[user_id]')));
			
	}
	
	
	
}

?>