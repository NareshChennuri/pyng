<?php

class Modules_Storage_RackspaceController extends Helper_Controller_Admin {

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
			$auth = new JO_Api_Rackspace_Authentication($request->getPost('rackspace[rackspace_api_username]'), $request->getPost('rackspace[rackspace_api_key]'), ($request->getPost('rackspace[rackspace_account_name]')?$request->getPost('rackspace[rackspace_account_name]'):NULL), ($request->getPost('rackspace[rackspace_authentication_service_uri]') == 'UK' ? JO_Api_Rackspace_Authentication::UK_AUTHURL : JO_Api_Rackspace_Authentication::US_AUTHURL));
			$loged = false;
			try {
				$loged = $auth->authenticate();
				$connect = new JO_Api_Rackspace_Connection($auth);
				try {
					$images = $connect->get_container($request->getPost('rackspace[rackspace_pins_contaners]'));
					if(!$images->cdn_uri) {
						$this->view->error = sprintf(self::translate('Authentication response did not indicate CDN availability for "%s"'), $request->getPost('rackspace[rackspace_pins_contaners]'));
						$loged = false;
					}
				} catch (JO_Exception $e) {
					$this->view->error = sprintf($this->translate('Container "%s" not found.'), $request->getPost('rackspace[rackspace_pins_contaners]'));
					$loged = false;
				}
				try {
					$images = $connect->get_container($request->getPost('rackspace[rackspace_users_contaners]'));
					if(!$images->cdn_uri) {
						$this->view->error = sprintf(self::translate('Authentication response did not indicate CDN availability for "%s"'), $request->getPost('rackspace[rackspace_pins_contaners]'));
						$loged = false;
					}
				} catch (JO_Exception $e) {
					$this->view->error = sprintf($this->translate('Container "%s" not found.'), $request->getPost('rackspace[rackspace_pins_contaners]'));
					$loged = false;
				}
			} catch (JO_Exception $e) {
				$this->view->error = $e->getMessage();
				$loged = false;
			}
			
			if($loged) {
				Model_Settings::updateAll(array('rackspace' => $request->getPost('rackspace')));
				JO_Session::set('successfu_edite', true);
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage_rackspace/');
			} else {
				//$this->view->error = $this->translate('Invalid App configuration');
			}
		}
	
		$this->view->modules_url = $this->getRequest()->getModule() . '/modules';
		$this->view->modules_storage_url = $this->getRequest()->getModule() . '/modules_storage';
	

	
		$store_config = Model_Settings::getSettingsPairs(array(
				'filter_group' => 'rackspace'
		));
	
		if(!isset($store_config['rackspace_api_username'])) {
			$store_config['rackspace_api_username'] = '';
		}
		if(!isset($store_config['rackspace_api_key'])) {
			$store_config['rackspace_api_key'] = '';
		}
		if(!isset($store_config['rackspace_pins_contaners'])) {
			$store_config['rackspace_pins_contaners'] = '';
		}
		if(!isset($store_config['rackspace_pins_contaners_cdn'])) {
			$store_config['rackspace_pins_contaners_cdn'] = '';
		}
		if(!isset($store_config['rackspace_users_contaners'])) {
			$store_config['rackspace_users_contaners'] = '';
		}
		if(!isset($store_config['rackspace_users_contaners_cdn'])) {
			$store_config['rackspace_users_contaners_cdn'] = '';
		}
		if(!isset($store_config['rackspace_account_name'])) {
			$store_config['rackspace_account_name'] = '';
		}
		if(!isset($store_config['rackspace_authentication_service_uri'])) {
			$store_config['rackspace_authentication_service_uri'] = '';
		}
		
		foreach($store_config AS $key => $data) {
			if($request->issetPost('rackspace['.$key.']')) {
				$this->view->{$key} = $request->getPost('rackspace['.$key.']');
			} else {
				$this->view->{$key} = $data;
			}
		}
	
	}
	
	/////////////////////////////////////////////////
	
	public function installAction() {
		Model_Extensions_Install::install('rackspace');
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage_rackspace' );
	}
	
	public function uninstallAction() {
		Model_Extensions_Install::uninstall('rackspace');
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_storage' );
	}

}

?>