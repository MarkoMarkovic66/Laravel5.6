<?php
namespace App\Dtos;

/**
 * スピーキングラリーを管理するクラス
 */
class SpeakingRallyDto {

    public $speakingRallyId;

    public $srTopicId;
    public $srCategoryId;
    public $category;
    public $context;
    public $createdStuffId;
    public $opened;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }
}
