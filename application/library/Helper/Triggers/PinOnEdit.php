<?php

class Helper_Triggers_PinOnEdit extends JO_Model {

	public function bind($pin_id) {
		$req = new JO_Http_Async();
		$request = $this->getRequest();
		$user_login_buttons = $this->getByMethod('pin_oncomplete');
		foreach($user_login_buttons AS $id => $login) {
			$req->curl_get_async( $request->getBaseUrl(), array(
				'controller' => 'modules_' . $login . '_pinoncomplete',
				'user' => JO_Session::get('user[user_id]'),
				'pin' => $pin_id,
				'type' => 'edit'
			));
		}	
	}	

	public static function getByMethod($method) {
		$ext = self::getAll();
		$data = array();
		foreach($ext AS $id => $e) {
			$mod = Helper_Config::get($e.'_methods');
			if(in_array($method, $mod)) {
				$data[$id] = $e;
			}
		}
		return $data;
	}

	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('extensions');
		return $db->fetchPairs($query);
	}
}

?>