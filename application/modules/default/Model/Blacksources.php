<?php

class Model_Blacksources {
	
	public static function is_exists($source) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db
					->select()
					->from('pins_sources_blocked', 'source_id')
					->where('source = ?', $source )
					->limit(1);
		
		return $db->fetchOne($query);
	}

	
	
}

?>