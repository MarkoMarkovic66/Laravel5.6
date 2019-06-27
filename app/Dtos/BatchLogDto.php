<?php
namespace App\Dtos;

use App\Enums\BatchLogType;
use App\Enums\BatchRunStatusType;
use App\Enums\BatchRunType;
use App\Enums\BatchNameType;

/**
 * BatchLogDto
 */
class BatchLogDto {
    public $id;

    public $batchName;
    public $batchNameText;

    public $runType;
    public $runTypeText;

    public $logType;
    public $logTypeText;

    public $status;
    public $statusText;

    public $eventAt;

    public $resultMessage;//フルテキスト
    public $resultMessageShorten;//省略形

    public $isDeleted;
    public $createdAt;
    public $updatedAt;
    public $deletedAt;

    function __construct(){
    }

    public function setBatchNameText(){
        if(!empty($this->batchName)){

            if($this->batchName == BatchNameType::BATCH_NAME_DATA_IMPORT){
                $this->batchNameText = BatchNameType::BATCH_NAME_DATA_IMPORT_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_CREATE_GV){
                $this->batchNameText = BatchNameType::BATCH_NAME_CREATE_GV_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_QUESTION_ALGORITHM){
                $this->batchNameText = BatchNameType::BATCH_NAME_QUESTION_ALGORITHM_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_CREATE_QUESTION){
                $this->batchNameText = BatchNameType::BATCH_NAME_CREATE_QUESTION_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_CREATE_TASK){
                $this->batchNameText = BatchNameType::BATCH_NAME_CREATE_TASK_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_CW_PULL){
                $this->batchNameText = BatchNameType::BATCH_NAME_CW_PULL_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_CW_PUSH){
                $this->batchNameText = BatchNameType::BATCH_NAME_CW_PUSH_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_TASK_RESET){
                $this->batchNameText = BatchNameType::BATCH_NAME_TASK_RESET_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_CREATE_MEMBER_REPORT){
                $this->batchNameText = BatchNameType::BATCH_NAME_CREATE_MEMBER_REPORT_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_PUSH_MEMBER_REPORT){
                $this->batchNameText = BatchNameType::BATCH_NAME_PUSH_MEMBER_REPORT_TEXT;

            }elseif($this->batchName == BatchNameType::BATCH_NAME_ACHIEVEMENT_COUNT){
                $this->batchNameText = BatchNameType::BATCH_NAME_ACHIEVEMENT_COUNT_TEXT;
            }
        }
    }

    public function setTypeText(){
        if(!empty($this->runType)){
            if($this->runType == BatchRunType::BATCH_RUN_AUTO){
                $this->runTypeText = BatchRunType::BATCH_RUN_AUTO_TEXT;
            }elseif($this->runType == BatchRunType::BATCH_RUN_MANUAL){
                $this->runTypeText = BatchRunType::BATCH_RUN_MANUAL_TEXT;
            }
        }

        if(!empty($this->logType)){
            if($this->logType == BatchLogType::BATCH_LOG_START){
                $this->logTypeText = BatchLogType::BATCH_LOG_START_TEXT;
            }elseif($this->logType == BatchLogType::BATCH_LOG_FINISH){
                $this->logTypeText = BatchLogType::BATCH_LOG_FINISH_TEXT;
            }
        }

        if(!empty($this->status)){
            if($this->status == BatchRunStatusType::BATCH_RUN_STATUS_OK){
                $this->statusText = BatchRunStatusType::BATCH_RUN_STATUS_OK_TEXT;
            }elseif($this->status == BatchRunStatusType::BATCH_RUN_STATUS_FAIL){
                $this->statusText = BatchRunStatusType::BATCH_RUN_STATUS_FAIL_TEXT;
            }
        }
    }

}
