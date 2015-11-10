<?php

class Model_Boards {
	
	public static function edit($board_id, $data) {
		set_time_limit(0);
		$db = JO_Db::getDefaultAdapter();
		
		$self = self::getBoard($board_id);
		if(!$self) {
			return false;
		}
		
		$update = Helper_Db::update('boards', $data, array('board_id = ?' => $board_id));
		
		self::generateBoardQuery($board_id, array('title' => $data['title'],'keyword' => $data['keyword']));
		
		$pins = Model_Pins::getPins(array(
			'filter_board_id' => $board_id	
		));
		
		if($self['title'] != $data['title']) {
			if($pins) {
				foreach($pins AS $pin) {
					Model_Pins::deleteCache($pin);
				}
			}
		}
		
		if($self['category_id'] != $data['category_id']) {
			$db->update('pins', array(
				'category_id' => $data['category_id'],
				'date_modified' => WM_Date::format(time(), 'yy-mm-dd H:i:s')
			), array('board_id = ?'=>$board_id));
		}
		
		return $update;
	}
	
	public static function getBoards($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from(array('p' => 'boards'))
					->joinLeft(array('u' => 'users'), 'p.user_id = u.user_id', array('fullname' => "CONCAT(firstname,' ', lastname)",'u.username', 'u.firstname', 'u.lastname'))
					->group('p.board_id');
//					->joinLeft(array('p' => 'boards'), 'p.board_id = b.board_id', array('board_title' => 'title'));
	
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
			'p.board_id',
		    'p.total_views',
		    'p.pins',
		    'p.followers',
		    'p.title'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('p.board_id' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_board_id']) && $data['filter_board_id']) {
			$query->where('p.board_id = ?', (int)$data['filter_board_id']);
		}
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('p.user_id = ?', (int)$data['filter_user_id']);
		}
		
    	if(isset($data['filter_board_name']) && $data['filter_board_name']) {
			$query->where("p.title LIKE ?", '%'.$data['filter_board_name'].'%');
		}
		
//		if(isset($data['filter_fullname']) && $data['filter_fullname']) {
//			$query->where("u.firstname LIKE ? OR u.lastname LIKE ?", '%'.$data['filter_fullname'].'%');
//		}
//		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where("u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ?", '%'.$data['filter_username'].'%');
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalBoards($data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from(array('p' => 'boards'), 'COUNT(p.board_id)')
					->joinLeft(array('u' => 'users'), 'p.user_id = u.user_id', array())
					->limit(1);
		
		////////////filter
		
		if(isset($data['filter_board_id']) && $data['filter_board_id']) {
			$query->where('p.board_id = ?', (int)$data['filter_board_id']);
		}
		
	if(isset($data['filter_board_name']) && $data['filter_board_name']) {
			$query->where("p.title LIKE ?", '%'.$data['filter_board_name'].'%');
		}
		
		if(isset($data['filter_user_id']) && $data['filter_user_id']) {
			$query->where('p.user_id = ?', (int)$data['filter_user_id']);
		}
		
    	if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where("u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ?", '%'.$data['filter_username'].'%');
		}
		
//		if(isset($data['filter_fullname']) && $data['filter_fullname']) {
//			$query->where("u.firstname LIKE ? OR u.lastname LIKE ?", '%'.$data['filter_fullname'].'%');
//		}
//		
//		if(isset($data['filter_username']) && $data['filter_username']) {
//			$query->where("u.username LIKE ?", '%'.$data['filter_username'].'%');
//		}
		
		return $db->fetchOne($query);
	}
	
	public static function getBoard($board_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('boards', array('*', "(SELECT keyword FROM url_alias WHERE query = 'board_id=".$board_id."' LIMIT 1) AS keyword"))
					->where('board_id = ?', $board_id)
					->limit(1);
		
		return $db->fetchRow($query);
		
	}
	
    public static function boardShared($board_id) {
		
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from(array('ub' => 'users_boards'), 'COUNT(ub.ub_id)')
					->where('ub.board_id = ?', $board_id)
					->where('ub.is_author = ?', '0')
					->limit(1);
		
		
		return $db->fetchOne($query);
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
		
		/*$update = array(
			'boards' => new JO_Db_Expr("(SELECT COUNT(board_id) FROM boards WHERE user_id = '".$board_info['user_id']."')")
		);*/
		
		//$db->update('users', $update, array('user_id=?'=>$board_info['user_id']));

		Helper_Db::update('users', array(
				'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id)'),
				'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id)'),
				'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
				'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
				'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
		), array('user_id=?'=>$board_info['user_id']));
		
		Model_Users::updateLatestPins($board_info['user_id']);
		
		return $del_boards;
		
	}
	
	public static function updateLatestPins($board_id, $pin_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		$board_info = self::getBoard($board_id);
		
		if($board_info) {
			$pins = Model_Pins::getPins(array(
				'filter_board_id' => $board_id,
				'filter_user_id' => $board_info['user_id'],
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
	
	/* SEO */
	
	public static function generateBoardQuery($board_id, $info = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!$info) {
			$info = self::getBoard($board_id);
		}
		
		if(!$info) {
			return;
		}
		
		if( ($cleared = trim(self::clear(trim(self::clear($info['keyword']))?$info['keyword']:$info['title']))) != '' ) {
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
		foreach(WM_Modules::getControllers( APPLICATION_PATH . '/modules/default/controllers/' ) AS $controller) {
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

	
	
}

?>