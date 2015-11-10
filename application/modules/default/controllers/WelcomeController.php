<?php

class WelcomeController extends Helper_Controller_Default {

	public function helpas() {
		
		if(!JO_Session::get('user[user_id]')) {
			$this->forward('error', 'error404');
		}
		
		$request = $this->getRequest();
		if($this->getLayout()->meta_title) {
			$this->getLayout()->placeholder('title', ($this->getLayout()->meta_title . ' - ' . JO_Registry::get('meta_title')));
		} else {
			$this->getLayout()->placeholder('title', JO_Registry::get('meta_title'));
		}
  
		if($this->getLayout()->meta_description) {
			$this->getLayout()->placeholder('description', $this->getLayout()->meta_description);
		} else {
			$this->getLayout()->placeholder('description', JO_Registry::get('meta_description'));
		}
  
		if($this->getLayout()->meta_keywords) {
			$this->getLayout()->placeholder('keywords', $this->getLayout()->meta_keywords);
		} else {
			$this->getLayout()->placeholder('keywords', JO_Registry::get('meta_keywords'));
		}
		
		$this->getLayout()->placeholder('site_name', JO_Registry::get('site_name'));
		
		$this->view->site_name = JO_Registry::get('site_name');
		$this->view->meta_title = JO_Registry::get('meta_title');
		
		$this->getLayout()->placeholder('google_analytics', html_entity_decode(JO_Registry::get('google_analytics'), ENT_QUOTES, 'utf-8'));
		
		$this->view->baseUrl = $request->getBaseUrl();
		$this->view->site_logo = $request->getBaseUrl() . 'data/images/logo.png';
		if(JO_Registry::get('site_logo') && file_exists(BASE_PATH .'/uploads'.JO_Registry::get('site_logo'))) {
		    $this->view->site_logo = $request->getBaseUrl() . 'uploads' . JO_Registry::get('site_logo'); 
		}
	}
	
	public function indexAction() {
	
		if(JO_Registry::get('isMobile')){
			$request = $this->getRequest();
			$this->noLayout(true);
			$this->noViewRenderer(true);
			$model = new Model_Users_UsersIds(array('start'=>0,'limit'=>300));
			if($model->count()) {
				$users = array_rand((array)$model,min(10, $model->count()));
				$following  = new Model_Users_Following();
				$ok = false;
				if($users) {
					if(is_array($users)) {
						foreach($users as $user){
							Helper_Db::insert('users_following_user', array(
									'user_id' => (string)JO_Session::get('user[user_id]'),
									'following_id' => (string)$user));
						}
					} else {
						Helper_Db::insert('users_following_user', array(
								'user_id' => (string)JO_Session::get('user[user_id]'),
								'following_id' => (string)$users));
					}
				}
			}
			new Model_Users_Edit(JO_Session::get('user[user_id]'),array('first_login'=>0));
			$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=index') );
			
		}else{
			
			$request = $this->getRequest();
			
			$this->view->total_following = Model_Boards_Follow::totalBoardFollow(JO_Session::get('user[user_id]'));
			
			if($this->view->total_following >= 5) {
				$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=welcome&action=second') );
			}
			
			//////////// Categories ////////////
			$this->view->categories = array();
		
			/* v2.2 */
			$config_enable_follow_private_profile = '';
			if(Helper_Config::get('config_enable_follow_private_profile')) {
				$config_enable_follow_private_profile = ' AND user_id IN (SELECT user_id FROM users WHERE public = 1)';
			}
			/* v2.2 */
			$categories = Model_Categories::getCategories(array(
				'filter_status' => 1,
				'where' => new JO_Db_Expr('category.category_id IN (SELECT category_id FROM boards WHERE category_id = category.category_id AND pins > 0 ' . $config_enable_follow_private_profile . ')')
			));
			
			$model_images = new Helper_Images();
			
			foreach($categories AS $category) {
				if($category['image']) {
					$category['thumb'] = $model_images->resize($category['image'], 113, 113, true);
				} else {
					$category['thumb'] = $model_images->resize(Helper_Config::get('no_image'), 113, 113);
				}
				
				$this->view->categories[] = $category;
			}
			
			$this->view->load_boards = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=welcome&action=boards');
			$this->view->next_step = WM_Router::create($request->getBaseUrl() . '?controller=welcome&action=second');
			
			//call header and footer childrens
			$this->view->children = array(
					'header_part' 	=> 'layout/header_part',
					'footer_part' 	=> 'layout/footer_part'
			);
		}
	}
	
	public function secondAction() {
		
		$request = $this->getRequest();
		
		$this->view->total_following = Model_Boards_Follow::totalBoardFollow(JO_Session::get('user[user_id]'));
		
		if($this->view->total_following < 5) {
			$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=welcome') );
		}
		
		//////////// Categories ////////////
		$this->view->categories = array();
		
		/* v2.2 */
		$config_enable_follow_private_profile = '';
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$config_enable_follow_private_profile = ' AND user_id IN (SELECT user_id FROM users WHERE public = 1)';
		}
		/* v2.2 */
		
		$categories = Model_Categories::getCategories(array(
				'filter_status' => 1,
				'where' => new JO_Db_Expr('category.category_id IN (SELECT category_id FROM boards WHERE category_id = category.category_id AND pins > 0 ' . $config_enable_follow_private_profile . ')')
		));
		
		$model_images = new Helper_Images();
		
		foreach($categories AS $category) {
			if($category['image']) {
				$category['thumb'] = $model_images->resize($category['image'], 113, 113, true);
			} else {
				$category['thumb'] = $model_images->resize(Helper_Config::get('no_image'), 113, 113);
			}
				
			$this->view->categories[] = $category;
		}
		
		$this->view->load_boards = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=welcome&action=boardsideas');
		$this->view->next_step = WM_Router::create($request->getBaseUrl() . '?controller=welcome&action=third');
		
		$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory' );
		$this->view->createBoardWithCat = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=create' );
		
		
		//call header and footer childrens
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
		
	}
	
	public function thirdAction() {
		
		$request = $this->getRequest();
		
		$this->view->total_following = Model_Boards_Follow::totalBoardFollow(JO_Session::get('user[user_id]'));
		
		if($this->view->total_following < 5) {
			$this->redirect( WM_Router::create($request->getBaseUrl() . '?controller=welcome') );
		}
		
		$this->view->board_url = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=boards&action=view&ignoreDisabled=true&board_id=' . $this->getRequest()->getQuery('board_id') );
		$this->view->board_name = $this->getRequest()->getQuery('name');
		
		//call header and footer childrens
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
	}
	
	public function boardsAction() {
		
		$request = $this->getRequest();
		
		$category_id = $request->getPost('category_id');
		
		$this->view->boards = array();
		
		if($category_id) {
			
			/* v2.2 */
			$config_enable_follow_private_profile = '';
			if(Helper_Config::get('config_enable_follow_private_profile')) {
				$config_enable_follow_private_profile = ' AND boards.user_id IN (SELECT user_id FROM users WHERE public = 1)';
			}
			/* v2.2 */
			$boards = new Model_Boards_PopularBoards(array(
				'start' => 0,
				'limit' => 25,
				'sort' => 'DESC',
				'order' => 'boards.total_views',
				'where' => new JO_Db_Expr("boards.category_id = '" . $category_id . "' AND boards.pins > 0 " . $config_enable_follow_private_profile)
			)); 
			
			if($boards->count()) {
				$formatObject = new Helper_Format();
				foreach($boards AS $board) {
					$data = $formatObject->fromatListBoard($board);
					$this->view->boards[] = array(
						'board_id' => $data['board_id'],
						'thumbs' => $data['thumbs'],
						'title' => $data['title'],
						'fullname' => $data['fullname'],
						'autor_id' => $board['board_user_id'],
						'following_board' => $data['following_board'],
						//texts
						'text_by' => $this->translate('by'),
						'text_follow' => $this->translate('Follow'),
						'text_unfollow' => $this->translate('Unfollow'),
						//links
						'board_follow_href' => WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=follow&user_id=' . $data['autor_id'] . '&board_id=' . $data['board_id'])
					);
				}
			} else {
				$this->view->error = $this->translate('No boards in this category!');
			}
			
		}
		
		echo $this->renderScript('json');
		
	}
	
	public function boardsideasAction() {
		
		$request = $this->getRequest();
		
		$category_id = $request->getPost('category_id');
		
		$this->view->boards = array();
		
		if($category_id) {
			
			/* v2.2 */
			$config_enable_follow_private_profile = '';
			if(Helper_Config::get('config_enable_follow_private_profile')) {
				$config_enable_follow_private_profile = ' AND boards.user_id IN (SELECT user_id FROM users WHERE public = 1)';
			}
			/* v2.2 */
			$boards = new Model_Boards_PopularBoards(array(
				'start' => 0,
				'limit' => 25,
				'sort' => 'DESC',
				'order' => 'boards.total_views',
				'where' => new JO_Db_Expr("boards.category_id = '" . $category_id . "' AND boards.pins > 0 " . $config_enable_follow_private_profile)
			));
			if($boards->count()) {
				foreach($boards AS $board) {
					$this->view->boards[$board['board_title']] = array(
						'board_id' => $board['board_board_id'],
						'category_id' => $board['board_category_id'],
						'title' => $board['board_title'],
						//texts
						'text_create_board' => $this->translate('Create Board')
					);
				}
			} else {
				$this->view->error = $this->translate('No boards in this category!');
			}

			sort($this->view->boards);
			
		}
		
		echo $this->renderScript('json');
		
	}
	
}

?>