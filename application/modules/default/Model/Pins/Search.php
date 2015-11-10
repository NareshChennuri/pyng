<?php

class Model_Pins_Search extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListPinsQuery();
	
		if(isset($data['filter_description']) && trim($data['filter_description'])) {
			$words = JO_Utf8::str_word_split( mb_strtolower($data['filter_description'], 'utf-8') , self::$searchWordLenght);
			
			if( count($words) > 0 ) {
	
				$sub = "SELECT `dic_id`, `dic_id` FROM `pins_dictionary` `d` WHERE ( ";
				foreach($words AS $key => $word) {
					if($key) {
						$sub .= ' OR ';
					}
					$sub .= "`d`.`word` = " . $db->quote($word) . " OR `d`.`word` LIKE " . $db->quote('%'.$word.'%') . "";
				}
				$sub .= ')';
				
				$dicts = $db->fetchPairs($sub);
				
				$tmp_dic_ids = array();
				if(COUNT($dicts) > 0) { 

					$query->joinLeft('pins_invert', 'pins.pin_id = pins_invert.pin_id', 'dic_id')
						->where('pins_invert.`dic_id` IN (' . implode(',', $dicts) . ')')
						->group('pins.pin_id');
				
				} else {
					$query->where('pins.pin_id = 0');
				}
				
			} else {
				$query->where('pins.pin_id = 0');
			}
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