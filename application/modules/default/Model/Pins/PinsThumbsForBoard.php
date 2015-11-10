<?php

class Model_Pins_PinsThumbsForBoard extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		//select default pin data
		$query = self::getListPinsQueryLite();
		
		if(isset($data['cover']) && $data['cover']) {
			$query->where('pins.pin_id = ?', (string)$data['cover']);
		} else {
			$query->where('pins.pin_id = 0');
		}
		
		if(isset($data['pins']) && count($data['pins'])) {
			$query->orWhere('pins.pin_id IN (?)', new JO_Db_Expr(implode(',', $data['pins'])));
		} else {
			$query->orWhere('pins.pin_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		//sort and limit add to query from Model_Pins_Abstract
		if(isset($data['cover']) && $data['cover']) {
			if(isset($data['pins']) && count($data['pins'])) {
				$query->order(new JO_Db_Expr('FIELD(pins.pin_id,'.(string)$data['cover'].','.implode(',', $data['pins']).'), pins.pin_id DESC'));
			} else {
				$query->order(new JO_Db_Expr('FIELD(pins.pin_id,'.(string)$data['cover'].'), pins.pin_id DESC'));
			}
		} else {
			$query->order('pins.pin_id DESC');
		}
		
		$this->data = $db->fetchAll($query);
		
// 		parent::__construct($db->fetchAll($query));
	}
	
}

?>