<?php

class Model_Pins_IsLiked {
	
	public $total = 0;
	/**
	 * @var Model_Pins_Pin
	 */
	public $pin = false;
	
	public function __construct($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$user_id = JO_Session::get('user[user_id]');
		
		if(!$user_id) {
			return $this;
		}
		
		$pin = new Model_Pins_Pin($pin_id);
		$this->pin = $pin->data; 
		$query = $db->select()
				->from('pins_likes', 'COUNT(like_id)')
				->where('user_id = ?', (string)$user_id)
				->where('pin_id = ?', (string)$pin_id)
				->limit(1);
		
		$this->total = $db->fetchOne($query);
	}
	
	public function like() {
		if($this->pin && !$this->total) {
			$db = JO_Db::getDefaultAdapter();
			try {
				$db->beginTransaction();
				$result = Helper_Db::insert('pins_likes', array(
					'pin_id' => (string)$this->pin['pin_pin_id'],
					'user_id' => (string)JO_Session::get('user[user_id]')
				));
				if($result) {
					$user_id = JO_Session::get('user[user_id]');	
					Helper_Db::update('pins', array(
							'likes' => new JO_Db_Expr('(' . $db->select()->from('pins_likes','COUNT(like_id)')->where('pin_id = ?', (string)$this->pin['pin_pin_id'])->limit(1) . ')')
					), array('pin_id = ?' => (string)$this->pin['pin_pin_id']));
					Helper_Db::update('users', array(
							'likes' => new JO_Db_Expr('(' . $db->select()->from('pins_likes','COUNT(like_id)')->where('user_id = ?', (string)$user_id)->limit(1) . ')')
					), array('user_id = ?' => (string)$user_id));
				}
				$db->commit();
				return $result;
			} catch (JO_Exception $e) {
				$db->rollBack();
			}
		}
		return false;
	}
	
	public function unlike() {
		if($this->pin && $this->total) {
			$db = JO_Db::getDefaultAdapter();
			try {
				$db->beginTransaction();
				$result = Helper_Db::delete('pins_likes', array(
						'pin_id = ?' => (string)$this->pin['pin_pin_id'],
						'user_id = ?' => (string)JO_Session::get('user[user_id]')
				));
				if($result) {
					$user_id = JO_Session::get('user[user_id]');
					Helper_Db::update('pins', array(
							'likes' => new JO_Db_Expr('(' . $db->select()->from('pins_likes','COUNT(like_id)')->where('pin_id = ?', (string)$this->pin['pin_pin_id'])->limit(1) . ')')
					), array('pin_id = ?' => (string)$this->pin['pin_pin_id']));
					Helper_Db::update('users', array(
							'likes' => new JO_Db_Expr('(' . $db->select()->from('pins_likes','COUNT(like_id)')->where('user_id = ?', (string)$user_id)->limit(1) . ')')
					), array('user_id = ?' => (string)$user_id));
				}
				$db->commit();
				return $result;
			} catch (JO_Exception $e) {
				$db->rollBack();
			}
		}
		return false;
	}

}

?>