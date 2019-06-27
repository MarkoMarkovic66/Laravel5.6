<?php
namespace App\Dtos;

/**
 * 1件のGrammar問題を管理するクラス
 */
class GrammarDto {

    public $problemId;

    public $grammarCode;
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

    function __construct() {
    }

}
