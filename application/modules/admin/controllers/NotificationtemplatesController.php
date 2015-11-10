<?php

class NotificationtemplatesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Notifications'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80400
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}

	public function indexAction() {
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
		
    	$percents = Model_Notificationtemplates::getAll();
		$this->view->percents = array();
		if($percents) {
			foreach($percents AS $percent) {
				$this->view->percents[] = $percent;
			}
		}
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Notificationtemplates::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/notificationtemplates/');
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Notificationtemplates::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/notificationtemplates/');
    	}
		$this->getForm();
	}
	
//	public function deleteAction() {
//		$this->setInvokeArg('noViewRenderer',true);
//		Model_Deposit::delete($this->getRequest()->getPost('id'));
//	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Notificationtemplates::get($id);
		}
		
		if($request->getPost('title')) {
    		$this->view->title = $request->getPost('title');
    	} elseif(isset($info)) {
    		$this->view->title = $info['title'];
    	} else {
    		$this->view->title = '';
    	}
		
		if($request->getPost('template')) {
    		$this->view->template1 = $request->getPost('template');
    	} elseif(isset($info)) {
    		$this->view->template1 = $info['template'];
    	} else {
    		$this->view->template1 = '';
    	}
    	
    	if(isset($info)) {
    		$this->view->info = $info['info'];
    	}
		
	}
	
}

?>