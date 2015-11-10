<?php

class PopularController extends Helper_Controller_Default {
	
	public function indexAction() {
		
		$request = $this->getRequest();

		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('popular', 'getPins');
		}*/
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('popular', 'getPins');
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('popular', 'getPins');
			}
			$pins = (array)$this->getPinsAction(true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		//call header and footer childrens
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
	public function pageAction(){
		$this->forward('popular', 'index');
	}
	
	public function viewAction(){
		$this->forward('popular', 'index');
	}
	
	public function getPinsAction($return_data = false) {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$data = array(
				'start' => ( $pp * $page ) - $pp,
				'limit' => $pp,
				'filter_price_from' => (int)$request->getRequest('price_from'),
				'filter_price_to' => (int)$request->getRequest('price_to')
		);
		
		$return = array();
		
		// pins data
		$pins = new Model_Pins_Popular($data);
		
		//format response data
		$formatObject = new Helper_Format();
		
		if($pins->count()) {
			$banners = Model_Banners::getBanners(
					new JO_Db_Expr("`controller` = '".$request->getController()."' AND position >= '".(int)$data['start']."' AND position <= '".(int)($data['start']+$pp)."'")
			);
			
			foreach($pins->data AS $row => $pin) {
				///banners
				$key = $row + (($pp*$page)-$pp);
				if(isset($banners[$key]) && $banners[$key]) {
					if( ($banners_result = $formatObject->fromatListBanners($banners[$key])) !== false) {
						$return[] = $banners_result;
					}
				}
				//pins
				$return[] = $formatObject->fromatList($pin);
			}

		} else {
			if($page == 1) {
				$message = $this->translate('No popular pyngs!');
			} else {
				$message = $this->translate('No more pyngs!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
		
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
		
	}
	
}

?>