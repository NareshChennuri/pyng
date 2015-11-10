<?php

class Model_Crons_Popular extends Model_Crons_Abstract {

	public function __construct() {
		
		$db = JO_Db::getDefaultAdapter();
		
		$file = BASE_PATH . '/cache/cache_popular_index.lock';
		
		if(file_exists($file)) { 
			if( filemtime($file) > (time()-(100)) ) {
				@unlink($file);
			} else {
				return;
			}
		}
		
		$query = $db->select()
			->from('pins', 'COUNT(pin_id)')
			->where('pins.likes > ? AND pins.repins > ?', 0);
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
		
		$total  = $db->fetchOne($query);
		
		file_put_contents($file, '');
		$pins_array = array();
		$pp = (int)Helper_Config::get('config_front_limit');
		
		if($pp) {
			$loop = 50*$pp;
			if($total <= $loop*4) {
				
				$query = $db->select()
					->from('pins', array('pin_id', 'pin_id'))
					->where('pins.likes > ? AND pins.repins > ?', 0);
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
				
				$query = $db->select()
					->from('pins', array('pin_id','pin_id'))
					//->where('pins.likes > ? AND pins.repins > ? AND pins.comments > ?', 0)
					->where('pins.likes > ? AND pins.repins > ?', 0)
					->order('pins.views DESC')
					->limit(3000);
				//for public boards
				if($config_private_boards) {
					$query->where('pins.public = 1');
				}
				/* v2.2 */
				if($config_enable_follow_private_profile) {
					$query = self::filterFriend($query);
				}
				/* v2.2 */
				$max_min  = $db->fetchPairs($query);
				
				$pins = array();
				while ( COUNT($pins) < $loop ) {
					$pin_id = array_rand($max_min, 1);
					if(isset($pins_array[$pin_id])) {
						continue;
					}
					$pin_exist_query = $db->select()->from('pins','pin_id')->where('pin_id = ?', $pin_id)->where('pins.likes > ? AND pins.repins > ?', 0)->limit(1);
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
			
			self::setCache('popular', ($pins?implode(',',$pins): ''));
		
		} else {
			self::setCache('popular', '');
		}
		
		@unlink($file);
		
	}
	
}

?>