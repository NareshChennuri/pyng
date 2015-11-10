<?php

class Model_Translate {
	
	public static function setTranslate($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		if(isset($data['translate'])) {
			foreach($data['translate'] AS $id => $value) {
				$db->update('language_keywords', array(
					'translate' => $value
				), array('language_keywords_id = ?' => $id));
			}
		}
		
	}
	
	public static function getTranslate($mod) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language_keywords')
					->where('module = ?',$mod)
					->order('keyword ASC');
					
		return $db->fetchAll($query);
	}

}

?>