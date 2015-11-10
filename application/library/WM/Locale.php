<?php

class WM_Locale {
	
	public function getLanguages() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language')
					->where('status  = 1')
					->order('sort_order ASC');
		
		return $db->fetchAll($query);
	}
	
	public function getLanguage($language_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language')
					->where('language_id  = ?', $language_id);
		
		return $db->fetchRow($query);
	}
	
}