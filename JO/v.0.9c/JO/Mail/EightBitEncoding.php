<?php

/**
* 8Bit Encoding class
*/
class JO_Mail_EightBitEncoding implements JO_Mail_IEncoding {
    /*
    * Function to "encode" data using
    * 8bit encoding.
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
        return '8bit';
    }
}