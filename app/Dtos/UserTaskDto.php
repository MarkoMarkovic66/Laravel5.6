<?php
namespace App\Dtos;

use App\Dtos\UserDto;
use App\Dtos\TaskDto;

use App\Enums\UserTaskStatusType;
use App\Enums\TaskType;


/**
 * 該当会員への1件の出題情報を管理するクラス
 */
class UserTaskDto {

    public $userTaskId;

    public $userId;
    public $taskId;

    public $userTaskType;
    public $userTaskTypeName;

    public $userTaskStatus;
    public $userTaskStatusName;

    public $questionSetDate;    //出題日
    public $answerDeadlineDate; //回答期限日
    public $answeredDate;       //回答日

    public $userMessageLogId;   //回答レコードid

    public $counselorInfo;
    public $operatorInfo;

    public $originalAnswer; //回答URL
    public $userFeedbackId;

    public $comment;

    public $userInfo;       //当該ユーザ情報
    public $taskInfo;       //当該出題情報

    public $srDraft;        //SR回答情報：生徒下書き

    public $feedbackInfo;   //フィードバック情報


    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    public $recNo;


    function __construct(){
    }

    public function setUserTaskStatusName(){
        if(!isset($this->userTaskStatus)){
            $this->userTaskStatus = '';
            return;
        }
        if($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_NONE){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_NONE;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_ASKED){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_ASKED;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_ANSWERED){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_ANSWERED;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_ANSWER_REJECT){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_ANSWER_REJECT;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_NOT_ANSWERED){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_NOT_ANSWERED;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_FEEDBACKED;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_FEEDBACK_REJECT){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_FEEDBACK_REJECT;

        }elseif($this->userTaskStatus == UserTaskStatusType::TASK_STATUS_VAL_RESPONSED){
            $this->userTaskStatusName = UserTaskStatusType::TASK_STATUS_NAME_RESPONSED;
        }
    }

    public function setUserTaskTypeName(){
        if(!isset($this->userTaskType)){
            $this->userTaskTypeName = '';
            return;
        }
        if($this->userTaskType == TaskType::TASK_GRAMMAR){
            $this->userTaskTypeName = TaskType::TASK_GRAMMAR_NAME;

        }elseif($this->userTaskType == TaskType::TASK_VOCABULARY){
            $this->userTaskTypeName = TaskType::TASK_VOCABULARY_NAME;

        }elseif($this->userTaskType == TaskType::TASK_SPEAKING_RALLY){
            $this->userTaskTypeName = TaskType::TASK_SPEAKING_RALLY_NAME;

        }elseif($this->userTaskType == TaskType::TASK_REVIEW){
            $this->userTaskTypeName = TaskType::TASK_REVIEW_NAME;

        }elseif($this->userTaskType == TaskType::TASK_OTHERS){
            $this->userTaskTypeName = TaskType::TASK_OTHERS_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_RV_LESSON){
            $this->userTaskTypeName = TaskType::TASK_TYPE_RV_LESSON_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_WORD_CARD){
            $this->userTaskTypeName = TaskType::TASK_TYPE_WORD_CARD_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_LISTENING_B){
            $this->userTaskTypeName = TaskType::TASK_TYPE_LISTENING_B_NAME;
        }elseif($this->userTaskType == TaskType::TASK_TYPE_LISTENING_C){
            $this->userTaskTypeName = TaskType::TASK_TYPE_LISTENING_C_NAME;
        }elseif($this->userTaskType == TaskType::TASK_TYPE_LISTENING_D){
            $this->userTaskTypeName = TaskType::TASK_TYPE_LISTENING_D_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_REVIEW_BEFORE_LESSON){
            $this->userTaskTypeName = TaskType::TASK_TYPE_REVIEW_BEFORE_LESSON_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_REVIEW_LESSON){
            $this->userTaskTypeName = TaskType::TASK_TYPE_REVIEW_LESSON_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_REVIEW_COUNSELOR){
            $this->userTaskTypeName = TaskType::TASK_TYPE_REVIEW_COUNSELOR_NAME;

        }elseif($this->userTaskType == TaskType::TASK_TYPE_REVIEW_SERVICE){
            $this->userTaskTypeName = TaskType::TASK_TYPE_REVIEW_SERVICE_NAME;

        }else{
            $this->userTaskTypeName = '';
        }

    }


}

