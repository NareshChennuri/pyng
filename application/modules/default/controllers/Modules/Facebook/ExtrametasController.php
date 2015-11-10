<?php

class Modules_Facebook_ExtrametasController extends Helper_Controller_Default {

	public function indexAction() {
		$this->noLayout(true);
		$invate_code = $this->getRequest()->getParam('invate_code');
		$og_data = false;
		if($invate_code && strlen($invate_code) == 32) {
			if(!$this->getRequest()->isFacebookBot()) {
				$this->redirect( WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=modules_facebook_invated&code=' . $invate_code) );
			} else {
				$og_data = $this->getRequest()->getParam('og_data');
			}
		}
		//if($this->getRequest()->isFacebookBot()) {
			
			$meta_data = Helper_Config::get('extra_metatags');
			
			if($meta_data) {
				$settings = Model_Extensions::getSettingsPairs('facebook');
				
				$this->view->site_name = Helper_Config::get('site_name');
				//$this->view->is_facebook = $this->getRequest()->isFacebookBot();
				$this->view->is_facebook = true;
				
				$this->view->oauth_fb_key = trim(Helper_Config::get('facebook_oauth_key'));
				$this->view->oauth_fb_secret = trim(Helper_Config::get('facebook_oauth_secret'));
				$this->view->og_namespace = trim(Helper_Config::get('facebook_og_namespace'));
				$this->view->og_recipe = trim(Helper_Config::get('facebook_og_recipe'));
				if(!$this->view->og_recipe) {
					$this->view->og_namespace = '';
				}
				
				if(isset($settings['facebook_module_status_enable']) && $settings['facebook_module_status_enable']) {
					if(isset($settings['facebook_og_meta_tags']) && $settings['facebook_og_meta_tags']) {
				
						if(isset($meta_data['user']) && is_array($meta_data['user'])) {
							$this->setViewChange('user');
							$this->view->user = $meta_data['user'];
							if($this->view->og_namespace) {
								JO_Layout::getInstance()->head_attributes = (' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# '.$this->view->og_namespace.': http://ogp.me/ns/fb/'.$this->view->og_namespace.'#"');
							}
						} else if(isset($meta_data['pin']) && is_array($meta_data['pin'])) {
							$this->setViewChange('pin');
							$this->view->pin = $meta_data['pin'];
							if($this->view->og_namespace) {
								JO_Layout::getInstance()->head_attributes = (' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# '.$this->view->og_namespace.': http://ogp.me/ns/fb/'.$this->view->og_namespace.'#"');
							}
						} else if(isset($meta_data['board']) && is_array($meta_data['board'])) {
							$this->setViewChange('board');
							$this->view->board = $meta_data['board'];
							if($this->view->og_namespace) {
								JO_Layout::getInstance()->head_attributes = (' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# '.$this->view->og_namespace.': http://ogp.me/ns/fb/'.$this->view->og_namespace.'#"');
							}
						} else {
							$this->noViewRenderer(true);
						}
					}
				}
			} elseif($og_data) {
				$settings = Model_Extensions::getSettingsPairs('facebook');
				
				$this->view->site_name = Helper_Config::get('site_name');
				//$this->view->is_facebook = $this->getRequest()->isFacebookBot();
				$this->view->is_facebook = true;
				
				$this->view->oauth_fb_key = trim(Helper_Config::get('facebook_oauth_key'));
				$this->view->oauth_fb_secret = trim(Helper_Config::get('facebook_oauth_secret'));
				$this->view->og_namespace = trim(Helper_Config::get('facebook_og_namespace'));
				$this->view->og_recipe = trim(Helper_Config::get('facebook_og_recipe'));
				if(!$this->view->og_recipe) {
					$this->view->og_namespace = '';
				}
				if(isset($settings['facebook_module_status_enable']) && $settings['facebook_module_status_enable']) {
					if(isset($settings['facebook_og_meta_tags']) && $settings['facebook_og_meta_tags']) {
						$this->setViewChange('og_data');
						$og_data['url'] = $this->getRequest()->getFullUrl();
						$this->view->og_data = $og_data;
						if($this->view->og_namespace) {
							JO_Layout::getInstance()->head_attributes = (' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# '.$this->view->og_namespace.': http://ogp.me/ns/fb/'.$this->view->og_namespace.'#"');
						}
					}
				}
				
			} else {
				$this->noViewRenderer(true);
			}
		//} else {
		//	$this->noViewRenderer(true);
		//}
	}
	
}

?>