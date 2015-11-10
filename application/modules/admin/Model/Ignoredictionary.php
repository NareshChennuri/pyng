<?php

class Model_Ignoredictionary {
	
	public static function create($data) { 
		if(!self::is_exists($data['word'])) {
			$db = JO_Db::getDefaultAdapter();
			$db->insert('pins_ignore_dictionary', array(
				'word' => $data['word']
			));		
			return $db->lastInsertId();	
		}
		return false;
	}
	
	public static function edit($dic_id, $data) {
		if(!self::is_exists($data['word'], $dic_id)) {
			$db = JO_Db::getDefaultAdapter();
			return $db->update('pins_ignore_dictionary', array(
				'word' => $data['word']
			), array('dic_id = ?' => $dic_id));			
		}
		return false;
	}
	
	public static function is_exists($word, $dic_id=0) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db
					->select()
					->from('pins_ignore_dictionary', 'dic_id')
					->where('word = ?', $word );
		
		$id = $db->fetchOne($query);

		if($dic_id && $dic_id == $id) {
			return false;
		}
		
		return $id;
	}
	
	public static function getWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_ignore_dictionary');
	
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
			'dic_id',
			'word'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('word' . $sort);
		}
		
		////////////filter
		
		if(isset($data['filter_dic_id']) && $data['filter_dic_id']) {
			$query->where('dic_id = ?', (int)$data['filter_dic_id']);
		}
		
		if(isset($data['filter_word']) && $data['filter_word']) {
			$query->where('word LIKE ?', '%' . $data['filter_word'] . '%');
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_ignore_dictionary', 'COUNT(dic_id)')
					->limit(1);
		
		////////////filter
		
		if(isset($data['filter_dic_id']) && $data['filter_dic_id']) {
			$query->where('dic_id = ?', (int)$data['filter_dic_id']);
		}
		
		if(isset($data['filter_word']) && $data['filter_word']) {
			$query->where('word LIKE ?', '%' . $data['filter_word'] . '%');
		}
		
		return $db->fetchOne($query);
	}
	
	public static function getWord($dic_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_ignore_dictionary', 'word')
					->where('dic_id = ?', $dic_id)
					->limit(1);
		
		return $db->fetchOne($query);
		
	}
	
	public function delete($dic_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('pins_ignore_dictionary', array('dic_id = ?' => (string)$dic_id));
	}

	
	
}

?>