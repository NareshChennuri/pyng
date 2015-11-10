<?php

class Model_Reportcommentcategories {
	
	public function createReportcommentcategory($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('pins_comment_reports_categories', array(
			'title' => $data['title']
		));
		
		$prc_id = $db->lastInsertId();
		
		
		return $prc_id;
	}
	
	public function editeReportcommentcategory($prc_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pins_comment_reports_categories', array(
			'title' => $data['title'],
		), array('prc_id = ?' => (int)$prc_id));
		
		
		
		return $prc_id;
	}
	
	public function changeSortOrder($prc_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pins_comment_reports_categories', array(
			'sort_order' => $sort_order
		), array('prc_id = ?' => (int)$prc_id));
	}
	
	
	public function deleteReportcommentcategory($prc_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('pins_comment_reports_categories', array('prc_id = ?' => (int)$prc_id));
	}
	
	public function getReportcommentcategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_comment_reports_categories')
					->order('pins_comment_reports_categories.sort_order ASC');
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		$data_info = $db->fetchAll($query);
		$result = array();
		if($data_info) { 
			$result = $data_info;
		}
		
		return $result;
	}
	
	public function getReportcommentcategory($prc_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_comment_reports_categories')
					->where('pins_comment_reports_categories.prc_id = ? ', (int)$prc_id);
		
		return $db->fetchRow($query);
		
	}
}

?>