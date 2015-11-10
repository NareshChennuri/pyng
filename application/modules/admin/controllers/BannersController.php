<?php

class BannersController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Banners'),
			'has_permision' => true,
			'menu' => self::translate('Banners'),
			'in_menu' => true,
			'permision_key' => 'banners',
			'sort_order' => 80504
		);
	}
	
	/////////////////// end config

	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	private function positions($key = null) {
		$array = array(
			'index' => self::translate('Home'),
			'all' => self::translate('Everything'),
			'videos' => self::translate('Videos'),
			'popular' => self::translate('Popular'),
			'gifts' => self::translate('Gifts'),
			'pin' => self::translate('View pin'),
			'category' => self::translate('Category')
		);
		
		if($key) {
			return isset($array[$key]) ? $array[$key] : null;
		} else {
			return $array;
		}
		
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
    	
    	$page = $reques->getRequest('page', 1);
  
    	$data = array(
    		'start' => ($page * Helper_Config::get('config_admin_limit')) - Helper_Config::get('config_admin_limit'),
			'limit' => Helper_Config::get('config_admin_limit')
    	);
    	
		$this->view->words = array();
        $words = Model_Banners::getBanners($data);
        
        
        if($words) {
            
            foreach($words AS $word) {
            	$word['text_controller'] = $this->positions($word['controller']);
                $this->view->words[] = $word;
            }
        } 
        
       
        $total_records = Model_Banners::getTotalBanners($data);
		
		$this->view->total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/banners/?page={page}');
		$this->view->pagination = $pagination->render();
        
	}
	
	public function createAction() {
		if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Banners::create($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/banners/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/banners/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Banners::edit($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/banners/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Banners::delete($this->getRequest()->getPost('id'));
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
					Model_Banners::delete($record_id);
				}
			}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$dic_id = $request->getRequest('id');
    	
    	if($dic_id) {
    		$banner_info = Model_Banners::getBanner($dic_id);
    	}
    	
    	if($request->getPost('status')) {
    		$this->view->status = $request->getPost('status');
    	} elseif(isset($banner_info['status'])) {
    		$this->view->status = $banner_info['status'];
    	} else {
    		$this->view->status = 1;
    	}
    	
    	if($request->getPost('status_in_js_mode')) {
    		$this->view->status_in_js_mode = $request->getPost('status_in_js_mode');
    	} elseif(isset($banner_info['status_in_js_mode'])) {
    		$this->view->status_in_js_mode = $banner_info['status_in_js_mode'];
    	} else {
    		$this->view->status_in_js_mode = 0;
    	}
    	
    	if($request->getPost('category_id')) {
    		$this->view->category_id = $request->getPost('category_id');
    	} elseif(isset($banner_info['category_id'])) {
    		$this->view->category_id = $banner_info['category_id'];
    	} else {
    		$this->view->category_id = '';
    	}
    	
    	if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} elseif(isset($banner_info['name'])) {
    		$this->view->name = $banner_info['name'];
    	} else {
    		$this->view->name = '';
    	}
    	
    	if($request->getPost('html')) {
    		$this->view->html = $request->getPost('html');
    	} elseif(isset($banner_info['html'])) {
    		$this->view->html = $banner_info['html'];
    	} else {
    		$this->view->html = '';
    	}
    	
    	if($request->getPost('height')) {
    		$this->view->height = $request->getPost('height');
    	} elseif(isset($banner_info['height'])) {
    		if($banner_info['height']) {
    			$this->view->height = $banner_info['height'];
    		} else {
    			$this->view->height = 180;
    		}
    	} else {
    		$this->view->height = 180;
    	}
    	
    	if($request->getPost('width')) {
    		$this->view->width = $request->getPost('width');
    	} elseif(isset($banner_info['width'])) {
    		if($banner_info['width']) {
    			$this->view->width = $banner_info['width'];
    		} else {
    			$this->view->width = 180;
    		}
    	} else {
    		$this->view->width = 180;
    	}
    	
    	if($request->getPost('position')) {
    		$this->view->position = $request->getPost('position');
    	} elseif(isset($banner_info['position'])) {
    		$this->view->position = $banner_info['position'];
    	} else {
    		$this->view->position = 0;
    	}
    	
    	if($request->getPost('controller_set')) {
    		$this->view->controller_set = $request->getPost('controller_set');
    	} elseif(isset($banner_info['controller'])) {
    		$this->view->controller_set = $banner_info['controller'];
    	} else {
    		$this->view->controller_set = '';
    	}
    	
    	$this->view->categories = Model_Categories::getCategories();
    	
    	$this->view->controllers = $this->positions();
    	
		
	}

	
	
}

?>