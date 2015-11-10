<?php

class Model_Pins_Create extends Model_Pins_Abstract {
	
	public $error = false;
	
	public function __construct($data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$data['category_id'] = 0;
		$data['public'] = 1;
		if(isset($data['board_id'])) {
			$board_info = new Model_Boards_Board($data['board_id']);
			if($board_info->count()) {
				$data['board_id'] = $board_info['board_board_id'];
				$data['category_id'] = $board_info['board_category_id'];
				$data['public'] = $board_info['board_public'];
			} else {
				$data['board_id'] = 0;
			}
		} else {
			$data['board_id'] = 0;
		}
		
		$data['date_added'] = WM_Date::format(time(), 'yy-mm-dd H:i:s');
		$data['date_modified'] = $data['date_added'];
		$data['user_id'] = isset($data['user_id']) ? $data['user_id'] : (string)JO_Session::get('user[user_id]');
		$data['store'] = Helper_Config::get('file_upload_method') ? Helper_Config::get('file_upload_method') : 'Model_Upload_Locale';
		$data['source_id'] = 0;
		//sorce
		
		if(isset($data['from']) && $data['from']) {
			$source = new Model_Sources_GetSourceByUrl($data['from']);
			if($source->source_id) {
				$data['source_id'] = $source->source_id;
			}
		}

		$data['from_md5'] = md5(isset($data['from'])?$data['from']:time());
		
		/* price */
		//$data['price'] = 0;
		if(isset($data['price']) && $data['price']) {
			$currencies = WM_Currency::getCurrencies();
			$price_left = array();
			$price_right = array();
			if($currencies) {
				foreach($currencies AS $currency) {
					if(trim($currency['symbol_left'])) {
						$price_left[] = preg_quote(trim($currency['symbol_left']));
					}
					if(trim($currency['symbol_right'])) {
						$price_right[] = preg_quote(trim($currency['symbol_right']));
					}
				}
				if($price_left) {
					if( preg_match('/(' . implode('|', $price_left) . ')([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/', $data['price'], $match) ) {
						$price_tmp = trim(str_replace(trim($match[1]), '', $match[0]));
						$currency = self::getCurrencyBySimbol(trim($match[1]));
						if($currency) {
							$data['price'] = round( $price_tmp / $currency, 4 );
						}
					}
				}
				if(!$data['price'] && $price_right) {
					if( preg_match('/(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?(' . implode('|', $price_right) . ')/', $data['price'], $match) ) {
						$price_tmp = trim(str_replace(trim($match[2]), '', $match[0]));
						$currency = self::getCurrencyBySimbol(trim($match[2]));
						if($currency) {
							$data['price'] = round( $price_tmp / $currency, 4 );
						}
					}
				}
			}
		}
		/* end price */
		
		$from = isset($data['from'])?$data['from']:time();
		$data['is_video'] = (isset($data['is_video']) && $data['is_video'] == 'true' ? 1 : 0);
		if(!$data['is_video']) {
			$auto = new Helper_AutoEmbed();
			if($auto->parseUrl($from)) {
				$data['is_video'] = 1;
			}
		}
		/* is video */
		
		///////////////// Event onComplete /////////////////////
		$on_add_call = Helper_Config::get('pin_onbefore_create');
		if($on_add_call) {
			foreach($on_add_call AS $call) {
				call_user_func($call, $data);
			}
		}
		
		///////////////// upload image /////////////////////
		$image = false;
		if(isset($data['media']) && !isset($data['image'])) {
			$image = $data['media'];
		} else if(isset($data['image'])) {
			$image = $data['image'];
		}
		
		if(!isset($data['gallery']) && !$image) {
			return $this;
		}
		
		$data['image'] = '';
		
		//create pin
		$data['pin_id'] = Helper_Db::insert('pins', $data); 
		
		if(!$data['pin_id']) { 
			return $this;
		}
		
		
		if(isset($data['gallery'])) {
			if(is_array($data['gallery']) && count($data['gallery']) > 0) {
				$method_for_upload = Helper_Config::get('file_upload_method');
				if($method_for_upload) {
					foreach($data['gallery'] AS $row => $image_get) {
						if($this->error) {
							return $this;		
						}
						$image = call_user_func(array($method_for_upload, 'uploadPin'), $image_get, (isset($data['title']) && $data['title'] ? $data['title'] : $data['description']), $data['pin_id']);
						$this->error = call_user_func(array($method_for_upload, 'getError'));
						
						if($image && isset($image['image'])) {
							if($row == 0) {
								Helper_Db::update('pins', array(
										'image' 	=> $image['image'],
										'store' 	=> $image['store'],
										'height' 	=> $image['height'],
										'width' 	=> $image['width'],
								), array('pin_id = ?' => (string)$data['pin_id']));
							} else {
								$gal_id = Helper_Db::insert('pins_gallery', array(
									'image' 	=> $image['image'],
									'store' 	=> $image['store'],
									'pin_id' 	=> $data['pin_id'],
									'sort_order' 	=> $row,
								));
								if(!$gal_id) {
									return $this;
								}
							}
						} else {
							return $this;
						}
					}
				} else {
					Helper_Db::delete('pins', array('pin_id = ?' => (string)$data['pin_id']));
					return $this;
				}
			} else {
				Helper_Db::delete('pins', array('pin_id = ?' => (string)$data['pin_id']));
				return $this;
			}
		} else {
		
			$method_for_upload = Helper_Config::get('file_upload_method');
			
			if($method_for_upload && $image) {
				$image = call_user_func(array($method_for_upload, 'uploadPin'), $image, (isset($data['title']) && $data['title'] ? $data['title'] : $data['description']), $data['pin_id']);
				$this->error = call_user_func(array($method_for_upload, 'getError'));
				
				if($image && isset($image['image'])) {
					Helper_Db::update('pins', array(
							'image' 	=> $image['image'],
							'store' 	=> $image['store'],
							'height' 	=> $image['height'],
							'width' 	=> $image['width'],
					), array('pin_id = ?' => (string)$data['pin_id']));
				} else {
					Helper_Db::delete('pins', array('pin_id = ?' => (string)$data['pin_id']));
					return $this;
				} 
			} else {
				Helper_Db::delete('pins', array('pin_id = ?' => (string)$data['pin_id']));
				return $this;
			}
		
		}
		
		///////////////// update latest pins for board /////////////////////
		if(isset($data['board_id']) && $data['board_id']) {
			new Model_Boards_UpdateLatestPins($data['board_id']);
		}
		
		///////////////// update latest pins for user /////////////////////
		new Model_Users_UpdateLatestPins($data['user_id']);
		
		///////////////// Pin total repins /////////////////////
		if(isset($data['repin_from']) && $data['repin_from']) {
			$pin_repin = new Model_Pins_Pin($data['repin_from']);
			if($pin_repin->count()) {
				Helper_Db::update('pins', array(
					'repins' => $db->fetchOne( $db->select()->from('pins','COUNT(pin_id)')->where('repin_from = ?', $data['repin_from'])->limit(1) )
				), array('pin_id = ?' => $data['repin_from']));
			}
		}
		
		///////////////// Word for search index's /////////////////////
		$spl = JO_Utf8::str_word_split( strip_tags( html_entity_decode($data['description'], ENT_QUOTES, 'utf-8') ) , self::$searchWordLenght);
		$words = array();
		foreach($spl AS $word) {
			$word = mb_strtolower($word, 'utf-8');
			if( !in_array($word, self::blackWordsDictionary()) && $word[0].$word[1] != '&#' ) {
				$words[$word] = $word;
			}
		}
		
		///////////////// Word for search index's insert /////////////////////
		foreach($words AS $word => $data1) {
			$dic_id = $db->fetchOne( $db->select()->from('pins_dictionary', 'dic_id')->where('word = ?', $word) );
			if(!$dic_id) {
				$db->insert('pins_dictionary', array(
					'word' => $word
				));
				$dic_id = $db->lastInsertId();
			} 
			if($dic_id) {
				$db->insert('pins_invert', array(
					'pin_id' => $data['pin_id'],
					'dic_id' => $dic_id
				));
			}
		}
		
		Helper_Db::delete('pins_images', array('pin_id = ?' => $data['pin_id']));
		
		///////////////// Extension on create //////////////////
		$extensions = Model_Extensions::getByMethod('pin_oncreate');
		if($extensions) {
			$front = JO_Front::getInstance();
			foreach($extensions AS $id => $ext) {
				$pin_data_ext = call_user_func(array($front->formatModuleName('model_' . $ext . '_pin'), 'oncreate'), $data['pin_id'], $data);
				if($pin_data_ext && is_array($pin_data_ext)) {
					$data = array_merge(data, $pin_data_ext);
				}
			}
		}
		
		///////////////// Event onComplete /////////////////////
		$trigger = new Helper_Triggers_PinOnCreate();
		$trigger->bind($data['pin_id']);
		
		$this->data = $data;
		
// 		parent::__construct($data);
	}
	
}

?>