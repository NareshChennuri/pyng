<?php

class Model_History_Today extends Model_History_Abstract {
	
	public static $filter_types = array(
			'comments' => self::COMMENTPIN,
			'follow' => self::FOLLOW_USER,
			'like' => self::LIKEPIN,
			'repin' => self::REPIN
	);
	
	public function __construct($data) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$arr = array(
				'comments' => self::COMMENTPIN,
				'follow' => self::FOLLOW_USER,
				'like' => self::LIKEPIN,
				'repin' => self::REPIN
		);
		
		$sql = $db->select()
		->from('users_history', 'to_user_id');
			
		if(isset($data['today']) && !is_null($data['today'])) {
			$sql->where('users_history.date_added BETWEEN DATE_ADD(?, INTERVAL -1 DAY) AND ?', $data['today']);
		} elseif( isset($data['week_range'])) {
			$sql->where("DATE(users_history.date_added) BETWEEN '".$data['week_range']['from']."' AND '".$data['week_range']['to']."'");
		}
			
		$sql->where('history_action IN ('.implode(',',$arr).')');
		
		$query = $db->select()
			->from('users')
			->where(isset($data['week_range'])?'1':'email_interval = 2')
			->where('user_id IN (?)', $sql);
			
		
		$results = $db->fetchAll($query);
		$return = array();
		if($results) {
			
			switch (Helper_Config::get('config_user_view')) {
				case 'username':
					$user_fullname = new JO_Db_Expr('users.username');
					break;
				case 'firstname':
					$user_fullname = new JO_Db_Expr('users.firstname');
					break;
				case 'fullname':
				default:
					$user_fullname = new JO_Db_Expr('CONCAT(users.firstname, " ", users.lastname)');
					break;
			}
			
			foreach($results AS $result) {
		
				foreach( $arr AS $k => $v ) {
					$sql2 = $db->select()
					->from('users_history', new JO_Db_Expr('DISTINCT from_user_id'))
					->where('to_user_id = ?', $result['user_id'])
					->where('`history_action` = ?', $v);
					if(isset($data['today']) && !is_null($data['today'])) {
						$query->where('users_history.date_added BETWEEN DATE_ADD(?, INTERVAL -1 DAY) AND ?', $data['today']);
					} elseif( isset($data['week_range'])) {
						$sql->where("DATE(users_history.date_added) BETWEEN '".$data['week_range']['from']."' AND '".$data['week_range']['to']."'");
					}
						
					$result['history_'.$k] = $db->fetchAll( $db->select()->from('users', array('user_id', 'avatar', 'fullname' => $user_fullname, 'store'))->where('user_id IN (?)', $sql2) );
						
				}
		
				$return[] = $result;
			}
		}
		
		parent::__construct($return);

	}
	
	
}

?>