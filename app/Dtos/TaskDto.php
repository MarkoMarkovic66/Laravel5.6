<?php
namespace App\Dtos;

use App\Enums\QuestionType;

/**
 * 当該会員への1件の出題候補情報を管理するクラス
 */
class TaskDto {

    public $userId;
    public $taskId;

    public $taskType;           //出題問題の種類
    public $taskTypeName;       //出題問題の種類
    public $candidateTaskId;    //出題候補のid
    public $taskOrder;          //出題問題の順序

    public $taskDetail;         //当該の出題問題の詳細情報

    public $isSelected;
    public $comment;

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }

    public function setTaskTypeName(){

        $this->taskTypeName = '';

        if(!isset($this->taskType)){
            return;
        }

        if($this->taskType == QuestionType::QUESTION_TYPE_GRAMMAR){
            $this->taskTypeName = QuestionType::QUESTION_TYPE_GRAMMAR_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_VOCABULARY){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_VOCABULARY_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_OTHER_13){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_OTHER_13_NAME;
        }if($this->taskType == QuestionType::QUESTION_TYPE_LISTENING_B){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_LISTENING_B_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_LISTENING_C){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_LISTENING_C_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_LISTENING_D){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_LISTENING_D_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_REVIEW_SERVICE){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_REVIEW_SERVICE_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_REVIEW_LESSON){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_REVIEW_LESSON_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON_NAME;

        }elseif($this->taskType == QuestionType::QUESTION_TYPE_RV_LESSON){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_RV_LESSON_NAME;
        }elseif($this->taskType == QuestionType::QUESTION_TYPE_WORD_CARD){
            $this->taskTypeName =  QuestionType::QUESTION_TYPE_WORD_CARD_NAME;
        }
    }

}

