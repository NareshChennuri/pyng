<?php

class CurrencyController extends Helper_Controller_Admin  {
	
	public static function config() {
		return array(
			'name' => self::translate('Currencies'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'children' => self::translate('Localisation'),
			'in_menu' => true,
			'permision_key' => 'currency',
			'sort_order' => 80600
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
    	if($this->session->get('error_permision')) {
    		$this->view->error_permision = $this->session->get('error_permision');
    		$this->session->clear('error_permision'); 
    	} 
    	
    	$default_currency = WM_Currency::getCurrencyCode();
    	
		$this->view->currency = array();
		$currencies = Model_Currency::getCurrencies(); 
		if($currencies) {
			foreach($currencies AS $currency) {
				$currency['is_set'] = $default_currency == $currency['code'];
				$currency['format'] = WM_Currency::format(1, $currency['code'], 1);
				$this->view->currency[] = $currency;
			}
		}
		
		$this->view->default_currency = WM_Currency::getCurrencyCode();
	}
	
	public function createAction() {
	if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form_pages');
		if($this->getRequest()->isPost()) {
    		Model_Currency::createCurrency($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/currency/');
    	}
		$this->getPageForm();
	}
	
	public function editAction() {
    	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/currency/');
		}
		$this->setViewChange('form_pages');
		if($this->getRequest()->isPost()) {
    		Model_Currency::editeCurrency($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/currency/');
    	}
		$this->getPageForm();
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Currency::changeStatus($this->getRequest()->getPost('id'));
		}
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Currency::deleteCurrency($this->getRequest()->getPost('id'));
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	
	private function getPageForm() {
		$request = $this->getRequest();
		
		$page_id = $request->getQuery('id');
		
		$pages_module = new Model_Currency();
		
		if($page_id) {
			$page_info = $pages_module->getCurrency($page_id);
		}
    	
		
		if($request->getPost('status')) {
    		$this->view->status = $request->getPost('status');
    	} elseif(isset($page_info)) {
    		$this->view->status = $page_info['status'];
    	} else {
    		$this->view->status = 1;
    	}
    	
		if($request->getPost('code')) {
    		$this->view->code = $request->getPost('code');
    	} elseif(isset($page_info)) {
    		$this->view->code = $page_info['code'];
    	} else {
    		$this->view->code = '';
    	}
    	
		if($request->getPost('decimal_place')) {
    		$this->view->decimal_place = $request->getPost('decimal_place');
    	} elseif(isset($page_info)) {
    		$this->view->decimal_place = $page_info['decimal_place'];
    	} else {
    		$this->view->decimal_place = 2;
    	}
    	
		if($request->getPost('value')) {
    		$this->view->value = $request->getPost('value');
    	} elseif(isset($page_info)) {
    		$this->view->value = $page_info['value'];
    	} else {
    		$this->view->value = 0;
    	}
    	
		if($request->getPost('decimal_point')) {
    		$this->view->decimal_point = $request->getPost('decimal_point');
    	} elseif(isset($page_info)) {
    		$this->view->decimal_point = $page_info['decimal_point'];
    	} else {
    		$this->view->decimal_point = '.';
    	}
    	
		if($request->getPost('thousand_point')) {
    		$this->view->thousand_point = $request->getPost('thousand_point');
    	} elseif(isset($page_info)) {
    		$this->view->thousand_point = $page_info['thousand_point'];
    	} else {
    		$this->view->thousand_point = ',';
    	}
    	
		if($request->getPost('title')) {
    		$this->view->title = $request->getPost('title');
    	} elseif(isset($page_info)) {
    		$this->view->title = $page_info['title'];
    	} else {
    		$this->view->title = '';
    	}
    	
		if($request->getPost('symbol_left')) {
    		$this->view->symbol_left = $request->getPost('symbol_left');
    	} elseif(isset($page_info)) {
    		$this->view->symbol_left = $page_info['symbol_left'];
    	} else {
    		$this->view->symbol_left = '';
    	}
    	
		if($request->getPost('symbol_right')) {
    		$this->view->symbol_right = $request->getPost('symbol_right');
    	} elseif(isset($page_info)) {
    		$this->view->symbol_right = $page_info['symbol_right'];
    	} else {
    		$this->view->symbol_right = '';
    	}
    	
		
	}
	
	
  
}