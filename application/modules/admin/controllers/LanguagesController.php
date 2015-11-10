<?php

class LanguagesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Languages'),
			'has_permision' => false,
			'menu' => self::translate('Systems'),
			'children' => self::translate('Localisation'),
			'in_menu' => false,
			'permision_key' => 'languages',
			'sort_order' => 80500
		);
	}
	
	/////////////////// end config

	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$model_languages = new Model_Language;
		
		$store_lang = Helper_Config::get('config_default_language_id');
		
		$languages = $model_languages->getLanguages();
		$this->view->languages = array();
		if($languages) {
			foreach($languages AS $language) {
				$this->view->languages[] = array(
					'language_id' => $language['language_id'],
					'name' => $language['name'],
					'status' => $language['status'],
					'is_set' => ($store_lang == $language['language_id'] ? 'Default' : false)
				);
			}
		}
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}	
		
	}
	
	public function createAction() {
	if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('language_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Language::createLanguage($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/languages/');
    	}
		
		$this->getForm();
	}
	
	public function editAction() {
    	if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/languages/');
		}
		$this->setViewChange('language_form');
		
		if($this->getRequest()->isPost()) {
    		Model_Language::editeLanguage($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/languages/');
    	}
		
		$this->getForm();
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Language::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Language::deleteLanguage($this->getRequest()->getPost('id'));
	}
	
	public function sort_orderAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$sort_order_data = $this->getRequest()->getPost('sort_order');
		foreach($sort_order_data AS $sort_order => $language_id) {
			if($language_id) {
				Model_Language::changeSortOrder($language_id, $sort_order);
			}
		}
		
		echo '1';
	}
	
	////////////////////////////////// HELP FUNCTIONS ///////////////////////
	
	private function getForm() {

		$request = $this->getRequest();
		
		$language_id = $request->getQuery('id');
		
		$model_language = new Model_Language;
		
		if($language_id) {
			$language_info = $model_language->getLanguage($language_id);
		}
		
		$countrycode = Model_Countries::getCountriesPairs();
		
		$this->view->locale_territories = array();
		$locale_territories = JO_Locale::listTerritory();
		if($locale_territories) {
			$sort_order = array();
			foreach($locale_territories AS $iso2 => $lt) {
				if(isset($countrycode[$iso2])) {
					$sort_order[$lt] = $countrycode[$iso2];
					$this->view->locale_territories[$lt] = array(
						'code' => $lt,
						'name' => $countrycode[$iso2]
					);
				} else {
					$sort_order[$lt] = $iso2;
					$this->view->locale_territories[$lt] = array(
						'code' => $lt,
						'name' => $iso2
					);
				}
			}
			array_multisort($sort_order, SORT_ASC, $this->view->locale_territories);
		}
		
		$this->view->flags = $this->getFlags();
		
		if($request->getPost('name')) {
			$this->view->name = $request->getPost('name');
		} elseif(isset($language_info)) {
			$this->view->name = $language_info['name'];
		}
		
		if($request->getPost('code')) {
			$this->view->code = $request->getPost('code');
		} elseif(isset($language_info)) {
			$this->view->code = $language_info['code'];
		}
		
		if($request->getPost('locale')) {
			$this->view->locale = $request->getPost('locale');
		} elseif(isset($language_info)) {
			$this->view->locale = $language_info['locale'];
		}
		
		if($request->getPost('locale_territory')) {
			$this->view->locale_territory = $request->getPost('locale_territory');
		} elseif(isset($language_info)) {
			$this->view->locale_territory = $language_info['locale_territory'];
		}
		
		if($request->getPost('image')) {
			$this->view->image = $request->getPost('image');
		} elseif(isset($language_info)) {
			$this->view->image = $language_info['image'];
		}
		
		if($request->getPost('status')) {
			$this->view->status = $request->getPost('status');
		} elseif(isset($language_info)) {
			$this->view->status = $language_info['status'];
		}
		
	}
	
	private function getFlags() {
		$path = realpath(BASE_PATH . '/cms/images/flags/');
		
		$flags = glob($path . '/*');
		$data = array();
		if($flags) {
			foreach($flags AS $flag) {
				$data[] = basename($flag);
			}
		}
		return $data;
	}
	
}