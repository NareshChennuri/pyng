<?php

class Model_Sources_Abstract extends ArrayObject {
	
	/**
	 * @param string $table
	 * @return array
	 */
	public static function describeTable($table, $row = '') {
		$db = JO_Db::getDefaultAdapter();
		$result = $db->describeTable($table);
		$data = array();
		foreach($result AS $res) {
			$data[$row . $res['COLUMN_NAME']] = $res['COLUMN_NAME'];
		}
		return $data;
	}
	
	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	public static function sortOrderLimit($query, $data = array()) {
		 
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		 
		$allow_sort = array(
			'pins_sources.source_id'
		);
		 
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} elseif(isset($data['order']) && $data['order'] instanceof JO_Db_Expr) {
			$query->order($data['order']);
		} else {
			$query->order('pins_sources.source_id' . $sort);
		}
		 
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		return $query;
	}
	
}

?>