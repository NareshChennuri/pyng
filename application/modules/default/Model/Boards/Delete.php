<?php

class Model_Boards_Delete {

	public $affected_rows = null;
	
	public function __construct( $board_id ) {
		
		$db= JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$board_info = new Model_Boards_Board($board_id);
			if(!$board_info->count()) {
				return $this;
			}
			
			$result = null;
			
			if($board_info['board_pins'] > 0) {
				$pins_query = $db->select()
					->from('pins')
					->where('board_id = ?', $board_id)
					->where('user_id = ?', $board_info['user_user_id']);
				$pins = $db->fetchAll($pins_query);
				if($pins) {
					foreach($pins AS $pin) {
						$deleted = new Model_Pins_Delete($pin['pin_id']);
						if(!$result) { $result = $deleted; }
					}
				}
			}
			
			$res = Helper_Db::delete('users_following', array('board_id = ?' => $board_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('users_following_ignore', array('board_id = ?' => $board_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('users_boards', array('board_id = ?' => $board_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('users_history', array('board_id = ?' => $board_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('url_alias', array('query = ?' => 'board_id=' . $board_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('boards', array('board_id = ?' => $board_id));
			if(!$result) { $result = $res; }
			
			$res = Helper_Db::update('users', array(
					'boards' => new JO_Db_Expr("(SELECT COUNT(board_id) FROM boards WHERE user_id = '".$board_info['user_user_id']."')")
			), array('user_id = ?' => $board_info['user_user_id']));
			if(!$result) { $result = $res; }
		
			if($board_info['board_pins'] > 0) {
				///////////////// update latest pins for user /////////////////////
				new Model_Users_UpdateLatestPins($board_info['user_user_id']);
			}
				
			$this->affected_rows = $result;
			
			$db->commit();
			
		} catch ( JO_Exception $e ) {
			$db->rollBack();
		}
	}
	
}

?>