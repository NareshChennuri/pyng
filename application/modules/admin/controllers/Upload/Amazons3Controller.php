<?php

class Upload_Amazons3Controller extends JO_Action {

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
		 
		if(isset($config['awsAccessKey'])) {
			$this->view->awsAccessKey = $config['awsAccessKey'];
		} elseif(isset($store_config['awsAccessKey'])) {
			$this->view->awsAccessKey = $store_config['awsAccessKey'];
		} else {
			$this->view->awsAccessKey = '';
		}
		 
		if(isset($config['awsSecretKey'])) {
			$this->view->awsSecretKey = $config['awsSecretKey'];
		} elseif(isset($store_config['awsSecretKey'])) {
			$this->view->awsSecretKey = $store_config['awsSecretKey'];
		} else {
			$this->view->awsSecretKey = '';
		}
		 
		if(isset($config['bucklet'])) {
			$this->view->bucklet = $config['bucklet'];
		} elseif(isset($store_config['bucklet'])) {
			$this->view->bucklet = $store_config['bucklet'];
		} else {
			$this->view->bucklet = '';
		}
		 
		if(isset($config['awsDomain'])) {
			$this->view->awsDomain = $config['awsDomain'];
		} elseif(isset($store_config['awsDomain'])) {
			$this->view->awsDomain = $store_config['awsDomain'];
		} else {
			$this->view->awsDomain = '';
		}
		
	}

}

?>