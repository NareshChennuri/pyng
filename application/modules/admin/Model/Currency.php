<?php

class Model_Currency {
	
	public static function createCurrency($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('currency', array(
			'date_added' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'code' => mb_strtoupper($data['code'], 'utf-8'),
			'decimal_place' => (int)$data['decimal_place'],
			'value' => (float)str_replace(',','.',$data['value']),
			'decimal_point' => (string)$data['decimal_point'],
			'thousand_point' => (string)$data['thousand_point'],
			'symbol_left' => $data['symbol_left'],
			'symbol_right' => $data['symbol_right'],
			'title' => $data['title']
		));
		
		$page_id = $db->lastInsertId();
		
		if(JO_Registry::get('config_currency_auto_update')) {
			WM_Currency::updateCurrencies(JO_Registry::get('config_currency'), true);
		}
		
		return $page_id;
	}
	
	public static function editeCurrency($page_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('currency', array(
			'date_modified' => new JO_Db_Expr('NOW()'),
			'status' => $data['status'],
			'code' => mb_strtoupper($data['code'], 'utf-8'),
			'decimal_place' => (int)$data['decimal_place'],
			'value' => (float)str_replace(',','.',$data['value']),
			'decimal_point' => (string)$data['decimal_point'],
			'thousand_point' => (string)$data['thousand_point'],
			'symbol_left' => $data['symbol_left'],
			'symbol_right' => $data['symbol_right'],
			'title' => $data['title']
		), array('currency_id = ?' => (int)$page_id));
		
		if(JO_Registry::get('config_currency_auto_update')) {
			WM_Currency::updateCurrencies(JO_Registry::get('config_currency'), true);
		}

		return $page_id;
	}
	
	public static function getCurrencies($data = array()) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('currency')
					->order('title ASC');
					
		if(isset($data['status'])) {
			$query->where('status = ?', (int)$data['status']);
		}
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}

		return $db->fetchAll($query);
	}
	
	public static function getCurrency($currency_id) {
		$db = JO_Db::getDefaultAdapter();

		$query = $db->select()
					->from('currency')
					->where('currency_id = ?', (int)$currency_id);

		return $db->fetchRow($query);
	}
	
	public static function changeStatus($currency_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('currency', array(
			'status' => new JO_Db_Expr('IF(status = 1, 0, 1)')
		), array('currency_id = ?' => (int)$currency_id));
	}
	
	public static function deleteCurrency($currency_id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('currency', array('currency_id = ?' => (int)$currency_id));
	}

}

?>