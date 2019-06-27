<?php
namespace App\Dtos;

/**
 * 復習を管理するクラス
 */
class ReviewDto {

    public $reviewId;

    public $reviewName;
    public $reviewContext;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }
}
