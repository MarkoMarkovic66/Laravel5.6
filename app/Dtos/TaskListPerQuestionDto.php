<?php

namespace App\Dtos;

/**
 * TaskListQuestionDto
 * 1件の出題を管理するクラス
 */
class TaskListPerQuestionDto {

    //public $userTaskPeriodId; //user_task_periods.id
    public $userTaskCalId;     //user_task_calendars.id

    public $taskId;           //tasks.id

    public $taskType;         //tasks.task_type
    public $candidateTaskId;  //tasks.cand_task_id

    public $question;         //1件の設問の出題

    function __construct(){
        $this->userTaskCalId = 0;
        $this->taskId = 0;
        $this->taskType = 0;
        $this->candidateTaskId = 0;
        $this->question = '';
    }
}
