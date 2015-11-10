<?php

class Apiv2_PinsController extends Helper_Controller_Default {

	public function indexAction() {
		$this->view->error = $this->translate('Please add a method to each call');
		echo $this->renderScript('json');
	}
	
}

?>