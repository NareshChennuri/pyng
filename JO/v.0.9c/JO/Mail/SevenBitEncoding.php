<?php
/**
* 7Bit Encoding class
*/
class JO_Mail_SevenBitEncoding implements JO_Mail_IEncoding {
    /*
    * Function to "encode" data using
    * 7bit encoding.
    * 
    * @param string $input Data to encode
    */
    public function encode($input)
    {
        return $input;
    }
    
    /**
    * Returns type
    */
    public function getType()
    {
        return '7bit';
    }
}