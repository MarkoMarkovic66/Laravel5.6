<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use DB;

use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\TaskType;
use App\Enums\UserTaskStatusType;
use App\Enums\TaskSelectedType;

use App\Models\Lesson;
use App\Models\Counseling;

use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use App\Models\UserTaskPeriod;
use App\Models\EnqueteTask;
use App\Models\RvTask;
use App\Models\HomeworkAnswer;

use Carbon\Carbon;

use App\Utils\DateUtils;
use AlueIntegCreateQuestionService;
use App\Services\SrService;

/**
 * AlueIntegActionWatchService
 * コマンドバッチ関連サービス
 */
class AlueIntegActionWatchService {

    use AlueCommandLogTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;

    protected $alueIntegCreateQuestionService;

    public function __construct(AlueIntegCreateQuestionService $alueIntegCreateQuestionService)
    {
        $this->signature = 'AlueIntegActionWatchService';
        $this->commandName = 'AlueIntegActionWatchService';
        $this->initAlueCommandLogTrait('console/actionWatch.log');
        $this->alueIntegCreateQuestionService = $alueIntegCreateQuestionService;
    }

    /**
     * Main
     */
    public function exec(&$resultMessage){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        DB::beginTransaction();
        try {
            //レッスン完了を監視
            $this->watchLessonComplete();

            //カウンセリング完了を監視
            $this->watchCounselingComplete();

            //SRフィードバック完了を監視
            //$this->watchSRFeedback(); //2018-12-28 暫定コメントアウト

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }

    /**
     * レッスン完了を監視
     */
    private function watchLessonComplete(){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'レッスン完了監視 start');

        //当日分の完了レッスンを取得する
        $targetDate = Carbon::now()->toDateString(); //当日分のレッスン完了のみを調べる
        $this->logwrite('info', 'targetDate: ' . $targetDate);

        $lessons = Lesson::where('deleted', IsDeletedType::ACTIVE)
                        ->where('lesson_type', LessonType::LESSON)
                        ->whereNotNull('start_at')
                        ->whereNotNull('end_at')
                        ->whereDate('end_at', $targetDate)
                        ->get();

        if(!empty($lessons) && $lessons->count() > 0){
            $this->logwrite('info', '完了レッスン数: '.$lessons->count().'件');

            foreach($lessons as $lesson){
                //完了したレッスン情報の取得
                $lessonId = $lesson->id; //lessonId
                $lessonStartAt = $lesson->start_at; //開始日時
                $lessonEndAt = $lesson->end_at; //終了日時

                //alugoUserId取得
                $alugoUserId = $lesson->student_id;
                if(empty($alugoUserId)){
                    $this->logwrite('info', 'レッスン student_id 登録無し');
                    continue;
                }

                //userId取得
                $userId = '';
                $userName = '';
                $user = User::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('arugo_user_id', $alugoUserId)
                                ->first();
                if(!empty($user) && $user->count() > 0){
                    $userId = $user->id;
                    $userName = $user->last_name.' '.$user->first_name;
                }else{
                    $this->logwrite('info', '対応UserId登録無し: alugoUserId: '.$alugoUserId);
                    continue;
                }

                $logMsg = '完了レッスンid: '.$lessonId
                        . ' 会員id: ' .$alugoUserId
                        . ' userId: ' .$userId
                        . ' 氏名: ' .$userName
                        . ' レッスン開始: ' .$lessonStartAt
                        . ' レッスン終了: ' .$lessonEndAt;
                $this->logwrite('info', $logMsg);

                $params = [
                    'lessonId'      => $lessonId,
                    'alugoUserId'   => $alugoUserId,
                    'userId'        => $userId,
                    'userName'      => $userName,
                    'lessonStartAt' => $lessonStartAt,
                    'lessonEndAt'   => $lessonEndAt
                ];

                /*
                 * レッスン・アンケート・タスク追加
                 * 当該レッスンについての「レッスン・アンケート・タスク」を追加しておく
                 */
                $this->createLessonEnqueteTask($params);

                /*
                 * レッスン・復習タスク追加
                 * 当該レッスンについての「復習タスク」を追加しておく
                 */
                $this->createLessonRVTask($params);

            }//foreach

        }else{
            $this->logwrite('info', '完了レッスン数: 0件');
        }//end of if

        $this->logwrite('info', 'レッスン完了監視 finish');
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * カウンセリング完了を監視
     */
    private function watchCounselingComplete(){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'カウンセリング完了監視 start');

        //当日分の完了カウンセリングを取得する
        $targetDate = Carbon::now()->toDateString(); //当日分のカウンセリング完了のみを調べる
        $this->logwrite('info', 'targetDate: ' . $targetDate);

        $counselings = Counseling::from('counseling as cns')
                        ->join('lesson as lsn', 'cns.lesson_id', 'lsn.id')
                        ->where('cns.deleted', IsDeletedType::ACTIVE)
                        ->where('lsn.deleted', IsDeletedType::ACTIVE)
                        ->where('lsn.lesson_type', LessonType::COUNSELING)
                        ->whereNotNull('lsn.start_at')
                        ->whereNotNull('lsn.end_at')
                        ->whereDate('lsn.end_at', $targetDate)
                        ->select(['cns.*', 'lsn.start_at', 'lsn.end_at'])
                        ->orderby('cns.id')
                        ->get();

        if(!empty($counselings) && $counselings->count() > 0){
            $this->logwrite('info', '完了カウンセリング数: '.$counselings->count().'件');

            foreach($counselings as $counseling){
                //完了したカウンセリング情報の取得
                $counselingId      = $counseling->id;       //counselingId
                $counselingStartAt = $counseling->start_at; //開始日時
                $counselingEndAt   = $counseling->end_at;   //終了日時

                //alugoUserId取得
                $alugoUserId = $counseling->student_id;
                if(empty($alugoUserId)){
                    $this->logwrite('info', 'カウンセリング student_id 登録無し');
                    continue;
                }

                //userId取得
                $userId = '';
                $userName = '';
                $user = User::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('arugo_user_id', $alugoUserId)
                                ->first();
                if(!empty($user) && $user->count() > 0){
                    $userId = $user->id;
                    $userName = $user->last_name.' '.$user->first_name;
                }else{
                    $this->logwrite('info', '対応UserId登録無し: alugoUserId: '.$alugoUserId);
                    continue;
                }

                $logMsg = '完了カウンセリングid: '.$counselingId
                        . ' 会員id: ' .$alugoUserId
                        . ' userId: ' .$userId
                        . ' 氏名: ' .$userName
                        . ' カウンセリング開始: ' .$counselingStartAt
                        . ' カウンセリング終了: ' .$counselingEndAt;
                $this->logwrite('info', $logMsg);

                $params = [
                    'counselingId'      => $counselingId,
                    'alugoUserId'       => $alugoUserId,
                    'userId'            => $userId,
                    'userName'          => $userName,
                    'counselingStartAt' => $counselingStartAt,
                    'counselingEndAt'   => $counselingEndAt
                ];

                /*
                 * カウンセリング・アンケート・タスク追加
                 * 当該カウンセリングについての「カウンセリング・アンケート・タスク」を追加しておく
                 */
                $this->createCounselingEnqueteTask($params);

            }//foreach

        }else{
            $this->logwrite('info', '完了カウンセリング数: 0件');
        }//end of if

        $this->logwrite('info', 'カウンセリング完了監視 finish');
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * レッスン・アンケート・タスク追加
     * @param array $params
     */
    private function createLessonEnqueteTask($params){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'レッスン・アンケート・タスク登録 start');

        $lessonId	   = $params['lessonId'];
        $alugoUserId   = $params['alugoUserId'];
        $userId        = $params['userId'];
        $userName      = $params['userName'];
        $lessonStartAt = $params['lessonStartAt'];
        $lessonEndAt   = $params['lessonEndAt'];

        //①レッスン・アンケート・タスクがすでにあるかどうかをチェックする
        $enquetTaskCnt = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->join('enquete_tasks as enqtsk', 'tsk.cand_task_id', 'enqtsk.id')
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('enqtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.task_type', TaskType::TASK_TYPE_REVIEW_LESSON)
                            ->where('utsk.user_id', $userId)
                            ->where('utsk.comment', $lessonId) //アンケート対象レッスンid
                            ->count();

        if($enquetTaskCnt > 0){
            //レッスン・アンケート・タスク登録済み
            $this->logwrite('info', 'レッスン・アンケート・タスク登録済み');
            return;
        }

        //②レッスン・アンケート・タスク・マスタレコード取得
        $enqueteTaskId = '';
        $enqueteTask = EnqueteTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('task_type', TaskType::TASK_TYPE_REVIEW_LESSON)
                                ->first();
        if(!empty($enqueteTask) && $enqueteTask->count() > 0){
            $enqueteTaskId = $enqueteTask->id;
        }else{
            $this->logwrite('info', 'レッスン・アンケート・タスクマスタ: レコード登録無し');
            return;
        }

        //③tasksに追加
        $task = new Task();
        $task->user_id		= $userId;
        $task->task_type    = TaskType::TASK_TYPE_REVIEW_LESSON;
        $task->cand_task_id = $enqueteTaskId;
        $task->task_order   = 1;
        $task->comment      = $lessonId; //アンケート対象レッスンid
        $task->is_selected  = TaskSelectedType::SELECTED;
        $task->is_deleted   = IsDeletedType::ACTIVE;
        $task->created_at   = Carbon::now();
        $task->save();
        $taskId = $task->id;

        //④属性情報の取得
        //出題日(当日日付)
        $questionSetDate = Carbon::now();
        //回答期限日
        $taskAnswerLimitDays = env('TASK_ANSWER_LIMIT_DAYS');
        $answerDeadlineDate = new Carbon();
        $answerDeadlineDate->addDay($taskAnswerLimitDays);
        //出題日(当日日付)の「曜日番号」
        $targetDayNumber  = DateUtils::getDayNumber();
        //出題日(当日日付)の「出題枠番号」
        $targetCellNumber = $this->alueIntegCreateQuestionService::getTaskCellNoForUserTaskPeriod($userId, $targetDayNumber);

        //⑤user_task_periodsに追加
        $userTaskPeriod = new UserTaskPeriod();
        $userTaskPeriod->user_id           = $userId;
        $userTaskPeriod->day_number        = $targetDayNumber;  //出題曜日Noを設定
        $userTaskPeriod->task_type         = TaskType::TASK_TYPE_REVIEW_LESSON;
        $userTaskPeriod->task_id           = $taskId;
        $userTaskPeriod->task_period_order = $targetCellNumber; //出題枠Noを設定
        $userTaskPeriod->comment           = $lessonId; //アンケート対象レッスンid
        $userTaskPeriod->is_deleted        = IsDeletedType::ACTIVE;
        $userTaskPeriod->created_at        = Carbon::now();
        $userTaskPeriod->save();

        //⑥user_tasksに追加
        $userTask = new UserTask();
        $userTask->user_id				= $userId;
        $userTask->task_id              = $taskId;
        $userTask->task_type            = TaskType::TASK_TYPE_REVIEW_LESSON;
        $userTask->day_number           = $targetDayNumber;  //出題曜日Noを設定
        $userTask->task_period_order    = $targetCellNumber; //出題枠Noを設定
        $userTask->user_task_status     = UserTaskStatusType::TASK_STATUS_VAL_ASKED;
        $userTask->question_set_date    = $questionSetDate;
        $userTask->answer_deadline_date = $answerDeadlineDate;
        $userTask->comment              = $lessonId; //アンケート対象レッスンid
        $userTask->is_completed         = UserTaskStatusType::TASK_INCOMPLETED;
        $userTask->is_deleted           = IsDeletedType::ACTIVE;
        $userTask->save();

        //⑦完了ログ出力
        $logMsg = '完了レッスンid: '.$lessonId
                . ' 会員id: ' .$alugoUserId
                . ' userId: ' .$userId
                . ' 氏名: ' .$userName
                . ' レッスン開始: ' .$lessonStartAt
                . ' レッスン終了: ' .$lessonEndAt
                . ' tasks.id: ' .$taskId
                . ' user_task_periods.id: ' .$userTaskPeriod->id
                . ' user_tasks.id: ' .$userTask->id
                . ' targetDayNumber: '  . $targetDayNumber
                . ' targetCellNumber: ' . $targetCellNumber;
        $this->logwrite('info', 'レッスン・アンケート・タスク登録完了: '.$logMsg);
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * レッスン・復習タスク追加
     * @param array $params
     */
    private function createLessonRVTask($params){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'レッスン復習タスク登録 start');

        $lessonId	   = $params['lessonId'];
        $alugoUserId   = $params['alugoUserId'];
        $userId        = $params['userId'];
        $userName      = $params['userName'];
        $lessonStartAt = $params['lessonStartAt'];
        $lessonEndAt   = $params['lessonEndAt'];

        //①レッスン復習タスクがすでにあるかどうかをチェックする
        $rvTaskCnt = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->join('rv_tasks as rvtsk', 'tsk.cand_task_id', 'rvtsk.id')
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('rvtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.task_type', TaskType::TASK_TYPE_RV_LESSON)
                            ->where('utsk.user_id', $userId)
                            ->where('rvtsk.activity_id', $lessonId) //アンケート対象レッスンid
                            ->count();

        if($rvTaskCnt > 0){
            //レッスン復習タスク登録済み
            $this->logwrite('info', 'レッスン復習タスク登録済み');
            return;
        }

        //②レッスン復習タスク・マスタレコード取得
        $rvTaskId = '';
        $rvTask = RvTask::where('is_deleted', IsDeletedType::ACTIVE)
                        ->where('user_id', $userId)
                        ->where('activity_id', $lessonId)
                        ->first();
        if(!empty($rvTask) && $rvTask->count() > 0){
            $rvTaskId = $rvTask->id;

        }else{
            //レッスン復習タスクマスタ・レコード作成
            $rvTask = new RvTask();
            $rvTask->user_id      = $userId;
            $rvTask->activity_id  = $lessonId;
            $rvTask->activity_at  = $lessonEndAt;
            $rvTask->is_deleted   = IsDeletedType::ACTIVE;
            $rvTask->created_at   = Carbon::now();
            $rvTask->save();
            $rvTaskId = $rvTask->id;
        }

        //③tasksに追加
        $task = new Task();
        $task->user_id		= $userId;
        $task->task_type    = TaskType::TASK_TYPE_RV_LESSON;
        $task->cand_task_id = $rvTaskId;
        $task->task_order   = 1;
        $task->is_selected  = TaskSelectedType::SELECTED;
        $task->is_deleted   = IsDeletedType::ACTIVE;
        $task->created_at   = Carbon::now();
        $task->save();
        $taskId = $task->id;

        //④属性情報の取得
        //出題日(当日日付)
        $questionSetDate = Carbon::now();
        //回答期限日
        $taskAnswerLimitDays = env('TASK_ANSWER_LIMIT_DAYS');
        $answerDeadlineDate = new Carbon();
        $answerDeadlineDate->addDay($taskAnswerLimitDays);
        //出題日(当日日付)の「曜日番号」
        $targetDayNumber  = DateUtils::getDayNumber();
        //出題日(当日日付)の「出題枠番号」
        $targetCellNumber = $this->alueIntegCreateQuestionService::getTaskCellNoForUserTaskPeriod($userId, $targetDayNumber);

        //⑤user_task_periodsに追加
        $userTaskPeriod = new UserTaskPeriod();
        $userTaskPeriod->user_id           = $userId;
        $userTaskPeriod->day_number        = $targetDayNumber;  //出題曜日Noを設定
        $userTaskPeriod->task_type         = TaskType::TASK_TYPE_RV_LESSON;
        $userTaskPeriod->task_id           = $taskId;
        $userTaskPeriod->task_period_order = $targetCellNumber; //出題枠Noを設定
        $userTaskPeriod->is_deleted        = IsDeletedType::ACTIVE;
        $userTaskPeriod->created_at        = Carbon::now();
        $userTaskPeriod->save();

        //⑥user_tasksに追加
        $userTask = new UserTask();
        $userTask->user_id				= $userId;
        $userTask->task_id              = $taskId;
        $userTask->task_type            = TaskType::TASK_TYPE_RV_LESSON;
        $userTask->day_number           = $targetDayNumber;  //出題曜日Noを設定
        $userTask->task_period_order    = $targetCellNumber; //出題枠Noを設定
        $userTask->user_task_status     = UserTaskStatusType::TASK_STATUS_VAL_ASKED;
        $userTask->question_set_date    = $questionSetDate;
        $userTask->answer_deadline_date = $answerDeadlineDate;
        $userTask->is_completed         = UserTaskStatusType::TASK_INCOMPLETED;
        $userTask->is_deleted           = IsDeletedType::ACTIVE;
        $userTask->save();

        //⑦完了ログ出力
        $logMsg = '完了レッスンid: '.$lessonId
                . ' 会員id: ' .$alugoUserId
                . ' userId: ' .$userId
                . ' 氏名: ' .$userName
                . ' レッスン開始: ' .$lessonStartAt
                . ' レッスン終了: ' .$lessonEndAt
                . ' rv_tasks.id: ' .$rvTaskId
                . ' tasks.id: ' .$taskId
                . ' user_task_periods.id: ' .$userTaskPeriod->id
                . ' user_tasks.id: ' .$userTask->id
                . ' targetDayNumber: '  . $targetDayNumber
                . ' targetCellNumber: ' . $targetCellNumber;
        $this->logwrite('info', 'レッスン復習タスク登録完了: '.$logMsg);
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * カウンセリング・アンケート・タスク追加
     * @param array $params
     */
    private function createCounselingEnqueteTask($params){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'カウンセリング・アンケート・タスク登録 start');

        $counselingId	   = $params['counselingId'];
        $alugoUserId       = $params['alugoUserId'];
        $userId            = $params['userId'];
        $userName          = $params['userName'];
        $counselingStartAt = $params['counselingStartAt'];
        $counselingEndAt   = $params['counselingEndAt'];

        //①カウンセリング・アンケート・タスクがすでにあるかどうかをチェックする
        $enquetTaskCnt = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->join('enquete_tasks as enqtsk', 'tsk.cand_task_id', 'enqtsk.id')
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('enqtsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.task_type', TaskType::TASK_TYPE_REVIEW_COUNSELOR)
                            ->where('utsk.user_id', $userId)
                            ->where('utsk.comment', $counselingId) //アンケート対象カウンセリングid
                            ->count();

        if($enquetTaskCnt > 0){
            //カウンセリング・アンケート・タスク登録済み
            $this->logwrite('info', 'カウンセリング・アンケート・タスク登録済み');
            return;
        }

        //②カウンセリング・アンケート・タスク・マスタレコード取得
        $enqueteTaskId = '';
        $enqueteTask = EnqueteTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('task_type', TaskType::TASK_TYPE_REVIEW_COUNSELOR)
                                ->first();
        if(!empty($enqueteTask) && $enqueteTask->count() > 0){
            $enqueteTaskId = $enqueteTask->id;
        }else{
            $this->logwrite('info', 'カウンセリング・アンケート・タスクマスタ: レコード登録無し');
            return;
        }

        //③tasksに追加
        $task = new Task();
        $task->user_id		= $userId;
        $task->task_type    = TaskType::TASK_TYPE_REVIEW_COUNSELOR;
        $task->cand_task_id = $enqueteTaskId;
        $task->task_order   = 1;
        $task->comment      = $counselingId; //アンケート対象カウンセリングid
        $task->is_selected  = TaskSelectedType::SELECTED;
        $task->is_deleted   = IsDeletedType::ACTIVE;
        $task->created_at   = Carbon::now();
        $task->save();
        $taskId = $task->id;

        //④属性情報の取得
        //出題日(当日日付)
        $questionSetDate = Carbon::now();
        //回答期限日
        $taskAnswerLimitDays = env('TASK_ANSWER_LIMIT_DAYS');
        $answerDeadlineDate = new Carbon();
        $answerDeadlineDate->addDay($taskAnswerLimitDays);
        //出題日(当日日付)の「曜日番号」
        $targetDayNumber  = DateUtils::getDayNumber();
        //出題日(当日日付)の「出題枠番号」
        $targetCellNumber = $this->alueIntegCreateQuestionService::getTaskCellNoForUserTaskPeriod($userId, $targetDayNumber);

        //⑤user_task_periodsに追加
        $userTaskPeriod = new UserTaskPeriod();
        $userTaskPeriod->user_id           = $userId;
        $userTaskPeriod->day_number        = $targetDayNumber;  //出題曜日Noを設定
        $userTaskPeriod->task_type         = TaskType::TASK_TYPE_REVIEW_COUNSELOR;
        $userTaskPeriod->task_id           = $taskId;
        $userTaskPeriod->task_period_order = $targetCellNumber; //出題枠Noを設定
        $userTaskPeriod->comment           = $counselingId; //アンケート対象カウンセリングid
        $userTaskPeriod->is_deleted        = IsDeletedType::ACTIVE;
        $userTaskPeriod->created_at        = Carbon::now();
        $userTaskPeriod->save();

        //⑥user_tasksに追加
        $userTask = new UserTask();
        $userTask->user_id				= $userId;
        $userTask->task_id              = $taskId;
        $userTask->task_type            = TaskType::TASK_TYPE_REVIEW_COUNSELOR;
        $userTask->day_number           = $targetDayNumber;  //出題曜日Noを設定
        $userTask->task_period_order    = $targetCellNumber; //出題枠Noを設定
        $userTask->user_task_status     = UserTaskStatusType::TASK_STATUS_VAL_ASKED;
        $userTask->question_set_date    = $questionSetDate;
        $userTask->answer_deadline_date = $answerDeadlineDate;
        $userTask->comment              = $counselingId; //アンケート対象カウンセリングid
        $userTask->is_completed         = UserTaskStatusType::TASK_INCOMPLETED;
        $userTask->is_deleted           = IsDeletedType::ACTIVE;
        $userTask->save();

        //⑦完了ログ出力
        $logMsg = '完了カウンセリングid: '.$counselingId
                . ' 会員id: ' .$alugoUserId
                . ' userId: ' .$userId
                . ' 氏名: ' .$userName
                . ' カウンセリング開始: ' .$counselingStartAt
                . ' カウンセリング終了: ' .$counselingEndAt
                . ' tasks.id: ' .$taskId
                . ' user_task_periods.id: ' .$userTaskPeriod->id
                . ' user_tasks.id: ' .$userTask->id
                . ' targetDayNumber: '  . $targetDayNumber
                . ' targetCellNumber: ' . $targetCellNumber;
        $this->logwrite('info', 'カウンセリング・アンケート・タスク登録完了: '.$logMsg);
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }


    /**
     * SRフィードバック完了を監視
     */
    private function watchSRFeedback(){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'SRフィードバック完了監視 start');

        //SRのフィードバック完了を取得する
        $homeworkAnswers = HomeworkAnswer::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('category', 2) // 2:宿題
                                        ->where('task_type', TaskType::TASK_SPEAKING_RALLY)
                                        ->whereNotNull('sound_file_id')
                                        ->whereNotNull('sr_answer_id')
                                        ->whereNull('feedback') //まだfeedbackされていないもの
                                        ->get();

        if(empty($homeworkAnswers) || $homeworkAnswers->count() < 1){
            $this->logwrite('info', 'SRフィードバック未完了数: 0件');
            return;
        }

        $this->logwrite('info', 'SRフィードバック未完了数: '.$homeworkAnswers->count().'件');
        $srService = new SrService();

        foreach($homeworkAnswers as $homeworkAnswer){
            //完了したSRフィードバック情報の取得
            $homeworkAnswerId = $homeworkAnswer->id; //homeworkAnswer.id
            $srAnswerid = $homeworkAnswer->sr_answer_id;
            $userId = $homeworkAnswer->user_id;
            $this->logwrite('info', 'SRフィードバック: userId: ' . $userId
                                    . ' homeworkAnswerId: ' . $homeworkAnswerId
                                    . ' srAnswerid: ' . $srAnswerid);
            try {
                $results = [];
                $ret = $srService->getFeedback($srAnswerid, $results);
                if($ret){
                    if(!empty($results) && is_array($results) && count($results) > 0){
                        $result = $results[0];
                    }else{
                        //NG
                        $errorMessage = 'SR feedback APIエラー: $results Array is NG';
                        throw new \Exception($errorMessage);
                    }

                    if($result['status'] === 'success'){
                        //OK
                        //$revisedAnswer = $result['detail']['revised_answer']; //現在は未使用
                        $coachAnswer   = $result['detail']['coach_answer'];
                        $coachMessage  = $result['detail']['coach_message'];
                        $approvedDate  = $result['detail']['approved_date'];
                        $feedback = $coachAnswer . "\n\n" . $coachMessage;
                        $srService->registFeedback($homeworkAnswerId, $feedback, $approvedDate);

                    }else{
                        //NG
                        $errorMessage = var_export($result, true);
                        throw new \Exception($errorMessage);
                    }

                }else{
                    //NG
                    $errorMessage = var_export($results, true);
                    throw new \Exception($errorMessage);
                }

            } catch (\Exception $ex) {
                $this->logwrite('error', $ex->getMessage());
                continue;
            }

        }//foreach

        $this->logwrite('info', 'SRフィードバック完了監視 finish');
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }


}

