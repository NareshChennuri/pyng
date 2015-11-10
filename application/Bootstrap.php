<?php

class Bootstrap extends JO_Application_Bootstrap_Bootstrap {
	
	public function _initWWW() {
		$request = JO_Request::getInstance();
		$domain = $request->getDomain(false);
		if(strpos(strtolower($domain),'www.') === 0) {
			$new_url = str_ireplace('www.','',$request->getFullUrl());
			JO_Action::getInstance()->redirect($new_url);
		}
	}
	
	public function _initInstall() {
		$request = JO_Request::getInstance();
		if( (!is_array(JO_Registry::get('config_db')) || !JO_Registry::get('config_db')) && $request->getModule() != 'install' ) {
			JO_Action::getInstance()->redirect($request->getBaseUrl() . '?module=install');
		}
	}
	
	public function _initRoute() {
		$request = JO_Request::getInstance();
		
		$uri = $request->getUri();
		$request->setParams('_route_', trim($uri, '/'));
		if(isset($_GET) && is_array($_GET)) {
			$request->setParams($_GET);
		}
		
		if($request->getModule() == 'install') {
			return'';
		}
		$pin_prefix = Helper_Config::get('config_pin_prefix') ? Helper_Config::get('config_pin_prefix') : 'pin';
		
		//init pin route
		if(preg_match('~^'.preg_quote($pin_prefix).'/([0-9]{1,})/?~i',trim($uri, '/'),$m)) {
			$request->setController('pin')->setAction('index')->setParams('pin_id', $m[1]);
		} elseif(preg_match('~^'.preg_quote($pin_prefix).'/(\w+)/([0-9]{1,})/?~i',trim($uri, '/'),$m)) {
			$request->setController('pin')->setAction($m[1])->setParams('pin_id', $m[2]);
		}
		
		
		if($uri && $request->getModule() != 'admin') {
			WM_Router::route($uri);
		}
// 		var_dump(JO_Request::getInstance()->getParams());exit;
//		var_dump( JO_Request::getInstance()->getSegment(2) );

	}
	
	public function _initCMD() {
		$args = JO_Shell::getArgv();
		if($args && is_array($args)) {
			$request = JO_Request::getInstance();
			foreach($args AS $key => $data) {
				if($key) {
					$request->setParams($key, (string)$data);
				}
			}
		}
	}
	
	public function _initTranslate() {
		$request = JO_Request::getInstance();
		if($request->getModule() == 'install') {
			return'';
		}
		$translate = new WM_Gettranslate();
		JO_Registry::set('JO_Translate', WM_Translate::getInstance(array('data' => $translate->getTranslate())));
	}
	
	public function _initCompresion() {
		JO_Response::getInstance()
		->setLevel(9);
	}
	
}