<?php

class PinsController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Pins'),
			'has_permision' => true,
			'menu' => self::translate('Pins'),
			'in_menu' => true,
			'permision_key' => 'pins',
			'sort_order' => 30505
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
    	$this->view->order = $reques->getRequest('order', 'p.pin_id');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_pin_id = $reques->getQuery('filter_pin_id');
    	$this->view->filter_user_id = $reques->getQuery('filter_user_id');
    	$this->view->filter_fullname = $reques->getQuery('filter_fullname');
    	$this->view->filter_username = $reques->getQuery('filter_username');
    	$this->view->filter_description = $reques->getQuery('filter_description');
    	$this->view->filter_board = $reques->getQuery('filter_board');
    	
    	
    	$url = '';
    	if($this->view->filter_pin_id) { $url .= '&filter_dic_id=' . $this->view->filter_pin_id; }
    	if($this->view->filter_fullname) { $url .= '&filter_fullname=' . $this->view->filter_fullname; }
    	if($this->view->filter_username) { $url .= '&filter_username=' . $this->view->filter_username; }
    	if($this->view->filter_user_id) { $url .= '&filter_user_id=' . $this->view->filter_user_id; }
    	if($this->view->filter_description) { $url .= '&filter_description=' . $this->view->filter_description; }
    	if($this->view->filter_board) { $url .= '&filter_board=' . $this->view->filter_board; }
  
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
    		'filter_pin_id' => $this->view->filter_pin_id,
    		'filter_fullname' => trim($this->view->filter_fullname),
    		'filter_username' => trim($this->view->filter_username),
    		'filter_description' => trim($this->view->filter_description),
    		'filter_board' => trim($this->view->filter_board),
    		'filter_user_id' => $this->view->filter_user_id
    	);
    	
		$this->view->pins = array();
        $pins = Model_Pins::getPins($data);
        
        
        if($pins) {
            
            foreach($pins AS $pin) {
            	$pin['pin_href'] = WM_Router::create( $reques->getBaseUrl() . '?controller=pin&pin_id=' . $pin['pin_id'] );
            	$pin['user_href'] = WM_Router::create($reques->getBaseUrl() . '?controller=users&action=profile&user_id=' . $pin['user_id']);
                $this->view->pins[] = $pin;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_pin_id = $reques->getModule() . '/pins/?order=p.pin_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_fullname = $reques->getModule() . '/pins/?order=fullname&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $reques->getModule() . '/pins/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_price = $reques->getModule() . '/pins/?order=p.price&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_likes = $reques->getModule() . '/pins/?order=p.likes&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_comments = $reques->getModule() . '/pins/?order=p.comments&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_vip = $reques->getModule() . '/pins/?order=p.vip&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	 
        $total_records = Model_Pins::getTotalPins($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/pins/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
	}
	
	public function changeStatusAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
			if(Model_Pins::changeStatus($this->getRequest()->getPost('id'))) {
				echo 'ok';
			} else {
				echo 'error';
			}
		}
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pins/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Pins::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pins/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Pins::delete($this->getRequest()->getPost('id'));
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
				Model_Pins::delete($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$pin_id = $request->getRequest('id');
		$pin_info = Model_Pins::getPin($pin_id);
		if(!$pin_info) {
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pins/');
		}
    	
		if($request->issetPost('description')) {
			$this->view->description = $request->issetPost('description');
		} else {
			$this->view->description = $pin_info['description'];
		}
    	
		if($request->issetPost('from')) {
			$this->view->from = $request->issetPost('from');
		} else {
			$this->view->from = $pin_info['from'];
		}
    	
		if($request->issetPost('board_id')) {
			$this->view->board_id = $request->issetPost('board_id');
		} else {
			$this->view->board_id = $pin_info['board_id'];
		}
		
		$this->view->pin_id = $pin_id;
		$this->view->pin_href = WM_Router::create( $request->getBaseUrl() . '?controller=pin&pin_id=' . $pin_id );
		
		$this->view->boards = Model_Boards::getBoards(array(
			'filter_user_id' => $pin_info['user_id'],
			'sort' => 'asc',
			'order' => 'p.title'
		));
		
	}
	
	/***************************************** COMMENTS FUNCTIONS ********************************************/
	
	public function commentsAction() {
		
		$request = $this->getRequest();
		
		$pin_id = $request->getRequest('filter_id');
		$pin_info = Model_Pins::getPin($pin_id);
		if(!$pin_info) {
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pins/');
		}
		
		$this->view->comments = Model_Comments::getComments(array(
			'filter_pin_id' => $pin_id,
			'sort' => 'ASC',
			'order' => 'pins_comments.comment_id'
		));
	}
	
	public function deleteCommentAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Comments::deleteComment($this->getRequest()->getPost('id'));
		}
	}
	
	public function deleteMultiCommentsAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Comments::deleteComment($record_id);
			}
		}
		}
	}
	
	
}

?>