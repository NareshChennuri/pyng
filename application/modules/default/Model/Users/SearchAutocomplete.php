<?php

class Model_Users_SearchAutocomplete extends Model_Users_Abstract {

	public function __construct($data) {
		$db = JO_Db::getDefaultAdapter();
	
		//select default pin data
		$query = self::getListUsersQueryLite();
	
		//$query->where(new JO_Db_Expr('users.user_id = ? OR ('.$db->select()->from('users_following','COUNT(users_following_id)')->where('following_id = users.user_id AND user_id = ?')->orWhere('user_id = users.user_id AND following_id = ?')->limit(1) .') OR ('.$db->select()->from('users_following_user', 'COUNT(ufu_id)')->where('following_id = users.user_id AND user_id = ?')->orWhere('user_id = users.user_id AND following_id = ?')->limit(1).')'), JO_Session::get('user[user_id]'));
		
		$sql1 = $db->select()->from('users_following','following_id')->where('user_id = ?');
		$sql2 = $db->select()->from('users_following','user_id')->where('following_id = ?');
		$sql3 = $db->select()->from('users_following_user','following_id')->where('user_id = ?');
		$sql4 = $db->select()->from('users_following_user','user_id')->where('following_id = ?');
		
		$query->where(new JO_Db_Expr('users.user_id = ? OR users.user_id IN ('.$sql1.') OR users.user_id IN ('.$sql2.') OR users.user_id IN ('.$sql3.') OR users.user_id IN ('.$sql4.')'), JO_Session::get('user[user_id]'));
		
		if(isset($data['filter_username']) && $data['filter_username']) {
			$query->where('users.firstname LIKE ? OR users.lastname LIKE ? OR users.username LIKE ?', '%' . str_replace(' ', '%', $data['filter_username']) . '%');
		} else {
			$query->where('users.user_id = 0');
		}
		
		//v2.2
		if(Helper_Config::get('config_enable_follow_private_profile')) {
			$query = self::filterFriend($query);
		}
	
		//sort and limit add to query from Model_Users_Abstract
		$query = self::sortOrderLimit($query, $data);
		
		parent::__construct($db->fetchAll($query));
		
	}
	
}

?>