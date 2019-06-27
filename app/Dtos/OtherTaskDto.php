<?php
namespace App\Dtos;

/**
 * その他出題を管理するクラス
 */
class OtherTaskDto {
    public $otherTaskId;
    
    public $otherTaskName;
    public $taskContext;
    public $url;
    public $priority;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }
}
