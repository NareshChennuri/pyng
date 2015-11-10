<?php

class SettingsController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Settings'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'settings',
			'sort_order' => 80100
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	private $error = array();
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}	
    	if($this->session->get('error_permision')) {
    		$this->view->error_permision = $this->session->get('error_permision');
    		$this->session->clear('error_permision'); 
    	}
    	
    	
		$request = $this->getRequest();
		$this->setViewChange('form');
		$this->view->error = false;
		if($request->isPost()) {
			
			if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
				$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/settings/');
			}
			
			if(JO_Registry::get('license_powered_check') != 'false') {
				$request->setParams('config_hide_brand', 0);
			}
			
			if($this->validate()) {
				Model_Settings::updateAll($request->getParams());
				$config = $request->getPost('config');
				if(isset($config['config_currency_auto_update']) && $config['config_currency_auto_update'] == 1) {
	    			WM_Currency::updateCurrencies($config['config_currency'], true);
	    		}
				$this->session->set('successfu_edite', true);
	    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/settings/');
			} else {
				$this->view->error = implode('<br />', $this->error);
			}
		} 
		$this->getForm();
	}
	
	private function getReserved() {
		$array = array();
		$array['admin'] = 'admin';
		$array['default'] = 'default';
		
		$front = JO_Front::getInstance()->getModuleDirectoryWithDefault('default/controllers');
		
		foreach(WM_Modules::getControllersWithFolders($front) AS $controller) {
			$controllerName = JO_Front::getInstance()->formatControllerName($controller);
			$array[$controller] = $controller;
			$array = array_merge($array, WM_Modules::getControllerActions($controllerName, $front));
		}
		
		$array = JO_Utf8::array_change_key_case_unicode($array);
		
		return $array;
	}
	
	private function validate() {
		$request = $this->getRequest();
		
		if(!$request->getPost('config[config_pin_prefix]')) {
			$this->error[] = $this->translate('Please enter Pin prefix');
		} else {
			
			$reserved = $this->getReserved();
			if(isset($reserved['pin'])) {
				unset($reserved['pin']);
			}
			
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('config[config_pin_prefix]'), $this->translate('Pin prefix'), 'not_empty;min_length[1];max_length[32];username');
			if($validate->_valid_form()) {
				if( array_key_exists(strtolower($request->getPost('config[config_pin_prefix]')), $reserved) ) {
					$this->error[] = sprintf($this->translate('%s is system defined word for Pin prefix.'), $request->getPost('config[config_pin_prefix]'));
				}
			} else {
				$this->error[] = $validate->_get_error_messages();
			}
		}
		
		if($this->error) {
			return false;
		}
		return true;
	}
	
	public function getForm() {
    	
		$request = $this->getRequest();
		
		$this->view->templates = $this->getTemplates(array(
			'mobile'		
		));
		
		
		$config = $request->getPost('config');
		$images = $request->getPost('images');
		$pages = $request->getPost('pages');
		
		$store_config = Model_Settings::getSettingsPairs();
		
		
		$this->view->pages = Model_Pages::getPagesFromParent(0);
		
		$this->view->pages_about = array();
		$this->view->pages_about[] = array(
			'title' => $this->translate('SEPARATOR MENU'),
			'page_id' => -1
		);
		if($this->view->pages) {
			foreach($this->view->pages AS $p) {
				$this->view->pages_about[] = array(
					'title' => $p['title'],
					'page_id' => $p['page_id']
				);
			}
		}
		
		$this->view->currencies = Model_Currency::getCurrencies();
		
	
		
    	$this->view->app_path = BASE_PATH;
    	
		//////////////////////////////////////// GENERAL ////////////////////////////////////////
	
		if(isset($config['config_base_domain'])) {
    		$this->view->config_base_domain = $config['config_base_domain'];
    	} elseif(isset($store_config['config_base_domain'])) { 
    		$this->view->config_base_domain = $store_config['config_base_domain'];
    	} else {
    		$this->view->config_base_domain = $request->getBaseUrl();
    	}
    	
		if(isset($config['config_on_facebook'])) {
    		$this->view->config_on_facebook = $config['config_on_facebook'];
    	} elseif(isset($store_config['config_on_facebook'])) { 
    		$this->view->config_on_facebook = $store_config['config_on_facebook'];
    	} else {
    		$this->view->config_on_facebook = '';
    	}
    	
    	if(isset($config['google_analytics'])) {
    		$this->view->google_analytics = $config['google_analytics'];
    	} elseif(isset($store_config['google_analytics'])) {
    		$this->view->google_analytics = $store_config['google_analytics'];
    	}
	
    	if(isset($config['config_fix_image_thumb_editor'])) {
    		$this->view->config_fix_image_thumb_editor = $config['config_fix_image_thumb_editor'];
    	} elseif(isset($store_config['config_fix_image_thumb_editor'])) {
    		$this->view->config_fix_image_thumb_editor = $store_config['config_fix_image_thumb_editor'];
    	}
	
    	if(isset($config['config_fix_external_urls'])) {
    		$this->view->config_fix_external_urls = $config['config_fix_external_urls'];
    	} elseif(isset($store_config['config_fix_external_urls'])) {
    		$this->view->config_fix_external_urls = $store_config['config_fix_external_urls'];
    	}
    	
    	//////////////////////////////////////// SEO ////////////////////////////////////////
		if(isset($config['site_name'])) {
    		$this->view->site_name = $config['site_name'];
    	} elseif(isset($store_config['site_name'])) {
    		$this->view->site_name = $store_config['site_name'];
    	}
    	
		if(isset($config['meta_title'])) {
    		$this->view->meta_title = $config['meta_title'];
    	} elseif(isset($store_config['meta_title'])) {
    		$this->view->meta_title = $store_config['meta_title'];
    	}
	
    	if(isset($config['meta_keywords'])) {
    		$this->view->meta_keywords = $config['meta_keywords'];
    	} elseif(isset($store_config['meta_keywords'])) {
    		$this->view->meta_keywords = $store_config['meta_keywords'];
    	}
	
    	if(isset($config['meta_description'])) {
    		$this->view->meta_description = $config['meta_description'];
    	} elseif(isset($store_config['meta_description'])) {
    		$this->view->meta_description = $store_config['meta_description'];
    	}
    	
    	//////////////////////////////////////// Options ////////////////////////////////////////
		if(isset($config['config_admin_limit'])) {
    		$this->view->config_admin_limit = $config['config_admin_limit'];
    	} elseif(isset($store_config['config_admin_limit'])) { 
    		$this->view->config_admin_limit = $store_config['config_admin_limit'];
    	} else {
    		$this->view->config_admin_limit = 15;
    	}
    	
		if(isset($config['config_front_limit'])) {
    		$this->view->config_front_limit = $config['config_front_limit'];
    	} elseif(isset($store_config['config_front_limit'])) { 
    		$this->view->config_front_limit = $store_config['config_front_limit'];
    	} else {
    		$this->view->config_front_limit = 10;
    	}
    	
		if(isset($config['template'])) {
    		$this->view->template = $config['template'];
    	} elseif(isset($store_config['template'])) { 
    		$this->view->template = $store_config['template'];
    	}
    	
		if(isset($config['config_maintenance'])) {
    		$this->view->config_maintenance = $config['config_maintenance'];
    	} elseif(isset($store_config['config_maintenance'])) { 
    		$this->view->config_maintenance = $store_config['config_maintenance'];
    	} else {
    		$this->view->config_maintenance = 0;
    	}
    	
		if(isset($config['config_hide_brand'])) {
    		$this->view->config_hide_brand = $config['config_hide_brand'];
    	} elseif(isset($store_config['config_hide_brand'])) { 
    		$this->view->config_hide_brand = $store_config['config_hide_brand'];
    	} else {
    		$this->view->config_hide_brand = 0;
    	}
    	
    	$this->view->data_timezones = JO_Date_Timezones::getTimezonse();
		if(isset($config['phpSettings']['date.timezone'])) {
    		$this->view->date_timezone = $config['phpSettings']['date.timezone'];
    	} elseif(isset($store_config['phpSettings']['date.timezone'])) { 
    		$this->view->date_timezone = $store_config['phpSettings']['date.timezone'];
    	} else {
    		$this->view->date_timezone = 'UTC';
    	}
    	
		if(isset($config['config_disable_js'])) {
    		$this->view->config_disable_js = $config['config_disable_js'];
    	} elseif(isset($store_config['config_disable_js'])) { 
    		$this->view->config_disable_js = $store_config['config_disable_js'];
    	} else {
    		$this->view->config_disable_js = 0;
    	}
    	
		if(isset($config['config_pin_prefix'])) {
    		$this->view->config_pin_prefix = $config['config_pin_prefix'];
    	} elseif(isset($store_config['config_pin_prefix'])) { 
    		$this->view->config_pin_prefix = $store_config['config_pin_prefix'];
    	} else {
    		$this->view->config_pin_prefix = 'pin';
    	}
    	
		if(isset($config['config_user_view'])) {
    		$this->view->config_user_view = $config['config_user_view'];
    	} elseif(isset($store_config['config_user_view'])) { 
    		$this->view->config_user_view = $store_config['config_user_view'];
    	} else {
    		$this->view->config_user_view = 'fullname';
    	}
    	
		if(isset($config['config_gallery_pin'])) {
    		$this->view->config_gallery_pin = $config['config_gallery_pin'];
    	} elseif(isset($store_config['config_gallery_pin'])) { 
    		$this->view->config_gallery_pin = $store_config['config_gallery_pin'];
    	} else {
    		$this->view->config_gallery_pin = 1;
    	}
    	
		if(isset($config['config_gallery_limit'])) {
    		$this->view->config_gallery_limit = $config['config_gallery_limit'];
    	} elseif(isset($store_config['config_gallery_limit'])) { 
    		$this->view->config_gallery_limit = $store_config['config_gallery_limit'];
    	} else {
    		$this->view->config_gallery_limit = 5;
    	}
    	
		if(isset($config['config_home_page_view_not_loged'])) {
    		$this->view->config_home_page_view_not_loged = $config['config_home_page_view_not_loged'];
    	} elseif(isset($store_config['config_home_page_view_not_loged'])) { 
    		$this->view->config_home_page_view_not_loged = $store_config['config_home_page_view_not_loged'];
    	} else {
    		$this->view->config_home_page_view_not_loged = 'randum';
    	}
    	
		if(isset($config['config_home_page_view_loged'])) {
    		$this->view->config_home_page_view_loged = $config['config_home_page_view_loged'];
    	} elseif(isset($store_config['config_home_page_view_loged'])) { 
    		$this->view->config_home_page_view_loged = $store_config['config_home_page_view_loged'];
    	} else {
    		$this->view->config_home_page_view_loged = 'following';
    	}
    	
		if(isset($config['config_image_minimum_size'])) {
    		$this->view->config_image_minimum_size = $config['config_image_minimum_size'];
    	} elseif(isset($store_config['config_image_minimum_size'])) { 
    		$this->view->config_image_minimum_size = $store_config['config_image_minimum_size'];
    	} else {
    		$this->view->config_image_minimum_size = 80;
    	}
    	
    	/* v2.2 */
		if(isset($config['config_enable_follow_private_profile'])) {
    		$this->view->config_enable_follow_private_profile = $config['config_enable_follow_private_profile'];
    	} elseif(isset($store_config['config_enable_follow_private_profile'])) { 
    		$this->view->config_enable_follow_private_profile = $store_config['config_enable_follow_private_profile'];
    	} else {
    		$this->view->config_enable_follow_private_profile = 0;
    	}
    	/* v2.2 */
    	
    	//////////////////////////////////////// Storage ////////////////////////////////////////
    	 
    	$this->view->upload_storage = array();
    	$this->view->upload_storage[] = array(
    			'key' => 'Model_Upload_Locale',
    			'active' => 'Model_Upload_Locale' == Helper_Config::get('file_upload_method'),
    			'name' => $this->translate('Locale')
    	);
    	/*$storage = (array)$this->view->callChildrenView('modules_storage');
    	if(isset($storage['modules']) && $storage['modules']) {*/
    	$storage_modules = $this->getUploadStorages();
    	
    	if($storage_modules) {
    		foreach($storage_modules AS $mod) {
    			if($mod['is_installed']) {
    				$key = 'Model_Upload_' . ucfirst(strtolower($mod['key']));
    				$this->view->upload_storage[] = array(
    						'key' => $key,
    						'active' => $key == Helper_Config::get('file_upload_method'),
    						'name' => $mod['name']
    				);
    			}
    		}
    	}
    	
    	if(isset($config['config_comments_list'])) {
    		$this->view->config_comments_list = $config['config_comments_list'];
    	} elseif(isset($store_config['config_comments_list'])) {
    		$this->view->config_comments_list = $store_config['config_comments_list'];
    	} else {
    		$this->view->config_comments_list = 0;
    	}
    	
    	/////////////////////////////// CURRENCY ///////////////////////
    	$this->view->currencies = array();
    	$currencies = Model_Currency::getCurrencies(array('status' => 1)); //WM_Currency::getCurrencies();
    	if($currencies) {
    		$this->view->currencies = $currencies;
    	}
    	
    	if(isset($config['config_currency'])) {
    		$this->view->config_currency = $config['config_currency'];
    	} elseif(isset($store_config['config_currency'])) { 
    		$this->view->config_currency = $store_config['config_currency'];
    	}
    	
    	if(isset($config['config_currency_auto_update'])) {
    		$this->view->config_currency_auto_update = $config['config_currency_auto_update'];
    	} elseif(isset($store_config['config_currency_auto_update'])) {
    		$this->view->config_currency_auto_update = $store_config['config_currency_auto_update'];
    	} else {
    		$this->view->config_currency_auto_update = 1;
    	}
    	
    	if(isset($config['config_cache_live'])) {
    		$this->view->config_cache_live = $config['config_cache_live'];
    	} elseif(isset($store_config['config_cache_live'])) {
    		$this->view->config_cache_live = $store_config['config_cache_live'];
    	} else {
    		$this->view->config_cache_live = 0;
    	}
    	
    	if(isset($config['enable_free_registration'])) {
    		$this->view->enable_free_registration = $config['enable_free_registration'];
    	} elseif(isset($store_config['enable_free_registration'])) {
    		$this->view->enable_free_registration = $store_config['enable_free_registration'];
    	} else {
    		$this->view->enable_free_registration = 0;
    	}
    	
    	//////////////////////////////////////// Contacts ////////////////////////////////////////
		if(isset($config['admin_mail'])) {
    		$this->view->admin_mail = $config['admin_mail'];
    	} elseif(isset($store_config['admin_mail'])) { 
    		$this->view->admin_mail = $store_config['admin_mail'];
    	}
    	
		if(isset($config['report_mail'])) {
    		$this->view->report_mail = $config['report_mail'];
    	} elseif(isset($store_config['report_mail'])) {
    		$this->view->report_mail = $store_config['report_mail'];
    	}
    	
		if(isset($config['noreply_mail'])) {
    		$this->view->noreply_mail = $config['noreply_mail'];
    	} elseif(isset($store_config['noreply_mail'])) {
    		$this->view->noreply_mail = $store_config['noreply_mail'];
    	}
    	
		if(isset($config['mail_smtp'])) {
    		$this->view->mail_smtp = $config['mail_smtp'];
    	} elseif(isset($store_config['mail_smtp'])) {
    		$this->view->mail_smtp = $store_config['mail_smtp'];
    	} else {
    		$this->view->mail_smtp = 0;
    	}
    	
		if(isset($config['mail_smtp_host'])) {
    		$this->view->mail_smtp_host = $config['mail_smtp_host'];
    	} elseif(isset($store_config['mail_smtp_host'])) {
    		$this->view->mail_smtp_host = $store_config['mail_smtp_host'];
    	}
    	
		if(isset($config['mail_smtp_port'])) {
    		$this->view->mail_smtp_port = $config['mail_smtp_port'];
    	} elseif(isset($store_config['mail_smtp_port'])) {
    		$this->view->mail_smtp_port = $store_config['mail_smtp_port'];
    	}
    	
		if(isset($config['mail_smtp_user'])) {
    		$this->view->mail_smtp_user = $config['mail_smtp_user'];
    	} elseif(isset($store_config['mail_smtp_user'])) {
    		$this->view->mail_smtp_user = $store_config['mail_smtp_user'];
    	}
    	
		if(isset($config['mail_smtp_password'])) {
    		$this->view->mail_smtp_password = $config['mail_smtp_password'];
    	} elseif(isset($store_config['mail_smtp_password'])) {
    		$this->view->mail_smtp_password = $store_config['mail_smtp_password'];
    	}
    	
		if(isset($config['mail_footer'])) {
    		$this->view->mail_footer = $config['mail_footer'];
    	} elseif(isset($store_config['mail_footer'])) {
    		$this->view->mail_footer = $store_config['mail_footer'];
    	}
    	
	    if(isset($config['not_rp'])) {
    		$this->view->not_rp = $config['not_rp'];
    	} elseif(isset($store_config['not_rp'])) {
    		$this->view->not_rp = $store_config['not_rp'];
    	} else {
    		$this->view->not_rp = 0;
    	}
    	
    	if(isset($config['not_rc'])) {
    		$this->view->not_rc = $config['not_rc'];
    	} elseif(isset($store_config['not_rc'])) {
    		$this->view->not_rc = $store_config['not_rc'];
    	} else {
    		$this->view->not_rc = 0;
    	}
    	
	    if(isset($config['not_ri'])) {
    		$this->view->not_ri = $config['not_ri'];
    	} elseif(isset($store_config['not_ri'])) {
    		$this->view->not_ri = $store_config['not_ri'];
    	} else {
    		$this->view->not_ri = 0;
    	}

    	
    	//////////////////////////////////////// Images ////////////////////////////////////////
    	/////// logo
		$image_model = new Helper_Images;
    	
    	if(isset($images['site_logo']) && $images['site_logo']) {
    		$this->view->site_logo = $images['site_logo'];
    	} elseif(isset($store_config['site_logo']) && $store_config['site_logo']) {
    		$this->view->site_logo = $store_config['site_logo'];
    	} else {
    		$this->view->site_logo = '';
    	}
    	
    	if($this->view->site_logo) {
    		$this->view->preview_logo = $image_model->resize($this->view->site_logo, 100, 100);
    	} else {
    		$this->view->preview_logo = $image_model->resize('/logo.png', 100, 100);
    	}
    	
    	if(!$this->view->preview_logo) {
    		$this->view->preview_logo = $image_model->resize('/logo.png', 100, 100);
    	}
    	
    	$this->view->preview = $image_model->resize('/logo.png', 100, 100);
    	
    	////mobile logo
    	if(isset($images['site_logo_mobile']) && $images['site_logo_mobile']) {
    		$this->view->site_logo_mobile = $images['site_logo_mobile'];
    	} elseif(isset($store_config['site_logo_mobile']) && $store_config['site_logo_mobile']) {
    		$this->view->site_logo_mobile = $store_config['site_logo_mobile'];
    	} else {
    		$this->view->site_logo_mobile = '';
    	}
    	 
    	if($this->view->site_logo_mobile) {
    		$this->view->preview_logo_mobile = $image_model->resize($this->view->site_logo_mobile, 100, 100);
    	} else {
    		$this->view->preview_logo_mobile = $image_model->resize('/logo.png', 100, 100);
    	}
    	 
    	if(!$this->view->preview_logo_mobile) {
    		$this->view->preview_logo_mobile = $image_model->resize('/logo.png', 100, 100);
    	}
    	 
    	$this->view->preview_mobile = $image_model->resize('/logo.png', 100, 100);
    	
    	////// no image
		if(isset($images['no_image']) && $images['no_image']) {
    		$this->view->no_image = $images['no_image'];
    	} elseif(isset($store_config['no_image']) && $store_config['no_image']) {
    		$this->view->no_image = $store_config['no_image'];
    	} else {
    		$this->view->no_image = '/no_image.png';
    	}
    	
    	if($this->view->no_image) {
    		$this->view->preview_no_image = $image_model->resize($this->view->no_image, 100, 100);
    	} else {
    		$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	if(!$this->view->preview_no_image) {
    		$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	////// no avatar
		if(isset($images['no_avatar']) && $images['no_avatar']) {
    		$this->view->no_avatar = $images['no_avatar'];
    	} elseif(isset($store_config['no_avatar']) && $store_config['no_avatar']) {
    		$this->view->no_avatar = $store_config['no_avatar'];
    	} else {
    		$this->view->no_avatar = '/no-avatar.png';
    	}
    	
    	if($this->view->no_avatar) {
    		$this->view->preview_no_avatar = $image_model->resize($this->view->no_avatar, 100, 100);
    	} else {
    		$this->view->preview_no_avatar = $image_model->resize('/no-avatar.png', 100, 100);
    	}
    	
    	if(!$this->view->preview_no_avatar) {
    		$this->view->preview_no_avatar = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
		////// favicon
		if(isset($images['favicon']) && $images['favicon']) {
    		$this->view->favicon = $images['favicon'];
    	} elseif(isset($store_config['favicon']) && $store_config['favicon']) {
    		$this->view->favicon = $store_config['favicon'];
    	} else {
    		$this->view->favicon = '';
    	}
    	
    	if($this->view->favicon) {
    		$this->view->preview_favicon = $image_model->resize($this->view->favicon, 100, 100);
    	} else {
    		$this->view->preview_favicon = $image_model->resize($this->view->no_image, 100, 100);
    	}
    	
    	if(!$this->view->preview_favicon) {
    		$this->view->preview_favicon = $image_model->resize($this->view->no_image, 100, 100);
    	}
    	
//    	$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	
	
		
    	
		//////////////////////////////////////// PAGES ////////////////////////////////////////
	
		if(isset($pages['about_menu'])) {
    		$this->view->about_menu = $pages['about_menu'];
    	} elseif(isset($store_config['about_menu'])) {
    		$this->view->about_menu = $store_config['about_menu'];
    	} else {
    		$this->view->about_menu = array();
    	}
	
		if(isset($pages['page_login_trouble'])) {
    		$this->view->page_login_trouble = $pages['page_login_trouble'];
    	} elseif(isset($store_config['page_login_trouble'])) {
    		$this->view->page_login_trouble = $store_config['page_login_trouble'];
    	} else {
    		$this->view->page_login_trouble = 0;
    	}
    	
		if(isset($pages['page_terms'])) {
    		$this->view->page_terms = $pages['page_terms'];
    	} elseif(isset($store_config['page_terms'])) {
    		$this->view->page_terms = $store_config['page_terms'];
    	} else {
    		$this->view->page_terms = 0;
    	}
    	
		if(isset($pages['page_contact'])) {
    		$this->view->page_contact = $pages['page_contact'];
    	} elseif(isset($store_config['page_contact'])) {
    		$this->view->page_contact = $store_config['page_contact'];
    	} else {
    		$this->view->page_contact = 0;
    	}
    	
		if(isset($pages['page_goodies'])) {
    		$this->view->page_goodies = $pages['page_goodies'];
    	} elseif(isset($store_config['page_goodies'])) {
    		$this->view->page_goodies = $store_config['page_goodies'];
    	} else {
    		$this->view->page_goodies = 0;
    	}
    	
		if(isset($pages['delete_account'])) {
    		$this->view->delete_account = $pages['delete_account'];
    	} elseif(isset($store_config['delete_account'])) {
    		$this->view->delete_account = $store_config['delete_account'];
    	} else {
    		$this->view->delete_account = 0;
    	}
    	
		if(isset($pages['support_page'])) {
    		$this->view->support_page = $pages['support_page'];
    	} elseif(isset($store_config['support_page'])) {
    		$this->view->support_page = $store_config['support_page'];
    	} else {
    		$this->view->support_page = 0;
    	}
    	
		if(isset($pages['page_pinmarklet'])) {
    		$this->view->page_pinmarklet = $pages['page_pinmarklet'];
    	} elseif(isset($store_config['page_pinmarklet'])) {
    		$this->view->page_pinmarklet = $store_config['page_pinmarklet'];
    	} else {
    		$this->view->page_pinmarklet = 0;
    	}
    	
		if(isset($pages['page_privacy_policy'])) {
    		$this->view->page_privacy_policy = $pages['page_privacy_policy'];
    	} elseif(isset($store_config['page_privacy_policy'])) {
    		$this->view->page_privacy_policy = $store_config['page_privacy_policy'];
    	} else {
    		$this->view->page_privacy_policy = 0;
    	}
    	
		if(isset($pages['page_private_boards'])) {
    		$this->view->page_private_boards = $pages['page_private_boards'];
    	} elseif(isset($store_config['page_private_boards'])) {
    		$this->view->page_private_boards = $store_config['page_private_boards'];
    	} else {
    		$this->view->page_private_boards = 0;
    	}
    	
    	//////////////////////////////////////// DATES ////////////////////////////////////////
	
    	$this->view->short_dates = array(
    		'dd.mm.yy',
    		'yy-mm-dd',
    	);
    	
    	$this->view->medium_dates = array(
    		'dd M yy',
    		'D, d M y',
    		'DD, dd-M-y',
    		'D, d M yy'
    	);
    	
    	$this->view->long_dates = array(
    		'dd MM yy',
    		'D, d MM y',
    		'DD, dd-MM-y',
    		'D, d MM yy'
    	);
    	
    	$this->view->long_dates_times = array(
    		'dd MM yy H:i:s',
    		'D, d MM y H:i:s',
    		'DD, dd-MM-y H:i:s',
    		'D, d MM yy H:i:s'
    	);
    	
    	$this->view->news_dates = array(
    		'dd M yy',
    		'D, d M y',
    		'DD, dd-M-y',
    		'D, d M yy',
    		'dd MM yy',
    		'D, d MM y',
    		'DD, dd-MM-y',
    		'D, d MM yy',
    		'dd MM yy | H:i',
    		'D, d MM y | H:i',
    		'DD, dd-MM-y | H:i',
    		'D, d MM yy | H:i',
    		'dd MM yy | H:i:s',
    		'D, d MM y | H:i:s',
    		'DD, dd-MM-y | H:i:s',
    		'D, d MM yy | H:i:s'
    	);
    	
    	if(isset($config['config_date_format_short'])) {
    		$this->view->config_date_format_short = $config['config_date_format_short'];
    	} elseif(isset($store_config['config_date_format_short'])) {
    		$this->view->config_date_format_short = $store_config['config_date_format_short'];
    	} else {
    		$this->view->config_date_format_short = 'dd.mm.yy';
    	}
    	
    	if(isset($config['config_date_format_medium'])) {
    		$this->view->config_date_format_medium = $config['config_date_format_medium'];
    	} elseif(isset($store_config['config_date_format_medium'])) {
    		$this->view->config_date_format_medium = $store_config['config_date_format_medium'];
    	} else {
    		$this->view->config_date_format_medium = 'dd M yy';
    	}
    	
    	if(isset($config['config_date_format_long'])) {
    		$this->view->config_date_format_long = $config['config_date_format_long'];
    	} elseif(isset($store_config['config_date_format_long'])) {
    		$this->view->config_date_format_long = $store_config['config_date_format_long'];
    	} else {
    		$this->view->config_date_format_long = 'dd MM yy';
    	}
    	
    	if(isset($config['config_date_format_news'])) {
    		$this->view->config_date_format_news = $config['config_date_format_news'];
    	} elseif(isset($store_config['config_date_format_news'])) {
    		$this->view->config_date_format_news = $store_config['config_date_format_news'];
    	} else {
    		$this->view->config_date_format_news = 'dd MM yy | H:i';
    	}
    	
    	if(isset($config['config_date_format_long_time'])) {
    		$this->view->config_date_format_long_time = $config['config_date_format_long_time'];
    	} elseif(isset($store_config['config_date_format_long_time'])) {
    		$this->view->config_date_format_long_time = $store_config['config_date_format_long_time'];
    	} else {
    		$this->view->config_date_format_long_time = 'dd MM yy H:i:s';
    	}
    	
  
    	
    	
    	//////////////////////////////////////// DATES ////////////////////////////////////////
		$this->view->categories = array();
		$categories = Model_Categories::getCategories(array('filter_without_children' => true));
		if($categories) {
			foreach($categories AS $category) {
				$this->view->categories[] = $category;
			}
		}
		
		if(isset($config['config_board_description_enable'])) {
    		$this->view->config_board_description_enable = $config['config_board_description_enable'];
    	} elseif(isset($store_config['config_board_description_enable'])) {
    		$this->view->config_board_description_enable = $store_config['config_board_description_enable'];
    	} else {
    		$this->view->config_board_description_enable = 0;
    	}
		
		if(isset($config['config_private_boards'])) {
    		$this->view->config_private_boards = $config['config_private_boards'];
    	} elseif(isset($store_config['config_private_boards'])) {
    		$this->view->config_private_boards = $store_config['config_private_boards'];
    	} else {
    		$this->view->config_private_boards = 0;
    	}
		
		if(isset($config['default_category_id'])) {
    		$this->view->default_category_id = $config['default_category_id'];
    	} elseif(isset($store_config['default_category_id'])) {
    		$this->view->default_category_id = $store_config['default_category_id'];
    	} else {
    		$this->view->default_category_id = 0;
    	}
		
		if(isset($config['default_boards'])) {
    		$this->view->default_boards = $config['default_boards'];
    	} elseif(isset($store_config['default_boards'])) {
    		$this->view->default_boards = $store_config['default_boards'];
    	} else {
    		$this->view->default_boards = array();
    	}
    	
		
	}
	
	private function getUploadStorages() {

    	$files = glob(dirname(__FILE__) . '/Modules/Storage/*.php');
    	
    	$this->view->modules_url = $this->getRequest()->getModule() . '/modules';
    	
    	$modules = array();
    	if($files) {
    		foreach($files AS $file) {
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$key = mb_strtolower($match[1], 'utf-8');
    				$modules[] = array(
    					'key' => $key,
    					'default' => 'Model_Upload_' . ucfirst(strtolower($key)) == Helper_Config::get('file_upload_method'),
    					'edit' => $this->getRequest()->getModule() . '/modules_storage_' . $key,
    					'name' => $this->translate($match[1]),
    					'is_installed' => Model_Extensions_Install::isInstalled($key),
    					'install' => $this->getRequest()->getModule() . '/modules_storage_' . $key . '/install',
    					'uninstall' => $this->getRequest()->getModule() . '/modules_storage_' . $key . '/uninstall'
    				);
    			}
    		}
    	}
    	return $modules;
	}
    
    private function getTemplates($ignore = array()) {
    	$template_path = JO_Layout::getInstance()->getTemplatePath();
    	$list = glob($template_path . '*');
    	$templates = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			if(!in_array(basename($dir), $ignore)) {
    				$templates[] = basename($dir);
    			}
    		}
    	}
    	return $templates;
    }

}

?>