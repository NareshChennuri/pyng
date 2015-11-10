<?php

class Model_Pins_AddComment {
	
	public $comment_id = 0;
	
	public function __construct($data) {
		
		$comment_id = Helper_Db::create('pins_comments', $data);
		
		if(!$comment_id) {
			return $this;
		}
		
		Helper_Db::update('pins', array(
			'comments' => new JO_Db_Expr("(SELECT COUNT(comment_id) FROM pins_comments WHERE pin_id = '".(string)$data['pin_id']."')"),
			'latest_comments' => new JO_Db_Expr("(SELECT GROUP_CONCAT(comment_id ORDER BY comment_id ASC) FROM (SELECT comment_id FROM pins_comments WHERE pin_id = '" . (string)$data['pin_id'] . "' ORDER BY comment_id ASC LIMIT 4) AS tmp)")
		), array('pin_id = ?' => $data['pin_id']));
		
		$this->comment_id = $comment_id;
		
	}
	
}

?>