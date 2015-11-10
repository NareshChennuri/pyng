<?php

class ErrorController extends Helper_Controller_Default {
	
	public function error404Action() { 
		
		$request = $this->getRequest();
		
		$this->setViewChange('index');
		if($request->isXmlHttpRequest()) {
			$this->noViewRenderer(true);
			$this->view->popup = true;
//			$this->getResponse()->addHeader("HTTP/1.0 404 Not Found");
			echo $this->view->render('error404', 'error');
		} else {
			
			$this->view->error_holder = $this->view->render('error404', 'error');
			$this->getResponse()->addHeader("HTTP/1.0 404 Not Found");
		}
	}
	
	public function maintenanceAction() {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		$response->addHeader('HTTP/1.1 503 Service Temporarily Unavailable');
		$response->addHeader('Status: 503 Service Temporarily Unavailable');
		$response->addHeader('Retry-After: 300');//300 seconds
		
	}
	
	public function poweredAction() {
		
		$request = $this->getRequest();
		
		$this->setViewChange('index');
		if($request->isXmlHttpRequest()) {
			$this->noViewRenderer(true);
			$this->view->popup = true;
//			$this->getResponse()->addHeader("HTTP/1.0 404 Not Found");
			echo $this->view->render('powered', 'error');
		} else {
			
			$this->view->error_holder = $this->view->render('powered', 'error');
			$this->getResponse()->addHeader("HTTP/1.0 406 Not Acceptable");
		}
		
	}
	
	public function licenceAction($args = array()) {
		
		$request = $this->getRequest();
		
		if(isset($args['text']) && $args['text']) {
			$this->view->text = $args['text'];
		} else {
			$this->view->text = 'Some error with licence!';
		}
		
		$this->setViewChange('index');
		if($request->isXmlHttpRequest()) {
			$this->noViewRenderer(true);
			$this->view->popup = true;
//			$this->getResponse()->addHeader("HTTP/1.0 404 Not Found");
			echo $this->view->render('licence', 'error');
		} else {
			
			$this->view->error_holder = $this->view->render('licence', 'error');
			$this->getResponse()->addHeader("HTTP/1.0 406 Not Acceptable");
		}
		
	}
	
}

?>