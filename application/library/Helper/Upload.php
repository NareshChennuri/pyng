<?php

class Helper_Upload extends JO_Upload {

	public function __construct() {
		parent::__construct();
		
		$translate = WM_Translate::getInstance();
		
		$this->setErrorMessage(1, $translate->translate("The uploaded file is larger than the allowed maximum size for uploading to the server settings."));
		$this->setErrorMessage(2, $translate->translate("The uploaded file is larger than the allowed maximum size for upload in your site."));
		$this->setErrorMessage(3, $translate->translate("File was partially uploaded"));
		$this->setErrorMessage(4, $translate->translate("File was not successfully uploaded"));
		// end  http errors
		$this->setErrorMessage(10, $translate->translate("Please select file to upload"));
		$this->setErrorMessage(11, $translate->translate("Only files with the following extensions are allowed: {ext_string}"));
		$this->setErrorMessage(12, $translate->translate("Sorry, the file name contains illegal characters. Use only alphanumeric characters and underscore without spaces. Correct the file name ends with a point and then the extension."));
		$this->setErrorMessage(13, $translate->translate("The name of the file exceeds maximum length of {max_length_filename} characters."));
		$this->setErrorMessage(14, $translate->translate("Sorry, the directory file upload does not exist!"));
		$this->setErrorMessage(15, $translate->translate("Error uploading files: {the_file}. File already exists!"));
		$this->setErrorMessage(16, $translate->translate("The uploaded file is renamed to {file_copy}."));
		
	}
}

?>