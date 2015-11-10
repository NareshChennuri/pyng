<?php
/**
* Attachment class to handle attachments which are contained
* in a variable.
*/
class JO_Mail_StringAttachment extends JO_Mail_Attachment
{
    /**
    * Constructor
    * 
    * @param string $data        File data
    * @param string $name        Name of attachment (filename)
    * @param string $contentType Content type of file
    * @param string $encoding    What encoding to use
    */
    public function __construct($data, $name = '', $contentType = 'application/octet-stream', $encoding = null)
    {
        $encoding = is_null($encoding) ? new JO_Mail_Base64Encoding() : $encoding;
        
        parent::__construct($data, $name, $contentType, $encoding);
    }
}