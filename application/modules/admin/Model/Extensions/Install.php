<?php

class Model_Extensions_Install {
	
	public static function tableExists($table) {
		$db = JO_Db::getDefaultAdapter();
		$pref = $db->getConfig('dbname');
		$results = $db->fetchAll('show tables');
		if($results) {
			foreach($results AS $result) {
				if( strtolower($table) == $result['Tables_in_' . $pref] ) {
					return true;
				}
			}
		}
		return false;
	}
	
	public static function isInstalled($name) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
			->from('extensions', 'COUNT(id)')
			->where('`code` = ?', $name);
		return $db->fetchOne($query);
	}
	
	public static function install($name) {
		return Helper_Db::insert('extensions', array(
					'code' => $name
				));
	}
	
	public static function uninstall($name) {
		return Helper_Db::delete('extensions', array(
					'code = ?' => $name
				));
	}

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
	
}

?>