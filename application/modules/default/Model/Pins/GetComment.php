<?php

class Model_Pins_GetComment /*extends ArrayObject*/ {
	
	public $data = array();
	
	/* (non-PHPdoc)
	 * @see ArrayObject::count()
	 */
	public function count() {
		return is_array($this->data) ? count($this->data) : 0;
	}

	public function __construct($comment_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
			->from('pins_comments')
			->where('comment_id = ?', $comment_id)
			->limit(1);
		$result = $db->fetchRow($query);
		$this->data = is_array($result) ? $result : array();
		
// 		parent::__construct($result);
	}
	
}

?>