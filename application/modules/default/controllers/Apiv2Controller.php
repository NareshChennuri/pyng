<?php

class Apiv2Controller extends Helper_Controller_Default {
	
	private $error = false;
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		if($request->getParam('command_api')) {
			$request->setParams('RSP', 'ajax');
			if($request->getParam('action_api')) {
				$this->forward($request->getParam('command_api'), $request->getParam('action_api'));
			} else {
				$this->forward($request->getParam('command_api'), 'index');
			}
		} else {
			$this->view->error = $this->translate('Please add a method to each call');
		}
		
		echo $this->renderScript('json');
	}
	
}

?>