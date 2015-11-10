<?php

class Model_Extensions {

	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('extensions');
		return $db->fetchPairs($query);
	}

	public static function getByMethod($method) {
		$ext = self::getAll();
		$data = array();
		foreach($ext AS $id => $e) {
			$mod = Helper_Config::get($e.'_methods');
			if(is_array($mod)) {
				if(in_array($method, $mod)) {
					$data[$id] = $e;
				}
			}
		}
		return $data;
	}
	
	public static function getSettingsPairs($group) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('system');
		
		if($group) {
			$query->where('`group` = ?', $group);
		} else {
			$query->where('`id` = 0');
		}
		
		$response = array();
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $result) {
				if($result['serialize']) {
					$response[$result['key']] = self::mb_unserialize($result['value']);
				} else {
					$response[$result['key']] = $result['value'];
				}
			}
		}
		
		return $response;
	}
  	
	public function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	} 
	
}

?>