<?php
namespace App\Dtos;

/**
 * Apiリクエスト内容を管理するクラス
 */
class ApiRequestDto {
    public $method;
    public $params;

    function __construct(){
        $this->method = '';
        $this->params = [];
    }

}

