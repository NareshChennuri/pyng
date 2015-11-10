<?php

class JsController extends Helper_Controller_Admin {

	public function indexAction() {
		$this->noLayout(true);
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		$response->addHeader('Content-type: application/javascript');
	}
	
}

?>