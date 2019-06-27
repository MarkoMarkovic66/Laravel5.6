<?php
namespace App\Dtos;

/**
 * 1件のVocabulary問題を管理するクラス
 */
class VocabularyDto {

    public $problemId;

    public $vocabularyCode;
    public $module;
    public $grade;
    public $stage;
    public $seqNumber;
    public $focus;
    public $tips;
    public $question;
    public $answer;
    public $branch;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }
}
