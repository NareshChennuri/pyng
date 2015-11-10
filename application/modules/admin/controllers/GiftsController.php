<?php

class GiftsController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Gifts between sums'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80301
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
		
    	$percents = Model_Gifts::getAll();
		$this->view->percents = array();
		if($percents) {
			foreach($percents AS $percent) {
				$this->view->percents[] = array(
					'id' => $percent['id'],
					'price_from' => WM_Currency::format($percent['price_from']),
					'price_to' => WM_Currency::format($percent['price_to'])
				);
			}
		}
	}
	
	public function createAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Gifts::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/gifts/');
    	}
		$this->getForm();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		if($this->getRequest()->isPost()) {
    		Model_Gifts::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/gifts/');
    	}
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Gifts::delete($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$id = $request->getQuery('id');
		
		if($id) {
			$info = Model_Gifts::get($id);
		}
		
		if($request->getPost('deposit')) {
    		$this->view->deposit = $request->getPost('deposit');
    	} elseif(isset($info)) {
    		$this->view->deposit = $info['sum'];
    	} else {
    		$this->view->deposit = '';
    	}
		
		if($request->getPost('months')) {
    		$this->view->months = $request->getPost('months');
    	} elseif(isset($info)) {
    		$this->view->months = $info['period'];
    	} else {
    		$this->view->months = '';
    	}
		
	}

}

?>