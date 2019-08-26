<?php

namespace Google\Api\Service;

use Google\Api\GoogleException;

class GoogleServiceException extends GoogleException
{
    protected $errors = array();

    public function __construct($message, $code = 0, Exception $previous = null, $errors = array())
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
        }
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
