<?php

class Model_Twitter_Users {

	public function deleteByUserId($user_id) {
		return Helper_Db::delete('oauth_twitter', array('user_id = ?' => $user_id));
	}
	
}

?>