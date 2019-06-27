<?php
namespace App\Dtos;

/**
 * 設問を管理するクラス
 */
class QuestionDto {

    public $questionType;

    public $questionId;

    //for grammar, vocabulary
    public $questionCode;
    public $module;
    public $grade;
    public $stage;
    public $seqNumber;
    public $focus;
    public $tips;
    public $question;
    public $answer;
    public $branch;

    //for speaking_rally
    public $srTopicId;
    public $srCategoryId;
    public $srCategory;
    public $srContext;
    public $srCreatedStuffId;
    public $srOpened;

    //for review
    public $reviewName;
    public $reviewContext;

    //for other_task
    public $otherTaskName;
    public $otherTaskContext;
    public $otherTaskUrl;
    public $otherTaskPriority;

    //for word card
    public $wordCardCategory;
    public $wordCardCategoryId;
    public $groupNo;
    public $word;

    function __construct(){
        $this->question = '';
        $this->answer = '';
        $this->srCategory = '';
        $this->srContext = '';
        $this->reviewName = '';
        $this->reviewContext = '';
        $this->otherTaskName = '';
        $this->otherTaskContext = '';
        $this->otherTaskUrl = '';
    }

}
