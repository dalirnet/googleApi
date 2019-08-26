<?php

namespace phpseclib\File\ASN1;

class Element
{
    var $element;

    function __construct($encoded)
    {
        $this->element = $encoded;
    }
}
