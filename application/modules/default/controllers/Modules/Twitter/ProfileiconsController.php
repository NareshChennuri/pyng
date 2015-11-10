<?php

class Modules_Twitter_ProfileiconsController extends Helper_Controller_Default {

	public function indexAction($user_data = null) {
		$this->noLayout(true);
		if($user_data) {
			$connect = new Model_Twitter_Login();
			$info = $connect->getDataByUserId($user_data['user_id']);
			if($info) {
				$this->view->profile = $info['username'];
			}
		}
	}
	
}

?>