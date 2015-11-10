<?php

class WM_Router {
	
	public static $systems = array(
		'page',
		'sort',
		'order',
		'extension',
		'setFile',
		'feed'
	);
	
	public function checkJsCss($part) {
		
		static $front = null;
		if($front === null) {
			$front = JO_Front::getInstance();
		}
		
		if(preg_match('/^(.*)\.(js|css)$/i',$part, $match)) {
			if($front->isDispatchable($match[2]) && in_array($match[1], WM_Modules::getControllerResources($match[2]))) {
				JO_Request::getInstance()->setController($match[2])->setAction($match[1]);
				return true;
			} else {
				return false;	
			}
		} elseif(preg_match('/^(.*)\.(json)$/i',$part, $match)) {
			if($front->isDispatchable($match[2])) {
				JO_Request::getInstance()->setController($match[2])->setAction($match[1]);
				return true;
			} else {
				return false;	
			}
		}
		return false;
	}
	
//	public function checkBoard($part) {
//		static $request = null, $db = null;
//		if($request === null) { $request = JO_Request::getInstance(); }
//		if($db === null) { $db = JO_Request::getInstance(); }
//		
//	}
	
	public static function route($uri) {
		
		$parts = explode('/', trim($uri, '/'));
		
		$db = JO_Db::getDefaultAdapter();
		$request = JO_Request::getInstance();
		
		$query = $db->select()
					->from('url_alias');
		
		$get_controller = null;
		$get_action = null;
		if($request->getController() != 'index' && in_array( $request->getController(), WM_Modules::getControllers() ) ) {
			$get_controller = $request->getController();
			if($request->getAction() != 'index' && in_array( $request->getAction(), WM_Modules::getControllerResources($get_controller) ) ) {
				$get_action = $request->getAction();
			}
		}

		
		
		foreach($parts AS $part) {
			
			if(self::checkJsCss($part)) {
				continue;
			} elseif( in_array($part, self::$systems) ) { 
				continue;
			} elseif($part == 'pinit.html') {
				JO_Request::getInstance()->setController('pinit')->setAction('index');
				continue;
			}
			
			$query->where('keyword = ?', $part);
			
			$results = $db->fetchRow($query);
			
			if($results) {
				parse_str($results['query'], $data);
				foreach ($data as $key => $value) { 
					
					/*if($key == 'board_id' && !$request->getParam('user_id')) {
						continue;
					}*/
					
					if($request->getRequest($key)) {
// 						$request->setParams($key, $request->getRequest($key) . '_' . $value);
						$request->setParams($key, $value);
					} else {
						$request->setParams($key, $value);
					}
					
					if($results['route']) {
						$call = explode('/', $results['route']);
						$controller = 'index';
						$action = 'index';
						
						if(trim($call[0])) {
							$controller = $call[0];
						}
						if(isset($call[1]) && trim($call[1])) {
							$action = $call[1];
						}
						
						if($get_controller) {
							$controller = $get_controller;
						}
						
						if($get_action) {
							$action = $get_action;
						}
						
						$request->setController($controller)->setAction($action);
					}
				}
			}
			$query->reset(JO_Db_Select::WHERE);
		}
	}
	
	public static function create($link) {
		
		if(!MOD_REWRITE) {
			return $link;
		}
		
		static $cached_link = array();
		static $modules = null;
		static $db = null;
		
		if($db === null) $db = JO_Db::getDefaultAdapter();
		if(isset($cached_link[$link])) return $cached_link[$link];
		
		$request = JO_Request::getInstance();
		
		if($modules === null) {
			$modules = WM_Modules::getList(array('admin','update','install'));
		}
	
		$created = false;
		$controller = '';
		$action = '';
		
		$url_data = parse_url(str_replace('&amp;', '&', $link));

		$url = ''; 
		
		$data = array();
		if(isset($url_data['query'])) {
			parse_str($url_data['query'], $data);
		}
		
		/* USERS URL-S */
		if(isset($data['user_id']) && isset($data['controller']) && isset($data['action'])) {
			$keyword = self::getAlias(new JO_Db_Expr($db->quote("user_id=" . $data['user_id']) ));
			
			switch(true) {
				case in_array($data['action'], array('pins', 'followers', 'following', 'activity')):
					if(trim($keyword['path'])) {
						$url .= '/' . urlencode($keyword['path']);
					} else {
						$url .= '/' . urlencode($keyword['keyword']);
					} 
					$url .= '/' . $data['action'];
					unset($data['user_id']);
					unset($data['controller']);
					unset($data['action']);
				break;
			}
			//return $link;
		}
		/* END USERS URL-S */
		
		foreach ($data as $key => $value) {
//			if(in_array($key, array('page_id', 'news_id', 'category_id')) ) {
			$keyword = self::getAlias(new JO_Db_Expr($db->quote($key."=" . (string)$value)));
			
			if($keyword) {
				if(trim($keyword['path'])) {
					$url .= '/' . urlencode($keyword['path']);
				} else {
					$url .= '/' . urlencode($keyword['keyword']);
				}
				unset($data[$key]);
				if($keyword['route'] == $controller . '/' . $action) {
					$created = true;	
				}
			} elseif($key == 'controller') {
				$controller = $value;
				unset($data[$key]);
			} elseif($key == 'action') {
				$action = $value;
				unset($data[$key]);
			} elseif($key == 'pin_id') {
				$pin = '/' . $value; 
				if($controller == 'pin' && (!$action || $action == 'index' ) ) {
					$pin = '/pin/' . $value; 
					$created = true;
				}
				$url .= $pin;
				unset($data[$key]);
			} elseif( in_array($key, self::$systems) ) {
				$url .= '/'.$key.'/' . $value;
				unset($data[$key]);
			}
			
			elseif($key == 'tag') {
				$url .= '/' . (is_array($value) ? implode(':',$value) : $value);
				unset($data[$key]);
			} elseif($key == 'module') {
//				if($value != $request->getModule()) {
//					$url_data['host'] = $value . '.' . str_replace('www.','',$url_data['host']);
//				}
				if(count($modules) > 1) {
					$url .= '/' . $value;
				} elseif(in_array($value, array('update','install'))) {
					$url .= '/' . $value;
				}
				unset($data[$key]);
			}
			
		}
		
		if(!$created) {
			if($action) {
				if($action != 'index') {
					$url = '/' . $controller . '/' . $action . $url;
				}
			} else if($controller) {
				if($controller != 'index') {
					$url = '/' . $controller . $url;
				}
			}
		}
		
		if ($url) {
			
			$query = '';
			if ($data) {
				if($created) {
					if(isset($data['controller'])) {
						unset($data['controller']);
					}
					if(isset($data['action'])) {
						unset($data['action']);
					}
				}
// 				$query .= http_build_query($data, '', '&amp;');
				$query .= http_build_query($data);

				if ($query) {
					$query = '?' . trim($query, '&');
				}	
			}
			
			if(isset($url_data['fragment']) && $url_data['fragment']) {
				$query .= '#' . $url_data['fragment'];
			}
			
			$result = $url . $query;
			
			$cached_link[$url_data['scheme'] . '://' . $url_data['host'] . (isset($url_data['port']) ? ':' . $url_data['port'] : '') . str_replace('/index.php', '', rtrim($url_data['path'], '/')) . $result] = true;
			
			return $url_data['scheme'] . '://' . $url_data['host'] . (isset($url_data['port']) ? ':' . $url_data['port'] : '') . str_replace('/index.php', '', rtrim($url_data['path'], '/')) . $result;
		} else {
			
			$cached_link[$link] = true;
			
			return $link;
		}
			
	}
	
	//////////////////////
	
	
	public static function getAlias($value) {

		static $aliases = array();
		if(isset($aliases[(string)$value])) { return $aliases[(string)$value]; }
		
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('url_alias')
					->where("query = ?", $value)
					->limit(1);
		$result = $db->fetchRow($query);
		

		$aliases[(string)$value] = $result;
		return $result;
	}
	
	
	//////////////////////////////////////////// v2 ////////////////////////////////////////////
	public static function pinAction($pin_id, $action = null, $query = '') {
		static $base_url = null, $pin_prefix = null;
		if($pin_prefix === null) $pin_prefix = Helper_Config::get('config_pin_prefix') ? Helper_Config::get('config_pin_prefix') : 'pin';
		
		if($base_url === null) { $base_url = JO_Request::getInstance()->getBaseUrl(); }
		if(MOD_REWRITE) {
			return $base_url . $pin_prefix . '/' . ($action?$action.'/':'') . $pin_id . ($query ? '?' . $query : '');
		} else {
			return $base_url . '?controller=pin' . ($action ? '&action=' . $action : '') . '&pin_id=' . $pin_id . ($query? '&' . $query : '');
		}
	} 
	
}

?>