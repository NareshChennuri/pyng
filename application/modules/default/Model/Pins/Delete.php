<?php

class Model_Pins_Delete {

	public $affected_rows = null;
	
	public function __construct( $pin_id ) {
		
		$db= JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$pin_info = new Model_Pins_Pin($pin_id);
			if(!$pin_info->count()) {
				return $this;
			}
			
			$pin_info = $pin_info->data;
			
			$result = null;
			
			if($pin_info['pin_image']) {
				$res = Helper_Db::create('pins_images_for_delete', array(
					'pin_id' => $pin_info['pin_pin_id'],
					'date_added' => $pin_info['pin_date_added'],
					'image' => $pin_info['pin_image'],
					'store' => $pin_info['pin_store'],
					'gallery_id' => 0
				));
				if(!$result) { $result = $res; }
			}
			
			$gallery = new Model_Pins_Gallery($pin_info['pin_pin_id']);
			if($gallery->count()) {
				foreach($gallery->data AS $gal) {
					$res = Helper_Db::create('pins_images_for_delete', array(
							'pin_id' => $gal['pin_id'],
							'date_added' => $pin_info['pin_date_added'],
							'image' => $gal['image'],
							'store' => $gal['store'],
							'gallery_id' => $gal['gallery_id']
					));
					if(!$result) {
						$result = $res;
					}
				}
			}
			
			if($pin_info['pin_comments']) {
				$comments = Model_Comments::getComments2(array(
						'filter_pin_id' => $pin_id
				));
				
				foreach($comments AS $comment) {
					$del = new Model_Pins_DeleteComment($comment['comment_id']);
					if(!$result) { $result = $del->affected_rows; }
				}
			}
			
			$res = Helper_Db::delete('pins', array('pin_id = ?' => $pin_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('pins_invert', array('pin_id = ?' => $pin_id));
			if(!$result) { $result = $res; }
			if($pin_info['pin_likes']) {
				$res = Helper_Db::delete('pins_likes', array('pin_id = ?' => $pin_id));
				if(!$result) { $result = $res; }
			}
			$res = Helper_Db::delete('pins_reports', array('pin_id = ?' => $pin_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('pins_views', array('pin_id = ?' => $pin_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('users_history', array('pin_id = ?' => $pin_id));
			if(!$result) { $result = $res; }
			$res = Helper_Db::delete('pins_images', array('pin_id = ?' => $pin_id));
			if(!$result) { $result = $res; }

			$res = Helper_Db::update('users', array(
				'likes' => new JO_Db_Expr('('.$db->select()->from('pins_likes', 'COUNT(like_id)')->where('pin_id IN (?)', new JO_Db_Expr('('.$db->select()->from('pins','pin_id')->where('user_id = ?', $pin_info['user_user_id']).')'))->limit(1).')')
			), array('user_id=?'=>$pin_info['user_user_id']));
			if(!$result) { $result = $res; }
			
			///////////////// update latest pins for user and board /////////////////////
			new Model_Users_UpdateLatestPins($pin_info['user_user_id']);
			new Model_Boards_UpdateLatestPins($pin_info['board_board_id']);
		
			///////////////// Extension on delete //////////////////
			$extensions = Model_Extensions::getByMethod('pin_ondelete');
			if($extensions) {
				$front = JO_Front::getInstance();
				foreach($extensions AS $id => $ext) {
					$res = call_user_func(array($front->formatModuleName('model_' . $ext . '_pin'), 'ondelete'), $pin_id);
					if(!$result) { $result = $res; }
				}
			}
			
			$this->affected_rows = $result;
			
			$db->commit();
			
		} catch ( JO_Exception $e ) {
			$db->rollBack();
		}
	}

}

?>