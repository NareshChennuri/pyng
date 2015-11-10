<?php

class Modules_Oauth_TwitterController extends Helper_Controller_Admin {
	
	public function indexAction() {
		
		if( !WM_Users::allow('edit', 'modules') ) {
			JO_Session::set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth/');
		}
	
		$request = $this->getRequest();
	
		if(JO_Session::get('successfu_edite')) {
			$this->view->successfu_edite = true;
			JO_Session::clear('successfu_edite');
		}
	
		if($request->isPost()) {
			//validate app id
			$validate = new Helper_Modules_Twitter($request->getPost('twitter[twitter_oauth_key]'),$request->getPost('twitter[twitter_oauth_secret]'));
			if($validate->checkValidAppId()) {
				Model_Settings::updateAll(array('twitter' => $request->getPost('twitter')));
				JO_Session::set('successfu_edite', true);
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth_twitter/');
			} else {
				$this->view->error = $this->translate('Invalid app key or secret');
			}
		}
		
		$this->view->modules_url = $this->getRequest()->getModule() . '/modules';
		$this->view->modules_oauth_url = $this->getRequest()->getModule() . '/modules_oauth';
	
		$methods = $this->getClassResources();
	
		$this->view->methods = array();
	
		$ignore = array(
				'index', 'error404', 'install', 'uninstall'
		);
	
		foreach($methods AS $type => $mods) {
			foreach($mods AS $key => $value) {
				if(in_array($value, $ignore)) {
					continue;
				}
				if($type == 'actions') {
					$this->view->methods[$type][] = array(
							'title' => $this->translate($value),
							'edit' => $this->getRequest()->getModule() . '/modules_oauth_twitter/' . strtolower($value)
					);
				} elseif($type == 'radio') {
					$this->view->methods[$type][] = array(
							'title' => $this->translate(str_replace('_', ' ', $value)),
							'key' => 'twitter_' . strtolower($value)
					);
				}
			}
		}
	
		$store_config = Model_Settings::getSettingsPairs(array(
				'filter_group' => 'twitter'
		));
	
		foreach($store_config AS $key => $data) {
			if($request->issetPost('twitter['.$key.']')) {
				$this->view->{$key} = $request->getPost('twitter['.$key.']');
			} else {
				$this->view->{$key} = $data;
			}
		}
	
	}
	
	public function Module_Status_EnableRadio() {
	}
	public function Login_With_TwitterRadio() {
	}
	public function Register_With_TwitterRadio() {
	}
	public function Add_Pin_To_TwitterRadio() {
	}
// 	public function Get_User_AvatarRadio() {
// 	}
	
	/**
	 * Get class resources (as resource/method pairs)
	 *
	 * Uses get_class_methods() by default, reflection on prior to 5.2.6,
	 * as a bug prevents the usage of get_class_methods() there.
	 *
	 * @return array
	 */
	public function getClassResources() {
		if (version_compare(PHP_VERSION, '5.2.6') === -1) {
			$class        = new ReflectionObject($this);
			$classMethods = $class->getMethods();
			$methodNames  = array();
	
			foreach ($classMethods as $method) {
				$methodNames[] = $method->getName();
			}
		} else {
			$methodNames = get_class_methods($this);
		}
	
		$_classResources = array(
				'actions' => array(),
				'radio' => array()
		);
		foreach ($methodNames as $method) {
			if ('Action' === substr($method, -6)) {
				$_classResources['actions'][strtolower(substr($method, 0, -6))] = substr($method, 0, -6);
			} elseif ('Radio' === substr($method, -5)) {
				$_classResources['radio'][strtolower(substr($method, 0, -5))] = substr($method, 0, -5);
			}
		}
	
		return $_classResources;
	}
	
	/////////////////////////////////////////////////
	
	public function installAction() {
		Model_Extensions_Install::install('twitter');
		if(!Model_Extensions_Install::tableExists('oauth_twitter')) {
			Helper_Db::query("CREATE TABLE IF NOT EXISTS oauth_twitter (
				id BIGINT(20) AUTO_INCREMENT,
				oauth_uid BIGINT(20),
				user_id BIGINT(20),
				username VARCHAR(100),
				twitter_oauth_token VARCHAR(200),
				twitter_oauth_token_secret VARCHAR(200),
				twit TINYINT(1),
				PRIMARY KEY (`id`),
				KEY `oauth_uid` (`oauth_uid`),
				KEY `user_id` (`user_id`)
				) ENGINE=InnoDB;");
		}
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth_twitter' );
	}
	
	public function uninstallAction() {
		Model_Extensions_Install::uninstall('twitter');
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth' );
	}
}

?>