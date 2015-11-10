<?php

class Model_Boards_Edit {
	
	public $affected_rows = null;

	public function __construct($board_id, $data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$board_info = new Model_Boards_Board($board_id);
			if(!$board_info->count()) {
				return $this;
			}
			
			$data['date_modified'] = WM_Date::format(time(), 'yy-mm-dd H:i:s');
			
			$result = Helper_Db::update('boards', $data, array('board_id = ?' => (string)$board_id));
			
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
			
			Helper_Db::delete('users_boards', array('board_id = ?' => (string)$board_id));
			
			$ins = Helper_Db::insert('users_boards', array(
					'user_id' => $board_info['user_user_id'],
					'board_id' => $board_id,
					'is_author' => 1,
					'sort_order' => (int)(isset($tmp[$board_info['user_user_id']]['sort_order'])?$tmp[$board_info['user_user_id']]['sort_order']:0)
			));
			
			if(!$result) {
				$result = $ins;
			}
			
			if(isset($data['friends'])) {
				foreach($data['friends'] AS $fr) {
					$ins = Helper_Db::insert('users_boards', array(
							'user_id' => $fr,
							'board_id' => $board_id,
							'allow' => (int)(isset($tmp[$fr]['allow'])?$tmp[$fr]['allow']:0),
							'sort_order' => (int)(isset($tmp[$fr]['sort_order'])?$tmp[$fr]['sort_order']:0)
					));
					if(!$result) {
						$result = $ins;
					}
				}
			}
			
			if($result && isset($data['category_id']) && $board_info['user_category_id'] != $data['category_id']) {
				$res = Helper_Db::update('pins', array(
						'category_id' => $data['category_id'],
						'date_modified' => $data['date_modified']
				), array('board_id = ?' => $board_id));
				if(!$result) {
					$result = $res;
				}
			} else {
				$res = Helper_Db::update('pins', array(
						'date_modified' => $data['date_modified']
				), array('board_id = ?' => $board_id));
				if(!$result) {
					$result = $res;
				}
			}
			
			$res = Helper_Db::update('boards', array(
					'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE board_id = boards.board_id)'),
					'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM users_following_ignore WHERE following_id = boards.user_id AND board_id = boards.board_id AND user_id != boards.user_id LIMIT 1) )')
			), array('board_id = ?' => $board_id));
			if(!$result) {
				$result = $res;
			}
			
			Helper_Db::update('users', array(
					'boards' => new JO_Db_Expr("(SELECT COUNT(board_id) FROM boards WHERE user_id = '".( isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]') )."')")
			), array('user_id = ?' => $board_info['user_user_id']));
				
			//private
			$config_private_boards = Helper_Config::get('config_private_boards');
			if(isset($data['public']) && $data['public'] != $board_info['board_public'] ) {
				Helper_Db::update('pins', array(
					'public' => $data['public']	
				), array('board_id = ?' => $board_id));
				
				Helper_Db::update('users', array(
						'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
						'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND public = 1' : '').')'),
						'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
						'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
						'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )')
				), array('user_id = ?' => $board_info['user_user_id']));
				
			}
			
			////autoseo
			new Model_Boards_Autoseo($board_id);
			
			$this->affected_rows = $result;
			
			$db->commit();
			
		} catch ( JO_Exception $e ) {
			$db->rollBack();
		}
		
	}
	
}

?>