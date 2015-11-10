<?php

class Model_Boards_Autoseo {
	
	public function __construct($board_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$board_info = new Model_Boards_Board($board_id);
		
		if(!$board_info->count()) {
			return $this;
		}
		
		if( ($cleared = trim($this->clear($board_info['board_title']))) != '' ) {
			$slug = $uniqueSlug = $cleared;
		} else {
			$slug = $uniqueSlug = 'user-board';
		}
		
		Helper_Db::delete('url_alias', array('query = ?' => 'board_id=' . $board_id));
		
		$uniqueSlug = $this->renameIfExist($uniqueSlug);
		
		Helper_Db::insert('url_alias', array(
			'query' => 'board_id=' . (int)$board_id,
			'keyword' => $uniqueSlug,
			'path' => $uniqueSlug,
			'route' => 'boards/view'
		));

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