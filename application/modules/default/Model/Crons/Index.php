<?php

class Model_Crons_Index extends Model_Crons_Abstract {

	public function __construct() {
		
		$db = JO_Db::getDefaultAdapter();
		
		$file = BASE_PATH . '/cache/cache_index.lock';
		
		if(file_exists($file)) {
			if( filemtime($file) > (time()-(100)) ) {
				@unlink($file);
			} else {
				return;
			}
		}
		
		$query = $db->select()
			->from('pins', array('max' => 'MAX(pin_id)', 'min' => 'MIN(pin_id)', 'total' => 'COUNT(pin_id)'))
			->limit(1);
    	
    	//for public boards
		$config_private_boards = Helper_Config::get('config_private_boards');
    	if($config_private_boards) {
    		$query->where('pins.public = 1');
    	}
			
		/* v2.2 */
		$config_enable_follow_private_profile = Helper_Config::get('config_enable_follow_private_profile');
		if($config_enable_follow_private_profile) {
			$query = self::filterFriend($query);
		}
		/* v2.2 */
    	
		$max_min  = $db->fetchRow($query);
		
		file_put_contents($file, '');
		$pins_array = array();
		$pp = (int)Helper_Config::get('config_front_limit');
		if($pp) {
		
			$loop = 50*$pp;
			if($max_min['total'] <= $loop*4) {
				$query = $db->select()
					->from('pins', array('pin_id', 'pin_id'));
					if($config_private_boards) {
						$query->where('pins.public = 1');
					}
					/* v2.2 */
					if($config_enable_follow_private_profile) {
						$query = self::filterFriend($query);
					}
					/* v2.2 */
					$query->order('RAND()');
				$pins = $db->fetchPairs($query);
			} else {
				$pins = array();
				while ( COUNT($pins) < $loop ) {
					$pin_id = mt_rand($max_min['min'], $max_min['max']);
					if(isset($pins_array[$pin_id])) {
						continue;
					}
					$pin_exist_query = $db->select()->from('pins','pin_id')->where('pin_id = ?', $pin_id)->limit(1);
					if($config_private_boards) {
						$pin_exist_query->where('pins.public = 1');
					}
					/* v2.2 */
					if($config_enable_follow_private_profile) {
						$pin_exist_query = self::filterFriend($pin_exist_query);
					}
					/* v2.2 */
					if($db->fetchOne($pin_exist_query) && !isset($pins_array[$pin_id])) {
						$pins[] = $pin_id;
						$pins_array[$pin_id] = true;
					}
				}
			}
			self::setCache('home',($pins?implode(',',$pins): ''));
		} else {
			self::setCache('home','');
		}
		
		@unlink($file);

	}
	
}

?>