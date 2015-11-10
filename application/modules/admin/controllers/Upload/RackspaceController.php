<?php

class Upload_RackspaceController extends JO_Action {

	public function indexAction() {
    	
		$request = $this->getRequest();
		
		$store_config = Model_Settings::getSettingsPairs();
		$config = $request->getPost('config');

		if(isset($config['default_upload_method'])) {
			$this->view->default_upload_method = $config['default_upload_method'];
		} elseif(isset($store_config['default_upload_method'])) {
			$this->view->default_upload_method = $store_config['default_upload_method'];
		} else {
			$this->view->default_upload_method = 'Model_Upload_Locale';
		}
		 
		if(isset($config['rsUsername'])) {
			$this->view->rsUsername = $config['rsUsername'];
		} elseif(isset($store_config['rsUsername'])) {
			$this->view->rsUsername = $store_config['rsUsername'];
		} else {
			$this->view->rsUsername = '';
		}
		 
		if(isset($config['rsApiKey'])) {
			$this->view->rsApiKey = $config['rsApiKey'];
		} elseif(isset($store_config['rsApiKey'])) {
			$this->view->rsApiKey = $store_config['rsApiKey'];
		} else {
			$this->view->rsApiKey = '';
		}
		
	}

}

?>