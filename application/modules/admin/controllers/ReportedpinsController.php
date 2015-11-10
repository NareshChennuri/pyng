<?php

class ReportedpinsController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Reported Pins'),
			'has_permision' => true,
			'menu' => self::translate('Pins'),
			'in_menu' => true,
			'permision_key' => 'reportedpins',
			'sort_order' => 30506
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
    	
    	$this->view->sort = $reques->getRequest('sort', 'DESC');
    	$this->view->order = $reques->getRequest('order', 'rp.date_added');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_pr_id = $reques->getQuery('filter_pr_id');
    	$this->view->filter_prc_id = $reques->getQuery('filter_prc_id');
    	$this->view->filter_fullname = $reques->getQuery('filter_fullname');
    	$this->view->filter_username = $reques->getQuery('filter_username');
    	$this->view->filter_description = $reques->getQuery('filter_description');
    	$this->view->filter_board = $reques->getQuery('filter_board');
    	
    	$this->view->categories = Model_Pins::getPinReportCategories();
    	
    	
    	$url = '';
    	if($this->view->filter_date_added) { $url .= '&filter_date_added=' . $this->view->filter_date_added; }
    	if($this->view->filter_pin_id) { $url .= '&filter_pin_id=' . $this->view->filter_pin_id; }
    	if($this->view->filter_fullname) { $url .= '&filter_fullname=' . $this->view->filter_fullname; }
    	if($this->view->filter_username) { $url .= '&filter_username=' . $this->view->filter_username; }
    	if($this->view->filter_user_id) { $url .= '&filter_user_id=' . $this->view->filter_user_id; }
    	if($this->view->filter_description) { $url .= '&filter_description=' . $this->view->filter_description; }
    	if($this->view->filter_board) { $url .= '&filter_description=' . $this->view->filter_board; }
  
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
    		'filter_pr_id' => $this->view->filter_pr_id,
    		'filter_prc_id' => trim($this->view->filter_prc_id),
    		'filter_username' => trim($this->view->filter_username),
    		'filter_description' => trim($this->view->filter_description),
    		'filter_board' => trim($this->view->filter_board),
    		'filter_user_id' => $this->view->filter_user_id
    	);
    	
		$this->view->pins = array();
        $pins = Model_Pins::getPinsR($data);
        
        
        if($pins) {
            
            foreach($pins AS $pin) {
            	$pin['pin_href'] = WM_Router::create( $reques->getBaseUrl() . '?controller=pin&pin_id=' . $pin['pin_id'] );
            	$pin['user_href'] = WM_Router::create($reques->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_id']);
                $this->view->pins[] = $pin;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_pin_id = $reques->getModule() . '/reportedpins/?order=p.pin_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_date_added = $reques->getModule() . '/reportedpins/?order=rp.date_added&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_fullname = $reques->getModule() . '/reportedpins/?order=fullname&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $reques->getModule() . '/reportedpins/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Pins::getTotalPinsR($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/reportedpins/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Pins::deleteR($this->getRequest()->getPost('id'));
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
				Model_Pins::deleteR($record_id);
			}
		}
		}
	}
	
    public function deletepAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Pins::deleteRP($this->getRequest()->getPost('id'));
		}
	}
}

?>