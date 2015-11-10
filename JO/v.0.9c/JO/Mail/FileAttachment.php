<?php

/**
* File based attachment class
*/
class JO_Mail_FileAttachment extends JO_Mail_Attachment {

    /**
    * Constructor
    * 
    * @param string $filename    Name of file
    * @param string $contentType Content type of file
    * @param string $encoding    What encoding to use
    */
    public function __construct($filename, $contentType = 'application/octet-stream', $encoding = null)
    {
        $encoding = is_null($encoding) ? new JO_Mail_Base64Encoding() : $encoding;

        parent::__construct(file_get_contents($filename), basename($filename), $contentType, $encoding);
    }
}

?>