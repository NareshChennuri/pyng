<?php

class Model_Sources_GetSourceByUrl {

	public $source_id = 0;
	
	public function __construct($url, $insert = true) {
		
		$host = str_replace('www.','',JO_Validate::validateHost($url));
		if(!$host) {
			return false;
		}
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('pins_sources', 'source_id')
							->where('source = ?', $host)
							->limit(1);
							
		$sourse_id = $db->fetchOne($query);
		if(!$sourse_id && $insert) {
			$sourse = new Model_Sources_Create(array(
				'source' => $host
			));
			$sourse_id = $sourse->source_id;
		}
		$this->source_id = $sourse_id;
	}
	
}

?>