<?php

class IgnoredictionaryController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Ignore dictionary'),
			'has_permision' => true,
			'menu' => self::translate('Catalog'),
			'in_menu' => true,
			'permision_key' => 'ignoredictionary',
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
    	$this->view->order = $reques->getRequest('order', 'word');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_dic_id = $reques->getQuery('filter_dic_id');
    	$this->view->filter_word = $reques->getQuery('filter_word');
    	
    	
    	$url = '';
    	if($this->view->filter_dic_id) { $url .= '&filter_dic_id=' . $this->view->filter_dic_id; }
    	if($this->view->filter_word) { $url .= '&filter_name=' . $this->view->filter_word; }
  
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
    		'filter_dic_id' => $this->view->filter_dic_id,
    		'filter_word' => $this->view->filter_word
    	);
    	
		$this->view->words = array();
        $words = Model_Ignoredictionary::getWords($data);
        
        
        if($words) {
            
            foreach($words AS $word) {
                $this->view->words[] = $word;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_dic_id = $reques->getModule() . '/ignoredictionary/?order=dic_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_word = $reques->getModule() . '/ignoredictionary/?order=word&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Ignoredictionary::getTotalWords($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/ignoredictionary/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function createAction() {
	if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Ignoredictionary::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/ignoredictionary/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/ignoredictionary/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Ignoredictionary::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/ignoredictionary/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Ignoredictionary::delete($this->getRequest()->getPost('id'));
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
				Model_Ignoredictionary::delete($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$dic_id = $request->getRequest('id');
    	
    	if($request->getPost('word')) {
    		$this->view->word = $request->getPost('word');
    	} elseif($dic_id) {
    		$this->view->word = Model_Ignoredictionary::getWord($dic_id);
    	} else {
    		$this->view->word = '';
    	}
		
	}

	
	
}

?>