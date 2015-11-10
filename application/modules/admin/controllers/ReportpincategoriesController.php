<?php

class ReportpincategoriesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Reported pins categories'),
			'has_permision' => true,
			'menu' => self::translate('Pins'),
			'in_menu' => true,
			'permision_key' => 'reportpincategories',
			'sort_order' => 30509
		);
	}
	
	/////////////////// end config

	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		$places_model = new Model_Reportpincategories;
		
		
		$data = array(
//			'start' => 0,
//			'limit' => Helper_Config::get('config_admin_limit'),
			'filter_without_children' => true
		);
		
		$this->view->new_record_url = $request->getBaseUrl() . $request->getModule() . '/reportpincategories/create/';
		
		$this->view->reportpincategories = array();
		$reportpincategories = $places_model->getReportpincategories($data);
		if($reportpincategories) {
			foreach($reportpincategories AS $category) {
				$category['edit'] = $request->getModule() . '/reportpincategories/edit/?id=' . $category['prc_id'];
				$category['boards'] = 1;
				$this->view->reportpincategories[] = $category;
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
		$this->setViewChange('reportpincategories_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Reportpincategories::createReportpincategory($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/reportpincategories/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/reportpincategories/');
		}
		$this->setViewChange('reportpincategories_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Reportpincategories::editeReportpincategory($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/reportpincategories/');
    	}
		
		$this->getForm();
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $place_id) {
			if($place_id) {
				Model_Reportpincategories::changeSortOrder($place_id, $sort_order);
			}
		}
		
		echo 1;
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Reportpincategories::changeStatus($this->getRequest()->getPost('id'));
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
				Model_Reportpincategories::changeStatus($record_id);
			}
		}
		}
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
    		Model_Reportpincategories::deleteReportpincategory($this->getRequest()->getPost('id'));
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
				Model_Reportpincategories::deleteReportpincategory($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$reportpincategories_id = $request->getRequest('id');
    	
    	$places_model = new Model_Reportpincategories; 
    	
    	if($reportpincategories_id) {
    		$reportpincategories_info = $places_model->getReportpincategory($reportpincategories_id);
    	}
    	
    	$this->view->cancel_url = $request->getModule() . '/reportpincategories/';
    	
		if($request->getPost('title')) {
    		$this->view->title = $request->getPost('title');
    	} elseif(isset($reportpincategories_info)) {
    		$this->view->title = $reportpincategories_info['title'];
    	}
    	

    	

    	
	}
	
}

?>