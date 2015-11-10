<?php

class Model_Crons_Abstract {
	
	public function setCache($key, $data) {
		
		$db = JO_Db::getDefaultAdapter();
		Helper_Db::delete('cache_index', array(
			'start_limit = ?' => $key
		));
		return Helper_Db::insert('cache_index',array(
			'start_limit' => $key,
			'data' => $data
		));
	}
	
	/* v2.2 */
	/**
	 * @return Ambigous <JO_Db_Select, JO_Db_Select>
	 */
	protected static function filterFriend(JO_Db_Select $query) {
		return $query->where('pins.user_id IN (SELECT user_id FROM users WHERE public = 1)');
	}
	/* end mod v2.2 */

}

?>