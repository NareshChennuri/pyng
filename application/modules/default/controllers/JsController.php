<?php

class JsController extends Helper_Controller_Default {

	public function pinmarkletAction() {
		$this->noLayout(true);
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		$response->addHeader('Content-type: application/javascript; charset=utf-8');
		
		$request = $this->getRequest();
		
		$this->view->checkpoint = WM_Router::create( $request->getBaseUrl() . '?controller=bookmarklet&action=urlinfo' );
		$this->view->bookmarklet = WM_Router::create( $request->getBaseUrl() . '?controller=bookmarklet' );
		
		$this->view->imagefolder = $request->getBaseUrl() . 'data/images/';
		$this->view->baseUrl = $request->getBaseUrl();
		
		$this->view->domain = str_replace('.','\.',$request->getDomain(true));
		
		$this->view->site_logo = $request->getBaseUrl() . 'data/images/logo.png';
		if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
		    $this->view->site_logo = $request->getBaseUrl() . 'uploads' . Helper_Config::get('site_logo'); 
		}
		
		$this->view->blacksource = array();
		$checked_domain = $request->getQuery('d');
		if( ( $cleared = JO_Validate::validateHost($checked_domain) ) !== false) {
			$cleared = preg_replace('/^www./i', '', $cleared);
			if( Model_Blacksources::is_exists($cleared) ) {
				$this->view->blacksource = array(
					'regexp' => '/^https?:\/\/.*?\.?' . preg_quote($cleared) . '\//',
					'key' => 'blacksource',
					'domain' => $cleared
				);
			}
		}
		
		$config_image_minimum_size = (int)Helper_Config::get('config_image_minimum_size');
		if(!$config_image_minimum_size) { $config_image_minimum_size = 80; }
		$this->view->config_image_minimum_size = $config_image_minimum_size;
		
	}

	public function i18nAction() {
		
		$translate = new WM_Gettranslate();
		$results = $translate->getTranslateJs();
		if($results) {
			foreach($results AS $key => $data) {
				$this->view->{$key} = $data;
			}
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		$response->addHeader('Content-type: application/json; charset=utf-8');
		
		echo 'var lang = ' . $this->renderScript('json') . ';';
	}
	
	public function pinitAction() {
		
		$request = $this->getRequest();
		$this->noLayout(true);
		
		$this->view->baseUrl = $request->getBaseUrl();
		
		$this->view->bookmarklet = WM_Router::create( $request->getBaseUrl() . '?controller=bookmarklet' );
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		$response->addHeader('Content-type: application/javascript; charset=utf-8');	
	}
	
	public function configAction() {
		$request = $this->getRequest();
		$this->noLayout(true);
		
		/* price formats */
		$currencies = WM_Currency::getCurrencies();
		$price_left = array();
		$price_right = array();
		if($currencies) {
			foreach($currencies AS $currency) {
				if(trim($currency['symbol_left'])) {
					$price_left[] = preg_quote(trim($currency['symbol_left']));
				}
				if(trim($currency['symbol_right'])) {
					$price_right[] = preg_quote(trim($currency['symbol_right']));
				}
			}
		}
		
		$price = array();
		if($price_left) {
			$price['price_left'] = 'js:/('.implode('|',$price_left).')([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/';
		}
		if($price_right) {
			$price['price_right'] = 'js:/(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?('.implode('|',$price_right).')/';
		}
		
		/* config data */
		$loged = JO_Session::get('user[user_id]');
		$config = array(
			'loged' => $loged,
			'load_dynamically_extensions' => array(),
			'load_dynamically_extensions_css' => array(),
			'facebook_app_id' => null,
			'regExPrice' => $price,
			'comments_list' => (int)Helper_Config::get('config_comments_list'),
			'disable_js' => (int)Helper_Config::get('config_disable_js'),
			'invate_limit' => 5,
			//urls
			'baseUrl' => $request->getBaseUrl(),
			'search_autocomplete' => WM_Router::create($request->getBaseUrl() . '?controller=search&action=autocomplete'),
			'get_user_friends' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=friends' ),
			'edit_description' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=editDescription'),
			'order_boards' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=sort_order'),
			'resend_email_verification' => WM_Router::create( $request->getBaseUrl() . '?controller=users&action=resend'),
			'createboardwithoutcategory' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory'),
			//texts
			'text' => array(
				'text_save_description' => $this->translate('Save Description'),
				'text_rearrange_boards' => $this->translate('Rearrange Boards'),
				'text_save_arrangement' => $this->translate('Save Arrangement'),
				'text_create_board_input' => $this->translate('Create New Board'),
				'text_create_board_button' => $this->translate('Create')
			)
		);
		
		$other_config =  Model_Extensions::getByMethod('extensions_config_js');
		if(is_array($other_config)) {
			$front = $this->getFrontController();
			foreach($other_config AS $ext) {
				$settings = Model_Extensions::getSettingsPairs($ext);
				if(isset($settings[$ext . '_module_status_enable']) && $settings[$ext . '_module_status_enable']) {
					$call = $front->formatModuleName('model_' . $ext . '_jsConfig'); 
					$config = call_user_func(array($call,'extendsConfig'), $config);
				}
			}
		}
		
		//format config
		$this->view->config_data = JO_Javascript::encode($config);
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		$response->addHeader('Content-type: application/javascript; charset=utf-8');
	}
	
}

?>