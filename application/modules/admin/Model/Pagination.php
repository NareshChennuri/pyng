<?php

class Model_Pagination extends JO_Pagination {

	protected $url;
	protected $text_first = '&laquo;';
	protected $text_last = '&raquo;';
	protected $text_next = '&rsaquo;';
	protected $text_prev = '&lsaquo;';
	
	public function setUrl($url) {
		$this->url = $url;
	}
	
	public function render() {
		$results = parent::render();
		
		if($this->num_pages < 2) return;
		
		$output ='';
		
		if($this->page > 1) {
			$output .= ' <a href="' . str_replace('{page}', 1, $this->url) . '">' . $this->text_first . '</a> <a href="' . str_replace('{page}', $this->page - 1, $this->url) . '">' . $this->text_prev . '</a> ';
		}
		
		foreach($results AS $result) {
			if($result === null) {
				$output .= ' <a href="javascript:void(0);"> ... </a> ';
			} elseif($result == $this->page) {
				$output .= ' <a class="number current" href="' . str_replace('{page}', $result, $this->url) . '">' . $result . '</a> ';
			} else {
				$output .= ' <a class="number" href="' . str_replace('{page}', $result, $this->url) . '">' . $result . '</a> ';
			}
		}
		
		
		if ($this->page < $this->num_pages) {
			$output .= ' <a href="' . str_replace('{page}', $this->page + 1, $this->url) . '">' . $this->text_next . '</a> <a href="' . str_replace('{page}', $this->num_pages, $this->url) . '">' . $this->text_last . '</a> ';
		}
		
		return $output;
		
	}
	
}

?>