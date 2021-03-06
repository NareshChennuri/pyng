<?php

class Model_Pins_PinsThumbsForFollowersAndFollowing extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		//select default pin data
		$query = self::getListPinsQueryLite();
		
		if(isset($data['pins']) && count($data['pins'])) {
			$query->where('pins.pin_id IN (?)', new JO_Db_Expr(implode(',', $data['pins'])));
		} else {
			$query->where('pins.pin_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		//sort and limit add to query from Model_Pins_Abstract
		$query->order('pins.pin_id DESC');
		
		$this->data = $db->fetchAll($query);
		
// 		parent::__construct($db->fetchAll($query));
	}

}

?>