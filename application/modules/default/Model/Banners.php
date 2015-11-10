<?php

class Model_Banners {
	
	public static function getBanners(JO_Db_Expr $where) {
		$db = JO_Db::getDefaultAdapter();
        
		$request = JO_Request::getInstance();
		
		$query = $db
					->select()
					->from('banners')
					->where($where)
					->where('status = 1')
					->order('position ASC')
					->limit(50);
		
		if(!Helper_Config::get('config_disable_js')) {
			if($request->isXmlHttpRequest()) {
				$query->where('status_in_js_mode = 1');
			}
		}
	
		$data = $db->fetchAll($query);
		$result = array();
		if($data) {
			foreach($data AS $r) {
				$result[$r['position']][] = $r;
			}
		}
		
		return $result; 
	}

}

?>