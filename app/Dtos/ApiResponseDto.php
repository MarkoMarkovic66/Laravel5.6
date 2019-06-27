<?php
namespace App\Dtos;

use App\Dtos\ApiErrorDto;

/**
 * Apiレスポンス内容を管理するクラス
 */
class ApiResponseDto {
    public $status;
    public $responses;
    public $errors;

    function __construct(){
        $this->status = '';
        $this->responses = [];
        $this->errors = new ApiErrorDto();
    }

}

