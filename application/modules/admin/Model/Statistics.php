<?php

class Model_Statistics {

	public static function getStatistics(JO_Db_Expr $where) {
		$db = JO_Db::getDefaultAdapter();
		
		self::stats();
		
		$query = $db->select()
					->from('statistics')
					->where($where)
					->order('id ASC');
		return $db->fetchAll($query);
	}

	public static function getTotalStatistics(JO_Db_Expr $where, $type) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('statistics', 'SUM(total)')
					->where($where)
					->where('`type` = ?', (int)$type)
					->limit(1);
		return $db->fetchOne($query);
	}

	public static function getTotalStatistics2($table) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from($table, 'COUNT(*)')->limit(1);
		return $db->fetchOne($query);
	}

	public static function getMin() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins', 'MIN(date_added)')
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function showDatabases() {
		$db = JO_Db::getDefaultAdapter();
		
		$results = $db->fetchAll('show databases');
		$data = array();
		if($results) {
			foreach($results AS $result) {
				$data[] = $result['Database'];
			}
		}
		return $data;
	}

	public function stats() {
		$db = JO_Db::getDefaultAdapter();
		
		Helper_Db::delete('statistics', array());
		
		Helper_Db::query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(pin_id),1 FROM pins GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		Helper_Db::query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(user_id),2 FROM users GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		Helper_Db::query("INSERT INTO `statistics`(`id`, `total`, `type`) SELECT DATE_FORMAT(`date_added`, '%Y%m'),COUNT(board_id),3 FROM boards GROUP BY DATE_FORMAT(`date_added`, '%Y%m');");
		
	}
	
}

?>