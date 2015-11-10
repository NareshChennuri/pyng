<?php

class InvitesController extends Helper_Controller_Admin {
	
	public static function config() {
		
		return array(
			'name' => self::translate('Waiting'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'invites',
			'sort_order' => 41000
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
    	$this->view->order = $reques->getRequest('order', 'u.sc_id');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_email = $reques->getQuery('filter_email');
    	$this->view->filter_sent = ($reques->getQuery('filter_sent')>-1 and $reques->getQuery('filter_sent')<4) ? $reques->getQuery('filter_sent') : 0;
    	
    	$url = '';
    	if($this->view->filter_email) { $url .= '&filter_email=' . $this->view->filter_email; }
    	if($this->view->filter_sent) { $url .= '&filter_sent=' . $this->view->filter_sent; }
  
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
    		'filter_email' => $this->view->filter_email,
    	    'filter_sent'	=>    $this->view->filter_sent
    	
    	);
    	
		$this->view->users = array();
        $users = Model_Users::getWaiting($data);
        
        
        if($users) {
            
            foreach($users AS $user) {
            	$user['date_added'] = WM_Date::format($user['date_added'], Helper_Config::get('config_date_format_long_time'));
                $user['invite_href'] = $reques->getModule() . '/invites/invite/?id=' . $user['sc_id'] . $url . $url1 . $url2;
//    			$user['items_href'] =  WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=ads&user_id=' . $user['user_id'] );
//              $user['items_href'] =  $reques->getModule() . '/ads/?filter_user_id=' . $user['user_id'];
                $this->view->users[] = $user;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_id = $reques->getModule() . '/invites/?order=u.sc_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_email = $reques->getModule() . '/invites/?order=u.email&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Users::getTotalWaiting($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/invites/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
    public function deleteWAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Users::deleteWait($this->getRequest()->getPost('id'));
		}
	}
	
    public function inviteAction() {
        if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		$this->setInvokeArg('noViewRenderer',true);
		
		$info = Model_Users::getWait($this->getRequest()->getPost('id'));
		if($info) {
                
		        $this->view->shared_content = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=register&user_id=-1&key=' . $info['key']);
    			
    	        $result = Model_Email::send(
    	        	$info['email'],
    	        	Helper_Config::get('noreply_mail'),
    	        	sprintf($this->translate('You have been invited to join %s'), Helper_Config::get('site_name')),
    	        	$this->view->render('invite', 'invites')
    	        );
		    Model_Users::invite($this->getRequest()->getPost('id'));
		
		}
		}
	}
	
	
	
	
	
}
