<?php

class LayoutController extends Helper_Controller_Default {	
	
	
	public function header_partAction() {

		
		$request=$this->getRequest();
		
		if(JO_Session::get('user[user_id]') && JO_Session::get('category_id')) {
			new Model_Users_Edit(JO_Session::get('user[user_id]'), array(
				'first_login' => '0'
			));
			JO_Session::clear('category_id');
			
			$template = Model_Notification::getTemplate('welcome');
			if($template) {
				$template_data = array(
						'user_id' => JO_Session::get('user[user_id]'),
						'user_firstname' => JO_Session::get('user[firstname]'),
						'user_lastname' => JO_Session::get('user[lastname]'),
						'user_fullname' => JO_Session::get('user[fullname]'),
						'user_username' => JO_Session::get('user[username]'),
						'site_url' => $request->getBaseUrl(),
						'site_name' => Helper_Config::get('site_name'),
						'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')
				);
							
				if(!$template['title']) {
					$template['title'] = $this->translate('Welcome to ${site_name}!');
				}
				
				$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);
				$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);
					
				Model_Email::send(
						JO_Session::get('user[email]'),
						Helper_Config::get('noreply_mail'),
						$title,
						$body
				);
				
				
			}
		}
		
		
		$this->view->show_landing = !Helper_Config::get('enable_free_registration');
		
		$this->getLayout()->placeholder('site_name', Helper_Config::get('site_name'));
		
		$this->view->site_name = Helper_Config::get('site_name');
		$this->view->meta_title = Helper_Config::get('meta_title');
		
		$this->getLayout()->placeholder('google_analytics', html_entity_decode(Helper_Config::get('google_analytics'), ENT_QUOTES, 'utf-8'));
		
		$this->view->baseUrl = $request->getBaseUrl();
		$this->view->site_logo = $request->getBaseUrl() . 'data/images/logo.png';
		if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
		    $this->view->site_logo = $request->getBaseUrl() . 'uploads' . Helper_Config::get('site_logo'); 
		}
		
		///global metas
		if( !JO_Layout::getInstance()->placeholder('header_metas') ) {
			$to_title = '';
			if(JO_Session::get('user[user_id]')) {
				$to_title = JO_Session::get('user[fullname]') . ' / ';
			}
			$this->getLayout()->placeholder('title', $to_title . Helper_Config::get('meta_title'));
			JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('layout/header_metas', array(
				'meta_title' => Helper_Config::get('meta_title'),
				'meta_description' => Helper_Config::get('meta_description'),
				'meta_keywords' => Helper_Config::get('meta_keywords'),
				'site_logo' => $this->view->site_logo
			)));
		}
		
		if(Helper_Config::get('favicon') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('favicon'))) {
		    $this->getLayout()->placeholder('favicon', $request->getBaseUrl() . 'uploads' . Helper_Config::get('favicon'));
		}
		
		$this->getLayout()->placeholder('site_logo', $this->view->site_logo);
		
		$this->view->show_header_invate = !JO_Session::get('user[user_id]');
		
		$this->view->controller_open = $request->getController();
		
		//==== brand =====//
		$this->view->show_brand = false;
		/*if( JO_Registry::get('license_powered_check') == 'false' && Helper_Config::get('config_hide_brand') ) {
			$this->view->show_brand = false;
		}*/
		
		////////// CURRENCY
		//autoupdate currency if set
		if(Helper_Config::get('config_currency_auto_update')) {
			WM_Currency::updateCurrencies();
		}
		
		//////////// Categories ////////////
		$this->view->categories = array();
		$this->view->category_active = false;
		$categories = Model_Categories::getCategories(array(
			'filter_status' => 1
		));
		foreach($categories AS $category) {
			$category['href'] = WM_Router::create( $request->getBaseUrl() . '?controller=category&category_id=' . $category['category_id'] );
			$category['active'] = $category['category_id'] == $request->getRequest('category_id');
			if($category['active']) {
				$this->view->category_active = $category['title'];
			} else {
				
			}
			$this->view->categories[] = $category;
		}
		
		////////////////////////////// USER MENU ///////////////////////////
		$this->view->is_loged = JO_Session::get('user[user_id]');
		if($this->view->is_loged) {
			$model_images = new Helper_Images();
			
			$avatar = Helper_Uploadimages::avatar(JO_Session::get('user'), '_A');
			$this->view->self_avatar = Helper_Uploadimages::userAvatars(JO_Session::get('user'));
			
			
			$this->view->self_profile = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $this->view->is_loged );
			$this->view->self_firstname = JO_Session::get('user[firstname]');
			$this->view->logout = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=logout' );
			
			$this->view->user_pins = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=pins&user_id=' . $this->view->is_loged  );
			$this->view->user_pins_likes = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=pins&user_id=' . $this->view->is_loged . '&filter=likes' );
			$this->view->settings = WM_Router::create( $request->getBaseUrl() . '?controller=settings' );
			
		}
		$this->view->login = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		$this->view->landing = WM_Router::create( $request->getBaseUrl() . '?controller=landing' );
		
		
		$this->view->registration = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=register' );
		
		////////////////////////////// GIFTS ///////////////////////////
		$this->view->gifts = WM_Router::create( $request->getBaseUrl() . '?controller=gifts' );
		$this->view->giftSums = array();
		$sums = Model_Gifts::getAll();
		foreach($sums AS $sum) {
			if($sum['price_from'] || $sum['price_to']) {
				$title = WM_Currency::format($sum['price_from']);
				$url = '';
				if($sum['price_to']) {
					$title .= ' - ';
					$title .= WM_Currency::format($sum['price_to']);
					$url = '&price_to=' . $sum['price_to'];
				} else {
					$title .= '+';
				}
					
				$this->view->giftSums[] = array(
						'title' => $title,
						'href' => WM_Router::create( $request->getBaseUrl() . '?controller=gifts&price_from=' . $sum['price_from'] . $url)
				);
			}
		}
		
		
		//////////// Video ////////////
		$this->view->video_url = WM_Router::create( $request->getBaseUrl() . '?controller=videos' );
		
		//////////// Popular ////////////
		$this->view->popular_url = WM_Router::create( $request->getBaseUrl() . '?controller=popular' );
		
		//////////// ALL PINS ////////////
		$this->view->all_url = WM_Router::create( $request->getBaseUrl() . '?controller=all' );
		
	
		////////////////////////////// SEARCH ///////////////////////////
		
		//$this->view->search_action = WM_Router::create($request->getBaseUrl() . '?controller=search');
		
		if( $request->getAction() != 'index' && $request->getController() == 'search' ) { 
			$with_action = $request->getAction();
			$this->view->search_action = WM_Router::create($request->getBaseUrl() . '?controller=search&action='.$request->getAction());
		} else {
			$with_action = 0;
			$this->view->search_action = WM_Router::create($request->getBaseUrl() . '?controller=search');
		}
		
		$this->view->search_autocomplete = WM_Router::create($request->getBaseUrl() . '?controller=search&action=autocomplete');
		if(strpos($this->view->search, '?') !== false) {
			$this->view->show_hidden = true;
			$this->view->with_action = $with_action;
		}
		
		$this->view->keywords = $request->issetQuery('q') ? $request->getQuery('q') : $this->translate('Search');
		
		////////////////////////////// ADD PIN ///////////////////////////
		$this->view->addPin = WM_Router::create($request->getBaseUrl() . '?controller=addpin');
		
		////////////////////////////// user ignore search engine ///////////////////////////
		if($request->getRequest('user_id')) {
			$user_info = Model_Users::getUser($request->getRequest('user_id'));
			if($user_info && $user_info['dont_search_index']) {
				$this->getLayout()->placeholder('inhead', '<meta name="robots" content="noindex"/>');
			}
		}
		
		////////////////////////////// ABOUT MENU ///////////////////////////
		
		$about_menu = Model_Pages::getMenu(0);
		$this->view->about_menu = array();
		foreach($about_menu AS $row => $page) {
			$class = '';
			if($row==0) {
				$class .= ' first';
			} else if( (count($about_menu)-1) == $row ) {
				$class .= ' last';
			}
			
			
			if($page['page_id'] == -1) {
				$has = true;
			} else {
				if($page['status']) {
					if(isset($has) && $has) {
						$class .= " group";
						$has = false;
					}
					$this->view->about_menu[] = array(
							'class' => trim($class),
							'title' => $page['title'],
							'href' => WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page['page_id'])
					);
				}
				$has = false;
			}
		}
		
		////////////////////////////// NEW PASSWORD ///////////////////////////
		
		$this->view->show_new_password = false;
		if( JO_Session::get('user[user_id]') && JO_Session::get('user[email]') != JO_Session::get('user[new_email]') ) {
			/*switch(true) {
				case 'index' == $request->getController():
				case 'all' == $request->getController():
				case 'category' == $request->getController():
				case 'videos' == $request->getController():
				case 'popular' == $request->getController():
				case 'gifts' == $request->getController():
					$this->view->show_new_password = true;
				break;
			}*/
			$this->view->show_new_password = true;
		}
		
		////////////////////////////// Board category /////////////////////////// 
		
		if( ($board_info = JO_Registry::forceGet('board_category_change')) instanceof ArrayObject ) {
			$this->view->board_category_change = array(
				'title' => 	$board_info['board_title'],
				'href' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=edit&user_id=' . $board_info['board_user_id'] . '&board_id=' . $board_info['board_board_id'] )	
			); 
		}
		
		
		///////////////////// invate menu user /////////////////////////////
		$methodsforinvates = $this->view->callChildrenView('invates/methodsforinvates');
		$this->view->methodsforinvates = (array)$methodsforinvates->user_invate_friends;
		
		///////////////////// extra metatags /////////////////////////////
		$extra_metas = '';
		$extra_metas_get = Model_Extensions::getByMethod('extra_metas');
		foreach($extra_metas_get AS $id => $mod) {
			$extra_metas .= $this->view->callChildren('modules_' . $mod . '_extrametas');
		}
		
		if($extra_metas) {
			JO_Layout::getInstance()->placeholder('header_metas', $extra_metas);
		}
		
	}	
	
    public function footer_partAction() {	
    	
	}
	
	public function left_partAction() {
		
	}
	
	public function header_metasAction($site_info = array()) {
		
		if($site_info) {

			if(!isset($site_info['site_logo']) || !$site_info['site_logo']) {
				$site_info['site_logo'] = $this->getRequest()->getBaseUrl() . 'data/images/logo.png';
				if(Helper_Config::get('site_logo') && file_exists(BASE_PATH .'/uploads'.Helper_Config::get('site_logo'))) {
					$site_info['site_logo'] = $this->getRequest()->getBaseUrl() . 'uploads' . Helper_Config::get('site_logo');
				}
			}
			
			$this->view->site_info = array(
				'title' => $site_info['meta_title'],
				'description' => $site_info['meta_description'],
				'keywords' => $site_info['meta_keywords'],
				'image' => $site_info['site_logo']
			);
			
			
		} else {
			$this->noViewRenderer(true);
		}
	}
	
}

?>