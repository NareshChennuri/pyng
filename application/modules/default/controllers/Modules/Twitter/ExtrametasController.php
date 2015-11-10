<?php

class Modules_Twitter_ExtrametasController extends Helper_Controller_Default {

	public function indexAction() {
		$this->noLayout(true);
		$meta_data = Helper_Config::get('extra_metatags');
		
		if(isset($meta_data['user']) && is_array($meta_data['user'])) {
			$this->setViewChange('user');
		} else if(isset($meta_data['pin']) && is_array($meta_data['pin'])) {
			$this->setViewChange('pin');
		} else if(isset($meta_data['board']) && is_array($meta_data['board'])) {
			$this->setViewChange('board');
		} else {
			$this->noViewRenderer(true);
		}
			
	}
	
}

?>