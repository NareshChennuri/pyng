<?php

class WM_ReCache2 {
	
	protected static $info_table = '`information_schema`.`TABLES`';
	
	public static function getCachingPage($tables = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$options = array(
			'livetime' => 86400
		);
		
		JO_Registry::set('static_cache_options', serialize($options));
		JO_Registry::set('static_cache_enable', true);
		
		$cache = new JO_Cache_Static($options);
		
		$cache_make_time = $cache->getCacheFileMTime();
		
		if(!$cache_make_time) {
			return false;
		}
	
		$config_data = $db->getConfig();
		
		$query1 = '';
		foreach($tables AS $k=>$table) {
			$table = str_replace('`','',$table);
			$query1 .= $query1 ? ' OR ' : '';
			if(strpos($table, '.') !== false) {
				$query1 .= "(`TABLE_SCHEMA`='".array_shift(explode('.', $table))."' AND `TABLE_NAME`='".end(explode('.', $table))."')";
			} else {
				if(isset($config_data['dbname'])) {
					$query1 .= "(";
					if($config_data['dbname']) {
						$query1 .= "`TABLE_SCHEMA`='".$config_data['dbname']."' AND ";
					}
					$query1 .= "`TABLE_NAME`='".$table."')";
				}
			}	
		}
		
		if($query1) {
			
			$query1 = "(" . $query1 . ") AND `UPDATE_TIME`>'".date("Y-m-d H:i:s", $cache_make_time)."'";
			$r = $db->fetchRow("SELECT UPDATE_TIME FROM " . self::$info_table . " WHERE $query1 ORDER BY UPDATE_TIME DESC LIMIT 1");

			if($r && strtotime($r['UPDATE_TIME']) >= $cache->getCacheFileMTime()) { 
				return false;
			} else {
				if(date('Ymd', $cache->getCacheFileMTime()) < date('Ymd')) {
					return false;
				}
				$response = JO_Response::getInstance();
				$md5_file = md5_file($cache->getCacheFile());
				$response->addHeader("Etag: " . $md5_file); 
				$response->addHeader("Last-Modified: ".gmdate("D, d M Y H:i:s", $cache_make_time)." GMT");
				$response->addHeader("Pragma: public");
				$response->addHeader("Cache-store: server");
				$response->appendBody($cache->get(), 9);
				exit;
			}
		}
		
	}
	
	
}

?>