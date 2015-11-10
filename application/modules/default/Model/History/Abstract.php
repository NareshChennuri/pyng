<?php

class Model_History_Abstract extends ArrayObject {

	const REPIN = 1;
	
	const FOLLOW = 2;
	const UNFOLLOW = 3;
	
	const FOLLOW_USER = 4;
	const UNFOLLOW_USER = 5;
	
	const ADDPIN = 6;
	
	const ADDBOARD = 7;
	
	const LIKEPIN = 8;
	const UNLIKEPIN = 9;
	
	const COMMENTPIN = 10;
	
	public static function getType($type) {
		
		static $result = array(), $translate = null;
		if(isset($result[$type])) { return $result[$type]; }
		if($translate === null) { $translate = JO_Translate::getInstance(); }
		
		$array = array(
			self::REPIN => $translate->translate('repinned your pin.'),
			self::FOLLOW => $translate->translate('is now following your pins.'),
			self::UNFOLLOW => $translate->translate('has unfollow your pins.'),
			self::FOLLOW_USER => $translate->translate('is now following you'),
			self::UNFOLLOW_USER => $translate->translate('has unfollow you'),
			self::ADDPIN => $translate->translate('Pinned to'),
			self::ADDBOARD => $translate->translate('Created'),
			self::LIKEPIN => $translate->translate('Like your pin'),
			self::UNLIKEPIN => $translate->translate('Unlike your pin'),
			self::COMMENTPIN => $translate->translate('Comment your pin')
		);
		
		if(isset($array[$type])) {
			$result[$type] = $array[$type];
			return $array[$type];
		} else {
			return false;	
		}
	}
    
    /**
     * @return Ambigous <JO_Db_Select, JO_Db_Select>
     */
    public static function sortOrderLimit($query, $data = array()) {
    	
    	if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
    		$sort = ' ASC';
    	} else {
    		$sort = ' DESC';
    	}
    	
    	$allow_sort = array(
			'history_id'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('history_id' . $sort);
		}
    	
    	if(isset($data['start']) && isset($data['limit'])) {
    		if($data['start'] < 0) {
    			$data['start'] = 0;
    		}
    		$query->limit($data['limit'], $data['start']);
    	}
    	return $query;
    }
	
    /**
     * @param string $table
     * @return array 
     */
    public static function describeTable($table, $row = '') {
        $db = JO_Db::getDefaultAdapter();
        $result = $db->describeTable($table);
        $data = array();
        foreach($result AS $res) {
            $data[$row . $res['COLUMN_NAME']] = $res['COLUMN_NAME'];
        }
        return $data;
    }

	
	
}

?>