<?php
namespace App\Dtos;

/**
 * Apiエラー内容を管理するクラス
 */
class ApiErrorDto {
    public $code;
    public $message;
    public $details;

    function __construct(){
        $this->code = '';
        $this->message = '';
        $this->details = [];
    }

}

