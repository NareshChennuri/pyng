<?php

class Model_Blacksources {
	
	public static function create($data) { 
		
		$data['source'] = preg_replace('/^www./i', '', JO_Validate::validateHost($data['source']));
		$data['source'] = mb_strtolower($data['source'],'utf-8');
		if(!$data['source']) {
			return false;
		}
		
		if(!self::is_exists($data['source'])) {
			$db = JO_Db::getDefaultAdapter();
			$db->insert('pins_sources_blocked', array(
				'source' => $data['source']
			));		
			return $db->lastInsertId();	
		}
		return false;
	}
	
	public static function edit($source_id, $data) {
		
		$data['source'] = preg_replace('/^www./i', '', JO_Validate::validateHost($data['source']));
		$data['source'] = mb_strtolower($data['source'],'utf-8');
		if(!$data['source']) {
			return false;
		}
		
		if(!self::is_exists($data['source'], $source_id)) {
			$db = JO_Db::getDefaultAdapter();
			return $db->update('pins_sources_blocked', array(
				'source' => $data['source']
			), array('source_id = ?' => $source_id));			
		}
		return false;
	}
	
	public static function is_exists($source, $source_id=0) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db
					->select()
					->from('pins_sources_blocked', 'source_id')
					->where('source = ?', $source );
		
		$id = $db->fetchOne($query);

		if($source_id && $source_id == $id) {
			return false;
		}
		
		return $id;
	}
	
	public static function getWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_sources_blocked');
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'desc') {
			$sort = ' DESC';
		} else {
			$sort = ' ASC';
		}
		
		$allow_sort = array(
			'source_id',
			'source'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('source' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_source_id']) && $data['filter_source_id']) {
			$query->where('source_id = ?', (int)$data['filter_source_id']);
		}
		
		if(isset($data['filter_source']) && $data['filter_source']) {
			$query->where('source LIKE ?', '%' . $data['filter_source'] . '%');
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_sources_blocked', 'COUNT(source_id)')
					->limit(1);
		
		////////////filter
		
		if(isset($data['filter_source_id']) && $data['filter_source_id']) {
			$query->where('source_id = ?', (int)$data['filter_source_id']);
		}
		
		if(isset($data['filter_source']) && $data['filter_source']) {
			$query->where('source LIKE ?', '%' . $data['filter_source'] . '%');
		}
		
		return $db->fetchOne($query);
	}
	
	public static function getWord($source_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_sources_blocked', 'source')
					->where('source_id = ?', $source_id)
					->limit(1);
		
		return $db->fetchOne($query);
		
	}
	
	public function delete($source_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('pins_sources_blocked', array('source_id = ?' => (string)$source_id));
	}

	
	
}

?>