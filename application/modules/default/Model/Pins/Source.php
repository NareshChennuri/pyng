<?php

class Model_Pins_Source extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListPinsQuery();
	
		if(isset($data['filter_source_id']) && !is_null($data['filter_source_id'])) {
			$query->where('pins.source_id = ?', (string)$data['filter_source_id']);
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