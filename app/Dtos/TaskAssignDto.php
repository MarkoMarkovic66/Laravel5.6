<?php

namespace App\Dtos;

/**
 * ReserveQuestionDto
 * 出題予約情報を管理するクラス
 */
class TaskAssignDto {

    public $userId;

    // 選択済みの出題リスト
    public $selectedTaskList;

    // 未選択の出題リスト
    public $reservedTaskList;


    function __construct() {
        $this->selectedTaskList = [];
        $this->reservedTaskList = [];
    }

}
