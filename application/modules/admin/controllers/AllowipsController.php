<?php

class AllowipsController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Allowed admin ip\'s'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'allowips',
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
    	$this->view->order = $reques->getRequest('order', 'id');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_ip_id = $reques->getQuery('filter_ip_id');
    	$this->view->filete_ip = $reques->getQuery('filete_ip');
    	
    	
    	$url = '';
    	if($this->view->filter_ip_id) { $url .= '&filter_ip_id=' . $this->view->filter_ip_id; }
    	if($this->view->filete_ip) { $url .= '&filter_name=' . $this->view->filete_ip; }
  
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
    		'filter_id' => $this->view->filter_ip_id,
    		'filete_ip' => $this->view->filete_ip
    	);
    	
		$this->view->ip_addresss = array();
        $ip_addresss = Model_Allowips::getWords($data);
        
        
        if($ip_addresss) {
            
            foreach($ip_addresss AS $ip_address) {
            	$ip_address['ip_address'] = JO_Request_Server::decode_ip($ip_address['ip_address']);
                $this->view->ip_addresss[] = $ip_address;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_ip_address_id = $reques->getModule() . '/allowips/?order=id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_ip_address = $reques->getModule() . '/allowips/?order=ip_address&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Allowips::getTotalWords($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/allowips/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function createAction() {
	if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Allowips::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/allowips/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/allowips/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Allowips::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/allowips/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Allowips::delete($this->getRequest()->getPost('id'));
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
				Model_Allowips::delete($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$ip_address_id = $request->getRequest('id');
    	
    	if($request->getPost('ip_address')) {
    		$this->view->ip_address = $request->getPost('ip_address');
    	} elseif($ip_address_id) {
    		$this->view->ip_address = Model_Allowips::getWord($ip_address_id);
    	} else {
    		$this->view->ip_address = '';
    	}
		
	}

	
	
}

?>