<?php

class Search_BoardsController extends Helper_Controller_Default {

	public function config() {
		$request = $this->getRequest();
		return array(
			'sort' => 2,
			'title' => $this->translate('Boards'),
			'active' => in_array($request->getAction(), array('boards')),
			'href' => WM_Router::create($request->getBaseUrl() . '?controller=search&action=boards&q=' . urlencode($request->getQuery('q')))
		);
	}
	
	public function getSearchResultAction($return_data = false) {
	
		$request = $this->getRequest();
		$response = $this->getResponse();
	
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
	
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$query = $request->getRequest('q');
	
		$data = array(
			'start' => ( $pp * $page ) - $pp,
			'limit' => $pp,
			'filter_title' => $query
		);
	
		$return = array();
	
		/* set board count */
		$has_boards = true;
		if(!trim($query)) {
			$has_boards = false;
		}
	
		// pins data
		$boards = $has_boards ? new Model_Boards_Search($data) : new ArrayObject();
	
		//format response data
		$formatObject = new Helper_Format();
	
		if($has_boards && $boards->count()) {
			foreach($boards AS $row => $board) {
				//boards
				$return[] = $formatObject->fromatListBoard($board);
			}
				
		} else {
			if($page == 1) {
				$message = $this->translate('No boards!');
			} else {
				$message = $this->translate('No more boards!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
	
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
	
	}
	
	///////////////autocomplete/
	
	public function autocomplete($query) {
		$request = $this->getRequest();
		
		$result = array();
		
		$boards = new Model_Boards_SearchAutocomplete(array(
			'filter_title' => $query,
			//'sort' => 'asc',
			//'order' => 'boards.title',
			'start' => 0,
			'limit' => 100
		));
		
		if($boards->count()) {
			foreach($boards AS $board) {
				$result[] = array(
						'template' => 'board',
						'title' => $board['board_title'],
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board['board_user_id'] . '&board_id=' . $board['board_board_id'])
				);
			}
		}
		
		return $result;
	}
	
	
}

?>