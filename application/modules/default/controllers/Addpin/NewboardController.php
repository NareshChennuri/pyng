<?php

class Addpin_NewboardController extends Helper_Controller_Default {

	public $SORT = 999999999;

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$this->view->pin_add_url = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=create' );
		
	}
	
}

?>