<?php

namespace App\AppException;

use Exception;

class AppException extends Exception{
    private $_message;
    private $_statusCode;

    public function __construct($message, $status)
    {
        $this->_message = $message;
        $this->_statusCode = $status;
    }

    public function getErrorMessage(){
        return $this->_message;
    }
    public function getStatusCode(){
        return $this->_statusCode;
    }
}