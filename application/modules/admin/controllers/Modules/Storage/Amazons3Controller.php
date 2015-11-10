<?php

class Modules_Storage_Amazons3Controller extends Helper_Controller_Admin {

	public function indexAction() {
	
		if( !WM_Users::allow('edit', 'modules') ) {
			JO_Session::set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage/');
		}
	
		$request = $this->getRequest();
	
		if(JO_Session::get('successfu_edite')) {
			$this->view->successfu_edite = true;
			JO_Session::clear('successfu_edite');
		}
	
		if($request->isPost()) {
			//validate app id
			$this->view->error = false;
			
			try {
				$s3 = new JO_Api_Amazon($request->getPost('amazons3[amazons3_access_key]'), $request->getPost('amazons3[amazons3_secret_key]'));
				//$s3->putBucket($request->getPost('amazons3[amazons3_bucklet]'), JO_Api_Amazon::ACL_PUBLIC_READ);
				$loged = $s3->getBucketLogging($request->getPost('amazons3[amazons3_bucklet]'));
				if($loged) {
					$upload = $s3->putObjectString('test', $request->getPost('amazons3[amazons3_bucklet]'), 'test_upload.txt', JO_Api_Amazon::ACL_PUBLIC_READ);
					if(!$upload) {
						$this->view->error = sprintf($this->translate('Unable to upload to Bucklet "%s"'), $request->getPost('amazons3[amazons3_bucklet]'));
						$loged = false;
					} else {
						$is_file = @file_get_contents(trim($request->getPost('amazons3[amazons3_bucklet_location]'),'/') . '/test_upload.txt');
						if($is_file != 'test') {
							$this->view->error = sprintf($this->translate('Unable to read test file "%s"'), trim($request->getPost('amazons3[amazons3_bucklet_location]'),'/') . '/test_upload.txt');
							$loged = false;
						}
					}
				} else {
					$this->view->error = sprintf($this->translate('Bucklet "%s" not found'), $request->getPost('amazons3[amazons3_bucklet]'));
					$loged = false;
				}
			} catch (JO_Exception $e) {
				$this->view->error = $e->getMessage();
				$loged = false;
			}
			
			if($loged) {
				Model_Settings::updateAll(array('amazons3' => $request->getPost('amazons3')));
				JO_Session::set('successfu_edite', true);
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage_amazons3/');
			} else {
				if(!$this->view->error) { 
					$this->view->error = $this->translate('Invalid App configuration');
				}
			}
		}
	
		$this->view->modules_url = $this->getRequest()->getModule() . '/modules';
		$this->view->modules_storage_url = $this->getRequest()->getModule() . '/modules_storage';
	
		
	
		$store_config = Model_Settings::getSettingsPairs(array(
				'filter_group' => 'amazons3'
		));
		
		if(!isset($store_config['amazons3_access_key'])) {
			$store_config['amazons3_access_key'] = '';
		}
		if(!isset($store_config['amazons3_secret_key'])) {
			$store_config['amazons3_secret_key'] = '';
		}
		if(!isset($store_config['amazons3_bucklet'])) {
			$store_config['amazons3_bucklet'] = '';
		}
		if(!isset($store_config['amazons3_bucklet_location'])) {
			$store_config['amazons3_bucklet_location'] = '';
		}
		
		
	
		foreach($store_config AS $key => $data) {
			if($request->issetPost('amazons3['.$key.']')) {
				$this->view->{$key} = $request->getPost('amazons3['.$key.']');
			} else {
				$this->view->{$key} = $data;
			}
		}
	
	}
	
	/////////////////////////////////////////////////
	
	public function installAction() {
		Model_Extensions_Install::install('amazons3');
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage_amazons3' );
	}
	
	public function uninstallAction() {
		Model_Extensions_Install::uninstall('amazons3');
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage' );
	}
	
}

?>