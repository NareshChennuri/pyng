<?php

class BlacksourcesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Blacksources'),
			'has_permision' => true,
			'menu' => self::translate('Catalog'),
			'in_menu' => true,
			'permision_key' => 'blacksources',
			'sort_order' => 80503
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
        
    	$reques = $this->getRequest();
    	
    	$this->view->sort = $reques->getRequest('sort', 'ASC');
    	$this->view->order = $reques->getRequest('order', 'source');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_source_id = $reques->getQuery('filter_source_id');
    	$this->view->filter_source = $reques->getQuery('filter_source');
    	
    	
    	$url = '';
    	if($this->view->filter_source_id) { $url .= '&filter_source_id=' . $this->view->filter_source_id; }
    	if($this->view->filter_source) { $url .= '&filter_name=' . $this->view->filter_source; }
  
    	$url1 = '';
    	if($this->view->sort) {
    		$url1 .= '&sort=' . $this->view->sort;
    	}
    	if($this->view->order) {
    		$url1 .= '&order=' . $this->view->order;
    	}
    	
    	$url2 = '&page=' . $page;
    	
    	
    	$data = array(
    		'start' => ($page * Helper_Config::get('config_admin_limit')) - Helper_Config::get('config_admin_limit'),
			'limit' => Helper_Config::get('config_admin_limit'),
    		'sort' => $this->view->sort,
    		'order' => $this->view->order,
    		'filter_source_id' => $this->view->filter_source_id,
    		'filter_source' => $this->view->filter_source
    	);
    	
		$this->view->sources = array();
        $sources = Model_Blacksources::getWords($data);
        
        
        if($sources) {
            
            foreach($sources AS $source) {
                $this->view->sources[] = $source;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_source_id = $reques->getModule() . '/blacksources/?order=source_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_source = $reques->getModule() . '/blacksources/?order=source&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Blacksources::getTotalWords($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/blacksources/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function createAction() {
	if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Blacksources::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/blacksources/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/blacksources/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Blacksources::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/blacksources/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Blacksources::delete($this->getRequest()->getPost('id'));
		}
	}
	
	public function deleteMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Blacksources::delete($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$source_id = $request->getRequest('id');
    	
    	if($request->getPost('source')) {
    		$this->view->source = $request->getPost('source');
    	} elseif($source_id) {
    		$this->view->source = Model_Blacksources::getWord($source_id);
    	} else {
    		$this->view->source = '';
    	}
		
	}

	
	
}

?>