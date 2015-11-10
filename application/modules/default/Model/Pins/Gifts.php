<?php

class Model_Pins_Gifts extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListPinsQuery();
	
		if(isset($data['filter_price_from']) && (int)$data['filter_price_from']) {
			$query->where('pins.price >= ?', (int)$data['filter_price_from']);
		} else {
			$query->where('pins.price > 0.0000');
		}
		
		if(isset($data['filter_price_to']) && (int)$data['filter_price_to']) {
			$query->where('pins.price <= ?', (int)$data['filter_price_to']);
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