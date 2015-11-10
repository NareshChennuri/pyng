<?php

class Model_Usergroups {

	public static function getGroups() {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
							->select()
							->from('user_groups');
		return $db->fetchAll($query);
	}
	
	public function getUserGroup($group_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
    						->from('user_groups')
    						->where("ug_id = ?", (int)$group_id);
    	$result = $db->fetchRow($query);
		
    	if(!$result) {
    		return false;
    	}
    	
    	$result['access'] = (array)unserialize($result['rights']);
    	
    	return $result;
    	
	}
	
	public static function editeUserGroup($group_id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('user_groups', array(
			'name' => $data['name'],
			'description' => $data['description'],
			'rights' => serialize(isset($data['access']) ? $data['access'] : array())
		),array('ug_id = ?' => (int)$group_id));
		
		return $group_id;
	}
	
	public static function createUserGroup($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('user_groups', array(
			'name' => $data['name'],
			'description' => $data['description'],
			'rights' => serialize(isset($data['access']) ? $data['access'] : array())
		));
		return $db->lastInsertId();
	}
	
	public static function deleteUserGroup($group_id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('user_groups', array('ug_id = ?' => (int)$group_id));
	}
	
	
}
