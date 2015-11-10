<?php

class Model_Crons_All {

	public function stats() {
		$db = JO_Db::getDefaultAdapter();
		
		Helper_Db::delete('statistics', array());
		
		Helper_Db::query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(pin_id),1 FROM pins GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		Helper_Db::query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(user_id),2 FROM users GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		Helper_Db::query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(board_id),3 FROM boards GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		
	}
	
	public static function updateStats() {
		$db = JO_Db::getDefaultAdapter();
		
		Helper_Db::delete('users_following_user', array(
			'user_id NOT IN (SELECT user_id FROM users)' => 1
		));
		
		Helper_Db::delete('users_following_user', array(
			'following_id NOT IN (SELECT user_id FROM users)' => 1
		));
		
		$config_private_boards = Helper_Config::get('config_private_boards');
		
		$db->update('users', array(
			'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE user_id = users.user_id '.($config_private_boards ? ' AND (public = 1 OR user_id = users.user_id)' : '').')'),
			'boards' => new JO_Db_Expr('(SELECT COUNT(DISTINCT board_id) FROM boards WHERE user_id = users.user_id '.($config_private_boards ? ' AND (public = 1 OR user_id = users.user_id)' : '').')'),
			'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins_likes WHERE user_id = users.user_id)'),
			'following' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT following_id) FROM users_following_user WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT following_id) FROM users_following WHERE user_id = users.user_id AND following_id != users.user_id LIMIT 1) )'),
			'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = users.user_id AND user_id != users.user_id LIMIT 1) )'),
			'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE user_id = users.user_id), \',\', 15 ) )')
		));
		
		$db->update('boards', array(
			'pins' => new JO_Db_Expr('(SELECT COUNT(DISTINCT pin_id) FROM pins WHERE board_id = boards.board_id '.($config_private_boards ? ' AND (public = 1 OR board_id = boards.board_id)' : '').')'),
			'followers' => new JO_Db_Expr('( (SELECT COUNT(DISTINCT user_id) FROM users_following_user WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) + (SELECT COUNT(DISTINCT user_id) FROM users_following WHERE following_id = boards.user_id AND user_id != boards.user_id LIMIT 1) - (SELECT COUNT(DISTINCT user_id) FROM users_following_ignore WHERE following_id = boards.user_id AND board_id = boards.board_id AND user_id != boards.user_id LIMIT 1) )'),
			'latest_pins' => new JO_Db_Expr('( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(pin_id ORDER BY `pin_id` DESC) FROM `pins` WHERE board_id = boards.board_id), \',\', 15 ) )')
		));
		
		$db->update('pins', array(
			'likes' => new JO_Db_Expr('(SELECT COUNT(DISTINCT user_id) FROM pins_likes WHERE pin_id = pins.pin_id)'),
			'comments' => new JO_Db_Expr('(SELECT COUNT(DISTINCT comment_id) FROM pins_comments WHERE pin_id = pins.pin_id)'),
			'latest_comments' => new JO_Db_Expr(' ( SUBSTRING_INDEX( (SELECT GROUP_CONCAT(comment_id ORDER BY `comment_id` ASC) FROM `pins_comments` WHERE pin_id = pins.pin_id), \',\', 10 ) ) ')
		));
		
		$repins_query = $db->select()
							->from('pins', array('repin_from', 'COUNT(pin_id)'))
							->where('repin_from > 0')
							->group('repin_from');
		
		$repins = $db->fetchPairs($repins_query);
		foreach($repins AS $pin_id => $repins) {
			Helper_Db::update('pins', array(
				'repins' => $repins
			), array('pin_id = ?' => $pin_id));
		}
	}
	
	public static function deletePinImagesFromStorage() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_images_for_delete')
					->limit(100);
		$res = $db->fetchAll($query);
		if($res) {
			foreach($res AS $v) {
				try {
					call_user_func(array($v['store'], 'deletePinImage'), $v );
					Helper_Db::delete('pins_images_for_delete', array('pin_id = ?' => $v['pin_id'], 'gallery_id = ?' => $v['gallery_id']));
				} catch (JO_Exception $e) {
					
				}
			}
		}
	}
	
}

?>