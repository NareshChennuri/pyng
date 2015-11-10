<?php

class Model_Users_Autoseo {
	
	public $affected_rows = 0;
	
	public function __construct($user_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$user_info = new Model_Users_User($user_id);
		
		if(!$user_info->count()) {
			return $this;
		}
		
		if( ($cleared = trim($this->clear($user_info['username']))) != '' ) {
			$slug = $uniqueSlug = $cleared;
		} else {
			$slug = $uniqueSlug = 'user';
		}
		
		Helper_Db::delete('url_alias', array('query = ?' => 'user_id=' . $user_id));
		
		$uniqueSlug = $this->renameIfExist($uniqueSlug);
		
		$res = Helper_Db::insert('url_alias', array(
			'query' => 'user_id=' . (int)$user_id,
			'keyword' => $uniqueSlug,
			'path' => $uniqueSlug,
			'route' => 'users/profile'
		));
		
		$this->affected_rows = $res;
		
	}
	
	public function clear($string) {
		$string = preg_replace('/[\/\#\!\@\\\\)\(\?\'\"\:\;\>\<\$\,\.\&\%\*\=\|\{\}\[\]\^\`\~\+\ ]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '-');
	}
	
	public function renameIfExist($uniqueSlug) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('url_alias', array('keyword', 'keyword'))
					->where('keyword = ?', $uniqueSlug)
					->orWhere('keyword LIKE ?', $uniqueSlug . '-%');
		$array = $db->fetchPairs($query);
		foreach(WM_Modules::getControllersWithFolders() AS $controller) {
			$controllerName = JO_Front::getInstance()->formatControllerName($controller);
			$array[$controller] = $controller;
			$array = array_merge($array, WM_Modules::getControllerActions($controllerName));
		}
		$array['admin'] = 'admin';
		$array['default'] = 'default';
		$array['board'] = 'board';
		
		$array = JO_Utf8::array_change_key_case_unicode($array);
		
		return $this->rename_if_exists($array, mb_strtolower($uniqueSlug, 'utf-8'));
	}
	
	public function rename_if_exists($array, $query) {
		$i = 0;
		
		$uniqueSlug = $query;
		while(isset($array[$uniqueSlug])) {
			$uniqueSlug = $query . '-' . ++$i;
		}
		
		return $uniqueSlug;
	}

}

?>