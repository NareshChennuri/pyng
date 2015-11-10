<?php

/**
* Encoding interface
*/
interface JO_Mail_IEncoding
{
    public function encode($input);
    public function getType();
}