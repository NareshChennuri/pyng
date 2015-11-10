<?php

class JO_Session_Db extends JO_Session_Abstract {
	
	protected static $session;
	
	public function __construct(JO_Session $session) {
		
		session_set_save_handler(
            array(&$this, "open"),
            array(&$this, "close"),
            array(&$this, "read"),
            array(&$this, "write"),
            array(&$this, "destroy"),
            array(&$this, "gc")
        );
    	self::$session = $session; 
        $this->initDb(); 
        register_shutdown_function('session_write_close');
	}
	
	public function open($save_path, $session_name) {
		$_db = JO_Db::getDefaultAdapter();
		self::destroy((string)self::$session->sid());
		return $_db->insert('_session_db', array(
			'value' => serialize(self::$session->getAll()),
			'updated_on' => time(),
			'session_id' => (string)self::$session->sid()
		));
	}
	
	public function close() {
		return self::gc(ini_get('session.gc_maxlifetime'));
		unset($this);
	}
	
	public function read($id) {
		$_db = JO_Db::getDefaultAdapter();
		$query = $_db->select()
							->from('_session_db', 'value')
							->where('session_id = ?', (string)self::$session->sid())
							->limit(1);
		$result = $_db->fetchRow($query);
		if($result) {
			return (array)unserialize($result['value']);
		}
		return array();
	}
	
	public function write($id, $sess_data) {
		$_db = JO_Db::getDefaultAdapter();
//		self::destroy((string)self::$session->sid());
		return $_db->replace('_session_db', array(
			'value' => serialize(self::$session->getAll()),
			'updated_on' => time(),
			'session_id' => (string)self::$session->sid()
		));
	}
	
	public function destroy($id) {
		$_db = JO_Db::getDefaultAdapter();
		return $_db->delete('_session_db', array('session_id = ?' => (string)self::$session->sid()));
	}
	
	public function gc($maxlifetime) {
		$_db = JO_Db::getDefaultAdapter();
		return $_db->delete('_session_db', array('updated_on <= ?' => ( time() - $maxlifetime ) ));
	}
	
	private function initDb() {
		$_db = JO_Db::getDefaultAdapter();
		$_db->query("
			CREATE TABLE IF NOT EXISTS `_session_db` (
			    `session_id` varchar(40) NOT NULL,
			    `value` text,
			    `updated_on` int(11) DEFAULT NULL,
			    PRIMARY KEY (`session_id`),
			    KEY (`updated_on`)
			  ) ENGINE=MyISAM;
		");
	}
	
}

?>