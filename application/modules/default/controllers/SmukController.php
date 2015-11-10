<?php

class SmukController extends Helper_Controller_Default {
	
	public function indexAction() {
		
		//exit;
		
		$base_url = 'http://pinterest.com/moose44/horse-barns-stalls/';
		$pages = 5;
		$enable_comments = false;
		$category_id = 29;
		$config_user_id = false;
		$enable_defaul_board_create = false;
		
		///end config
		
		//exit;
		ini_set('memory_limit','500M');
		
		$this->noViewRenderer(true);
		ignore_user_abort(true);
		
		if(!$enable_defaul_board_create) {
			Helper_Config::set('default_boards', false);
		}
		
		for($i=1; $i<$pages; $i++) {
			
			$base_url_format = $base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'page=' . $i;
			
			$html = @file_get_contents($base_url_format);
			
			if( $html ) {
		
				$dom = new JO_Html_Dom();
				$dom->load($html);
		    	
		    	$hrefs = $dom->find('.PinImage');
				
		    	if($hrefs) {
			    	foreach($hrefs AS $href) {
			    		$price = 0;
			    		
			    		$url = JO_Url_Relativetoabsolute::toAbsolute($base_url_format, $href->href);
			    		
						$html2 = @file_get_contents($url);
						
						if( $html2 ) {
							$dom = new JO_Html_Dom();
			    			$dom->load( $html2 );
			    		
			    			$board = $dom->find('h3.serif a', 0)->innertext;
			    			$image = $dom->find('#pinCloseupImage', 0)->src;
			    			$description = $dom->find('#PinCaption .description', 0)->innertext;
			    			$from = $dom->find('#PinSource a', 0)->href;
			    			$usernames = $dom->find('#PinnerName a', 0)->innertext;
			    			$avatar = $dom->find('#PinnerImage img', 0)->src;
			    			$ext = strtolower(strrchr($avatar, '.'));
							$avatar = preg_replace('/_60'.preg_quote($ext).'$/i', '_600'.$ext, $avatar);
							if(!@getimagesize($avatar)) {
								$avatar = preg_replace('/'.preg_quote($ext).'$/i', '_600'.$ext, $avatar);
							}
			    			
			    			$username = trim($dom->find('#PinnerName a', 0)->href, '/');
			    			$price_o = $dom->find('.buyable', 0);
							if($price_o) {
	    						$price = $price_o->innertext;
	    					}
	    					
	    					if(!$config_user_id) {
		    					$user_id = Model_Users_Spider_Users::getUserByName($username, $usernames, $avatar);
		    					if(!$user_id) {
		    						continue;
		    					}
	    					} else {
	    						$user_id = $config_user_id;
	    					}
	    					
	    					$board_data = new Model_Boards_BoardIdByTitle(trim($board), $user_id, $category_id);
	    					if(!$board_data->count()) {
	    						continue;
	    					}
	    					
	    					$board_id = $board_data['board_board_id'];
	    					
				    		$price_f = 0;
				    		if(preg_match('/([0-9.]{1,})/',$price,$m)) {
				    			$price_f = $m[1];
				    		}
				    		
				    		$result = new Model_Pins_Create(array(
			    				'board_id' => $board_id,
			    				'description' => htmlspecialchars(strip_tags($description), ENT_QUOTES, 'utf-8'),
			    				'image' => (string)$image,
			    				'price' => (float)$price,
			    				'from' => urldecode($from),
			    				'public' => '1',
				    			'user_id' => $user_id
				    		));
				    		
				    		if(!$result->count()) {
				    			continue;
				    		}
				    		
				    		$pin_id = $result->data['pin_id'];
				    		
				    		//// get comments
				    		if($enable_comments) { 
					    		$commm = $dom->find('.PinComments .comment');
					    		if($commm) {
					    			foreach($commm AS $com) {
					    				$avatar = $com->find('.CommenterImage img', 0)->src;
					    				$usernames = $com->find('.CommenterName', 0)->innertext;
						    			$username = trim($com->find('.CommenterName', 0)->href, '/');
						    			$text = explode('<br />', $com->find('.comment .CommenterMeta', 0)->innertext);
						    			$text = isset($text[1]) ? $text[1] : '';
						    			if($text) {
						    				$user_id = Model_Users_Spider_Users::getUserByName($username, $usernames, $avatar);
					    					if(!$user_id) {
					    						continue;
					    					}
					    					
					    					$result = new Model_Pins_AddComment(array(
												'pin_id' => $pin_id,
												'user_id' => $user_id,
												'comment' => $text,
												'date_added' => WM_Date::format(null, 'yy-mm-dd H:i:s')
											));

						    			}
				    			
					    			}
					    			
					    			sleep(1);
					    		}
				    		}
				    		
				    		sleep(1);
				    		
			    			
						}
			    		
			    	}
		    	}
	    	
	    	
	    	
			}
	    	
			
		}
		
		exit;
	}
	
}

?>