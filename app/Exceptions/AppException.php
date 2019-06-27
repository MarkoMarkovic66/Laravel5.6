<?php
namespace App\Exceptions;

use Exception;

/**
 * 共通Exception
 */
class AppException extends Exception{
    private $statusCode;

    public function __construct($message, $status) {
        parent::__construct($message, $status);
        $this->statusCode = $status;
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

}
