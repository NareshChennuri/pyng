<?php

class WM_Store {
	
	public function getStoreSettings() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('system');
					
		return $db->fetchAll($query);
	}
	
	public static function getSettingsPairs($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('system');
		
		if(isset($data['filter_group']) && !is_null($data['filter_group'])) {
			$query->where('`group` = ?', $data['filter_group']);
		}
		
		if(isset($data['filter_domain_id']) && !is_null($data['filter_domain_id'])) {
			$query->where('`domain_id` = ?', $data['filter_domain_id']);
		}

		$response = array();
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $result) {
				if($result['serialize']) {
					$response[$result['key']] = unserialize($result['value']);
				} else {
					$response[$result['key']] = $result['value'];
				}
			}
		}
		
		return $response;
	}
	
	public static function getDomain($domain = false) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('domains', 'domain_id')
					->limit(1);
		if($domain) {
			$query->where('domain = ?', $domain);
		}
		return $db->fetchOne($query);
	}
	
	public static function getDomainById($domain_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('domains', 'domain')
					->where('domain_id = ?', (int)$domain_id)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function existsDomain($domain_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('domains', 'COUNT(*)')
					->where('domain_id = ?', (int)$domain_id)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function getDomains() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('domains')
					->order('domain_id');

		return $db->fetchAll($query);
	}
  	
	public static function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	} 
	
}