<?php

class Model_Pins_DeleteComment {

	public $affected_rows = null;
	
	public function __construct( $comment_id ) {
		
		$db= JO_Db::getDefaultAdapter();
		
		try {
			$db->beginTransaction();
			
			$info = self::getComment($comment_id);
			
			if($info) {
				
				$result = Helper_Db::delete('pins_comments', array('comment_id = ?' => $comment_id));
				$res = Helper_Db::delete('pins_reports_comments', array('comment_id = ?' => $comment_id));
				if(!$result) { $result = $res; }
				
				$res = Helper_Db::update('pins', array(
					'comments' => new JO_Db_Expr("(SELECT COUNT(comment_id) FROM pins_comments WHERE pin_id = '".(string)$info['pin_id']."')"),
					'latest_comments' => new JO_Db_Expr("(SELECT GROUP_CONCAT(comment_id ORDER BY comment_id ASC) FROM (SELECT comment_id FROM pins_comments WHERE pin_id = '" . (string)$info['pin_id'] . "' ORDER BY comment_id ASC LIMIT 4) AS tmp)")
				), array('pin_id = ?' => (string)$info['pin_id']));
				if(!$result) { $result = $res; }
				
				$this->affected_rows = $result;
				
			}
			
			$db->commit();
			
		} catch ( JO_Exception $e ) {
			$db->rollBack();
		}
	}
	
	public static function getComment($comment_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_comments')
					->where('comment_id = ?', $comment_id)
					->limit(1);
		return $db->fetchRow($query);
	}
	
}

?>