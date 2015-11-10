<?php 

class IndexController extends Helper_Controller_Admin {
	
	public static function config() {
		
		return array(
			'name' => self::translate('Dashboard'),
			'has_permision' => true,
			'menu' => self::translate('Dashboard'),
			'in_menu' => true,
			'permision_key' => 'index',
			'sort_order' => 1
		);
	}
	
	/////////////////// end config

    public function indexAction() {
    	$request = $this->getRequest();
    	
    	$min = Model_Statistics::getMin();
    	$year = date('Y');
    	if($min) {
    		$year = (int)substr($min, 0, 4);
    		$year = $year<2012?2012:$year;
    	}
    	
    	$this->view->years_select = array();
    	$cur = date('Y');
    	for( $i = $cur; $i >= $year; $i --) {
    		$this->view->years_select[] = $i;
    	}
    	
    }
    
    
    //////////// translated
    public function i18nAction() {
    	
    	$this->view->error_validate_1 = $this->translate('You have not filled out a field. Check what is it!');
    	$this->view->error_validate_2 = $this->translate('You have not filled %d boxes. Check who they are!');
    	$this->view->select_all = $this->translate('Select all');
    	$this->view->remove_all = $this->translate('Remove all');
    	$this->view->confirm = $this->translate('Do you really want to perform the selected action?');
    	$this->view->delete_confirm = $this->translate('Delete/Uninstall cannot be undone! Are you sure you want to do this?');
    	
    	echo 'var lang = ' . $this->renderScript('json');
    }
    
    public function total_usersAction() {
//    	$this->view->total = Model_Statistics::getTotalStatistics(new JO_Db_Expr(1),2);
    	$this->view->total = Model_Statistics::getTotalStatistics2('users');
    	echo $this->renderScript('json');
    }
    
    public function total_pinsAction() {
//    	$this->view->total = Model_Statistics::getTotalStatistics(new JO_Db_Expr(1),1);
    	$this->view->total = Model_Statistics::getTotalStatistics2('pins');
    	echo $this->renderScript('json');
    }
    
    public function total_boardsAction() {
//    	$this->view->total = Model_Statistics::getTotalStatistics(new JO_Db_Expr(1),3);
    	$this->view->total = Model_Statistics::getTotalStatistics2('boards');
    	echo $this->renderScript('json');
    }
    
    public function waiting_invitationAction() {
    	
    	$data = array(
    		'start' => 0,
			'limit' => 20,
    		'sort' => 'u.sc_id',
    		'order' => 'DESC',
    	    'filter_sent' => 0
    	
    	);
    	
    	$this->view->users = array();
        $users = Model_Users::getWaiting($data);
        if($users) {
        foreach($users AS $user) {
        		$user['date_added'] = WM_Date::format($user['date_added'], Helper_Config::get('config_date_format_long_time'));
                $user['invite_href'] = $this->getRequest()->getModule() . '/invites/invite/?id=' . $user['sc_id'];
                $this->view->users[] = $user;
            }
        }
    	
    	echo $this->renderScript('json');
    }

    public function monthly_chartAction() {
    	
    	if(preg_match('/^20([0-9]{2})$/', $this->getRequest()->getQuery('year'))) {
    		$year = $this->getRequest()->getQuery('year');
    	} else {
    		$year = WM_Date::format(null, 'yy');
    	}
    	
    	$data = Model_Statistics::getStatistics(new JO_Db_Expr("`id` LIKE '".$year."%'"));
    	
    	$this->view->xAxis = array('categories' => array());
    	$this->view->series = array(
    		array('name' => 'Pins', 'data' => array()),
    		array('name' => 'Users', 'data' => array()),
    		array('name' => 'Boards', 'data' => array())
    	);
    	
    	$get_m = array();
    	
    	if($data) {
    		foreach($data AS $t) {
    			$get_m[$t['id']][$t['type']] = $t['total'];
    		}
    	}
    	
    	for( $i = 1; $i < 13; $i++ ) {
    		if($year == date('Y') && $i > date('m')) {
				continue;
    		}
    		
    		$this->view->xAxis['categories'][] = WM_Date::format(date('Y').'-'.sprintf('%02d',$i).'-01', 'MM');
    		
    		for($r = 1; $r < 4; $r++ ) {
    			if( isset($get_m[$year . sprintf('%02d',$i)][$r]) ) {
    				$total = (int)$get_m[$year . sprintf('%02d',$i)][$r];
    			} else {
    				$total = 0;
    			}
    			$this->view->series[($r-1)]['data'][] = $total;
    		}
    	}
    	
    	echo $this->renderScript('json');
    }
    
}
