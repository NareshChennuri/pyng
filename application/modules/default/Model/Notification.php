<?php

class Model_Notification {

	public static function getTemplate($key) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('notification_templates')
					->where('`key` = ?', $key)
					->limit(1);
		return $db->fetchRow($query);
	}

	public static function parseTemplate($template,$template_data) {
		$search = array();
		$replace = array();
		if(is_array($template_data)) {
			foreach($template_data AS $k => $v) {
				$search[$k] = '/'.preg_quote('${'.$k.'}').'/i';
				$replace[$k] = $v;
			}
		}
		return preg_replace($search, $replace, $template);
	}
	
}

?>