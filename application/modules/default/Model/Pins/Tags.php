<?php

class Model_Pins_Tags extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListPinsQuery();
	
		if(isset($data['filter_tag']) && trim($data['filter_tag'])) {
			
			$tag_id = Model_Pintags_Pin::getTagIdByTag($data['filter_tag']);
			
			if( $tag_id ) {
	
				$query->where('pins.pin_id IN (SELECT pin_id FROM pins_tags_invert WHERE tag_id = ?)', $tag_id);
				
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