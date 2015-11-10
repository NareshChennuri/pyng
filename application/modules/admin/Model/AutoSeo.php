<?php

class Model_AutoSeo {
	
	
	public function getTotalKey($keyword) {
		$db = JO_Db::getDefaultAdapter();
		
		$array = array();
		$array['admin'] = 'admin';
		$array['default'] = 'default';
		
		$front = JO_Front::getInstance()->getModuleDirectoryWithDefault('default/controllers');
		
		foreach(WM_Modules::getControllersWithFolders($front) AS $controller) {
			$controllerName = JO_Front::getInstance()->formatControllerName($controller);
			$array[$controller] = $controller;
			$array = array_merge($array, WM_Modules::getControllerActions($controllerName, $front));
		}
		
		$array = JO_Utf8::array_change_key_case_unicode($array);
		
		if(isset($array[mb_strtolower($keyword, 'utf-8')])) {
			return 1;
		}
		
		$query = $db->select()
					->from('url_alias', new JO_Db_Expr('COUNT(url_alias_id)'))
					->where("LOWER(keyword) = ?", (string)mb_strtolower($keyword, 'utf-8'));
		return $db->fetchOne($query);
	}
	
	public function clear($string) {
		$string = preg_replace('/[^a-z0-9а-яА-Я\-\.]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '-');
	}
	
	public function translate($string) {
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		if(JO_Registry::forceGet('config_latin_translate_query')) {
			$cir = array('/а/','/б/','/в/','/г/','/д/','/е/','/ж/','/з/','/и/','/й/','/к/',
					    '/л/','/м/','/н/','/о/','/п/','/р/','/с/','/т/','/у/','/ф/','/х/','/ц/','/ч/','/ш/','/щ/',
					    '/ъ/','/ь/','/ю/','/я/','/А/','/Б/','/В/','/Г/','/Д/','/Е/','/Ж/','/З/','/И/','/Й/','/К/',
					    '/Л/','/М/','/Н/','/О/','/П/','/Р/','/С/','/Т/','/У/','/Ф/','/Х/','/Ц/','/Ч/','/Ш/','/Щ/',
					    '/Ъ/','/Ь/','/Ю/','/Я/');
	    
	        $lat = array('a','b','v','g','d','e','zh','z','i','y','k',
					    'l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sht',
					    'a','y','yu','a','a','b','v','g','d','e','zh','z','i','y','k',
					    'l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sht',
					    'a','y','yu','a');
	        
	        $string = preg_replace($cir, $lat, $string);
		}
        return self::clear($string);
	}
	
	public function getLanguages() {
		$data = Model_Language::getLanguages();
		if($data) {
			return $data;
		}
		return array();
	}
	
}

?>