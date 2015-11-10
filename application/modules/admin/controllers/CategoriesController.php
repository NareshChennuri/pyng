<?php

class CategoriesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Categories'),
			'has_permision' => true,
			'menu' => self::translate('Catalog'),
			'in_menu' => true,
			'permision_key' => 'categories',
			'sort_order' => 80502
		);
	}
	
	/////////////////// end config

	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		$places_model = new Model_Categories;
		
		
		$data = array(
//			'start' => 0,
//			'limit' => Helper_Config::get('config_admin_limit'),
			'filter_without_children' => true
		);
		
		$this->view->new_record_url = $request->getBaseUrl() . $request->getModule() . '/categories/create/';
		
		$this->view->categories = array();
		$categories = $places_model->getCategories($data);
		if($categories) {
			foreach($categories AS $category) {
				$category['edit'] = $request->getModule() . '/categories/edit/?id=' . $category['category_id'];
//				$category['boards'] = 1;
				$this->view->categories[] = $category;
			}
		}
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}	
	}
	
	public function createAction() {
		if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('categories_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Categories::createCategory($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/');
		}
		$this->setViewChange('categories_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Categories::editeCategory($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/categories/');
    	}
		
		$this->getForm();
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $place_id) {
			if($place_id) {
				Model_Categories::changeSortOrder($place_id, $sort_order);
			}
		}
		
		echo 1;
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Categories::changeStatus($this->getRequest()->getPost('id'));
		}
	}
	
	public function changeStatusMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Categories::changeStatus($record_id);
			}
		}
		}
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Categories::deleteCategory($this->getRequest()->getPost('id'));
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
				Model_Categories::deleteCategory($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$categories_id = $request->getRequest('id');
    	
    	$places_model = new Model_Categories;
    	
    	if($categories_id) {
    		$categories_info = $places_model->getCategory($categories_id);
    	}
    	
    	$this->view->cancel_url = $request->getModule() . '/categories/';
    	
		if($request->getPost('status')) {
    		$this->view->status = $request->getPost('status');
    	} elseif(isset($categories_info)) {
    		$this->view->status = $categories_info['status'];
    	} else {
    		$this->view->status = 1;
    	}
    	
		if($request->getPost('title')) {
    		$this->view->title = $request->getPost('title');
    	} elseif(isset($categories_info)) {
    		$this->view->title = $categories_info['title'];
    	}
    	
		if($request->getPost('meta_title')) {
    		$this->view->meta_title = $request->getPost('meta_title');
    	} elseif(isset($categories_info)) {
    		$this->view->meta_title = $categories_info['meta_title'];
    	}
    	
		if($request->getPost('meta_description')) {
    		$this->view->meta_description = $request->getPost('meta_description');
    	} elseif(isset($categories_info)) {
    		$this->view->meta_description = $categories_info['meta_description'];
    	}
    	
		if($request->getPost('meta_keywords')) {
    		$this->view->meta_keywords = $request->getPost('meta_keywords');
    	} elseif(isset($categories_info)) {
    		$this->view->meta_keywords = $categories_info['meta_keywords'];
    	}
    	
		if($request->getRequest('keyword')) {
    		$this->view->keyword = $request->getRequest('keyword');
    	} elseif(isset($categories_info)) {
    		$this->view->keyword = $categories_info['keyword'];
    	}
    	
    	// image
    	$image_model = new Helper_Images;
		if($request->getRequest('image')) {
    		$this->view->image = $request->getRequest('image');
    	} elseif(isset($categories_info['image'])) {
    		$this->view->image = $categories_info['image'];
    	} else {
    		$this->view->image = '';
    	}
    	
    	if($this->view->image) {
    		$this->view->preview_image = $image_model->resize($this->view->image, 100, 100);
    	} else {
    		$this->view->preview_image = $image_model->resize(Helper_Config::get('no_image'), 100, 100);
    	}
    	
    	if(!$this->view->preview_image) {
    		$this->view->preview_image = $image_model->resize(Helper_Config::get('no_image'), 100, 100);
    	}
    	
    	
	}
	
}

?>