<?php

class Model_Language {
	
	public function __construct() {}
	
	public function createLanguage($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$sort_order = (int)$db->fetchOne($db->select()->from('language', new JO_Db_Expr('max(sort_order)')));
		
		$db->insert('language', array(
			'name' => $data['name'],
			'code' => $data['code'],
			'locale' => $data['locale'],
			'locale_territory' => $data['locale_territory'],
			'image' => $data['image'],
			'sort_order' => ($sort_order+1),
			'status' => $data['status'],
		));
		
		$language_id = $db->lastInsertId();
		
		$tables = self::generateInsertQueryes(JO_Registry::get('default_config_language_id'), $language_id);
		foreach($tables AS $query) {
			$db->query($query);
		}
		
		return $language_id;
	}
	
	public function editeLanguage($language_id, $data) {
		$db = JO_Db::getDefaultAdapter();

		$db->update('language', array(
			'name' => $data['name'],
			'code' => $data['code'],
			'locale' => $data['locale'],
			'locale_territory' => $data['locale_territory'],
			'image' => $data['image'],
			'status' => $data['status'],
		), array('language_id = ?' => (int)$language_id));
		
		return $db->lastInsertId();
	}
	
	public static function getLanguages() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language')
					->order('sort_order ASC');
			
		return $db->fetchAll($query);
	}
	
	public static function getLanguage($language_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language')
					->where('language_id = ?', (int)$language_id);
			
		return $db->fetchRow($query);
	}
	
	public function changeStatus($language_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('language', array(
			'status' => new JO_Db_Expr('IF(status = 1, 0, 1)')
		), array('language_id = ?' => (int)$language_id));
	}
	
	public function changeSortOrder($language_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('language', array(
			'sort_order' => $sort_order
		), array('language_id = ?' => (int)$language_id));
	}
	
	public function deleteLanguage($language_id) {
		if($language_id == JO_Registry::get('config_default_language_id')) {
			return;
		}
		
		$db = JO_Db::getDefaultAdapter();
		set_time_limit(0);
		ignore_user_abort(true);
		//trie navsqkade kadeto ima rezultati s tozi ezik
		
		$tables = self::generateDeleteQueryes($language_id);
		foreach($tables AS $query) {
			$db->query($query);
		}
		
		$db->delete('language', array('language_id = ?' => (int)$language_id));
	}
	
	private static function generateInsertQueryes($default_language_id, $language_id) {
		$db = JO_Db::getDefaultAdapter();
		$tables = $db->listTables();
		$insert = array();
		
		foreach($tables AS $table) {
			if( in_array($table, array('language','users','ads')) ) continue;
			$rows = array_keys($db->describeTable($table));
			if($rows) {
				if(in_array('language_id', $rows)) {
					$insert[$table] = "INSERT INTO `" . $table . "` (`" . implode('`, `',$rows) . "`) SELECT ";
					foreach($rows AS $key => $row) {
						if($row == 'language_id') {
							$insert[$table] .= ($key ? ', ' : '') . (int)$language_id;
						} else {
							$insert[$table] .= ($key ? ', ' : '') . '`' . $row . '`';
						}
					}
					$insert[$table] .= "FROM `" . $table . "` WHERE `language_id` = " . $default_language_id;
				}
			}
		}
		
		return $insert;
	}
	
	private static function generateDeleteQueryes($language_id) {
		$db = JO_Db::getDefaultAdapter();
		$tables = $db->listTables();
		$delete = array();
		
		foreach($tables AS $table) {
			if( in_array($table, array('language','users','ads')) ) continue;
			$rows = array_keys($db->describeTable($table));
			if($rows) {
				if(in_array('language_id', $rows)) {
					$delete[$table] = "DELETE FROM `" . $table . "` WHERE `language_id` = " . $language_id;
				}
			}
		}
		
		return $delete;
	}


	
}

?>