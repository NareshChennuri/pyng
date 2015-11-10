<?php

class PagesController extends Helper_Controller_Default {
	
	public function indexAction() {
		$this->getRequest()->setParams('page_id', (string)JO_Registry::get('page_pinmarklet'));
		$this->forward('pages', 'read');
	}
	
    public function readAction() {
    	
	    $pageIDs = explode('_',$this->getRequest()->getRequest('page_id'));
	    $pageID = end($pageIDs);
	    
	    
	    
//	    if($pageID == JO_Registry::get('page_contact')) {
//	    	$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=contacts'));
//	    }

	    if($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->issetQuery('popup')) {
	    	$this->forward('pages', 'ajaxRead');
	    }
	    
    	$this->view->page = Model_Pages::getPage($pageID);
    	if(!$this->view->page) {
    		$this->forward('error', 'error404');
    	}
    	
    	if($this->view->page['parent_id']) {
    		$this->getRequest()->setParams('active_page_id', $this->view->page['parent_id']);
    	} else {
    		$this->getRequest()->setParams('active_page_id', $pageID);
    	}
    	
    	$model_images = new Helper_Images();
    	
		$this->view->page['description'] = html_entity_decode($this->view->page['description'], ENT_QUOTES, 'utf-8');
		if(Helper_Config::get('config_fix_image_thumb_editor')) {
			$this->view->page['description'] = $model_images->fixEditorText($this->view->page['description']);
		}
		if(Helper_Config::get('config_fix_external_urls')) {
			$this->view->page['description'] = $this->fixUrl($this->view->page['description']);
		}

		$this->view->page['description'] = $this->replaceTags($this->view->page['description']);
    	   
		
		$this->getLayout()->placeholder('title', ($this->view->page['meta_title'] ? $this->view->page['meta_title'] : $this->view->page['title']));
		JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('layout/header_metas', array(
				'meta_title' => ($this->view->page['meta_title'] ? $this->view->page['meta_title'] : $this->view->page['title']),
				'meta_description' => $this->view->page['meta_description'] ? $this->view->page['meta_description'] : Helper_Config::get('meta_description'),
				'meta_keywords' => $this->view->page['meta_keywords'] ? $this->view->page['meta_keywords'] : Helper_Config::get('meta_keywords')
		)));
		
    
		$this->view->images = array();
		$images = Model_Gallery::getGalleryImages($pageID, 'pages');
		if($images) {
			foreach($images AS $image) {
				$this->view->images[] = array(
					'title' => $image['title'],
					'thumb' => $model_images->resize($image['image'], 91, 47, true),
					'popup' => $model_images->resizeWidth($image['image'], 582),
					'image' => 'uploads' . $image['image']
				);
			}
		}
		
		$this->view->full_url = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . $pageID);
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part',
        	'left_part' 	=> 'pages/left_part'
        );
	}
	
	public function left_partAction(){
		
		$request = $this->getRequest();
		
		$pages = Model_Pages::getPages(array(
			'parent_id' => 0
		));
// 		$pages = Model_Pages::getPagesMenu(0);
		
		$this->view->pages = array();
		if($pages) {
			foreach($pages AS $page) {
				$this->view->pages[] = array(
					'title' => $page['title'],
					'href' => WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page['page_id']),
					'active' => $page['page_id'] == $request->getRequest('active_page_id')
				);
			}
		}
		
		
	}
	
    public function ajaxReadAction() {
    	
	    if($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->issetQuery('popup')) {
//	    	$this->noLayout(true);
	    } else {
	    	$this->forward('pages', 'read');
	    }
    	
	    $pageIDs = explode('_',$this->getRequest()->getRequest('page_id'));
	    $pageID = end($pageIDs);
	    
    	$this->view->page = Model_Pages::getPage($pageID);
    	if(!$this->view->page) {
    		$this->forward('error', 'error404');
    	}
    	
    	$model_images = new Helper_Images();
    	
		$this->view->page['description'] = html_entity_decode($this->view->page['description'], ENT_QUOTES, 'utf-8');
		if(Helper_Config::get('config_fix_image_thumb_editor')) {
			$this->view->page['description'] = $model_images->fixEditorText($this->view->page['description']);
		}
		if(Helper_Config::get('config_fix_external_urls')) {
			$this->view->page['description'] = $this->fixUrl($this->view->page['description']);
		}
		
		$this->view->full_url = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&action=read&page_id=' . $pageID);
		
	}
	
	
	private function replaceTags($description) {
		$search = array(
			'/\{pinit_button\}/i',
			'/\{pinit_button_for_web\}/i'
		);
		
		$this->view->baseUrl = $this->getRequest()->getBaseUrl();
		$this->view->bookmarklet = WM_Router::create( $this->getRequest()->getBaseUrl() . '?controller=bookmarklet' );
		
		$replace = array(
			$this->view->render('pinit_button', 'pages_templates'),
			$this->view->render('pinit_button_for_web', 'pages_templates')
		);
		
		
		return preg_replace($search, $replace, $description);
	}
	
	public function fixUrl($text) {
		
		$dom = new JO_Html_Dom();
		$dom->load($text);
		$tags = $dom->find('a[href!='.JO_Request::getInstance()->getDomain().']');
		foreach($tags AS $tag) {
			$tag->rel = 'nofollow';
			if($tag->target) {
				unset($tag->target);
			}
			$tag->onclick = ($tag->onclick ? $tag->onclick . ';' : '') . "target='_blank';";
		}
		
		return (string)$dom;
	}
}