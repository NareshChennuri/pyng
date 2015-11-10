<?php

class Model_Boards_Create {

	public $board_id = null;
	public $error;
	
	public function __construct( $data = array() ) {
		
		$db= JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$data['user_id'] = isset($data['user_id'])?$data['user_id'] : JO_Session::get('user[user_id]');
			$data['public'] = isset($data['public']) ? (int)$data['public'] : 1;
			$data['date_added'] = date('Y-m-d H:i:s');
			$board_id = Helper_Db::create('boards', $data);
			
			if(!$board_id) {
				return $this;
			}
			
			Helper_Db::insert('users_boards', array(
					'user_id' => isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]'),
					'board_id' => $board_id,
					'is_author' => 1
			));
			
			if(isset($data['friends'])) {
				foreach($data['friends'] AS $fr) {
					Helper_Db::insert('users_boards', array(
							'user_id' => $fr,
							'board_id' => $board_id
					));
				}
			}
			
			$config_private_boards = Helper_Config::get('config_private_boards');
			Helper_Db::update('users', array(
				'boards' => new JO_Db_Expr("(SELECT COUNT(board_id) FROM boards WHERE user_id = '".( isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]') )."' ".($config_private_boards ? ' AND public = 1' : '').")")		
			), array('user_id = ?' => isset($data['user_id'])?(string)$data['user_id']:JO_Session::get('user[user_id]')));
			
			////autoseo
			new Model_Boards_Autoseo($board_id);
			
			$this->board_id = $board_id;
			
			$db->commit();
			
		} catch ( JO_Exception $e ) {
			$db->rollBack();
			$this->error = $e->getMessage();
		}
	}
	
}

?>