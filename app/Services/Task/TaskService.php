<?php
namespace App\Services\Task;

use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\AccountKindType;
use App\Enums\QuestionType;
use App\Enums\TaskType;
use App\Enums\UserTaskStatusType;
use App\Enums\UserMessageLogMessageType;
use App\Enums\UserMessageLogCwPostType;
use App\Enums\UserMessageLogConnectType;
use App\Enums\UserMessageLogLinkedType;
use App\Enums\UserTaskStaffType;
use App\Enums\IsReviewType;
use App\Enums\CategoryType;

use App\Models\Account;
use App\Models\UserTask;
use App\Models\Task;
use App\Models\User;
use App\Models\Grammar;
use App\Models\Vocabulary;
use App\Models\SpeakingRally;
use App\Models\Review;
use App\Models\OtherTask;
use App\Models\UserFeedback;
use App\Models\UserMessageLog;
use App\Models\UserTaskStaff;
use App\Models\UserCounselor;
use App\Models\HomeworkAnswer;

use App\Dtos\UserTaskDto;
use App\Dtos\UserTaskListDto;
use App\Dtos\UserDto;
use App\Dtos\TaskDto;
use App\Dtos\QuestionDto;
use App\Dtos\FeedbackDto;
use App\Dtos\UserMessageLogDto;
use App\Dtos\UserTaskStaffDto;

use App\Utils\DateUtils;
use Carbon\Carbon;

use ChatworkService;
use DB;
use Log;
use Auth;

use Lang;

use App\Models\Lesson;
use App\Models\Reserve;
use App\Utils\CommonUtils;

use App\Services\Homework\HomeworkService;

/**
 * TaskService
 * 宿題管理関連Service
 */
class TaskService {
    /*
     * 宿題種別の配列を生成
     *
     */
    public function getTaskTypes($is_all = true) {

        $OtherTask = new OtherTask();
        $other_tasks = $OtherTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->orderby('task_type_id')
                                ->get();

        if($is_all == true) {
            $task_type_arr = array(
                '0' => 'なし',
                '1' => '瞬間口頭G',
                '2' => '瞬間口頭V',
                '3' => 'Speaking Rally',
                '4' => '復習',
            );
        } else {
            $task_type_arr = array();
        }
        foreach ($other_tasks as $key => $val) {
            $task_type_arr[$val->task_type_id] = $val->other_task_name;
        }

        return $task_type_arr;

    }

    /**
     * 宿題検索
     * @param array $loginAccountInfo
     * @param collection $params
     * @return array<UserTaskDto>
     */
    public function doTaskSearch($loginAccountInfo, $params) {

        $questionSetDateSince    = $params->get('questionSetDateSince');
        $questionSetDateUntil    = $params->get('questionSetDateUntil');
        $answerDeadlineDateSince = $params->get('answerDeadlineDateSince');
        $answerDeadlineDateUntil = $params->get('answerDeadlineDateUntil');
        $answeredDateSince       = $params->get('answeredDateSince');
        $answeredDateUntil       = $params->get('answeredDateUntil');

        $freeword       = $params->get('freeword');
        $selectTaskType = $params->get('selectTaskType');
        $selectStudent  = $params->get('selectStudent');
        $selectStatus   = $params->get('selectStatus');
        $selectOperator = $params->get('selectOperator');
        $pageNo         = $params->get('pageNo');
        $isOnlyNoAssignOperator = $params->get('isOnlyNoAssignOperator');

        /*
         * ログインアカウント種別による絞り込み
         */
        $targetAccountKindId = $loginAccountInfo['account_kind_id'];
        $targetAccountId = $loginAccountInfo['id'];

/*
 * 検索用SQL
 */
$queryBase = <<<SQL
select
    usrtsk.user_id,
    usrtsk.task_type,
    usrtsk.user_task_status,
    date(usrtsk.question_set_date) as qsdate,
    date(usrtsk.answer_deadline_date) as addate,
    date(usrtsk.answered_date) as awdate,
    usrtsk.task_period_order,
    min(usrtsk.id) as minUserTaskId,
    max(usrtsk.id) as maxUserTaskId
from user_tasks as usrtsk
SQL;

$queryWhere = <<<SQL
 where usrtsk.is_deleted = 0
 -- and usrtsk.question_set_date IS NOT NULL
 -- and usrtsk.task_period_order IS NOT NULL
 and usrtsk.user_task_status > 0
SQL;

$queryGroup = <<<SQL
 group by usrtsk.user_id, usrtsk.task_type, date(usrtsk.question_set_date), usrtsk.task_period_order
SQL;

$queryOrder = <<<SQL
 order by date(usrtsk.question_set_date) DESC, usrtsk.task_period_order ASC
SQL;

        if($targetAccountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID){
            //カウンセラー：担当する会員のみに限定
            $queryBase  .= ' inner join user_counselors as csl '
                         . ' on (csl.user_id = usrtsk.user_id)';
            $queryWhere .= ' and ((csl.is_deleted = 0) and (csl.account_id = ' . $targetAccountId. '))';

            if($isOnlyNoAssignOperator){
                //未割当のみに限定する場合
                //user_tasksのうち、オペレータが未割当のもの
                $queryBase  .= ' left join user_task_staffs as stf '
                             . ' on (stf.user_task_id = usrtsk.id and stf.user_id = usrtsk.user_id and stf.staff_type = 2)';
                $queryWhere .= ' and ((stf.id IS NULL) or (stf.id IS NOT NULL and stf.account_id = 0))';
            }

        }elseif($targetAccountKindId == AccountKindType::ACCOUNT_OPERATOR_ID){
            //オペレータ：担当する会員のみに限定
            $queryBase  .= ' inner join user_task_staffs as stf '
                         . ' on (stf.user_task_id = usrtsk.id and stf.user_id = usrtsk.user_id and stf.staff_type = 2)';
            $queryWhere .= ' and ((stf.is_deleted = 0) and (stf.account_id = ' . $targetAccountId. '))';

        }else{
            //管理者、運用者：全員
            if($isOnlyNoAssignOperator){
                //未割当のみに限定する場合
                //user_tasksのうち、オペレータが未割当のもの
                $queryBase  .= ' left join user_task_staffs as stf '
                             . ' on (stf.user_task_id = usrtsk.id and stf.user_id = usrtsk.user_id and stf.staff_type = 2)';
                $queryWhere .= ' and ((stf.id IS NULL) or (stf.id IS NOT NULL and stf.account_id = 0))';
            }

        }//ログインアカウント種別による絞り込み

        /*
         * 検索条件による絞り込み
         */
        if(!empty($questionSetDateSince)){
            $queryWhere .= ' and usrtsk.question_set_date >= '."'".$questionSetDateSince."'";
        }
        if(!empty($questionSetDateUntil)){
            $queryWhere .= ' and usrtsk.question_set_date <= '."'".$questionSetDateUntil."'";
        }

        if(!empty($answerDeadlineDateSince)){
            $queryWhere .= ' and usrtsk.answer_deadline_date >= '."'".$answerDeadlineDateSince."'";
        }
        if(!empty($answerDeadlineDateUntil)){
            $queryWhere .= ' and usrtsk.answer_deadline_date <= '."'".$answerDeadlineDateUntil."'";
        }

        if(!empty($answeredDateSince)){
            $queryWhere .= ' and usrtsk.answered_date >= '."'".$answeredDateSince."'";
        }
        if(!empty($answeredDateUntil)){
            $queryWhere .= ' and usrtsk.answered_date <= '."'".$answeredDateUntil."'";
        }

        if(!empty($freeword)){
        }

        if(empty($selectTaskType)){
            $queryWhere .= ' and usrtsk.task_type IN ('. TaskType::TASK_GRAMMAR        . ','
                                                       . TaskType::TASK_VOCABULARY     . ','
                                                       . TaskType::TASK_SPEAKING_RALLY
                                                    . ')';
        }else{
            $queryWhere .= ' and usrtsk.task_type = '. $selectTaskType;
        }

        if(!empty($selectStudent)){
            $queryWhere .= ' and usrtsk.user_id = '. $selectStudent;
        }

        if(!empty($selectStatus)){
            $queryWhere .= ' and usrtsk.user_task_status = '. $selectStatus;
        }

        if(!empty($selectOperator)){
            $queryBase  .= ' inner join user_task_staffs as stf '
                         . ' on (stf.user_task_id = usrtsk.id and stf.user_id = usrtsk.user_id and stf.staff_type = 2)';
            $queryWhere .= ' and (stf.is_deleted = 0 and stf.id IS NOT NULL and stf.account_id =' . $selectOperator . ')';
        }

        $queryCnt = 'select count(tb.minUserTaskId) as cnt from ('
                  . $queryBase
                  . $queryWhere
                  . $queryGroup
                  . ') tb';

        $totalRowCount = 0;
        $retArr = DB::select($queryCnt);
        if(!empty($retArr) && is_array($retArr) && count($retArr) > 0){
            $totalRowCount = $retArr[0]->cnt;
        }

        //対象ページ指定からskip/take算出
        if($pageNo < 1){
            $pageNo = 1;
        }
        $take = 30; //2018-06-09 速度向上のため30行/ページとする
        $skip = ($pageNo -1) * $take;

        //総ページ数算出
        $totalPageCount = intdiv($totalRowCount, $take);
        if(($totalRowCount % $take) > 0){
            $totalPageCount += 1;
        }

        //検索実行
        $taskList = [];

        $querySearch = $queryBase
                     . $queryWhere
                     . $queryGroup
                     . $queryOrder
                     . ' limit '  . $take
                     . ' offset ' . $skip;
        $userTasks = DB::select($querySearch);

        if(!empty($userTasks) && is_array($userTasks) && count($userTasks) > 0){
            foreach($userTasks as $userTask){

                $userTaskListDto = new UserTaskListDto();

                $userTaskListDto->userId              = $userTask->user_id;
                $userTaskListDto->userTaskType        = $userTask->task_type;
                $userTaskListDto->setUserTaskTypeName();
                $userTaskListDto->userTaskStatus      = $userTask->user_task_status;
                $userTaskListDto->setUserTaskStatusName();

                $userTaskListDto->taskPeriodOrder     = $userTask->task_period_order;

                $userTaskListDto->questionSetDate     = $userTask->qsdate; //DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->question_set_date));
                $userTaskListDto->answerDeadlineDate  = $userTask->addate; //DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answer_deadline_date));
                $userTaskListDto->answeredDate        = $userTask->awdate; //DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answered_date));

                $userTaskListDto->minUserTaskId       = $userTask->minUserTaskId;
                $userTaskListDto->maxUserTaskId       = $userTask->maxUserTaskId;


                //回答レコードid
                //$userTaskListDto->userMessageLogId    = $userTask->user_message_log_id;
                //カウンセラー情報
                //$userTaskListDto->counselorInfo = $this->getStaffInfo($userTask, UserTaskStaffType::USER_TASK_STAFF_COUNSELOR);

                //オペレータ情報
                $operatorInfos = $this->getStaffInfo($userTaskListDto->minUserTaskId, $userTaskListDto->userId, UserTaskStaffType::USER_TASK_STAFF_OPERATOR);
                if(count($operatorInfos) > 0){
                    $userTaskListDto->operatorInfo = $operatorInfos[0];
                }else{
                    $userTaskListDto->operatorInfo = null;
                }

                //ユーザ情報
                $userTaskListDto->userInfo            = $this->getUserInfo($userTask);
                //出題情報
                //$userTaskListDto->taskInfo            = $this->getTaskInfo($userTask);
                //回答情報
                //$userTaskListDto->originalAnswer      = $userTask->original_answer;
                //フィードバックid
                //$userTaskListDto->userFeedbackId      = $userTask->user_feedback_id;
                //フィードバック情報
                //$userTaskListDto->feedbackInfo        = $this->getFeedbackInfo($userTask);

                $taskList[] = $userTaskListDto;
            }
        }
        return ['taskList' => $taskList,
                'totalRowCount' => $totalRowCount,
                'totalPageCount' => $totalPageCount
                ];
    }

    /**
     * User情報を取得
     * @param eloquant $userTask
     * @return UserDto
     */
    public function getUserInfo($userTask){
        $userInfo = new UserDto();

        if(empty($userTask)){
            return $userInfo;
        }

        $user = User::where('id', $userTask->user_id)
                    ->first();

        if(empty($user) || $user->count() < 1){
            return $userInfo;
        }

        $userInfo->userId         = $user->id;
        $userInfo->arugoUserId    = $user->arugo_user_id;
        $userInfo->srUserId       = $user->sr_user_id;
        $userInfo->cwRoomId       = $user->cw_room_id;
        $userInfo->cwRoomName     = $user->cw_room_name;
        $userInfo->firstName      = $user->first_name;
        $userInfo->lastName       = $user->last_name;
        $userInfo->firstNameEn    = $user->first_name_en;
        $userInfo->lastNameEn     = $user->last_name_en;
        $userInfo->mail           = $user->mail;
        $userInfo->phoneNumber    = $user->phone_number;
        $userInfo->curriculumId   = $user->curriculum_id;
        $userInfo->studySec       = $user->study_sec;
        $userInfo->status         = $user->status;
        $userInfo->matrix         = $user->matrix;
        $userInfo->openedAt       = $user->opened_at;
        $userInfo->closedAt       = $user->closed_at;
        $userInfo->resignedAt     = $user->resigned_at;
        $userInfo->accountType    = $user->user_type;
        $userInfo->activated      = $user->activated;
        $userInfo->quitted        = $user->quitted;
        $userInfo->isDeleted      = $user->is_deleted;
        $userInfo->createdAt      = $user->created_at;
        $userInfo->updatedAt      = $user->updated_at;
        $userInfo->deletedAt      = $user->deleted_at;
        return $userInfo;
    }

    /**
     * スタッフ情報を取得する
     * @param type $taskId
     * @param type $userId
     * @param type $staffType
     * @return UserTaskStaffDto
     */
    public function getStaffInfo($userTaskId, $userId, $staffType){

        $ret = [];
        if($staffType == UserTaskStaffType::USER_TASK_STAFF_COUNSELOR){
            //カウンセラーを取得
            $staffRows = UserCounselor::from('user_counselors as csl')
                            ->join('accounts as act', 'csl.account_id', 'act.id')
                            ->where('csl.is_deleted', IsDeletedType::ACTIVE)
                            ->where('act.is_deleted', IsDeletedType::ACTIVE)
                            ->where('csl.user_id', $userId)
                            ->select(['csl.*', 'act.*'])
                            ->orderby('csl.account_id')
                            ->get();
            if(!empty($staffRows) && $staffRows->count() > 0){
                foreach($staffRows as $staffRow){
                    $userTaskStaffDto = new UserTaskStaffDto();
                    $userTaskStaffDto->accountId = $staffRow->account_id;
                    $userTaskStaffDto->staffType = UserTaskStaffType::USER_TASK_STAFF_COUNSELOR;
                    $userTaskStaffDto->staffName = $staffRow->last_name . ' ' . $staffRow->first_name;
                    $ret[] = $userTaskStaffDto;
                }
            }

        }elseif($staffType == UserTaskStaffType::USER_TASK_STAFF_OPERATOR){
            //オペレーターを取得
            $staffRows = UserTaskStaff::from('user_task_staffs as stf')
                            ->join('accounts as act', 'act.id', 'stf.account_id')
                            ->where('stf.is_deleted', IsDeletedType::ACTIVE)
                            ->where('act.is_deleted', IsDeletedType::ACTIVE)
                            ->where('stf.staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                            ->where('stf.user_task_id', $userTaskId)
                            ->select(['stf.*', 'act.*'])
                            ->orderby('stf.account_id')
                            ->get();
            if(!empty($staffRows) && $staffRows->count() > 0){
                foreach($staffRows as $staffRow){
                    $userTaskStaffDto = new UserTaskStaffDto();
                    $userTaskStaffDto->accountId = $staffRow->account_id;
                    $userTaskStaffDto->staffType = UserTaskStaffType::USER_TASK_STAFF_OPERATOR;
                    $userTaskStaffDto->staffName = $staffRow->last_name . ' ' . $staffRow->first_name;
                    $ret[] = $userTaskStaffDto;
                }
            }
        }
        return $ret;
    }

    /**
     * Task情報を取得
     * @param eloquant $userTask
     * @return TaskDto
     */
    public function getTaskInfo($userTask){
        $taskDto = new TaskDto();
        if (method_exists($userTask, 'task')) {
            $task = $userTask->task()->first();
        } else {
            $task = DB::table('tasks')->where('id', $userTask->task_id)->first();
        }

        $taskDto->taskId           = $task->id;
        $taskDto->userId           = $task->user_id;
        $taskDto->taskType         = $task->task_type;
        $taskDto->setTaskTypeName();
        $taskDto->candidateTaskId  = $task->cand_task_id;
        $taskDto->taskOrder        = $task->task_order;
        $taskDto->isSelected       = $task->is_selected;
        $taskDto->comment          = $task->comment;
        $taskDto->isDeleted        = $task->is_deleted;
        $taskDto->createdAt        = $task->created_at;
        $taskDto->updatedAt        = $task->updated_at;
        $taskDto->deletedAt        = $task->deleted_at;

        $taskDto->taskDetail       = $this->getTaskInfoDetail($task);
        return $taskDto;
    }

    /**
     * 宿題詳細を取得
     * @param eloquant $task
     * @return QuestionDto
     */
    public function getTaskInfoDetail($task){
        $taskType = $task->task_type;
        $candTaskId = $task->cand_task_id;
        $questionDto = $this->getTaskInfoDetailContents($taskType, $candTaskId);
        return $questionDto;
    }

    /**
     * 宿題の内容を取得
     *
     * @param type $taskType
     * @param type $candTaskId
     * @return QuestionDto
     */
    public function getTaskInfoDetailContents($taskType, $candTaskId){

        $questionDto = new QuestionDto();
        $questionDto->questionType = $taskType;

        if($taskType == QuestionType::QUESTION_TYPE_GRAMMAR){// 文法
            $question = Grammar::where('id', $candTaskId)
                                ->where('is_deleted', IsDeletedType::ACTIVE)
                                ->first();
            if($question->count() > 0){
                $questionDto->questionId    = $question->id;
                $questionDto->questionCode  = $question->grammar_code;
                $questionDto->module        = $question->module;
                $questionDto->grade         = $question->grade;
                $questionDto->stage         = $question->stage;
                $questionDto->seqNumber     = $question->seq_number;
                $questionDto->focus         = $question->focus;
                $questionDto->tips          = $question->tips;
                $questionDto->question      = $question->question;
                $questionDto->answer        = $question->answer;
                $questionDto->branch        = $question->branch;
            }

        }elseif($taskType == QuestionType::QUESTION_TYPE_VOCABULARY){// 語彙
            $question = Vocabulary::where('id', $candTaskId)
                                ->where('is_deleted', IsDeletedType::ACTIVE)
                                ->first();
            if($question->count() > 0){
                $questionDto->questionId    = $question->id;
                $questionDto->questionCode  = $question->vocabulary_code;
                $questionDto->module        = $question->module;
                $questionDto->grade         = $question->grade;
                $questionDto->stage         = $question->stage;
                $questionDto->seqNumber     = $question->seq_number;
                $questionDto->focus         = $question->focus;
                $questionDto->tips          = $question->tips;
                $questionDto->question      = $question->question;
                $questionDto->answer        = $question->answer;
                $questionDto->branch        = $question->branch;
            }

        }elseif($taskType == QuestionType::QUESTION_TYPE_SPEAKING_RALLY){// Speaking Rally
            $question = SpeakingRally::where('id', $candTaskId)
                                ->where('is_deleted', IsDeletedType::ACTIVE)
                                ->first();
            if($question->count() > 0){
                $questionDto->questionId        = $question->id;
                $questionDto->srTopicId         = $question->sr_topic_id;
                $questionDto->srCategoryId      = $question->sr_category_id;
                $questionDto->srCategory        = $question->category;
                $questionDto->srContext         = $question->context;
                $questionDto->srCreatedStuffId  = $question->created_stuff_id;
                $questionDto->srOpened          = $question->opened;
            }

        }elseif($taskType == QuestionType::QUESTION_TYPE_REVIEW){// 復習
            $question = Review::where('id', $candTaskId)
                                ->where('is_deleted', IsDeletedType::ACTIVE)
                                ->first();
            if($question->count() > 0){
                $questionDto->questionId    = $question->id;
                $questionDto->reviewName    = $question->review_name;
                $questionDto->reviewContext = $question->review_context;
            }

        }elseif($taskType > QuestionType::QUESTION_TYPE_OTHER){// その他
            $question = OtherTask::where('id', $candTaskId)
                                ->where('is_deleted', IsDeletedType::ACTIVE)
                                ->first();
            if($question->count() > 0){
                $questionDto->questionId        = $question->id;
                $questionDto->otherTaskName     = $question->other_task_name;
                $questionDto->otherTaskContext  = $question->task_context;
                $questionDto->otherTaskUrl      = $question->url;
                $questionDto->otherTaskPriority = $question->priority;
            }
        }
        return $questionDto;
    }

    /**
     * 2018-12-17 追加
     * 宿題回答およびFB情報取得
     * @param type $params
     */
    public function getTaskAnswerFb($params){
        $userId        = $params->get('userId');
        $taskType      = $params->get('taskType');
        $taskStatus    = $params->get('taskStatus');
        $minUserTaskId = $params->get('minUserTaskId');
        $maxUserTaskId = $params->get('maxUserTaskId');

        //宿題基本情報の取得
        //※user_tasksは複数レコードあるが、基本情報は先頭の1件だけでよい。
        $userTask = UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('id', $minUserTaskId)
                            ->where('user_id', $userId)
                            ->where('task_type', $taskType)
                            ->first();
        if(empty($userTask) || $userTask->count() < 1){
            Log::error('user_tasksレコードがありません: '
                            .' minUserTaskId: '.$minUserTaskId
                            .' userId: '.$userId
                            .' taskType: '.$taskType);
            return false;
        }

        $userTaskDto = new UserTaskDto();
        $userTaskDto->userTaskId    = $userTask->id;
        $userTaskDto->userId        = $userTask->user_id;
        $userTaskDto->taskId        = $userTask->task_id;

        $userTaskDto->userTaskType  = $userTask->task_type;
        $userTaskDto->setUserTaskTypeName();

        $userTaskDto->userTaskStatus = $userTask->user_task_status;
        $userTaskDto->setUserTaskStatusName();

        $userTaskDto->questionSetDate    = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->question_set_date));    //出題日
        $userTaskDto->answerDeadlineDate = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answer_deadline_date)); //回答期限日
        $userTaskDto->answeredDate       = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answered_date));        //回答日

        //オペレータ情報
        $userTaskDto->operatorInfo       = $this->getStaffInfo($userTaskDto->userTaskId, $userTaskDto->userId, UserTaskStaffType::USER_TASK_STAFF_OPERATOR);
        //ユーザ情報
        $userTaskDto->userInfo           = $this->getUserInfo($userTask);

        if($taskType == TaskType::TASK_GRAMMAR || $taskType == TaskType::TASK_VOCABULARY){
            //瞬間口頭英作G,V

            //設問情報の取得 ※複数レコードある
            $taskInfos = [];
            for($userTaskId = $minUserTaskId; $userTaskId <= $maxUserTaskId; $userTaskId++ ){
                $userTask = UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('id', $userTaskId)
                                    ->where('user_id', $userId)
                                    ->where('task_type', $taskType)
                                    ->first();
                if(empty($userTask) || $userTask->count() < 1){
                    continue;
                }
                //設問詳細情報の取得
                $taskInfos [] = $this->getTaskInfo($userTask);
            }//for
            //設問情報の集合をセット
            $userTaskDto->taskInfo = $taskInfos;

            //回答音声URL取得：「回答済み」以降の場合は音声ファイルがあるはずなので回答音声URLを取得する
            if($userTask->user_task_status >= UserTaskStatusType::TASK_STATUS_VAL_ANSWERED){
                    $ansSound = HomeworkAnswer::from('homework_answers as hans')
                                    ->join('sound_files as sndf', 'hans.sound_file_id', 'sndf.id')
                                    ->where('hans.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('sndf.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('hans.activity_id', $minUserTaskId)
                                    ->where('hans.user_id', $userId)
                                    ->where('hans.task_type', $taskType)
                                    ->select([
                                        'sndf.file_url',     //回答音声の S3 URL
                                        ])
                                    ->first();
                if(empty($ansSound) || $ansSound->count() < 1){
                    Log::error('GV: homework_answers / sound_filesレコードがありません: '
                                    .' minUserTaskId: '.$minUserTaskId
                                    .' userId: '.$userId
                                    .' taskType: '.$taskType);
                    $userTaskDto->originalAnswer = '';

                }else{
                    $userTaskDto->originalAnswer = !empty($ansSound->file_url)
                                                    ? $ansSound->file_url
                                                    : null;
                }
            }//endif:回答音声URL取得

            //GVフィードバック取得：「フィードバック済み」以降の場合はフィードバック情報を取得する
            if($userTask->user_task_status >= UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED){
                //フィードバック情報
                $userTaskDto->feedbackInfo = $this->getFeedbackInfo($userId, $minUserTaskId);
            }

        }elseif($taskType == TaskType::TASK_SPEAKING_RALLY){
            //SR

            //設問情報の取得 ※SRテーマを取得する
            $srTask = UserTask::from('user_tasks as usrtsk')
                            ->join('tasks as tsk', 'usrtsk.task_id', 'tsk.id')
                            ->join('speaking_rallies as srtsk', 'tsk.cand_task_id', 'srtsk.id')
                            ->where('usrtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('srtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('usrtsk.id', $minUserTaskId)
                            ->where('usrtsk.user_id', $userId)
                            ->where('usrtsk.task_type', $taskType)
                            ->select([
                                    'srtsk.sr_category_id',
                                    'srtsk.context',
                                ])
                            ->first();
            if(empty($srTask) || $srTask->count() < 1){
                Log::error('SR: tasks / speaking_ralliesレコードがありません: '
                                .' userTaskId: '.$minUserTaskId
                                .' userId: '.$userId
                                .' taskType: '.$taskType);
                return false;
            }
            $taskInfo = [
                'srThema' => $srTask->context
            ];
            $userTaskDto->taskInfo = $taskInfo;


            //回答音声URL取得：「回答済み」以降の場合は、生徒下書きおよび音声ファイルがあるはずなのでこれらを取得する
            if($userTask->user_task_status >= UserTaskStatusType::TASK_STATUS_VAL_ANSWERED){

                $ansSound = HomeworkAnswer::from('homework_answers as hans')
                                    ->join('sound_files as sndf', 'hans.sound_file_id', 'sndf.id')
                                    ->where('hans.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('sndf.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('hans.activity_id', $minUserTaskId)
                                    ->where('hans.user_id', $userId)
                                    ->where('hans.task_type', $taskType)
                                    ->select([
                                        'hans.answer',      //生徒下書き
                                        'sndf.file_url',    //回答音声の S3 URL
                                        ])
                                    ->first();
                if(empty($ansSound) || $ansSound->count() < 1){
                    Log::error('SR: homework_answers / sound_filesレコードがありません: '
                                    .' minUserTaskId: '.$minUserTaskId
                                    .' userId: '.$userId
                                    .' taskType: '.$taskType);

                    $userTaskDto->srDraft = '';  //SR 生徒下書き
                    $userTaskDto->originalAnswer = '';

                }else{
                    $userTaskDto->srDraft = $ansSound->answer;  //SR 生徒下書き
                    $userTaskDto->originalAnswer = !empty($ansSound->file_url)
                                                    ? $ansSound->file_url
                                                    : null;
                }
            }//endif:回答音声URL取得

            //SRフィードバック取得：「フィードバック済み」以降の場合はフィードバック情報を取得する
            if($userTask->user_task_status >= UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED){
                //フィードバック情報
                $userTaskDto->feedbackInfo = $this->getFeedbackInfo($userId, $minUserTaskId);
            }

        }//end if

         return $userTaskDto;
    }

    /**
     * フィードバック情報を取得
     * @param type $userId
     * @param type $userTaskId
     * @return boolean|FeedbackDto
     */
    public function getFeedbackInfo($userId, $userTaskId){

        $feedbackDto = new FeedbackDto();

        $userFeedback = UserFeedback::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->where('user_task_id', $userTaskId)
                            ->first();
        if(empty($userFeedback) || $userFeedback->count() < 1){
            return false;
        }

        //フィードバック者
        $feedbackAccountName = '';
        $feedbackAccount = $userFeedback->feedbackAccount()->first();
        if($feedbackAccount){
            $feedbackAccountName = $feedbackAccount->last_name . ' ' . $feedbackAccount->first_name
                                 . ' ('
                                 . $feedbackAccount->first_name_en . ' ' . $feedbackAccount->last_name_en
                                 . ')';
        }
        //承認者
        $approvedAccountName = '';
        $approvedAccount = $userFeedback->approvedAccount()->first();
        if($approvedAccount){
            $approvedAccountName = $approvedAccount->last_name . ' ' . $approvedAccount->first_name
                                 . ' ('
                                 . $approvedAccount->first_name_en . ' ' . $approvedAccount->last_name_en
                                 . ')';
        }

        $feedbackDto->feedbackId          = $userFeedback->id;
        $feedbackDto->userId              = $userFeedback->user_id;
        $feedbackDto->userTaskId          = $userFeedback->user_task_id;

        $feedbackDto->feedbackAccountId   = $userFeedback->feedback_account_id;
        $feedbackDto->feedbackAccountName = $feedbackAccountName;

        $feedbackDto->feedbackComment     = $userFeedback->feedback_comment;
        $feedbackDto->feedbackStatus      = $userFeedback->feedback_status;
        $feedbackDto->feedbackDate        = DateUtils::convertToYYMMDDHHMMSS($userFeedback->feedback_date);

        $feedbackDto->approvedAccountId   = $userFeedback->approved_account_id;
        $feedbackDto->approvedAccountName = $approvedAccountName;
        $feedbackDto->approvedDate        = DateUtils::convertToYYMMDDHHMMSS($userFeedback->approved_date);

        $feedbackDto->userComment         = $userFeedback->user_comment;

        return $feedbackDto;
    }

    /**
     *  会員名一覧を取得
     * @param array $loginAccountInfo
     * @return collection
     */
    public function getMemberList($loginAccountInfo){
        $targetAccountKindId = $loginAccountInfo['account_kind_id'];
        $targetAccountId = $loginAccountInfo['id'];

        if($targetAccountKindId == AccountKindType::ACCOUNT_COUNSELOR_ID){
            //カウンセラーログイン：担当する会員のみに限定
            $myQuery = User::from('users as usr')
                        ->join('user_counselors as csl', 'csl.user_id', 'usr.id')
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                        ->where('csl.is_deleted', IsDeletedType::ACTIVE)
                        ->where('csl.account_id', $targetAccountId)
                        ->select(['usr.id', 'usr.arugo_user_id', 'usr.first_name', 'usr.last_name'])
                        ->orderby('usr.arugo_user_id');

        }elseif($targetAccountKindId == AccountKindType::ACCOUNT_OPERATOR_ID){
            //オペレータログイン：担当する会員のみに限定
            $myQuery = User::from('users as usr')
                        ->join('user_task_staffs as stf', 'stf.user_id', 'usr.id')
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                        ->where('stf.is_deleted', IsDeletedType::ACTIVE)
                        ->where('stf.staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                        ->where('stf.account_id', $targetAccountId)
                        ->select(['usr.id', 'usr.arugo_user_id', 'usr.first_name', 'usr.last_name'])
                        ->orderby('usr.arugo_user_id');

        }else{
            //管理者、運用者：全員
            $myQuery = User::from('users as usr')
                        ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                        ->select(['usr.id', 'usr.arugo_user_id', 'usr.first_name', 'usr.last_name'])
                        ->orderby('usr.arugo_user_id');
        }
        $members = $myQuery->distinct()->get();

        $memberList = collect();

        $members->each(function ($item, $key) use(&$memberList){
            $memberName = $item->last_name . ' '. $item->first_name;
            $memberList->put(
                    $item->id,
                    [
                        'userId' => $item->id,
                        'alugoUserId' => $item->arugo_user_id,
                        'userName' => $memberName
                    ]);
        });

        return $memberList;
    }

    /**
     *  オペレータ名一覧を取得
     * @param array $loginAccountInfo
     * @return collection
     */
    public function getOperatorList($loginAccountInfo){
        $targetAccountKindId = $loginAccountInfo['account_kind_id'];
        $targetAccountId = $loginAccountInfo['id'];

        $myQuery = Account::select([
                            'id',
                            'first_name', 'last_name',
                            'first_name_en', 'last_name_en'])
                    ->where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('account_kind_id', AccountKindType::ACCOUNT_OPERATOR_ID)
                    ->orderby('id');

        if($targetAccountKindId == AccountKindType::ACCOUNT_OPERATOR_ID){
            //オペレータログイン：該当するオペレータのみに限定
            $myQuery = $myQuery
                        ->where('id', $targetAccountId);
        }

        $operators = $myQuery->get();

        $operatorList = collect();

        $operators->each(function ($item) use(&$operatorList){
            $operatorName = $item->last_name . ' ' . $item->first_name
                          . ' (' . $item->first_name_en . ' ' .$item->last_name_en . ')';
            $operatorList->put($item->id, $operatorName);
        });

        return $operatorList;
    }

    /**
     * ステータス更新
     * @param collection $params
     */
    public function doChangeStatus($params){
        $userId = $params->get('userId');
        $userTaskId = $params->get('userTaskId');
        $selectedStatusId = empty($params->get('selectedStatusId')) ? 0 : $params->get('selectedStatusId');

        $ret = false;
        DB::transaction(function () use($userId, $userTaskId, $selectedStatusId, &$ret){

            /*
             * 「ユーザ回答差し戻し」を行った際は、
             * すでに紐付けられている回答を「差し戻し」とする。
             */
            if($selectedStatusId == '3'){//3: ユーザ回答差し戻し

                //user_tasksのステータスを更新
                $ret = UserTask::where('user_id', $userId) //同一のユーザIDを持つものは全て更新
                        ->update([
                            'user_task_status' => $selectedStatusId,
                            'updated_at' => DateUtils::getCurrentStr()
                        ]);
                if($ret < 1){
                    return false;
                }

                //user_message_logsの該当レコードを特定
                $userMessageLogId = UserTask::where('id', $userTaskId)
                                    ->value('user_message_log_id');
                //user_message_logsの該当レコードを「差し戻し」に更新
                $ret = UserMessageLog::where('id', $userMessageLogId)
                                        ->update([
                                            //'user_task_linked' => UserMessageLogLinkedType::REJECT,
                                            'user_task_linked' => UserMessageLogLinkedType::NOT_LINKED,
                                            'updated_at' => DateUtils::getCurrentStr()
                                        ]);
                if($ret < 1){
                    return false;
                }

            }else{

                //user_tasksのステータスを更新
                $ret = UserTask::where('id', $userTaskId)
                        ->update([
                            'user_task_status' => $selectedStatusId,
                            'updated_at' => DateUtils::getCurrentStr()
                        ]);
                if($ret < 1){
                    return false;
                }
            }
        });

        return true;
    }

    /**
     * オペレータアサイン
     * @param collection $params
     */
    public function doAssignOperator($params){
        $minUserTaskId = $params->get('minUserTaskId');
        $maxUserTaskId = $params->get('maxUserTaskId');
        $userId = $params->get('userId');
        $selectedOperatorId = empty($params->get('selectedOperatorId')) ? 0 : $params->get('selectedOperatorId');

        if(!is_numeric($minUserTaskId) || !is_numeric($maxUserTaskId)){
            Log::info(__CLASS__.__METHOD__.'UserTaskId is not numeric');
            return false;
        }
        if( $minUserTaskId > $maxUserTaskId ){
            Log::info(__CLASS__.__METHOD__.'UserTaskId is illegal');
            return false;
        }

        for($userTaskId = $minUserTaskId; $userTaskId <= $maxUserTaskId; $userTaskId++ ){

            $isExist = UserTaskStaff::where('user_task_id', $userTaskId)
                                ->where('user_id', $userId)
                                ->where('staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                                ->exists();
            if($isExist){
                if($selectedOperatorId == 0){
                    UserTaskStaff::where('user_task_id', $userTaskId)
                            ->where('user_id', $userId)
                            ->where('staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                            ->delete();
                }else{
                    UserTaskStaff::where('user_task_id', $userTaskId)
                            ->where('user_id', $userId)
                            ->where('staff_type', UserTaskStaffType::USER_TASK_STAFF_OPERATOR)
                            ->update([
                                'account_id'=>$selectedOperatorId,
                                'updated_at'=>DateUtils::getCurrentStr()
                            ]);
                }
            }else{
                if($selectedOperatorId != 0){
                    UserTaskStaff::insert([
                                'user_task_id'=>$userTaskId,
                                'staff_type'=>UserTaskStaffType::USER_TASK_STAFF_OPERATOR,
                                'account_id'=>$selectedOperatorId,
                                'user_id'=>$userId,
                                'created_at'=>DateUtils::getCurrentStr()
                            ]);
                }
            }
        }//for

    }

    /**
     * 2018-12-23
     * フィードバック登録
     * @param collection $params
     */
    public function doRegistFeedback($params){
        $loginAccountId = $params->get('loginAccountId');
        $userId = $params->get('userId');
        $userTaskId = $params->get('userTaskId');
        $feedbackText = $params->get('feedbackText');

        DB::transaction(function () use($loginAccountId, $userId, $userTaskId, $feedbackText){
            /*
             * フィードバック済みのレコードがあれば更新、無ければ新規作成
             */
            $userFeedback = UserFeedback::firstOrNew([
                                'user_id' => $userId,
                                'user_task_id' => $userTaskId,
                                'is_deleted' => IsDeletedType::ACTIVE,
                            ]);

            $userFeedback->user_id              = $userId;
            $userFeedback->user_task_id         = $userTaskId;
            $userFeedback->feedback_account_id  = $loginAccountId;
            $userFeedback->feedback_comment     = $feedbackText;
            $userFeedback->feedback_status      = UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED;
            $userFeedback->feedback_date        = Carbon::now();
            $userFeedback->approved_account_id  = null;
            $userFeedback->approved_date        = null;
            $userFeedback->user_comment         = null;
            $userFeedback->is_deleted           = IsDeletedType::ACTIVE;
            $userFeedback->deleted_at           = null;

            if ( ! $userFeedback->id) {
                //新規
                $userFeedback->created_at = Carbon::now();
                $userFeedback->updated_at = null;

            }else{
                //更新
                $userFeedback->updated_at = Carbon::now();
            }

            $userFeedback->save();
            $userFeedbackId = $userFeedback->id;

            /*
             * user_tasksを更新
             */
            $this->updateUserTaskOnSameGroup(
                            $userId,
                            $userTaskId,
                            UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED,
                            $userFeedbackId);
        });
    }
    /**
     * 2018-12-23
     * フィードバック承認
     * @param collection $params
     */
    public function doApproveFeedback($params){
        $loginAccountId = $params->get('loginAccountId');
        $userId = $params->get('userId');
        $userTaskId = $params->get('userTaskId');

        DB::transaction(function () use($loginAccountId, $userId, $userTaskId){

            /*
             * user_feedbacksを更新
             */
            UserFeedback::where('is_deleted', IsDeletedType::ACTIVE)
                        ->where('user_id', $userId)
                        ->where('user_task_id', $userTaskId)
                        ->update([
                            'feedback_status' => UserTaskStatusType::TASK_STATUS_VAL_RESPONSED, // 7: フィードバック完了
                            'approved_account_id' => $loginAccountId,
                            'approved_date' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
            /*
             * user_tasksを更新
             */
            $this->updateUserTaskOnSameGroup(
                            $userId,
                            $userTaskId,
                            UserTaskStatusType::TASK_STATUS_VAL_RESPONSED,// 7: フィードバック完了
                            null);
        });
    }

    /**
     * 2018-12-23
     * フィードバック差し戻し
     * @param collection $params
     */
    public function doRejectFeedback($params){
        $loginAccountId = $params->get('loginAccountId');
        $userId = $params->get('userId');
        $userTaskId = $params->get('userTaskId');

        DB::transaction(function () use($loginAccountId, $userId, $userTaskId){

            /*
             * user_feedbacksを更新
             */
            UserFeedback::where('is_deleted', IsDeletedType::ACTIVE)
                        ->where('user_id', $userId)
                        ->where('user_task_id', $userTaskId)
                        ->update([
                            'feedback_status' => UserTaskStatusType::TASK_STATUS_VAL_FEEDBACK_REJECT,   // 6: フィードバック差し戻し
                            'approved_account_id' => $loginAccountId,
                            'approved_date' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

            /*
             * user_tasksを更新
             */
            $this->updateUserTaskOnSameGroup(
                            $userId,
                            $userTaskId,
                            UserTaskStatusType::TASK_STATUS_VAL_FEEDBACK_REJECT,// 6: フィードバック差し戻し
                            null);
        });
    }

    private function updateUserTaskOnSameGroup($userId, $userTaskId, $userTaskStatus, $userFeedbackId=null){
        /*
         * user_tasksを更新
         * ①user_task_status
         * ②user_feedback_id
         */
        //該当するuser_tasksを特定する
        $targetUserTaskIds = [];
        $userTasks = UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                        ->where('id', '>=', $userTaskId)
                        ->where('user_id', $userId)
                        ->orderby('id')
                        ->get();
        //同じ日、同じ枠に複数出題がある場合に対応（瞬間口頭英作など）
        $key = '';
        $keySave = '';
        if(!empty($userTasks) && $userTasks->count() > 0){
            foreach($userTasks as $userTask){
                $key = $userTask->task_type  . '-' .
                       $userTask->day_number . '-' .
                       $userTask->task_period_order;
                if(empty($keySave)){
                    $keySave = $key;
                    $targetUserTaskIds[] = $userTask->id;

                }elseif($keySave != $key){
                    break;
                }else{
                    $targetUserTaskIds[] = $userTask->id;
                }
            }
        }

        if(count($targetUserTaskIds) > 0){
            if($userTaskStatus === UserTaskStatusType::TASK_STATUS_VAL_FEEDBACKED){
                //フィードバック登録
                UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                        ->whereIn('id', $targetUserTaskIds)
                        ->update([
                            'user_task_status' => $userTaskStatus,
                            'user_feedback_id' => $userFeedbackId,
                            'updated_at' => Carbon::now()
                        ]);

            }else{
                //フィードバック差し戻し、完了
                UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                        ->whereIn('id', $targetUserTaskIds)
                        ->update([
                            'user_task_status' => $userTaskStatus,
                            'updated_at' => Carbon::now()
                        ]);
            }
        }
    }





    /**
     * 回答紐付け登録
     * @param collection $params
     */
    public function doRegistAnswerLink($params){
        $userId = $params->get('userId');
        $userTaskId = $params->get('userTaskId');
        $userTaskQuestionSetDate = $params->get('userTaskQuestionSetDate');
        $userMessageLogId = $params->get('userMessageLogId');
        $postedContext = $params->get('postedContext');

        DB::transaction(function () use($userId, $userTaskId, $userTaskQuestionSetDate, $userMessageLogId, $postedContext){
            /*
             * user_tasksを更新
             */
            UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId) //同一のユーザIDを持つものに全て紐付ける
                    ->whereDate('question_set_date', $userTaskQuestionSetDate)//同一の出題日を持つものに全て紐付ける
                    ->update([
                        'user_task_status' => UserTaskStatusType::TASK_STATUS_VAL_ANSWERED,//回答済み
                        'answered_date' => DateUtils::getCurrentStr(),
                        'user_message_log_id' => $userMessageLogId,
                        'original_answer' => $postedContext,
                        'updated_at' => DateUtils::getCurrentStr()
                    ]);

            /*
             * user_message_logsの該当レコードを紐付け済みに更新
             */
            UserMessageLog::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('id', $userMessageLogId)
                    ->update([
                        'user_task_linked' => UserMessageLogLinkedType::LINKED,
                        'updated_at' => DateUtils::getCurrentStr()
                    ]);
        });
    }

    /**
     * 回答紐付け解除
     * @param collection $params
     */
    public function doReleaseAnswerLink($params){
        $userId = $params->get('userId');
        $userTaskId = $params->get('userTaskId');
        $userTaskQuestionSetDate = $params->get('userTaskQuestionSetDate');
        $userMessageLogId = $params->get('userMessageLogId');

        DB::transaction(function () use($userId, $userTaskId, $userTaskQuestionSetDate, $userMessageLogId){
            /*
             * user_tasksを更新
             */
            UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_id', $userId) //同一のユーザIDを持つものを全て紐付け解除する
                    ->whereDate('question_set_date', $userTaskQuestionSetDate)//同一の出題日を持つものを全て紐付け解除する
                    ->update([
                        'user_task_status' => UserTaskStatusType::TASK_STATUS_VAL_ASKED,//出題済み
                        'answered_date' => null,
                        'user_message_log_id' => null,
                        'original_answer' => null,
                        'updated_at' => DateUtils::getCurrentStr()
                    ]);

            /*
             * user_message_logsの該当レコードを紐付け未済に更新
             */
            UserMessageLog::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('id', $userMessageLogId)
                    ->update([
                        'user_task_linked' => UserMessageLogLinkedType::NOT_LINKED,
                        'updated_at' => DateUtils::getCurrentStr()
                    ]);
        });
    }

    /**
     * 回答一覧取得
     */
    public function getAnswerList(){
        $answerList = [];
        $userMessageLogs = UserMessageLog::select('*')
                ->where('is_deleted', IsDeletedType::ACTIVE)
                ->where('message_type', UserMessageLogMessageType::CHATWORK)
                ->where('cw_post_type', UserMessageLogCwPostType::FILE)//音声ファイル
                ->where('connect_type', UserMessageLogConnectType::RECEIVE)//受信
                ->where('user_task_linked', UserMessageLogLinkedType::NOT_LINKED)
                ->orderby('posted_at', 'desc')
                ->get();

        if(count($userMessageLogs) > 0){
            foreach($userMessageLogs as $userMessageLog){

                $user = $userMessageLog->user()->first();
                $userName = '';
                if(count($user) > 0){
                    $userName = $user->last_name . ' ' . $user->first_name;
                }

                $userMessageLogDto = new UserMessageLogDto();
                $userMessageLogDto->userMessageLogId  = $userMessageLog->id;
                $userMessageLogDto->userId            = $userMessageLog->user_id;
                $userMessageLogDto->userName          = $userName;
                $userMessageLogDto->messageType       = $userMessageLog->message_type;
                $userMessageLogDto->cwRoomId          = $userMessageLog->cw_room_id;
                $userMessageLogDto->cwRoomName        = $userMessageLog->cw_room_name;
                $userMessageLogDto->cwPostType        = $userMessageLog->cw_post_type;
                $userMessageLogDto->connectType       = $userMessageLog->connect_type;
                $userMessageLogDto->postedName        = $userMessageLog->posted_name;
                $userMessageLogDto->postedAt          = DateUtils::convertToYYMMDD($userMessageLog->posted_at);
                $userMessageLogDto->deadline          = DateUtils::convertToYYMMDDHHMMSS($userMessageLog->deadline);
                $userMessageLogDto->topicCategory     = $userMessageLog->topic_category;
                $userMessageLogDto->topicId           = $userMessageLog->topic_id;
                $userMessageLogDto->topicStuffId      = $userMessageLog->topic_stuff_id;
                $userMessageLogDto->postedContext     = $userMessageLog->posted_context;
                $userMessageLogDto->userTaskLinked    = $userMessageLog->user_task_linked;
                $userMessageLogDto->comment           = $userMessageLog->comment;
                $userMessageLogDto->isDeleted         = $userMessageLog->is_deleted;
                $userMessageLogDto->createdAt         = $userMessageLog->created_at;
                $userMessageLogDto->updatedAt         = $userMessageLog->updated_at;
                $userMessageLogDto->deletedAt         = $userMessageLog->deleted_at;
                $answerList[] = $userMessageLogDto;
            }
        }
        return $answerList;
    }

    /**
     * @deprecated
     * Chatwork一括投稿データ取得
     * @param string $params
     * @return array
     */
    public function getChatworkContributeData($params){

        if(strlen($params) < 1){
            return [];
        }
        $userTaskIds = explode(',', $params);
        if(empty($userTaskIds) || count($userTaskIds) < 1){
            return [];
        }

        $myQuery = UserTask::from('user_tasks as usrtsk')
                    ->join('users as usr', 'usr.id', 'usrtsk.user_id')
                    ->select(['usrtsk.id as userTaskId', 'usrtsk.*', 'usr.id as userId', 'usr.*'])
                    ->where('usrtsk.is_deleted', IsDeletedType::ACTIVE)
                    ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                    ->whereIn('usrtsk.id', $userTaskIds);

        //検索実行
        $userTasks = $myQuery->get();

        $taskList = [];
        if(count($userTasks) > 0){
            foreach($userTasks as $userTask){
                $userTaskDto = new UserTaskDto();

                $userTaskDto->userTaskId          = $userTask->userTaskId;
                $userTaskDto->userId              = $userTask->user_id;
                $userTaskDto->taskId              = $userTask->task_id;
                $userTaskDto->userTaskStatus      = $userTask->user_task_status;
                $userTaskDto->setUserTaskStatusName();

                $userTaskDto->questionSetDate     = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->question_set_date));
                $userTaskDto->answerDeadlineDate  = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answer_deadline_date));
                $userTaskDto->answeredDate        = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answered_date));

                //回答レコードid
                $userTaskDto->userMessageLogId    = $userTask->user_message_log_id;

                //カウンセラー情報
                $userTaskDto->counselorInfo = $this->getStaffInfo($userTask, UserTaskStaffType::USER_TASK_STAFF_COUNSELOR);
                //オペレータ情報
                $userTaskDto->operatorInfo  = $this->getStaffInfo($userTask, UserTaskStaffType::USER_TASK_STAFF_OPERATOR);

                $userTaskDto->originalAnswer      = $userTask->original_answer;
                $userTaskDto->userFeedbackId      = $userTask->user_feedback_id;
                $userTaskDto->comment             = $userTask->comment;
                $userTaskDto->isDeleted           = $userTask->is_deleted;
                $userTaskDto->createdAt           = $userTask->created_at;
                $userTaskDto->updatedAt           = $userTask->updated_at;
                $userTaskDto->deletedAt           = $userTask->deleted_at;
                //ユーザ情報
                $userTaskDto->userInfo            = $this->getUserInfo($userTask);
                //出題情報
                $userTaskDto->taskInfo            = $this->getTaskInfo($userTask);
                //フィードバック情報
                $userTaskDto->feedbackInfo        = $this->getFeedbackInfo($userTask);

                $taskList[] = $userTaskDto;
            }
        }
        return $taskList;
    }

    /**
     * @deprecated
     * Chatwork一括投稿実行
     * @param string $params
     * @return boolean
     */
    public function doChatworkContribute($loginAccountId, $contents) {

        if(count($contents) < 1){
            return true;
        }

        foreach($contents as $content){
            $userId = $content['userId'];
            $userTaskId = $content['userTaskId'];
            $feedbackText = $content['feedbackText'];

            /*
             * まずfeedback情報を更新しておく
             */
            $params = collect();
            $params->put('loginAccountId', $loginAccountId);
            $params->put('userId',         $userId);
            $params->put('userTaskId',     $userTaskId);
            $params->put('feedbackText',   $feedbackText);
            $this->doRegistFeedback($params);

            /*
             * 次にchatwork投稿を行う
             */
            //対象となるUserTask情報を取得する
            $userTask = UserTask::from('user_tasks as usrtsk')
                ->join('users as usr', 'usr.id', 'usrtsk.user_id')
                ->select(['usrtsk.id as userTaskId', 'usrtsk.*', 'usr.id as userId', 'usr.*'])
                ->where('usrtsk.is_deleted', IsDeletedType::ACTIVE)
                ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                ->where('usrtsk.id', $userTaskId)
                ->first();

            $userTaskDto = new UserTaskDto();
            $userTaskDto->userTaskId          = $userTask->userTaskId;
            $userTaskDto->userId              = $userTask->user_id;
            $userTaskDto->taskId              = $userTask->task_id;
            $userTaskDto->userTaskStatus      = $userTask->user_task_status;
            $userTaskDto->setUserTaskStatusName();

            $userTaskDto->questionSetDate     = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->question_set_date));
            $userTaskDto->answerDeadlineDate  = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answer_deadline_date));
            $userTaskDto->answeredDate        = DateUtils::convertToYYMMDD(DateUtils::shortenToYYMMDD($userTask->answered_date));

            //回答レコードid
            $userTaskDto->userMessageLogId    = $userTask->user_message_log_id;

            $userTaskDto->originalAnswer      = $userTask->original_answer;
            $userTaskDto->userFeedbackId      = $userTask->user_feedback_id;
            $userTaskDto->comment             = $userTask->comment;

            //ユーザ情報
            $userTaskDto->userInfo            = $this->getUserInfo($userTask);
            //出題情報
            $userTaskDto->taskInfo            = $this->getTaskInfo($userTask);
            //フィードバック情報
            $userTaskDto->feedbackInfo        = $this->getFeedbackInfo($userTask);

            /*
             * Chatwork投稿
             */
            $p = [];
            $p['roomid']  = $userTaskDto->userInfo->cwRoomId;
            $p['title']   = '宿題へのフィードバック';
            $p['message'] = $userTaskDto->feedbackInfo->feedbackComment;
            ChatworkService::sendChatworkMessage($p);

            //投稿完了を更新する
            $this->finishChatworkContibute($loginAccountId, $userTaskDto);
        }//foreach

    }

    /**
     * @deprecated
     * 投稿完了を更新する
     * @param UserTaskDto $userTaskDto
     */
    private function finishChatworkContibute($loginAccountId, $userTaskDto) {

        DB::transaction(function () use($loginAccountId, $userTaskDto){
            /*
             * user_tasksを更新
             */
            UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('id', $userTaskDto->userTaskId)
                    ->update([
                        'user_task_status' => UserTaskStatusType::TASK_STATUS_VAL_RESPONSED,//投稿済み
                        'updated_at' => DateUtils::getCurrentStr()
                    ]);

            /*
             * user_feedbacksを更新
             */
            UserFeedback::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('id', $userTaskDto->userFeedbackId)
                    ->update([
                        'approved_account_id' => $loginAccountId,
                        'approved_date' => DateUtils::getCurrentStr(),
                        'updated_at' => DateUtils::getCurrentStr()
                    ]);
        });

    }

    /**
     * オペレータが未設定のuser_tasksを抽出する
     *
     * @param type $maxRecordNum 取得最大件数
     * @return UserTaskDto
     */
    public function getNoAssignOperatorTask($maxRecordNum=0){
        /***
            $mySql = "select tb1.*, `tsk`.`task_type`, `tsk`.`cand_task_id`, `tsk`.`task_order`"
                    . " from  "
                    . " ( "
                    . " select  "
                    . " `usrtsk`.`id` as userTaskId, "
                    . " `usrtsk`.`user_id` as userTaskUserId, "
                    . " `usrtsk`.`task_id` as userTaskTaskId, "
                    . " `usrtsk`.`user_task_status`, "
                    . " `usrtsk`.`question_set_date`, "
                    . " `usrtsk`.`answer_deadline_date`, "
                    . " `usrtsk`.`answered_date`, "
                    . " `usrtsk`.`user_message_log_id`, "
                    . " `usrtsk`.`original_answer`, "
                    . " `usrtsk`.`user_feedback_id`, "
                    . " `usrtsk`.`comment`, "
                    . " `usrtsk`.`updated_at` as userTaskUpdatedAt, "
                    . " `stf`.id as staffId, "
                    . " `stf`.account_id as staffAccountId "
                    . " from `user_tasks` as `usrtsk`  "
                    . " left join `user_task_staffs` as `stf` on `stf`.`user_task_id` = `usrtsk`.`id` "
                    . " where `usrtsk`.`is_deleted` = 0 "
                    . " and `usrtsk`.`user_task_status` > 0 "
                    . " ) tb1 "
                    . " inner join `tasks` as `tsk` on `tsk`.`id` = `tb1`.`userTaskTaskId` "
                    . " where `tsk`.`task_type` in (1, 2, 3) "
                    . " and (tb1.staffId IS NULL OR tb1.staffAccountId = 0) "
                    . " order by `tb1`.`userTaskUserId` desc ";

            if(!empty($maxRecordNum)){
                $mySql .= " limit " . $maxRecordNum . " offset 0 ";
            }
            $userTasks = DB::select(DB::raw($mySql));
        ******/

            $userTasks = UserTask::from('user_tasks as usrtsk')
                            ->leftjoin('user_task_staffs as stf', 'stf.user_task_id', 'usrtsk.id')
                            ->where('usrtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('usrtsk.user_task_status', '>=', UserTaskStatusType::TASK_STATUS_VAL_ASKED)
                            ->whereIn('usrtsk.task_type', [1,2,3])
                            ->where(function($q){
                                $q->whereNull('stf.id')
                                  ->orWhere('stf.account_id', 0);
                            })
                            ->select([
                                'usrtsk.id as userTaskId',
                                'usrtsk.user_id as userTaskUserId',
                                'usrtsk.task_id as userTaskTaskId',
                                'usrtsk.task_type',
                                'usrtsk.user_task_status',
                                'usrtsk.question_set_date',
                                'usrtsk.answer_deadline_date',
                                'usrtsk.answered_date',
                                'stf.id as staffId',
                                'stf.account_id as staffAccountId'
                            ])
                            ->limit(10, 0)
                            ->get();

            $taskList = [];
            foreach($userTasks as $userTask){
                $userTaskDto = new UserTaskDto();
                $userTaskDto->userTaskId         = $userTask->userTaskId;
                $userTaskDto->userId             = $userTask->userTaskUserId;
                $userTaskDto->taskId             = $userTask->userTaskTaskId;
                $userTaskDto->userTaskStatus     = $userTask->user_task_status;
                $userTaskDto->questionSetDate    = $userTask->question_set_date;
                $userTaskDto->answerDeadlineDate = $userTask->answer_deadline_date;
                $userTaskDto->setUserTaskStatusName();

                $userDto= new UserDto();
                $user = User::find($userTask->userTaskUserId);
                if(!empty($user) && $user->count() > 0){
                    $userDto->userId      = $user->id;
                    $userDto->arugoUserId = $user->arugo_user_id;
                    $userDto->srUserId    = $user->sr_user_id;
                    $userDto->cwRoomId    = $user->cw_room_id;
                    $userDto->firstName   = $user->first_name;
                    $userDto->lastName    = $user->last_name;
                    $userDto->firstNameEn = $user->first_name_en;
                    $userDto->lastNameEn  = $user->last_name_en;
                    $userDto->mail        = $user->mail;
                    $userTaskDto->userInfo = $userDto;//当該ユーザ情報

                    $taskDto = new TaskDto();
                    $taskDto->userId           = $userTask->userTaskUserId;
                    $taskDto->taskId           = $userTask->userTaskTaskId;
                    $taskDto->taskType         = $userTask->task_type;
                    //$taskDto->candidateTaskId  = $userTask->cand_task_id;
                    //$taskDto->taskOrder        = $userTask->task_order;
                    $taskDto->setTaskTypeName();
                    $userTaskDto->taskInfo     = $taskDto;  //= [];//TaskService::getTaskContents($userTask->task_type, $userTask->cand_task_id);
                    $taskList[] = $userTaskDto;
                }
            }

        return $taskList;
    }
    /**
     * Mapping Type function
     *
     * @return void
     */
    public static function getMappingTaskType()
    {
        $aryMapping = [
            QuestionType::QUESTION_TYPE_GRAMMAR              => QuestionType::QUESTION_TYPE_GRAMMAR_NAME,
            QuestionType::QUESTION_TYPE_VOCABULARY           => QuestionType::QUESTION_TYPE_VOCABULARY_NAME,
            QuestionType::QUESTION_TYPE_WORD_CARD            => QuestionType::QUESTION_TYPE_WORD_CARD_NAME,
            QuestionType::QUESTION_TYPE_LISTENING_B          => QuestionType::QUESTION_TYPE_LISTENING_B_NAME,
            QuestionType::QUESTION_TYPE_LISTENING_C          => QuestionType::QUESTION_TYPE_LISTENING_C_NAME,
            QuestionType::QUESTION_TYPE_LISTENING_D          => QuestionType::QUESTION_TYPE_LISTENING_D_NAME,
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY       => QuestionType::QUESTION_TYPE_SPEAKING_RALLY_NAME,
            QuestionType::QUESTION_TYPE_RV_LESSON            => QuestionType::QUESTION_TYPE_RV_LESSON_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON => QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_LESSON        => QuestionType::QUESTION_TYPE_REVIEW_LESSON_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR     => QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR_NAME,
            QuestionType::QUESTION_TYPE_REVIEW_SERVICE       => QuestionType::QUESTION_TYPE_REVIEW_SERVICE_NAME,
        ];

        return $aryMapping;
    }

    /**
     * Mapping Type function
     *
     * @return void
     */
    public static function getIconMappingTaskType()
    {
        $aryMapping = [
            QuestionType::QUESTION_TYPE_GRAMMAR              => QuestionType::QUESTION_TYPE_GRAMMAR_ICON,
            QuestionType::QUESTION_TYPE_VOCABULARY           => QuestionType::QUESTION_TYPE_VOCABULARY_ICON,
            QuestionType::QUESTION_TYPE_WORD_CARD            => QuestionType::QUESTION_TYPE_WORD_CARD_ICON,
            QuestionType::QUESTION_TYPE_LISTENING_B          => QuestionType::QUESTION_TYPE_OTHER_05_ICON,
            QuestionType::QUESTION_TYPE_LISTENING_C          => QuestionType::QUESTION_TYPE_OTHER_06_ICON,
            QuestionType::QUESTION_TYPE_LISTENING_D          => QuestionType::QUESTION_TYPE_OTHER_07_ICON,
            QuestionType::QUESTION_TYPE_SPEAKING_RALLY       => QuestionType::QUESTION_TYPE_SPEAKING_RALLY_ICON,
            QuestionType::QUESTION_TYPE_RV_LESSON            => QuestionType::QUESTION_TYPE_RV_LESSON_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON => QuestionType::QUESTION_TYPE_REVIEW_BEFORE_LESSON_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_LESSON        => QuestionType::QUESTION_TYPE_REVIEW_LESSON_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR     => QuestionType::QUESTION_TYPE_REVIEW_COUNSELOR_ICON,
            QuestionType::QUESTION_TYPE_REVIEW_SERVICE       => QuestionType::QUESTION_TYPE_REVIEW_SERVICE_ICON,
        ];

        return $aryMapping;
    }

    /**
     * Get Task name by type
     *
     * @param  integer $taskType Task type
     * @return string
     */
    public static function getTaskTypeName($taskType)
    {
        $aryMapping = self::getMappingTaskType();

        if (isset($aryMapping[$taskType])) {
            return $aryMapping[$taskType];
        }

        return '';
    }

    /**
     * Get Task name by type
     *
     * @param  integer $taskType Task type
     * @return string
     */
    public static function getTaskTypeIcon($taskType)
    {
        $aryMapping = self::getIconMappingTaskType();

        if (isset($aryMapping[$taskType])) {
            return $aryMapping[$taskType];
        }

        return '';
    }

    /**
     * GetAllTask function
     *
     * @return json $result
     */
    public static function getAllTask()
    {
        //get user logined
        $user = Auth::user();

        //Get data review by user
        $reviewServices = DB::table('review_services')
            ->where(
                [
                    ['user_id',$user->id],
                    ['alugo_user_id',$user->arugo_user_id],
                ]
            )->count();

        $reviewLessons = DB::table('review_lessons')
            ->where(
                [
                    ['user_id', $user->id],
                    ['alugo_user_id', $user->arugo_user_id]
                ]
            )
            ->pluck('lesson_id', 'lesson_id');

        $reviewConseling = DB::table('review_counselors')
            ->where(
                [
                    ['user_id', $user->id],
                    ['alugo_user_id', $user->arugo_user_id]
                ]
            )
            ->pluck('counselor_id', 'counselor_id');


        //Make time get data
        //get time range lesson reserve
        $start_at = $user->opened_at;
        $end_at = $user->closed_at;

        $weekMax = self::getWeekNo($end_at, $start_at);
        $weekCurrent = self::getWeekNo(date('Y-m-d'), $start_at);

        $taskRVLesson = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->join('rv_tasks as rvtsk', 'tsk.cand_task_id', 'rvtsk.id')
                            ->where('utsk.question_set_date', '>=', $start_at)
                            ->where('utsk.answer_deadline_date', '<=', $end_at)
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('rvtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.task_type', TaskType::TASK_TYPE_RV_LESSON)
                            ->where('utsk.user_id', $user->id)
                            ->get();
        $aryLessonExclude = [];

        $aryTaskAll = [];

        if ($taskRVLesson) {
            foreach ($taskRVLesson as $key => $t) {
                $aryLessonExclude[] = $t->activity_id;

                $week = self::getWeekNo($t->question_set_date, $start_at);

                $favorite_id = HomeworkService::getFavorite($user, $t->activity_id, CategoryType::HOMEWORK);

                $aryOutput = self::_makeAryOutputGetAllTask(
                    $t->task_id,
                    $t->question_set_date,
                    $t->question_set_date,
                    $t->answer_deadline_date,
                    $t->is_completed,
                    $t->task_type,
                    self::getTaskTypeName($t->task_type),
                    $favorite_id
                );

                $aryOutput['lesson_id'] = $t->activity_id;

                if ($reviewLessons != null && in_array($t->activity_id, $reviewLessons->toArray())) {
                    $aryOutput['is_review'] = IsReviewType::REVIEW;
                }

                $aryTaskAll[$week][] = $aryOutput;
            }
        }

        //get all lesson olf of user
        $lesson = Lesson::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['lesson_type', '=', LessonType::LESSON],
                ['start_at', '>=', $start_at],
                ['end_at', '<', $end_at],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )->whereNotIn('id', $aryLessonExclude)->orderBy('start_at', 'ASC')->get();

        //get all lesson reserve
        $assement = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['start_at', '>=', $start_at],
                ['end_at', '<', $end_at],
                ['deleted', '=', IsDeletedType::ACTIVE],
                ['lesson_type', '=', LessonType::ASSESSMENT],
            ]
        )
        ->whereNotNull('assessment_id')
        ->orderBy('start_at', 'ASC')->get();


        $reserve = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['lesson_type', '=', LessonType::LESSON],
                ['start_at', '>=', $start_at],
                ['end_at', '<', $end_at],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )->whereNull('lesson_id')->orderBy('start_at', 'ASC')->get();

        $aryMappingTaskType = self::getMappingTaskType();
        //get all task
        $task = DB::table('user_tasks')
                ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
                ->whereIn('tasks.task_type', array_keys($aryMappingTaskType))
                ->where('user_tasks.user_id',  $user->id)
                ->where('user_tasks.question_set_date', '>=', $start_at)
                ->where('user_tasks.answer_deadline_date', '<=', $end_at)
                ->where('user_tasks.question_set_date', '<=', date('Y-m-d 23:59:59'))
                ->where('user_tasks.is_deleted', IsDeletedType::ACTIVE)
                ->where('tasks.task_type', '<>', TaskType::TASK_TYPE_RV_LESSON)
                ->orderBy('user_tasks.question_set_date', 'ASC')
                ->groupBy('user_tasks.question_set_date', 'user_tasks.task_type')
                ->get();

        if ($lesson) {
            foreach ($lesson as $t) {
                $is_completed = ($t->end_at != null) ? 1 : 0;
                $week = self::getWeekNo($t->start_at, $start_at);

                $favorite_id = HomeworkService::getFavorite($user, $t->id, CategoryType::LESSON);

                $aryTaskAll[$week][] = self::_makeAryOutputGetAllTask(
                    $t->id,
                    $t->start_at,
                    $t->start_at,
                    $t->end_at,
                    $is_completed,
                    'LESSON',
                    'Lesson',
                    $favorite_id
                );
            }
        }

        if ($reserve) {
            foreach ($reserve as $t) {
                $week = self::getWeekNo($t->start_at, $start_at);
                $year = date('Y', strtotime($t->start_at));

                $favorite_id = HomeworkService::getFavorite($user, $t->id, CategoryType::LESSON);

                $aryTaskAll[$week][] = self::_makeAryOutputGetAllTask(
                    $t->id,
                    $t->start_at,
                    $t->start_at,
                    $t->end_at,
                    0,
                    'LESSON',
                    'Lesson',
                    $favorite_id,
                    1
                );
            }
        }

        if ($assement) {
            foreach ($assement as $t) {
                $week = self::getWeekNo($t->start_at, $start_at);
                $year = date('Y', strtotime($t->start_at));

                $aryTaskAll[$week][] = self::_makeAryOutputGetAllTask(
                    $t->id,
                    $t->start_at,
                    $t->start_at,
                    $t->end_at,
                    0,
                    'ASSESSMENT',
                    'Assessment',
                    null,
                    1
                );
            }
        }


        foreach ($task as $t) {
            if ($t->task_type == TaskType::TASK_TYPE_REVIEW_SERVICE && $reviewServices) {
                continue;
            }

            if ($t->task_type == TaskType::TASK_TYPE_REVIEW_LESSON) {
                continue;
            }

            if ($t->task_type == TaskType::TASK_TYPE_REVIEW_COUNSELOR) {
                continue;
            }

            $week = self::getWeekNo($t->question_set_date, $start_at);

            $favorite_id = HomeworkService::getFavorite($user, $t->id, CategoryType::HOMEWORK);

            $aryTaskTmp = self::_makeAryOutputGetAllTask(
                $t->id,
                $t->question_set_date,
                $t->question_set_date,
                $t->answer_deadline_date,
                $t->is_completed,
                $t->task_type,
                self::getTaskTypeName($t->task_type),
                $favorite_id
            );

            $aryTaskTmp['cand_task_id'] = $t->cand_task_id;
            if ($t->task_type == TaskType::TASK_TYPE_WORD_CARD) {
                $wordcardTaskDetail = DB::table('tasks as tsk')
                    ->join('word_cards as wc', 'wc.id', '=', 'tsk.cand_task_id')
                    ->join('word_card_categories as catwc', 'wc.word_card_category_id', '=', 'catwc.id')
                    ->where(
                        [
                            ['tsk.id', '=', $t->task_id],
                            ['tsk.user_id', '=', $t->user_id],
                        ]
                    )
                    ->first();

                $aryTaskTmp['word_card_category'] = $wordcardTaskDetail->category;
            }

            $aryTaskAll[$week][] = $aryTaskTmp;

        }

        //get all lesson reserve
        $counseling = Reserve::where(
            [
                ['student_id', '=', $user->arugo_user_id],
                ['lesson_type', '=', LessonType::COUNSELING],
                ['start_at', '>=', $start_at],
                ['end_at', '<', $end_at],
                ['deleted', '=', IsDeletedType::ACTIVE],
            ]
        )->orderBy('start_at', 'ASC')->get();

        if ($counseling) {
            foreach ($counseling as $t) {
                $week = self::getWeekNo($t->start_at, $start_at);
                $year = date('Y', strtotime($t->start_at));

                $favorite_id = HomeworkService::getFavorite($user, $t->id,  CategoryType::LESSON);

                $tmp = self::_makeAryOutputGetAllTask(
                    $t->id,
                    $t->start_at,
                    $t->start_at,
                    $t->end_at,
                    0,
                    'COUNSELING',
                    'Counseling',
                    $favorite_id,
                    1
                );


                if ($reviewConseling != null && in_array($t->id, $reviewConseling->toArray())) {
                    $tmp['is_review'] = IsReviewType::REVIEW;
                }

                $aryTaskAll[$week][] = $tmp;
            }
        }

        //result
        $response = [];

        foreach ($aryTaskAll as $w => &$data) {
            usort($data, array('TaskService','sortTask'));
        }

        for ($i = 1; $i <= $weekMax; $i++ ) {
            if (!isset($aryTaskAll[$i])) {
                $aryTaskAll[$i] = [];
            }
        }

        $response['data'] = $aryTaskAll;
        $response['max_week'] = $weekMax;
        $response['current_week'] = $weekCurrent;

        $result = CommonUtils::apiResultOK(
            $response,
            CommonUtils::getCommon()
        );

        return $result;
    }

    /**
     * SortTask
     *
     * @param [type] $a
     * @param [type] $b
     * @return void
     */
    public static function sortTask($a, $b) {
        if ($a['start_time'] == $b['start_time']) {
            return 0;
        }

        return ($a['start_time'] > $b['start_time']) ? 1 : -1;
    }

    /**
     * GetWeekNo function
     *
     * @param [type] $date
     * @return void
     */
    public static function getWeekNo($date, $start_at)
    {
        $dateTarget = date('Y-m-d', strtotime($date));
        $weekTarget = \Carbon\Carbon::createFromFormat('Y-m-d', $dateTarget)->format('W');
        $weekStart = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($start_at)))->format('W');

        $yearNow = date('Y', strtotime($start_at));
        $yearTarget = date('Y', strtotime($date));

        $week = 1;

        for ($i = $yearNow; $i < $yearTarget; $i++) {
            if(date('L', strtotime($i .'-01-01'))) {
                $week += 53;
            } else {
                $week += 52;
            }
        }

        return $week - $weekStart + $weekTarget;
    }

    /**
     * MakeAryOutputGetAllTask function
     *
     * @param [type] $id
     * @param [type] $date
     * @param [type] $start
     * @param [type] $end
     * @param [type] $is_completed
     * @param [type] $type
     * @param [type] $name
     * @return void
     */
    private static function _makeAryOutputGetAllTask($id, $date, $start, $end, $is_completed, $type, $name, $favorite_id, $is_reserve = 0)
    {
        $checkIsLated = 1;

        if (strtotime($end) > time()) {
            $checkIsLated = 0;
        } elseif ($is_completed) {
            $checkIsLated = 0;
        }

        return [
            'id'            => $id,
            'date'          => date('m.d', strtotime($date)),
            'date_time'     => date('Y-m-d', strtotime($date)),
            'dayOfWeek'     => date('l', strtotime($date)),
            'name'          => $name,
            'start_time'    => date('H:i', strtotime($start)),
            'end_time'      => date('H:i', strtotime($end)),
            'type'          => $type,
            'is_completed'  => $is_completed,
            'is_comingsoon' => (date('Y-m-d', strtotime($date)) > date('Y-m-d')) ? 1 : 0,
            'is_lated'      => $checkIsLated,
            'favorite_id'   => $favorite_id,
            'is_reserve'    => $is_reserve
        ];
    }




}