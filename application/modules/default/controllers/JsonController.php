<?php

class JsonController extends Helper_Controller_Default {

	public function countAction() {
		$request = $this->getRequest();
		
		$json = array(
			'count' => 0		
		);
		
		$url = urldecode($request->getQuery('url'));
		if($url && JO_Validate::validateHost($url)) {
			$source_id = Model_Source::getSourceByUrl($url, false);
			
			if($source_id) {
				$total_pins = Model_Pins::getTotalPinsLikes(array(
					'filter_source_id' => $source_id,
					'filter_from_md5' => md5($url)
				));
				$json['count'] = $total_pins;
			} else {
				$json['count'] = 0;
			}
		} else {
			$json['error'] = $this->translate('Invalid Url');
		}
		
		$json['url'] = $url;
//		$json['count'] = 1212;
//		$json['error'] = $this->translate('Invalid Url');
		
		if($request->getQuery('callback')) {
			$response = $this->getResponse();
			$response->addHeader('Cache-Control: no-cache, must-revalidate');
			$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			$response->addHeader('Content-type: application/json');
			echo 'receiveCount('.JO_Json::encode($json).');';
			exit;
		} else {
			foreach($json AS $k => $v) {
				$this->view->{$k} = $v;
			}
			echo $this->renderScript('json');
		}
		
	}
	
}

?>