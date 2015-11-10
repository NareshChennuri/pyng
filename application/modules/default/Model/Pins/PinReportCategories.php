<?php

class Model_Pins_PinReportCategories extends ArrayObject {

	public function __construct() {
		
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
			->from('pins_reports_categories', array('prc_id', 'title'))
			->order('sort_order ASC');
			parent::__construct($db->fetchPairs($query));
		
	}

    /**
     * Return an associative array of the stored data.
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this as $key => $value) {
            if ($value instanceof ArrayObject) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }
	
}

?>