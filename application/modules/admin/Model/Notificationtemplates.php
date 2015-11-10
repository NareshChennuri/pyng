<?php

class Model_Notificationtemplates {
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('notification_templates', array(
			'title' => $data['title'],
			'template' => $data['template'],
			'info' => $data['info']
		));
		return $db->lastInsertId();
	}

	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('notification_templates', array(
			'title' => $data['title'],
			'template' => $data['template']
		), array('`key` = ?' => (string)$id));
		return $id;
	}

//	public static function delete($id) {
//		$db = JO_Db::getDefaultAdapter();
//		return $db->delete('notification_templates', array('`key` = ?' => (int)$id));
//	}

    public static function getAll() {
        $db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from("notification_templates");
        return $db->fetchAll($query);
    }

    public static function get($id) {
        
    	static $result = null;
    	
    	if($result !== null) return $result;
    	
    	$db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from("notification_templates")
            ->where("`key` = ?", (string)$id);
        $result = $db->fetchRow($query);
        
        return $result;
    }
    
}

?>