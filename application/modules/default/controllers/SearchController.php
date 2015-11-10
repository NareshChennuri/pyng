<?php

class SearchController extends Helper_Controller_Default {
	
	private function searchMenu() {
		$request = $this->getRequest();

		if(!file_exists(dirname(__FILE__) . '/Search/'.ucfirst(strtolower($request->getAction())).'Controller.php')) {
			$this->forward('error', 'error404');
		}
		
		$methods = glob( dirname(__FILE__) . '/Search/*.php' );
		$return = array();
		if($methods) {
			foreach($methods AS $file) {
				$controller = basename($file, '.php');
				$class_name = 'Search_' . $controller;
				if(!class_exists($class_name, false)) {
					JO_Loader::loadFile($file);
				}
				$class = new $class_name();
				if(method_exists($class, 'config')) {
					$config = $class->config();
					$return[ ( isset($config['sort']) ? $config['sort'] : 0 ) ] = $class->config();
				}
			}
		}
		
		ksort($return);
		
		return $return;
	}
	
	public function autocompleteAction() {
		$request = $this->getRequest();
		
		$this->view->items = array();
		
		if(JO_Session::get('user[user_id]') && $request->getPost('value')) {
			$methods = glob( dirname(__FILE__) . '/Search/*.php' );
			if($methods) {
				foreach($methods AS $file) {
					$controller = basename($file, '.php');
					$class_name = 'Search_' . $controller;
					if(!class_exists($class_name, false)) {
						JO_Loader::loadFile($file);
					}
					$class = new $class_name();
					if(method_exists($class, 'autocomplete')) {
						$this->view->items = array_merge($this->view->items, $class->autocomplete($request->getPost('value')));
					}
				}
			}
		}
			
		$this->view->items[] = array(
				'template' => 'global',
				'label' => sprintf($this->translate('Search for %s'), $request->getPost('value')),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=search&q=' . $request->getPost('value'))
		);
		
		if($request->isXmlHttpRequest()) {
			echo $this->renderScript('json');
		} else {
			$this->forward('error', 'error404');
		}
		
	}
	
	public function callRewriteAction($methodName) {
		$this->setViewChange('index');
		
		$request = $this->getRequest();

		$this->view->menuSearch = $this->searchMenu();
		
		$this->view->query = $request->getRequest('q');
		
		
		/*//get pins data
		if($request->isXmlHttpRequest()) {
			$this->forward('search_' . $methodName, 'getSearchResult');
		}*/
		
		//get pins data
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			if($request->isXmlHttpRequest()) {
				$this->forward('search_' . $methodName, 'getSearchResult');
			}
		} else {
			
			$class_name = 'search_' . $methodName;
			
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward($class_name, 'getSearchResult');
			}
			
			$controller_name = $this->getFrontController()->formatControllerName($class_name);
			$instance = new $controller_name();
			$pins = (array)$instance->getSearchResultAction(true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
}

?>