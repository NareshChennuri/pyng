<?php

class Modules_Oauth_InstagramController extends Helper_Controller_Admin {
	
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
			$validate = new Helper_Modules_Instagram($request->getPost('instagram[instagram_oauth_key]'),$request->getPost('instagram[instagram_oauth_secret]'));
			if($validate->checkValidAppId()) {
			Model_Settings::updateAll(array('instagram' => $request->getPost('instagram')));
			JO_Session::set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth_instagram/');
			} else {
				$this->view->error = $this->translate('Invalid appID');
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
							'edit' => $this->getRequest()->getModule() . '/modules_oauth_instagram/' . strtolower($value)
					);
				} elseif($type == 'radio') {
					$this->view->methods[$type][] = array(
							'title' => $this->translate(str_replace('_', ' ', $value)),
							'key' => 'instagram_' . strtolower($value)
					);
				}
			}
		}
	
		$store_config = Model_Settings::getSettingsPairs(array(
				'filter_group' => 'instagram'
		));
	
		foreach($store_config AS $key => $data) {
			$this->view->{$key} = $data;
		}
	
	}
	
	public function Module_Status_EnableRadio() {
	}
	public function Login_With_InstagramRadio() {
	}
	public function Register_With_InstagramRadio() {
	}
	public function Fetch_Instagram_MediaRadio() {
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
		Model_Extensions_Install::install('instagram');
		if(!Model_Extensions_Install::tableExists('oauth_instagram')) {
			Helper_Db::query("CREATE TABLE IF NOT EXISTS oauth_instagram (
				id BIGINT(20) AUTO_INCREMENT,
				oauth_uid BIGINT(20),
				user_id BIGINT(20),
				username VARCHAR(100),
				access_token VARCHAR(200),
				PRIMARY KEY (`id`),
				KEY `oauth_uid` (`oauth_uid`),
				KEY `user_id` (`user_id`)
				) ENGINE=InnoDB;");
		}
		if(!Model_Extensions_Install::tableExists('instagram_media')) {
			Helper_Db::query("CREATE TABLE IF NOT EXISTS `instagram_media` (
			  `media_id` bigint(20) NOT NULL AUTO_INCREMENT,
			  `user_id` bigint(20) NOT NULL,
			  `pin_id` bigint(20) NOT NULL,
			  `instagram_media_id` bigint(20) NOT NULL,
			  `width` bigint(20) NOT NULL,
			  `height` bigint(20) NOT NULL,
			  `media` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
			  `instagram_profile_id` bigint(20) NOT NULL,
			  `md5key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
			  `title` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
			  `from` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (`media_id`),
			  KEY `user_id` (`user_id`),
			  KEY `instagram_media_id` (`instagram_media_id`),
			  KEY `md5key` (`md5key`),
			  KEY `instagram_profile_id` (`instagram_profile_id`),
			  KEY `pin_id` (`pin_id`)
			) ENGINE=InnoDB;");
		}
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth_instagram' );
	}
	
	public function uninstallAction() {
		Model_Extensions_Install::uninstall('instagram');
		$this->redirect( $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/modules_oauth' );
	}
}

?>