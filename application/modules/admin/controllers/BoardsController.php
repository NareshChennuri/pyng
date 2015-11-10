<?php

class BoardsController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Boards'),
			'has_permision' => true,
			'menu' => self::translate('Pins'),
			'in_menu' => true,
			'permision_key' => 'boards',
			'sort_order' => 30509
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
    	$this->view->order = $reques->getRequest('order', 'p.board_id');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_board_id = $reques->getQuery('filter_board_id');
    	$this->view->filter_user_id = $reques->getQuery('filter_user_id');
    	$this->view->filter_board_name = $reques->getQuery('filter_board_name');
    	$this->view->filter_username = $reques->getQuery('filter_username');
    	
    	
    	$url = '';
    	if($this->view->filter_board_id) { $url .= '&filter_dic_id=' . $this->view->filter_board_id; }
    	if($this->view->filter_board_name) { $url .= '&filter_board_name=' . $this->view->filter_board_name; }
    	if($this->view->filter_username) { $url .= '&filter_username=' . $this->view->filter_username; }
    	if($this->view->filter_user_id) { $url .= '&filter_user_id=' . $this->view->filter_user_id; }
  
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
    		'filter_board_id' => $this->view->filter_board_id,
    		'filter_board_name' => trim($this->view->filter_board_name),
    		'filter_username' => trim($this->view->filter_username),
    		'filter_user_id' => $this->view->filter_user_id
    	);
    	
		$this->view->boards = array();
        $boards = Model_Boards::getBoards($data);
        
        
        if($boards) {
            
            foreach($boards AS $board) {
            	$board['board_href'] = WM_Router::create( $reques->getBaseUrl() . '?controller=board&board_id=' . $board['board_id'] );
            	$board['user_href'] = WM_Router::create($reques->getBaseUrl() . '?controller=users&action=profile&user_id=' . $board['user_id']);
                $board['shared'] = Model_Boards::boardShared($board['board_id']);
            	$this->view->boards[] = $board;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_board_id = $reques->getModule() . '/boards/?order=p.board_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_total_views = $reques->getModule() . '/boards/?order=p.total_views&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_followers = $reques->getModule() . '/boards/?order=p.followers&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_pins = $reques->getModule() . '/boards/?order=p.pins&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $reques->getModule() . '/boards/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        $total_records = Model_Boards::getTotalBoards($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/boards/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function editAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/boards/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
		   
    		Model_Boards::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/boards/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
			Model_Boards::delete($this->getRequest()->getPost('id'));
			echo 'ok';
		}
	}
	
	public function deleteMultiAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/boards/');
		}
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Boards::delete($record_id);
			}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$board_id = $request->getRequest('id');
    	
	    $board_info = Model_Boards::getBoard($board_id);

		
		if($board_info) {
			$this->view->board_id = $board_id;
//			$this->view->title = $board_info['title'];
			/*$this->view->type = $user_info['type'];*/
			/*$this->view->username = $user_info['username'];*/
//			$this->view->names = $user_info['firstname'] . ' ' . $user_info['lastname'];
			/*$this->view->email = $user_info['email'];*/
//			$this->view->status = $user_info['status'];
			
//			$this->view->profile_url = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=users&action=profile&user_id=' . $user_id );
			
		}
		
    	if($request->getPost('title')) {
			$this->view->title = $request->getPost('title');
		} elseif(isset($board_info)) {
			$this->view->title = ($board_info['title']);
		} else {
			$this->view->title = '';
		}
		
    	if($request->getPost('description')) {
			$this->view->description = $request->getPost('description');
		} elseif(isset($board_info)) {
			$this->view->description = ($board_info['description']);
		} else {
			$this->view->description = '';
		}
		
	    if($request->getPost('category_id')) {
			$this->view->category_id = $request->getPost('category_id');
		} elseif(isset($board_info)) {
			$this->view->category_id = ($board_info['category_id']);
		} else {
			$this->view->category_id = '';
		}
		
	    if($request->getPost('keyword')) {
			$this->view->keyword = $request->getPost('keyword');
		} elseif(isset($board_info)) {
			$this->view->keyword = ($board_info['keyword']);
		} else {
			$this->view->keyword = '';
		}
		
		
		
		$this->view->categories = Model_Categories::getCategories();
		    	
		
	}

	
	
}

?>