<?php

class UsersController extends Helper_Controller_Admin {
	
	public static function config() {
		
		return array(
			'name' => self::translate('Users management'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'users',
			'sort_order' => 21000
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
    	$this->view->order = $reques->getRequest('order', 'u.user_id');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_id = $reques->getQuery('filter_id');
    	$this->view->filter_name = $reques->getQuery('filter_name');
    	$this->view->filter_username = $reques->getQuery('filter_username');
    	$this->view->filter_email = $reques->getQuery('filter_email');
    	$this->view->filter_delete_account = $reques->getQuery('filter_delete_account');
    	
    	
    	$url = '';
    	if($this->view->filter_id) { $url .= '&filter_id=' . $this->view->filter_id; }
    	if($this->view->filter_name) { $url .= '&filter_name=' . $this->view->filter_name; }
    	if($this->view->filter_username) { $url .= '&filter_username=' . $this->view->filter_username; }
    	if($this->view->filter_email) { $url .= '&filter_email=' . $this->view->filter_email; }
    	if($this->view->filter_delete_account) { $url .= '&filter_delete_account=' . $this->view->filter_delete_account; }
  		$filter_delete_account = $this->view->filter_delete_account;
    	if($this->view->filter_delete_account == '*') {
  			$filter_delete_account = null;
  		}
  		
  		if(!$reques->issetQuery('filter_delete_account')) {
  			$this->view->filter_delete_account = '*';
  		}
    	
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
    		'filter_user_id' => $this->view->filter_id,
    		'filter_name' => $this->view->filter_name,
    		'filter_username' => $this->view->filter_username,
    		'filter_delete_account' => $filter_delete_account,
    		'filter_email' => $this->view->filter_email
    	);
    	
		$this->view->users = array();
        $users = Model_Users::getUsers($data);
        
        
        if($users) {
            
            foreach($users AS $user) {
            	
                $user['edit_href'] = $reques->getModule() . '/users/edite/?id=' . $user['user_id'] . $url . $url1 . $url2;
//    			$user['items_href'] =  WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=ads&user_id=' . $user['user_id'] );
                //$user['items_href'] =  $reques->getModule() . '/ads/?filter_user_id=' . $user['user_id'];
                $user['username_href'] =  $reques->getModule() . '/pins/?filter_user_id=' . $user['user_id'];
                $user['boards_href'] =  $reques->getModule() . '/boards/?filter_user_id=' . $user['user_id'];
                $user['profile_url'] = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user['user_id']);
				$user['delete_account_date'] = $user['delete_account_date'] != '0000-00-00 00:00:00' ? WM_Date::format($user['delete_account_date'], Helper_Config::get('config_date_format_long_time')) : '';
                
                $this->view->users[] = $user;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_id = $reques->getModule() . '/users/?order=u.user_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_name = $reques->getModule() . '/users/?order=u.firstname&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $reques->getModule() . '/users/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_pins = $reques->getModule() . '/users/?order=u.pins&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_boards = $reques->getModule() . '/users/?order=u.boards&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_likes = $reques->getModule() . '/users/?order=u.likes&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
        
        $total_records = Model_Users::getTotalUsers($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/users/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}

	
//	public function createAction() {
//		$this->setViewChange('form');
//		
//		if($this->getRequest()->isPost()) {
//    		Model_Users::createUser($this->getRequest()->getParams());
//    		$this->session->set('successfu_edite', true);
//    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/');
//    	}
//		
//		$this->getForm();
//	}
	
	public function editeAction() {
    	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/');
		}
		$this->setViewChange('form');
		
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Users::editeUser($request->getQuery('id'), $request->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    		if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    		if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    		if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    		if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    		if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
    		if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    		if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
    		if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
    		if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
    		if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
    		if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
    		if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
    		if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
    		
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?' . $url);
    	}
		
		$this->getForm();
	}
	
    public function createAction() {
        if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form');
		
		$request = $this->getRequest();
		
		if($request->isPost()) {
		    if(trim($request->getPost('password')) and !Model_Users::isExistEmail($request->getPost('email')) and !Model_Users::isExistUsername($request->getPost('username'))) {
        		Model_Users::createUser($request->getParams());
        		$this->session->set('successfu_edite', true);    		
        		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/');
		    }
    	}
    	
    	$this->view->new = true;
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Users::deleteUser($this->getRequest()->getPost('id'));
		}
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$user_id = $request->getQuery('id');
		
		$url = '';
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
    		
    	$this->view->cancel_url = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?' . $url;
		
		$model_users = new Model_Users;
		
		$user_info = $model_users->getUser($user_id);
		
		$this->view->utypes = array(
			'user' => $this->translate('User'),
			'agency' => $this->translate('Agency'),
			'employer' => $this->translate('Employer')
		);
		
		
		if($user_info) {
			$this->view->user_id = $user_id;
			//$this->view->title = $user_info['title'];
			/*$this->view->type = $user_info['type'];*/
			/*$this->view->username = $user_info['username'];*/
			$this->view->names = $user_info['firstname'] . ' ' . $user_info['lastname'];
			/*$this->view->email = $user_info['email'];*/
			$this->view->status = $user_info['status'];
			
			$this->view->profile_url = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_id );
			
		}
		
    	if($request->getPost('username')) {
			$this->view->username = $request->getPost('username');
		} elseif(isset($user_info)) {
			$this->view->username = ($user_info['username']);
		} else {
			$this->view->username = '';
		}
		
    	if($request->getPost('email')) {
			$this->view->email = $request->getPost('email');
		} elseif(isset($user_info)) {
			$this->view->email = ($user_info['email']);
		} else {
			$this->view->email = '';
		}
		
    	if($request->getPost('is_admin')) {
			$this->view->is_admin = $request->getPost('is_admin');
		} elseif(isset($user_info)) {
			$this->view->is_admin = ($user_info['is_admin']);
		} else {
			$this->view->is_admin = '';
		}
		
    	if($request->getPost('is_developer')) {
			$this->view->is_developer = $request->getPost('is_developer');
		} elseif(isset($user_info)) {
			$this->view->is_developer = ($user_info['is_developer']);
		} else {
			$this->view->is_developer = '';
		}
		
	    if($request->getPost('firstname')) {
			$this->view->firstname = $request->getPost('firstname');
		} elseif(isset($user_info)) {
			$this->view->firstname = ($user_info['firstname']);
		} else {
			$this->view->firstname = '';
		}
		
	    if($request->getPost('lastname')) {
			$this->view->lastname = $request->getPost('lastname');
		} elseif(isset($user_info)) {
			$this->view->lastname = ($user_info['lastname']);
		} else {
			$this->view->lastname = '';
		}
		
		if($request->getPost('groups')) {
			$this->view->groups = $request->getPost('groups');
		} elseif(isset($user_info)) {
			$this->view->groups = (array)unserialize($user_info['groups']);
		} else {
			$this->view->groups = array();
		}
		
		
		$this->view->groups_list = Model_Usergroups::getGroups();
		
		
	} 
}
