<?php

class Model_Reportpincategories {
	
	public function createReportpincategory($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('pins_reports_categories', array(
			'title' => $data['title']
		));
		
		$prc_id = $db->lastInsertId();
		
		
		return $prc_id;
	}
	
	public function editeReportpincategory($prc_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pins_reports_categories', array(
			'title' => $data['title'],
		), array('prc_id = ?' => (int)$prc_id));
		
		
		
		return $prc_id;
	}
	
	public function changeSortOrder($prc_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('pins_reports_categories', array(
			'sort_order' => $sort_order
		), array('prc_id = ?' => (int)$prc_id));
	}
	
	
	public function deleteReportpincategory($prc_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('pins_reports_categories', array('prc_id = ?' => (int)$prc_id));
	}
	
	public function getReportpincategories($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_reports_categories')
					->order('pins_reports_categories.sort_order ASC');
					
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
	
	public function getReportpincategory($prc_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_reports_categories')
					->where('pins_reports_categories.prc_id = ? ', (int)$prc_id);
		
		return $db->fetchRow($query);
		
	}
}

?>