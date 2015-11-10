<?php

class Model_Facebook_Users {

	public function deleteByUserId($user_id) {
		return Helper_Db::delete('oauth_facebook', array('user_id = ?' => $user_id));
	}
	
}

?>