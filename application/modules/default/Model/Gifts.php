<?php

class Model_Gifts {
	
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
	
		$query = $db->select()
			->from("gifts_sums")
			->order(new JO_Db_Expr("price_from ASC,price_to ASC"));
		return $db->fetchAll($query);
	}
	
}

?>