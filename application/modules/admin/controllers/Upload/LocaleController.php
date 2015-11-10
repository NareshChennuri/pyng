<?php

class Upload_LocaleController extends JO_Action {

	public function indexAction() {
    	
		$request = $this->getRequest();
		
		$store_config = Model_Settings::getSettingsPairs();
		$config = $request->getPost('config');
		
		$this->view->is_enable_s3 = JO_Registry::get('system_enable_amazon');

		if(isset($config['default_upload_method'])) {
			$this->view->default_upload_method = $config['default_upload_method'];
		} elseif(isset($store_config['default_upload_method'])) {
			$this->view->default_upload_method = $store_config['default_upload_method'];
		} else {
			$this->view->default_upload_method = 'Model_Upload_Locale';
		}
		
	}
	
}

?>