<?php

class Model_Pins_Likes extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListPinsQuery();
	
		if(isset($data['filter_like_pin_id']) && !is_null($data['filter_like_pin_id'])) {
			$query->where('pins.pin_id IN (' . new JO_Db_Expr($db->select()->from('pins_likes','pin_id')->where('user_id = ?')) . ')', (string)$data['filter_like_pin_id']);
		} else {
			$query->where('pins.pin_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
	
		//sort and limit add to query from Model_Pins_Abstract
		$query = self::sortOrderLimit($query, $data);
		
		$this->data = $db->fetchAll($query);
		
// 		parent::__construct($db->fetchAll($query));
	
	}
	
}

?>