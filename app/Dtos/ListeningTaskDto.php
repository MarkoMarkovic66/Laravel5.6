<?php
namespace App\Dtos;

/**
 * 1件のListeningTask情報を管理するクラス
 */
class ListeningTaskDto {

    public $stageId;        // ステージID
    public $unitId;         // cc_stage_unit.unit_id
    public $subjectId;      // cc_subject.id
    public $questionId;     // cc_question.id
    public $unitType;       // cc_unit.unit_type
    public $hearingId;      // cc_hearing.id
    public $result;         // 空文字
    public $clear;          // false固定
    public $questionNumber; // 連番
    public $soundFile;      // review_file.file

    function __construct() {
    }

}
