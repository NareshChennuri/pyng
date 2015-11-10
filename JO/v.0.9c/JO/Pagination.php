<?php

class JO_Pagination {
	
	protected $total = 0;
	protected $page = 1;
	protected $limit = 20;
	protected $num_links = 5;
	
	protected $num_pages;
	
	public function __construct() {}
	
	public function setTotal($total) {
		$this->total = $total;
		return $this;
	}
	
	public function setPage($page) {
		$this->page = $page;
		return $this;
	}
	
	public function setLimit($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	public function setNumLinks($num) {
		$this->num_links = $num;
		return $this;
	}
	
	public function render() {
		$total = $this->total;
		
		if ($this->page < 1) {
			$this->page = 1;
		}
		
		if (!$this->limit) {
			$limit = 10;
		} else {
			$limit = $this->limit;
		}
		
		$num_links = $this->num_links;
		$this->num_pages = ceil($total / $limit);
		
		$output = array();

		if ($this->num_pages > 1) {
			if ($this->num_pages <= $num_links) {
				$start = 1;
				$end = $this->num_pages;
			} else {
				$start = $this->page - floor($num_links / 2);
				$end = $this->page + floor($num_links / 2);
			
				if ($start < 1) {
					$end += abs($start) + 1;
					$start = 1;
				}
						
				if ($end > $this->num_pages) {
					$start -= ($end - $this->num_pages);
					$end = $this->num_pages;
				}
			}

			if ($start > 1) {
				$output[] = null;
			}

			for ($i = $start; $i <= $end; $i++) {
				$output[] = $i;
			}
							
			if ($end < $this->num_pages) {
				$output[] = null;
			}
		}
		
		return $output;
	}

}

?>