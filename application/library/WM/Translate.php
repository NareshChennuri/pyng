<?php

class WM_Translate extends JO_Translate {

    /**
     * @var WM_Translate
     */
    protected static $_instance;

    /**
     * @param array $options
     * @return Ambigous <WM_Translate, JO_Translate>
     */
    public static function getInstance($options = array()) {
        if (self::$_instance == null) {
            self::$_instance = new self($options);
            self::initDB();
        }
        return self::$_instance;
    }

    public function translate($key, $value = null) {

        $key = trim($key);

//        $db = JO_Db::getDefaultAdapter();
//
//        $check_query = $db->select()
//                ->from('language_keywords', 'COUNT(language_keywords_id)')
//                ->where('`key` = ?', /* new JO_Db_Expr("MD5(".$db->quote($key).")") */ md5($key))
//                ->where('module = ?', JO_Request::getInstance()->getModule());
//
//        $check = $db->fetchOne($check_query);
//
//        if ($check < 1) {
        $check = isset(self::$data[$key]);
        if (!$check) {
            Helper_Db::insert('language_keywords', array(
                'keyword' => $key,
                'key' => md5($key)/* new JO_Db_Expr("MD5(".$db->quote($key).")") */,
                'module' => JO_Request::getInstance()->getModule()
            ));
        }

        return parent::translate($key, $key);
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