<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;

use App\Enums\IsDeletedType;
use App\Enums\UserTaskStatusType;
use App\Enums\TaskType;

use App\Models\UserTaskPeriod;
use App\Models\UserTask;

use App\Utils\DateUtils;
use Carbon\Carbon;
use DB;

/**
 * AlueIntegCreateTaskService
 * 宿題タスク作成バッチ
 * user_task_periodsからuser_tasksを生成する。
 *
 * --------------------------------------------------
 * 2018-10-16
 * alue統合アプリでは、user_tasksを生成した時点で当該ユーザへの宿題タスク付与は完了する。
 */
class AlueIntegCreateTaskService {

    use AlueCommandLogTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;

    public function __construct()
    {
        $this->signature = 'AlueIntegCreateTaskService';
        $this->commandName = 'AlueIntegCreateTaskService';
        $this->initAlueCommandLogTrait('console/createTask.log');
    }

    /**
     * CreateTask Main
     */
    public function exec(&$resultMessage){

        $this->logwrite('info', 'AlueIntegCreateTaskService start');

        try{
            $this->createUserTasks();

        } catch (\Exception $ex) {
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', 'AlueIntegCreateTaskService finish');
        return true;
    }

    /**
     * user_task_periodsからuser_tasksを生成
     * @return boolean
     */
	private function createUserTasks() {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        DB::beginTransaction();
        try {
            /*
             * user_tasksクリア処理
             * user_tasksレコードのうち、
             *   user_task_status = 0   未出題(未送信)
             *   user_task_status = -1  送信エラー（すなわち「未出題 以下」の値となる）
             * については論理削除しておく。
             *
             * ※user_task_status >= 1  出題済み 以上のものはクリアしない。
             * -------------------------------------------------------------------------
             * 2018-10-16
             * 本処理はsubsysからの継承であるが、alue統合アプリでは、原則として
             * 「未出題、送信エラー」などのステータスは発生しない。
             * ---------------------------------------------------------------
             */
            UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                    ->where('user_task_status' , '<=', UserTaskStatusType::TASK_STATUS_VAL_NONE) //未出題 以下
                    ->update([
                        'is_deleted' => IsDeletedType::DELETED,
                        'updated_at' => Carbon::now()
                    ]);
            $this->logwrite('info', 'AlueIntegCreateTaskService: UserTasks reset Done');


            //user_task_periodsからuser_tasksを生成
            $dayNumber = DateUtils::getDayNumber();//本日の曜日番号を取得
            $this->logwrite('info', 'AlueIntegCreateTaskService: 対象曜日番号:' . $dayNumber);

            //回答期限日を設定
            $duration = env('TASK_ANSWER_LIMIT_DAYS');
            $answerDeadlineDate = DateUtils::getSpecifiedDate($duration);

            //user_task_periods読み取り
            $userTaskPeriods = UserTaskPeriod::where('is_deleted', IsDeletedType::ACTIVE)
                                            ->where('day_number', $dayNumber)
                                            ->orderby('user_id')
                                            ->orderby('task_period_order')
                                            ->orderby('task_type')
                                            ->orderby('task_id')
                                            ->get();
            //user_tasksへ書き出し
            $dataCount = 0;
            foreach($userTaskPeriods as $userTaskPeriod){

                /*
                 * 2018-10-16 alue統合アプリでは「復習タスク」は対象外となる
                 * ---------------------------------------------------------------
                 * 復習タスクの場合のみ、投稿すべきタスクであるかを判断する
                 *　/
                $userId = $userTaskPeriod->user_id;
                $taskType = $userTaskPeriod->task_type;
                if($taskType == TaskType::TASK_REVIEW){
                    //復習の場合は投稿すべきタスクがあるかどうかを判定する
                    $reviewDateText = AlueChatworkPushService::getReviewDateText($userId);
                    if(empty($reviewDateText)){
                        //復習対象レッスン日なし → すなわち復習対象なし
                        continue;
                    }
                }
                 * ---------------------------------------------------------------
                 */

                /*
                 * 2018-10-16 alue統合アプリでは「出題枠No」を制限しない。
                 * これは「お代わり」や「アンケートタスク」などが発生するためである。
                 * ---------------------------------------------------------------
                 * 出題枠Noが 5 までに設定されたタスクのみを送信する
                 * 注意：
                 * CreatQuestionCommandで生成されるuser_task_periodsは
                 * 送信が詰まっていた場合などに、出題枠No:6以上 のレコードが生成される場合がある。
                 * これらは送信対象としないものとする。
                 * /
                $cellNumber = $userTaskPeriod->task_period_order;
                if($cellNumber > 5){
                    continue;
                }
                 * ---------------------------------------------------------------
                 */

                $userTask = new UserTask();
                $userTask->user_id				= $userTaskPeriod->user_id;
                $userTask->task_id				= $userTaskPeriod->task_id;
                $userTask->task_type			= $userTaskPeriod->task_type;
                $userTask->day_number			= $userTaskPeriod->day_number;
                $userTask->task_period_order	= $userTaskPeriod->task_period_order;
                $userTask->user_task_status		= UserTaskStatusType::TASK_STATUS_VAL_ASKED; //1:出題済み
                $userTask->question_set_date    = Carbon::now(); //実際のタスク付与日時をセットする
                $userTask->answer_deadline_date = $answerDeadlineDate;
                $userTask->is_deleted           = IsDeletedType::ACTIVE;
                $userTask->created_at           = Carbon::now();
                $userTask->save();

                $dataCount ++;
                $this->logwrite('info', 'AlueIntegCreateTaskService: user_tasks出力:'
                                . ' dayNumber:'         . $dayNumber
                                . ' user_id:'           . $userTaskPeriod->user_id
                                . ' task_period_order:' . $userTaskPeriod->task_period_order
                                . ' task_type:'         . $userTaskPeriod->task_type
                                . ' task_id:'           . $userTaskPeriod->task_id
                                . ' userTaskPeriodId:'  . $userTaskPeriod->id );
            }//foreach

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }

        $this->logwrite('info', 'AlueIntegCreateTaskService: user_tasks出力完了: ' . $dataCount.'件');
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }


}
