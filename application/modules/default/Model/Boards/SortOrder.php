<?php

class Model_Boards_SortOrder {
	
	public $affected_rows = null;

	public function __construct($ids = array(), $page = 1) {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$result = 0;
			
			$plus = (int)( Helper_Config::get('config_front_limit') * $page ) - Helper_Config::get('config_front_limit');
			if(is_array($ids)) {
				foreach($ids AS $sort_order => $id) {
					$result += Helper_Db::update('boards', array(
										'sort_order' => (int)($sort_order + $plus)
								), array('board_id = ?' => (string)$id, 'user_id = ?' => JO_Session::get('user[user_id]')));
				}
			}
				
			$this->affected_rows = $result;
				
			$db->commit();
				
		} catch ( JO_Exception $e ) {
			$db->rollBack();
		}
		
	}
	
}

?>