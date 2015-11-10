<?php

class Model_Pins_Gallery /*extends ArrayObject*/ {
	
	public $data = array();
	
	/* (non-PHPdoc)
	 * @see ArrayObject::count()
	 */
	public function count() {
		return is_array($this->data) ? count($this->data) : 0;
	}

	public function __construct($pin_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
			->from('pins_gallery')
			->where('pin_id = ?', $pin_id)
			->order('sort_order ASC');
		$this->data = $db->fetchAll($query);
		
// 		parent::__construct($result);
	}
	
}

?>