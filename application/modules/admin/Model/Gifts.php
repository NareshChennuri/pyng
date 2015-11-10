<?php

class Model_Gifts {
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('gifts_sums', array(
				'price_from' => (string)$data['price_from'],
				'price_to'=> (string)$data['price_to']
		));
		return $db->lastInsertId();
	}
	
	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('gifts_sums', array(
				'price_from' => (string)$data['price_from'],
				'price_to'=> (string)$data['price_to']
		), array('id = ?' => (string)$id));
		return $id;
	}
	
	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('gifts_sums', array('id = ?' => (string)$id));
	}
	
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
	
		$query = $db->select()
		->from("gifts_sums")
		->order(new JO_Db_Expr("price_from ASC,price_to ASC"));
		return $db->fetchAll($query);
	}
	
	public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
	
		$query = $db->select()
		->from("gifts_sums")
		->where("id = ?", (string)$id);
		return $db->fetchRow($query);
	}

}

?>