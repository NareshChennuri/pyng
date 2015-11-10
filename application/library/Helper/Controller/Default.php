<?php

class Helper_Controller_Default extends JO_Action {
	
	public function __construct() {
		$request = JO_Request::getInstance();
		
		parent::__construct();
		
		//set default timezone if is not set
		if( !ini_get('date.timezone') ) {

			ini_set('date.timezone', 'UTC');

		}
		
		WM_Users::initSession(JO_Session::get('user[user_id]'));
		if(!JO_Session::get('user[user_id]')) {
			JO_Session::set('user', array('user_id' => 0));
		}
		
		//mobile version

		if($request->issetParam('full_version')) {

			$re = $request->setCookie('full_version', 1, 86400, '/', '.' . $request->getDomain());

			$this->redirect( $request->getBaseUrl() );

		} else if($request->issetParam('remove_full_version')) {

			$re = $request->setCookie('full_version', 0, 86400, '/', '.' . $request->getDomain());

			$this->redirect( $request->getBaseUrl() );

		}

		

		$mobile_detect = new JO_Mobile_Detect();
		JO_Registry::set('isMobile', false);

		if( $mobile_detect->isMobile() && !$mobile_detect->isTablet() && in_array( 'mobile', WM_Modules::getTemplates() ) ) {

			if( !$request->getCookie('full_version') ) {

				JO_Registry::set('template', 'mobile');
				Helper_Config::set('config_disable_js', 0);
				if(Helper_Config::get('site_logo_mobile')) {

					Helper_Config::set('site_logo', Helper_Config::get('site_logo_mobile'));

				}

			}
			JO_Registry::set('isMobile', true);

		}
		
		//is not ajax
		if(!$request->isXmlHttpRequest()) {

			WM_Licensecheck::checkIt();
			
			if(Helper_Config::get('config_maintenance')) {
				if($request->getController() != 'error' && $request->getAction() != 'maintenance') {
					$this->forward('error', 'maintenance');
				}
			}
		
			////first login

			if(JO_Session::get('user[user_id]')) {

				if(JO_Session::get('user[first_login]')) {

					if( !in_array($request->getController(), array('pages','smuk', 'crons','pin','boards','js','json','cache','data')) ) {
						if($request->getParam('direct_path') != 'true') {
							if($request->getController() != 'welcome') {
								if($request->getController() == 'users' && $request->getAction() == 'logout') { } else {
									$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=welcome') );
								}
							}
						}
					}

				} else {

					if($request->getController() == 'welcome') {

						JO_Action::getInstance()->redirect( $request->getBaseUrl() );

					}

				}

			} elseif($request->getController() == 'welcome') {
				JO_Action::getInstance()->redirect( $request->getBaseUrl() );
			}
			
		}
	
		Helper_Config::check();
		
		WM_Licensecheck::checkIt();
		
	}

}

?>