<?php

class CategoryController extends Helper_Controller_Default {
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		$category_id = $request->getRequest('category_id');
		
		$category_info = Model_Categories::getCategory($category_id);
		if(!$category_info) {
			$this->forward('error', 'error404');
		}
		
		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('category', 'getPins');
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
				$this->forward('category', 'getPins');
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('category', 'getPins');
			}
			$pins = (array)$this->getPinsAction(true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		$this->view->category = $category_info;

		$category_logo = '';
		if($category_info['image'] && file_exists(BASE_PATH .'/uploads' . $category_info['image'])) {
			$category_logo = $request->getBaseUrl() . 'uploads' . $category_info['image']; 
		}
		
		$this->getLayout()->placeholder('title', ($category_info['meta_title'] ? $category_info['meta_title'] : $category_info['title']));
		JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('layout/header_metas', array(
			'meta_title' => ($category_info['meta_title'] ? $category_info['meta_title'] : $category_info['title']),
			'meta_description' => $category_info['meta_description'] ? $category_info['meta_description'] : Helper_Config::get('meta_description'),
			'meta_keywords' => $category_info['meta_keywords'] ? $category_info['meta_keywords'] : Helper_Config::get('meta_keywords'),
			'site_logo' => $category_logo
		)));
		
		
		//call header and footer childrens
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
	public function viewAction() {
		$this->forward('category','index');
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
			'filter_category_id' => $request->getRequest('category_id'),
		);
		
		$return = array();
		
		// pins data
		$pins = new Model_Pins_Category($data);
		
		//format response data
		$formatObject = new Helper_Format();
		
		if($pins->count()) {
			$banners = Model_Banners::getBanners(
					new JO_Db_Expr("`controller` = '".$request->getController()."' AND (category_id = 0 OR category_id = '".$request->getRequest('category_id')."') AND position >= '".(int)$data['start']."' AND position <= '".(int)($data['start']+$pp)."'")
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