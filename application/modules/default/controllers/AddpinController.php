<?php

class AddpinController extends Helper_Controller_Default {

	public function indexAction() {
		
		$request = $this->getRequest();

		$goodies = Model_Pages::getPage( Helper_Config::get('page_goodies') );
		
		$pin_text = $this->translate('Pyng images from any website as you browse the web with the %s"Pyng It" button.%s');
		if($goodies) {
			$this->view->pin_text = sprintf($pin_text, '<a href="'.WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . Helper_Config::get('page_goodies')).'">', '</a>');
		}
		
		$methods = glob(dirname(__FILE__) . '/Addpin/*.php');
		$this->view->methods = array();
		if($methods) {
			$front = $this->getFrontController();
			foreach($methods AS $row => $method) {
				$controller = basename($method, 'Controller.php');
				if($controller) {
					$name = $front->formatControllerName('addpin_' . strtolower($controller));
					if(!class_exists($name, true)) {
						JO_Loader::loadClass($name);
					}
					$instance = new $name();
					$this->view->methods[ (isset($instance->SORT) ? $instance->SORT : 0) ] = $this->view->callChildren('addpin_' . strtolower($controller));
				}
			}
		} 
		
		ksort($this->view->methods);
		
		$this->view->popup_main_box = $this->view->render('popup_main','addpin');
		
		if($request->isXmlHttpRequest()) {
			$this->noViewRenderer(true);
			echo $this->view->popup_main_box;
		} else {
			$this->forward('error', 'error404');
		}
	}
	
	
	
}

?>