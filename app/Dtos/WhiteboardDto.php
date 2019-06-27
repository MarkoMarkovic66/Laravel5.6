<?php
namespace App\Dtos;

/**
 * 1件のWhiteboard情報を管理するクラス
 */
class WhiteboardDto {

    public $whiteboardId;
    public $board;
    public $createdAt;

    function __construct() {
    }

}
