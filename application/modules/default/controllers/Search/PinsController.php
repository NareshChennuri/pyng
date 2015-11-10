<?php

class Search_PinsController extends Helper_Controller_Default {

	public function config() {
		$request = $this->getRequest();
		return array(
			'sort' => 1,
			'title' => $this->translate('Pins'),
			'active' => in_array($request->getAction(), array('pins')),
			'href' => WM_Router::create($request->getBaseUrl() . '?controller=search&action=pins&q=' . urlencode($request->getQuery('q')))
		);
	}
	
	public function getSearchResultAction($return_data = false) {
	
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$query = $request->getRequest('q');
	
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
				'filter_description' => $query
		);
	
		$return = array();
	
	
		/* set board count */
		$has_pins = true;
		if(!trim($query)) {
			$has_pins = false;
		}
	
		// pins data
		$pins = $has_pins ? new Model_Pins_Search($data) : new ArrayObject();
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_pins && $pins->count()) {
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
				$message = $this->translate('No pyngs!');
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