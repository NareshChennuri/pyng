<?php

class ModulesController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Modules methods'),
			'has_permision' => true,
			'menu' => self::translate('Modules'),
			'in_menu' => true,
			'permision_key' => 'modules',
			'sort_order' => 72000
		);
	}
	
	/////////////////// end config
	
	public function indexAction() {
		
		if(JO_Session::get('successfu_edite')) {
    		$this->view->successfu_edite = JO_Session::get('successfu_edite');
    		JO_Session::clear('successfu_edite'); 
    	}
    	
    	if(JO_Session::get('error_permision')) {
    		$this->view->error_permision = JO_Session::get('error_permision');
    		JO_Session::clear('error_permision'); 
    	} 

    	$files = glob(dirname(__FILE__) . '/Modules/*.php');
    	
    	$this->view->modules = array();
    	if($files) {
    		foreach($files AS $file) {
    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
    				$key = mb_strtolower($match[1], 'utf-8');
    				$this->view->modules[] = array(
    					'key' => $key,
    					'edit' => $this->getRequest()->getModule() . '/modules_' . $key,
    					'name' => $this->translate($match[1]),
    					'is_installed' => Model_Extensions_Install::isInstalled($key),
    					'install' => $this->getRequest()->getModule() . '/modules_' . $key . '/install',
    					'uninstall' => $this->getRequest()->getModule() . '/modules_' . $key . '/uninstall'
    				);
    			}
    		}
    	}
	}
}

?>