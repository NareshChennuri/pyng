<?php

class Model_Pins_HomePage extends Model_Pins_Abstract {

	public function __construct($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		//select default pin data
		$query = self::getListPinsQuery();
	
		//filter bi follow or get from cache
		if(JO_Session::get('user[user_id]')) {
			if(Helper_Config::get('config_home_page_view_loged') == 'following') {
				$query = $this->getFollowing($query, $data);
			} elseif(Helper_Config::get('config_home_page_view_loged') == 'latest') {
				$query = $this->getLatest($query, $data);
			} else {
				$query = $this->getFollowing($query, $data);
			}
		} else {
			
			if(Helper_Config::get('config_home_page_view_not_loged') == 'randum') {
				$query = $this->getRandum($query, $data);
			} elseif(Helper_Config::get('config_home_page_view_not_loged') == 'latest') {
				$query = $this->getLatest($query, $data);
			} else {
				$query = $this->getRandum($query, $data);
			}
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		//echo $query;exit;
		$this->data = $db->fetchAll($query);
		
		//parent::__construct($db->fetchAll($query));
		
	}
	
	private function getFollowing($query, $data = array()) {
		$db = JO_Db::getDefaultAdapter();
		$has_pins = 'pins.user_id = ?';
		if(JO_Session::get('user[following]')) {
			$has_pins .= ' OR pins.user_id IN (' . $db->select()->from('users_following','following_id')->where('user_id = ?')->where('users_following.board_id = pins.board_id') . ')';
			$has_pins .= ' OR pins.user_id IN (' . $db->select()->from('users_following_user','following_id')->where('user_id = ?') . ')';
		}
		$query->where(new JO_Db_Expr($has_pins), JO_Session::get('user[user_id]'));
		if(JO_Session::get('user[following]') /*&& !JO_Session::get('user[pins]')*/) {
			$query->where('pins.board_id NOT IN (SELECT board_id FROM `users_following_ignore` WHERE user_id = ?)', JO_Session::get('user[user_id]'));
		}

		//sort and limit add to query from Model_Pins_Abstract
		$query = self::sortOrderLimit($query, $data);
		return $query;
	}
	
	private function getLatest($query, $data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
		
		$query = self::sortOrderLimit($query, $data);
		return $query;
	}
	
	private function getRandum($query, $data = array()) {
		$db = JO_Db::getDefaultAdapter();
		//// load from cache
		$check = $db->select()
					->from('cache_index')
					->where('start_limit = ?', 'home')
					->limit(1);
		$cache = $db->fetchRow($check);
		if(!isset($cache['data']) || !$cache['data']) {
			$query->where('pins.pin_id = 0');
			return $query;
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
		return $query;
	}
	
}

?>