<?php

class WM_Gettranslate {
	
	public static function getTranslate() {
		self::initDB();
		
		$request = JO_Request::getInstance();
		
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language_keywords', array('keyword', 'translate' => new JO_Db_Expr('IF(translate != \'\',translate,keyword)')))
					->where('language_keywords.module = ?', $request->getModule());
					
		$result = $db->fetchPairs($query);
		
		foreach($result AS $k=>$v) {
			$v = trim($v) ? $v : $k;
			$result[$k] = html_entity_decode($v, ENT_QUOTES, 'utf-8');
		}

		return $result;
	}
	
	public static function getTranslateJs() {
		self::initDB();
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language_keywords', array('keyword', 'translate' => new JO_Db_Expr('IF(translate != \'\',translate,keyword)')))
					->where('language_keywords.module = ?', JO_Request::getInstance()->getModule());
					
		$result = $db->fetchPairs($query);
		
		foreach($result AS $k=>$v) {
			$result[$k] = html_entity_decode($v, ENT_QUOTES, 'utf-8');
		}

		return $result;
	}
	
	private static function initDB() {
		Helper_Db::query("CREATE TABLE IF NOT EXISTS `language_keywords` (
		  `language_keywords_id` int(11) NOT NULL auto_increment,
		  `key` char(32) collate utf8_unicode_ci NOT NULL,
		  `keyword` text character set utf8 collate utf8_bin NOT NULL,
		  `translate` text character set utf8 collate utf8_bin NOT NULL,
		  `module` varchar(128) collate utf8_unicode_ci NOT NULL,
		  PRIMARY KEY  (`language_keywords_id`),
		  KEY `module` (`module`),
		  KEY `key` (`key`),
		  FULLTEXT KEY `keyword` (`keyword`)
		) ENGINE=MyISAM;");

	}

}

?>