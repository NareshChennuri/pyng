<?php

class ReportcommentcategoriesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Reported comment categories'),
			'has_permision' => true,
			'menu' => self::translate('Pins'),
			'in_menu' => true,
			'permision_key' => 'reportcommentcategories',
			'sort_order' => 30510
		);
	}
	
	/////////////////// end config

	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		$places_model = new Model_Reportcommentcategories;
		
		
		$data = array(
//			'start' => 0,
//			'limit' => Helper_Config::get('config_admin_limit'),
			'filter_without_children' => true
		);
		
		$this->view->new_record_url = $request->getBaseUrl() . $request->getModule() . '/reportcommentcategories/create/';
		
		$this->view->reportcommentcategories = array();
		$reportcommentcategories = $places_model->getReportcommentcategories($data);
		if($reportcommentcategories) {
			foreach($reportcommentcategories AS $category) {
				$category['edit'] = $request->getModule() . '/reportcommentcategories/edit/?id=' . $category['prc_id'];
				$category['boards'] = 1;
				$this->view->reportcommentcategories[] = $category;
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
		$this->setViewChange('reportcommentcategories_form');
		
		if($this->getRequest()->isPost()) { 
    		Model_Reportcommentcategories::createReportcommentcategory($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/reportcommentcategories/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/reportcommentcategories/');
		}
		$this->setViewChange('reportcommentcategories_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Reportcommentcategories::editeReportcommentcategory($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/reportcommentcategories/');
    	}
		
		$this->getForm();
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $place_id) {
			if($place_id) {
				Model_Reportcommentcategories::changeSortOrder($place_id, $sort_order);
			}
		}
		
		echo 1;
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Reportcommentcategories::changeStatus($this->getRequest()->getPost('id'));
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
				Model_Reportcommentcategories::changeStatus($record_id);
			}
		}
		}
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
    		Model_Reportcommentcategories::deleteReportcommentcategory($this->getRequest()->getPost('id'));
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
				Model_Reportcommentcategories::deleteReportcommentcategory($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$reportcommentcategories_id = $request->getRequest('id');
    	
    	$places_model = new Model_Reportcommentcategories; 
    	
    	if($reportcommentcategories_id) {
    		$reportcommentcategories_info = $places_model->getReportcommentcategory($reportcommentcategories_id);
    	}
    	
    	$this->view->cancel_url = $request->getModule() . '/reportcommentcategories/';
    	
		if($request->getPost('title')) {
    		$this->view->title = $request->getPost('title');
    	} elseif(isset($reportcommentcategories_info)) {
    		$this->view->title = $reportcommentcategories_info['title'];
    	}
    	

    	

    	
	}
	
}

?>