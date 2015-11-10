<?php

class SourcesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Sources'),
			'has_permision' => true,
			'menu' => self::translate('Catalog'),
			'in_menu' => true,
			'permision_key' => 'sources',
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
		
		$places_model = new Model_Source();
		
		$this->view->page_num = $page = $request->getRequest('page', 1);
		
		$this->view->filter_source = $request->getQuery('filter_source');
		$url = '';
		if($this->view->filter_source) {
			$url .= '&filter_source=' . $this->view->filter_source;
		}
		
		$data = array(
			'start' => ($page * Helper_Config::get('config_admin_limit')) - Helper_Config::get('config_admin_limit'),
			'limit' => Helper_Config::get('config_admin_limit'),
			'filter_source' => trim($this->view->filter_source),
		);
		
		$this->view->new_record_url = $request->getBaseUrl() . $request->getModule() . '/sources/create/';
		
		$this->view->sources = array();
		$sources = $places_model->getSources($data);
		if($sources) {
			$imgObject = new Helper_Images();
			foreach($sources AS $source) {
				$source['edit'] = $request->getModule() . '/sources/edit/?id=' . $source['source_id'];
				$this->view->sources[] = $source;
			}
		}
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}	
    	
    	$total = $places_model->getTotalSources($data);
    	$pagination = new Model_Pagination;
    	$pagination->setLimit(Helper_Config::get('config_admin_limit'));
    	$pagination->setPage($page);
    	$pagination->setTotal($total);
    	$pagination->setUrl($this->getRequest()->getModule() . '/sources/?page={page}' . $url);
    	$this->view->pagination = $pagination->render();
    	
	}
	
	public function editAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/sources/');
		}
		$this->setViewChange('sources_form');
		
		if($this->getRequest()->isPost()) {
    		if(Model_Source::editeSource($this->getRequest()->getQuery('id'), $this->getRequest()->getParams()) !== false) {
	    		$this->session->set('successfu_edite', true);
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/sources/');
    		} else {
    			$this->view->error = $this->translate('Source is invalid!');
    		}
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Source::deleteSource($this->getRequest()->getPost('id'));
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
				Model_Source::deleteSource($record_id);
			}
		}
		}
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	private function getForm() {
		$request = $this->getRequest();
    	
    	$sources_id = $request->getRequest('id');
    	
    	$places_model = new Model_Source;
    	
    	if($sources_id) {
    		$sources_info = $places_model->getSource($sources_id);
    	}
    	
    	$this->view->cancel_url = $request->getModule() . '/sources/';
    	
		if($request->getPost('source')) {
    		$this->view->source = $request->getPost('source');
    	} elseif(isset($sources_info)) {
    		$this->view->source = $sources_info['source'];
    	}

	}
	
}

?>