<?php

class DictionaryController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Search dictionary'),
			'has_permision' => true,
			'menu' => self::translate('Catalog'),
			'in_menu' => true,
			'permision_key' => 'dictionary',
			'sort_order' => 80504
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
        $words = Model_Dictionary::getWords($data);
        
        
        if($words) {
            foreach($words AS $word) {
                $this->view->words[] = $word;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_dic_id = $reques->getModule() . '/dictionary/?order=dic_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_word = $reques->getModule() . '/dictionary/?order=word&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Dictionary::getTotalWords($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/dictionary/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Dictionary::delete($this->getRequest()->getPost('id'));
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
				Model_Dictionary::delete($record_id);
			}
		}
		}
	}

	
	
}

?>