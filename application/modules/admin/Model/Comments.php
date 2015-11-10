<?php

class Model_Comments {

	public static function getComments($data) {

		$db = JO_Db::getDefaultAdapter();	
		$query = $db
							->select()
							->from('pins_comments')
							->joinLeft(array('u' => 'users'), 'pins_comments.user_id = u.user_id', array('fullname' => "CONCAT(firstname,' ', lastname)",'username'));
							
		if(isset($data['filter_pin_id'])) {
			$query->where('pins_comments.pin_id = ?', (int)$data['filter_pin_id']);
		}
		
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'desc') {
			$sort = ' DESC';
		} else {
			$sort = ' ASC';
		}
		
		$allow_sort = array(
			'pins_comments.comment_id'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('pins_comments.comment_id' . $sort);
		}
				
		return $db->fetchAll($query);

	}
	
	public static function deleteComment($com_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$info = self::getComment($com_id);
		$results = false;
		if($info) {
			$results = $db->delete('pins_comments', array('comment_id = ?' => $com_id));
			$db->delete('pins_reports_comments', array('comment_id = ?' => $com_id));
			
			$comments = self::getComments(array(
				'filter_pin_id' => $info['pin_id'],
				'start' => 0,
				'limit' => 4,
				'sort' => 'ASC',
				'order' => 'pins_comments.comment_id'
			));
			
			$fcm = array();
			if($comments) {
				foreach($comments AS $c) {
					$fcm[] = $c['comment_id'];
				}
			}
			$db->update('pins', array(
				'comments' => new JO_Db_Expr('comments - 1'),
				'latest_comments' => implode(',',$fcm)
			), array('pin_id = ?' => $info['pin_id']));
			
		}
		return $results;
	}
	
	public static function getComment($com_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('pins_comments')
					->where('comment_id = ?', $com_id)
					->limit('1');
		return $db->fetchRow($query);
	}
	
    public static function getCommentsR($data = array()) {
		$db = JO_Db::getDefaultAdapter();
    
		
			$query = $db
					->select()
					->from(array('rp' => 'pins_reports_comments'))
					->joinLeft(array('prc' => 'pins_comment_reports_categories'), 'rp.prc_id = prc.prc_id', array('category_title' => 'title'))
					->joinLeft(array('c' => 'pins_comments'), 'rp.comment_id = c.comment_id')
					->joinLeft(array('p' => 'pins'), 'p.pin_id = c.pin_id')
					->joinLeft(array('u' => 'users'), 'rp.user_id = u.user_id', array('fullname' => "CONCAT(firstname,' ', lastname)",'u.username'))
					->joinLeft(array('b' => 'boards'), 'p.board_id = b.board_id', array('board_title' => 'title'));
	
		
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		if(isset($data['sort']) && strtolower($data['sort']) == 'asc') {
			$sort = ' ASC';
		} else {
			$sort = ' DESC';
		}
		
		$allow_sort = array(
			'p.pin_id',
		    'c.comment_id',
		    'rp.date_added'
		);
		
		if(isset($data['order']) && in_array($data['order'], $allow_sort)) {
			$query->order($data['order'] . $sort);
		} else {
			$query->order('rp.date_added' . $sort);
		}
		
        if(isset($data['filter_pr_id']) && $data['filter_pr_id']) {
			$query->where('pr_id = ?', (int)$data['filter_pr_id']);
		}
		
        if(isset($data['filter_prc_id']) && $data['filter_prc_id']) {
			$query->where('rp.prc_id = ?', (int)$data['filter_prc_id']);
		}
		

		return $db->fetchAll($query);
	}
	
	public static function getTotalCommentsR($data = array()) {
		
		$db = JO_Db::getDefaultAdapter();
        
		
			$query = $db
					->select()
					->from(array('p' => 'pins_reports_comments'), 'COUNT(p.comment_id)');

		
//		$query = $db
//					->select()
//					->from(array('p' => 'pins'), 'COUNT(p.pin_id)')
//					->limit(1);
		
		////////////filter
		
    	if(isset($data['filter_pr_id']) && $data['filter_pr_id']) {
			$query->where('pr_id = ?', (int)$data['filter_pr_id']);
		}
		
		return $db->fetchOne($query);
	}
	
    public static function deleteR($report_id) {
		$db = JO_Db::getDefaultAdapter();
			$db->delete('pins_reports_comments', array('pr_id = ?' => $report_id));
			
			return true;
		
	}
	
    public static function getRComment($report_id) {
		$db = JO_Db::getDefaultAdapter();
        
		$query = $db
					->select()
					->from('pins_reports_comments')
					->where('pr_id = ?', $report_id)
					->limit(1);
		
		return $db->fetchRow($query);
		
	}
	
    public static function deleteRC($report_id) {
    		$rp = self::getRComment($report_id);
    		self::deleteComment($rp['comment_id']);
			
			return true;
		
	}
	
 public static function getCommentReportCategories() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('pins_comment_reports_categories', array('prc_id', 'title'))
					->order('sort_order ASC');
		return $db->fetchAll($query);
	}

}

?>