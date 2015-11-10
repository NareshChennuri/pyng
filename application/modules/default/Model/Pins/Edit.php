<?php

class Model_Pins_Edit extends Model_Pins_Abstract {
	
	public $affected_rows = null;
	
	public function __construct($pin_id, $data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
		
		try {
			
			$db->beginTransaction();
			
			$pin_info = new Model_Pins_Pin($pin_id);
			if(!$pin_info->count()) {
				return $this;
			}
			
			$pin_info = $pin_info->data;
			
			if(isset($data['board_id'])) {
				$board_info = new Model_Boards_Board($data['board_id']);
				if($board_info->count()) {
					$data['board_id'] = $board_info['board_board_id'];
					$data['category_id'] = $board_info['board_category_id'];
					$data['public'] = $board_info['board_public'];
				} else {
					$data['board_id'] = 0;
				}
			}
			
			$data['likes'] = new JO_Db_Expr('(SELECT COUNT(DISTINCT user_id) FROM pins_likes WHERE pin_id = pins.pin_id)');
			$data['comments'] = new JO_Db_Expr('(SELECT COUNT(DISTINCT comment_id) FROM pins_comments WHERE pin_id = pins.pin_id)');
			
			$data['date_modified'] = WM_Date::format(time(), 'yy-mm-dd H:i:s');
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
			$on_add_call = Helper_Config::get('pin_onbefore_edit');
			if($on_add_call) {
				foreach($on_add_call AS $call) {
					call_user_func($call, $data);
				}
			}
			
			//edit pin
			$result = Helper_Db::update('pins', $data, array('pin_id = ?' => $pin_id));
			
			///////////////// update latest pins for board /////////////////////
			if(isset($data['board_id']) && $data['board_id'] && $pin_info['pin_board_id'] != $data['board_id']) {
				new Model_Boards_UpdateLatestPins($data['board_id']);
				new Model_Boards_UpdateLatestPins($pin_info['pin_board_id']);
				$board_info = new Model_Boards_Board($data['board_id']);
				if($board_info->count()) {
					if($board_info['board_cover'] == $pin_id) {
						$res = Helper_Db::update('boards', array(
								'cover' => 0
						), array('board_id = >' => $pin_info['pin_board_id']));
						if(!$result && $res) { $result = $res; } 
					}
				}
			}
			
			///////////////// update latest pins for user /////////////////////
			new Model_Users_UpdateLatestPins($pin_info['pin_user_id']);
			
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
			Helper_Db::delete('pins_invert', array( 'pin_id = ?' => $pin_id ));
			foreach($words AS $word => $data1) {
				$dic_id = $db->fetchOne( $db->select()->from('pins_dictionary', 'dic_id')->where('word = ?', $word) );
				if(!$dic_id) {
					$dic_id = Helper_Db::insert('pins_dictionary', array(
						'word' => $word
					));
				}
				if($dic_id) {
					$res = Helper_Db::insert('pins_invert', array(
						'pin_id' => $pin_id,
						'dic_id' => $dic_id
					));
					if(!$result && $res) { $result = $res; }
				}
			}
		
			///////////////// Extension on edit //////////////////
			$extensions = Model_Extensions::getByMethod('pin_onedit');
			if($extensions) {
				$front = JO_Front::getInstance();
				foreach($extensions AS $id => $ext) {
					$pin_data_ext = call_user_func(array($front->formatModuleName('model_' . $ext . '_pin'), 'onedit'), $pin_id, $data);
					if($pin_data_ext && is_array($pin_data_ext)) {
						$data = array_merge(data, $pin_data_ext);
					}
				}
			}
			
			///////////////// Event onComplete /////////////////////
			$trigger = new Helper_Triggers_PinOnEdit();
			$trigger->bind($pin_id);
			
			$this->affected_rows = $result;
			
			$db->commit();
			
		} catch (JO_Exception $e) {
			echo $e; exit;
			$db->rollBack();
		}
	}
	
}

?>