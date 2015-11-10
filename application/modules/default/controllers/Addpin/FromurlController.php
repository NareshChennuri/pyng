<?php

class Addpin_FromurlController extends Helper_Controller_Default {

	public $SORT = 1;
	
	public function indexAction() {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		$this->view->pin_add_url = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromurl&action=stepone' );
		
	}
	
	public function steponeAction() {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromurl&action=steptwo' );
		if( JO_Session::get('user[user_id]') ) {
			
			
		} else {
			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		
	}
	
	public function steptwoAction() {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
		
			$this->view->images = array();
			
			if($request->isGet() && $request->getQuery('url')) {
	
				$cleared = preg_replace('/^www./i', '', JO_Validate::validateHost($request->getQuery('url')));
				$cleared = mb_strtolower($cleared, 'utf-8');
				if( Model_Blacksources::is_exists($cleared) ) {
					$this->view->error = sprintf($this->translate('Source %s is blocked!'), $cleared);
					$this->setViewChange('stepone');
				} else {
				
					$video_url = $request->getQuery('url');
					$video_url = trim($video_url);
					if(strpos($video_url,'http') === false) {
						$video_url = 'http://' . $video_url;
					}
						
					$http = new JO_Http();
					$http->setUseragent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
					$http->setReferrer($video_url);
					$http->useCurl(true);
					$http->execute($video_url, $request->getBaseUrl(), 'GET');
					$http->setMaxredirect(5);
					
					/*if(isset($http->headers['location']) && $http->headers['location']) {
						$new_url = $http->headers['location'];
						$http = new JO_Http();
						$http->setUseragent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
						$http->setReferrer($video_url);
						$http->useCurl(true);
						$http->execute($new_url, $request->getBaseUrl(), 'GET');
						if(is_array($new_url)) {
							$video_url = array_shift($new_url);
						} else if($new_url) {
							$video_url = $new_url;
						}
					}*/
					
						
					$videoObject = new Helper_AutoEmbed();
					$parsedVideo = $videoObject->parseUrl($video_url);
					$video_image = false;
					if($parsedVideo) {
						$video_image = $videoObject->getImageURL();
					}
					
					$config_image_minimum_size = (int)Helper_Config::get('config_image_minimum_size');
					if(!$config_image_minimum_size) { $config_image_minimum_size = 80; }
					
					if($http->error) {
						$this->view->error = str_replace("'", "\'", $http->error);;
					} elseif($video_url && ($imagesize = @getimagesize($video_url) ) !== false ) {
						if($imagesize && $imagesize[0] >= $config_image_minimum_size && $imagesize[1] >= $config_image_minimum_size) {
							$this->view->images[] = array(
									'src' => $video_url,
									'width' => $imagesize[0],
									'height' => $imagesize[1],
							);
						}
					} elseif($video_image && ($imagesize = @getimagesize($video_image) ) !== false ) {
						if($imagesize && $imagesize[0] >= $config_image_minimum_size && $imagesize[1] >= $config_image_minimum_size) {
							$this->view->images[] = array(
									'src' => $video_image,
									'width' => $imagesize[0],
									'height' => $imagesize[1],
							);
						}
					} else { 
						$html = $http->result;
						
						$dom = new JO_Dom_Query($html);
						
						$meta = $dom->query('meta');
						$charset = false;
						if($meta->count()) {
							for($i=0; $i<$meta->count(); $i++) {
								$content = $meta->getItem($i)->getAttribute('content');
								if(preg_match('/charset=([^\"\']+)/', $content, $match)) {
									$charset = trim($match[1]);
								}
							}
						}
						
						if($charset) {
							$html = iconv($charset, "UTF-8", $html);
						} else {
							$html = JO_Utf8::convertToUtf8($http->result);
						}
						
						$this->view->title = '';
						if(preg_match('/<title>(.*)<\/title>/sim', $html, $match )) {
							$this->view->title = $match[1];
						}
				
						$meta_image = $dom->query('meta[property="og:image"]');
				
						$meta_image_src = null;
						if($meta_image->count()) {
							$meta_image_src = $meta_image->rewind()->getAttribute('content');
						}
				
						if($meta_image_src) {
							if( ($imagesize = @getimagesize($meta_image_src)) !== false ) {
								if($imagesize && $imagesize[0] >= $config_image_minimum_size && $imagesize[1] >= $config_image_minimum_size) {
									$this->view->images[] = array(
											'src' => $meta_image_src,
											'width' => $imagesize[0],
											'height' => $imagesize[1]
									);
								}
							}
						}
				
						$images = $dom->query('img');
						
						if($images->count() > 0) {
							$images_array = array();
							for($i=0; $i<$images->count(); $i++) {
								$src = $images->getItem($i)->getAttribute('src');
								$image_full = JO_Url_Relativetoabsolute::toAbsolute($request->getQuery('url'), $src);
								$images_array[$image_full] = $image_full;
							}
							
							foreach($images_array AS $image_full) {
								$imagesize = @getimagesize($image_full);
								if($imagesize && $imagesize[0] >=$config_image_minimum_size && $imagesize[1] >= $config_image_minimum_size) {
									$this->view->images[] = array(
											'src' => $image_full,
											'width' => $imagesize[0],
											'height' => $imagesize[1]
									);
								}
							}
						}
					}
						
					$this->view->from = $video_url;
					
				}
					
			}
			
			$this->view->total_images = count($this->view->images);
			if($this->view->total_images < 1) {
				$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromurl&action=steptwo' );
				$this->view->error_total_images = true;
				$this->setViewChange('stepone');
			} else {
				
				$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory' );
				
				$this->view->from_url = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=createpin' );
				
				$boards = new Model_Boards_BoardsWithShared(array(
					'filter_user_id' => JO_Session::get('user[user_id]')
				));
				
				$this->view->boards = array();
				if($boards->count()) {
					foreach($boards AS $board) {
						$this->view->boards[] = array(
								'board_id' => $board['board_board_id'],
								'title' => $board['board_title']
						);
					}
				}
				
				
				///////////////// Extension on create //////////////////
				$this->view->form_extensions = array();
				$extensions = Model_Extensions::getByMethod('pin_oncreateform');
				if($extensions) {
					$front = JO_Front::getInstance();
					foreach($extensions AS $id => $ext) {
						$this->view->form_extensions[] = array(
								'html' => $this->view->callChildren('modules_' . $ext . '_oncreateform'),
								'view' => $this->view->callChildrenView('modules_' . $ext . '_oncreateform'),
								'key' => $ext
						);
					}
				}
				
			}
		} else {
// 			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
		}
	}
	
}

?>