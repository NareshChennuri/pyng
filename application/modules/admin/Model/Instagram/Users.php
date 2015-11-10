<?php

class Model_Instagram_Users {

	public function deleteByUserId($user_id) {
		return Helper_Db::delete('oauth_instagram', array('user_id = ?' => $user_id));
	}
	
}

?>