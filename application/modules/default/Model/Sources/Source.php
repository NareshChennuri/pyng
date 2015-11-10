<?php

class Model_Sources_Source extends Model_Sources_Abstract {
	
	public function __construct($source_id) {
		
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_sources')
					->where('source_id = ?', (string)$source_id)
					->limit(1);
		$response = $db->fetchRow($query);
		$response = is_array($response) ? $response : array();
		parent::__construct($response);
		
	}
	
}

?>