<?php

class Model_Source {

	public static function getSource($source_id) {
		static $result = array();
		if(isset($result[$source_id])) { return $result[$source_id]; }
		
		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('pins_sources')
							->where('source_id = ?', (string)$source_id)
							->limit(1);
		$result[$source_id] = $db->fetchRow($query);
		return $result[$source_id];
	}

	public static function getSourceByUrl($url, $insert = true) {
		static $result = array();
		if(isset($result[$url])) { return $result[$url]; }
		
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
			$db->insert('pins_sources', array('source' => $host));
			$sourse_id = $db->lastInsertId();
			if(!$sourse_id) {
				return false;
			}
			self::generateSourceQuery($sourse_id);
		}
							
		$result[$url] = $sourse_id;
		return $result[$url];
	}
	
	/* SEO */
	
	public static function generateSourceQuery($source_id) {
		$db = JO_Db::getDefaultAdapter();
		$info = self::getSource($source_id);
		
		if(!$info) {
			return;
		}
		
		if(trim($info['source'])) {
			$slug = $uniqueSlug = self::clear($info['source']);
		} else {
			$slug = $uniqueSlug = 'source';
		}
		
		$index = 1;
		$db->query("DELETE FROM url_alias WHERE query = 'source_id=" . (int)$source_id . "'");
		while (self::getTotalKey($uniqueSlug)) {
			$uniqueSlug = $slug . '-' . $index ++;
		}
		
		$db->insert('url_alias', array(
			'query' => 'source_id=' . (int)$source_id,
			'keyword' => $uniqueSlug,
			'path' => $uniqueSlug,
			'route' => 'source/index'
		));
		
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