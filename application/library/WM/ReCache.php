<?php

class WM_ReCache {
	
	public static function writeUrl($url) {
		if(JO_Request::getInstance()->getController() == 'cron') return;
		$db = JO_Db::getDefaultAdapter();
		$db->insertIgnore('cache_url',array(
			'url' => $url,
			'date_add' => time(),
			'host' => str_replace('www.','',JO_Request::getInstance()->getServer('HTTP_HOST'))
		));
	}

	public static function rebuidCache() {
		set_time_limit(0);
		ignore_user_abort(true);
		
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('cache_url')
					->where('date_add < ?', (time()));
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $result) {
				self::getPageDataForCaching($result, $db);
				//sleep(5);
			}
		}
	}
	
	private static function getPageDataForCaching($data, $db) {
		//$html = @file_get_contents();
		
		$cache_host = str_replace('www.','', $data['host']);
		
		$php_self = rtrim(JO_Request::getInstance()->getServer('PHP_SELF'), 'index.php');
		$php_self = trim($php_self, '/');
		
		$request_path = trim($data['url'],' /');
		$request_path = str_replace(array('?','&'),'/',$request_path);
		$request_path = trim($request_path, '/');
		if(strpos($request_path,'/') !== false) {
			$path = dirname($request_path) . '/';
			$tmp = explode('/', $request_path);
			$cache_name = self::clearString(end($tmp)) . '.cache';
		} else {
			$path = '';
			$cache_name = self::clearString(trim($request_path) ? $request_path : 'home') . '.cache';
		}
		
		$cache_folder = BASE_PATH . '/cache/' . $cache_host . '/' . $path;
		$cache_file = $cache_folder . $cache_name;
		
		$url_get = 'http://' . $cache_host . '/' . ($php_self ? $php_self . '/' : '') . $data['url'];
		if(strpos($url_get,'?') !== false) {
			$url_get .= '&disableCache=true';
		} else {
			$url_get .= '?disableCache=true';
		}
		
		$data = file_get_contents($url_get);
		
		if(file_exists($cache_file) && is_file($cache_file)) { 
			if($data) {
				if(mb_strlen(self::deleteTag(@file_get_contents($cache_file)), 'utf-8') !== mb_strlen(self::deleteTag($data), 'utf-8')) {
					if(@file_put_contents($cache_file, $data)) {
						$db->update('cache_url',array(
							'date_add' => time()
						), array('id = ?' => $data['id']));
					}
				}
			}
			
		} else {
			if(!file_exists($cache_folder) || !is_dir($cache_folder)) { 
				if(@mkdir($cache_folder, 0777, true)) { 
					if(@file_put_contents($cache_file, $data)) {
						$db->update('cache_url',array(
							'date_add' => time()
						), array('id = ?' => $data['id']));
					}
				}
			}
			
		}
		
	}
	
	

	
	private static function deleteTag($code) {
		return preg_replace('/<div\sid="copyrights">(.*)<\/div>/is','',$code);
	}
	
	private static function compress_code($buffer) {
		$buffer = preg_replace('/([ ]{2,})/', ' ', $buffer);
		$buffer = preg_replace('/(\/\/([^\/<>]*)\n)/isumU', '', $buffer);
	  	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	  	$buffer = preg_replace('#<!–[^\[].+–>#', '',  $buffer);
	  	$buffer = preg_replace('/\n\r|\r\n|\n|\r|\t| {2}/', '', $buffer);
	  	return $buffer;
	}
	
	private static function getFileLenght($file) {
		return mb_strlen(@file_get_contents($file), 'utf-8');
	}
	
	private static function clearString($string) {
		$string = preg_replace('/[^a-z0-9а-яА-Я\-\=\+\&\[\]\:\.]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '- ');
	}
	
}

?>