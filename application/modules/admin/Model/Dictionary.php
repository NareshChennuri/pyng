<?php

class Model_Dictionary {
	
	public static function getWords($data = array()) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_dictionary');
	
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
					->from('pins_dictionary', 'COUNT(dic_id)')
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
	
	public function delete($dic_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('pins_dictionary', array('dic_id = ?' => (string)$dic_id));
		$db->delete('pins_invert', array('dic_id = ?' => (string)$dic_id));
	}

	
	
}

?>