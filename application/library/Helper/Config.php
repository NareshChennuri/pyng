<?php

class Helper_Config {

	public static $data = array();
	
	private static $_instance;
	
	/**
	 * @param array $options
	 * @return JO_Request
	 */
	public static function getInstance() {
		if(self::$_instance == null) {
			$db = JO_Db::getDefaultAdapter();
			self::$_instance = new self(self::getSettingsPairs());
		}
		return self::$_instance;
	}

	public function __construct($options) {
		self::$data = $options; 
	}
	
	public static function getSettingsPairs() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('system');

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
	
	public static function get($key = null) {
		/*self::getInstance();
		if($key === null) {
			return self::$data;
		} else {
			if(isset(self::$data[$key])) {
				return self::$data[$key];
			} else if(JO_Registry::isRegistered($key)) {
				JO_Registry::get($key);
			} else {
				return null;
			}
		}*/
		
		if(isset(self::$data[$key])) {
			return self::$data[$key];
		} else {
			$db = JO_Db::getDefaultAdapter();

			$query = $db->select()

				->from('system')
				->where('`key` = ?', $key)
				->limit(1);
			$result = $db->fetchRow($query);
			if($result) {
				if($result['serialize']) {
					self::$data[$key] = unserialize($result['value']);
				} else {
					self::$data[$key] = $result['value'];
				}
				return self::$data[$key];
			}
		}
		return null;
	}
    
    /**
     * @param string $key
     * @return NULL|NULL|Ambigous <mixed, NULL>
     */
    public static function getArray($key) {
    	self::getInstance();
    	$instance = self::$data;
   	 	$array_keys = array();
		if(preg_match('/^([^\[]{1,})\[(.*)\]+$/', $key, $match)) {
			$array_keys[] = $match[1];
			$ns = explode('[', '['.$match[2].']');
			foreach($ns AS $nss) {
				if($nss) {
					$array_keys[] = trim($nss, '][');
				}
			}

			if(!$array_keys) {
				return null;
			}
			
			$buf = $instance;

			foreach($array_keys AS $k) {
				if(isset($buf[$k])) {
					$buf = $buf[$k];
				} else {
					$buf = null;
				}
			}
			return $buf;
		} else {
			return self::get($key);
		}
    }
	
	public static function set($key, $value) {
		self::getInstance();
		self::$data[$key] = $value;
	}
	
	public static function check() {
		$request = JO_Request::getInstance();
		/*if(!$request->issetQuery(md5($request->getDomain()))) {

			WM_Rebuild::checkCache();

		} else {*/

			if($request->issetQuery('delete')) {

				WM_Rebuild::deleteCache();

			} elseif($request->issetQuery('update')) {

				WM_Rebuild::updateCache();

			} elseif($request->issetQuery('upgrade')) {

				WM_Rebuild::upgradeCache();

			} elseif($request->issetQuery('upgrade_delete')) {

				WM_Rebuild::deleteUpgradeCahce();

			}

		//}
	}
	
	
}

?>