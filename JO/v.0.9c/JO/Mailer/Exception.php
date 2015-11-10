<?php

class JO_Mailer_Exception extends JO_Exception {

	public function errorMessage() {
		$errorMsg = '<strong>' . $this->getMessage() . "</strong><br />\n";
		return $errorMsg;
	}
	
}

?>