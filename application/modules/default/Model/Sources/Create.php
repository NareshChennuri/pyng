<?php

class Model_Sources_Create {
	
	public $source_id = 0;
	public $uniqueSlug = '';
	
	public function __construct($data = array()) {
		if(isset($data['source'])) {
			$this->source_id = Helper_Db::insert('pins_sources', array(
				'source' => $data['source']
			));
			
			if($this->source_id) {
				$this->uniqueSlug = self::generateSourceQuery($this->source_id);
			}
		}
	}
	
	/* SEO */
	
	public static function generateSourceQuery($source_id) {
		$info = new Model_Sources_Source($source_id);
		
		if(!$info->count()) {
			return;
		}
		
		if(trim($info['source'])) {
			$slug = $uniqueSlug = self::clear($info['source']);
		} else {
			$slug = $uniqueSlug = 'source';
		}
		
		$index = 1;
		Helper_Db::delete('url_alias', array('query = ?' => 'source_id='.$source_id));
		while (self::getTotalKey($uniqueSlug)) {
			$uniqueSlug = $slug . '-' . $index ++;
		}
		
		if(Helper_Db::insert('url_alias', array(
			'query' => 'source_id=' . (int)$source_id,
			'keyword' => $uniqueSlug,
			'path' => $uniqueSlug,
			'route' => 'source/index'
		))) {
			return $uniqueSlug;
		} else {
			return false;
		}
		
	}
	
	public function clear($string) {
		$string = preg_replace('/[^a-z0-9\-\.]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '-');
	}
	
	public function getTotalKey($keyword) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('url_alias', new JO_Db_Expr('COUNT(url_alias_id)'))
					->where("keyword = ?", (string)$keyword);
		return $db->fetchOne($query);
	}
	
}

?>