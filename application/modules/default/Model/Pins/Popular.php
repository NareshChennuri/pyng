<?php

class Model_Pins_Popular extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListPinsQuery();
	
		//// load from cache
		$check = $db->select()
			->from('cache_index')
			->where('start_limit = ?', 'popular')
			->limit(1);
		$cache = $db->fetchRow($check);
		if(!isset($cache['data']) || !$cache['data']) {
			$this->data = array();
			return $this;
		}
		
		if($cache && $cache['data']) {
			$query->where('pins.pin_id IN ('.$cache['data'].')');
		} else {
			$query->where('pins.pin_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
	
		//sort and limit add to query from Model_Pins_Abstract
		$query = self::sortOrderLimit($query, $data);

		$query->reset(JO_Db_Select::ORDER);
		$query->order(new JO_Db_Expr('FIELD(pins.pin_id,'.$cache['data'].')'));
		
		$this->data = $db->fetchAll($query);
		
// 		parent::__construct($db->fetchAll($query));
	
	}
	
}

?>