<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;

use App\Enums\IsDeletedType;
use App\Enums\TaskType;
use App\Enums\TaskSelectedType;

use App\Models\User;
use App\Models\UserScore;
use App\Models\TaskSetting;
use App\Models\Task;
use App\Models\UserTaskPeriod;
use App\Models\WordCardCategory;
use App\Models\WordCard;
use App\Models\UserWordCardAssign;
use App\Models\ListeningTask;
use App\Models\UserListeningAssign;
use App\Models\SpeakingRally;
use App\Models\UserSrAssign;
use App\Models\UserTaskCalendar;

use App\Dtos\UserTaskCalendarDto;

use Carbon\Carbon;
use Exception;
use DB;

/**
 * AlueIntegCreateQuestionService
 */
class AlueIntegCreateQuestionService {

    use AlueCommandLogTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;

    public function __construct()
    {
        $this->signature = 'AlueIntegCreateQuestionService';
        $this->commandName = 'AlueIntegCreateQuestionService';
        $this->initAlueCommandLogTrait('console/createQuestion.log');
    }

    /**
     * Main
     */
    public function exec(&$resultMessage){
        try {
            $this->createQuestions();
        } catch (\Exception $ex) {
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }
        return true;
    }


    private function createQuestions(){

        DB::beginTransaction();
        try {
            //全ユーザのアセスメント情報を取得する
            $userAssesmentInfos = $this->getUserAssesmentInfo();
            //タスク種別毎のAssessmentItem名の一覧を取得する
            $assessmentItemNameByTaskTypes = $this->getAssessmentItemNameByTaskType();

            //ユーザー単位での出題処理を実行
            foreach($userAssesmentInfos as $userAssesmentInfo){

                //対象ユーザの出題カレンダーの有無をチェックし、出題カレンダーが無い場合は新規作成する
                $targetUserId = $userAssesmentInfo['userId'];//対象ユーザidを取得
                $userTaskCalendars = [];
                if( ! $this->getTaskCalendar($targetUserId, $userTaskCalendars)){
                    //出題カレンダーが無い場合は新規作成する
                    $this->createTaskCalendar($userAssesmentInfo);
                    //対象ユーザの出題カレンダー取得
                    $this->getTaskCalendar($targetUserId, $userTaskCalendars);
                }

                //対象ユーザの出題カレンダーに従って、翌週分の出題問題を作成する
                $this->createUserTaskPeriods($targetUserId, $userTaskCalendars, $userAssesmentInfo, $assessmentItemNameByTaskTypes);

            }//end of foreach

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
     * 対象ユーザの出題カレンダーの有無をチェックする
     * 出題カレンダーがあれば取得する
     *
     * @param int $targetUserId
     * @param array &$userTaskCalendar
     * @return bool
     */
    private function getTaskCalendar($targetUserId, &$userTaskCalendars){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        $userTaskCalendarRows = UserTaskCalendar::where('is_deleted', IsDeletedType::ACTIVE)
                                            ->where('user_id', $targetUserId)
                                            ->orderby('day_number')
                                            ->orderby('task_period_order')
                                            ->get();

        if(empty($userTaskCalendarRows) || $userTaskCalendarRows->count() < 1){
            $this->logwrite('info', '出題カレンダー無し userId: '.$targetUserId);
            return false;
        }

        foreach($userTaskCalendarRows as $userTaskCalendarRow){
            $userTaskCalendarDto = new UserTaskCalendarDto();
            $userTaskCalendarDto->userId          = $userTaskCalendarRow->user_id;
            $userTaskCalendarDto->dayNumber       = $userTaskCalendarRow->day_number;
            $userTaskCalendarDto->taskPeriodOrder = $userTaskCalendarRow->task_period_order;
            $userTaskCalendarDto->taskType        = $userTaskCalendarRow->task_type;
            $userTaskCalendars[] = $userTaskCalendarDto;
        }//foreach

        $this->logwrite('info', '出題カレンダーあり userId: '. $targetUserId
                            . ' 出題カレンダーレコード件数: ' . count($userTaskCalendars)
                        );

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }

    /**
     * 出題カレンダーが無い場合は新規作成する
     * @param array $userAssesmentInfo
     */
    private function createTaskCalendar($userAssesmentInfo){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        //宿題出題対象は下記のみとなる
        $taskTypes = [
                TaskType::TASK_GRAMMAR,          //瞬間口頭英作G
                TaskType::TASK_VOCABULARY,       //瞬間口頭英作V
                TaskType::TASK_SPEAKING_RALLY,   //SR
                TaskType::TASK_TYPE_WORD_CARD,   //単語カード
                TaskType::TASK_TYPE_LISTENING_B, //リスニングトレーニング
                TaskType::TASK_TYPE_LISTENING_C, //リスニングトレーニング
                TaskType::TASK_TYPE_LISTENING_D, //リスニングトレーニング
            ];

        //タスク種別毎に、出題カレンダー情報を作成する
        foreach($taskTypes as $taskType){

            $this->logwrite('info', 'Create Task Calendar: Target TaskType: ' . $taskType);

            //タスクsetting情報を取得
            $taskSettingInfo = $this->getTaskSettingInfo($taskType);
            $targetTaskSettingInfo = $taskSettingInfo->where('taskType', $taskType)->first();

            if( empty($targetTaskSettingInfo) ||
                !$targetTaskSettingInfo->has('assessmentItem') ||
                !$targetTaskSettingInfo->has('dayNumbersPerGrade') )
            {
                //タスクsetting情報が存在しない場合は処理をskipする
                $this->logwrite('info', 'targetTaskSettingInfo has no target data');
                continue;
            }

            //task_setting情報（アセスメント項目とGrade別出題曜日）を取得する
            $targetAssessmentItem = $targetTaskSettingInfo->get('assessmentItem'); //アセスメント項目
            $targetDayNumbersPerGrade = $targetTaskSettingInfo->get('dayNumbersPerGrade'); //Grade別出題曜日

            //ユーザー単位での出題処理を実行
            //対象ユーザidを取得
            $targetUserId = $userAssesmentInfo['userId'];

            //対象ユーザの該当するassessmentItemについてのgradeを取得
            $assesments = $userAssesmentInfo['assesments'];
            $targetUserAssesment = $assesments->where('category', $targetAssessmentItem)->first();
            $targetUserGrade = trim($targetUserAssesment['grade']);

            if(empty($targetUserGrade) || strlen($targetUserGrade) < 1){
                //grade情報が無ければskipし、次のタスク種別に行く
                $this->logwrite('info', 'targetUser has no GradeData: '
                                        . ' targetUserId: ' . $targetUserId
                                        . ' targetUserAssesment: ' . var_export($targetUserAssesment, true)
                                );
                continue;
            }

            $this->logwrite('info', 'targetUser GradeData: '
                                  . ' targetUserId: ' . $targetUserId
                                  . ' targetUserAssesment: ' . var_export($targetUserAssesment, true)
                                  . ' targetUserGrade: ' . $targetUserGrade
                            );

            /*
             * 当該ユーザの出題カレンダーレコードを作成する
             */
            //このgradeに対応するdayNumberがあれば、user_task_calendarsレコードを出力する
            if( !empty($targetUserGrade) && array_key_exists($targetUserGrade, $targetDayNumbersPerGrade)){
                //該当ユーザのgradeが、対象となるgrades一覧に含まれていれば、指定された曜日に出題を行う
                $targetDayNumbers = $targetDayNumbersPerGrade[$targetUserGrade];
                //user_task_calendarsレコードの出力
                $this->putUserTaskCalendarRecord($taskType, $targetUserId, $targetDayNumbers);

                $this->logwrite('info', 'create user_task_calendars: '
                                        . ' targetTaskType: '   . $taskType
                                        . ' targetUserId: '     . $targetUserId
                                        . ' targetDayNumbers: ' . var_export($targetDayNumbers, true)
                                );
            }//end of if

        }//foreach

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * user_task_calendarsレコードの出力
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param array $targetDayNumbers
     */
    private function putUserTaskCalendarRecord($targetTaskType, $targetUserId, $targetDayNumbers){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        foreach($targetDayNumbers as $targetDayNumber){
            //出題枠Noを決定する
            $targetCellNumber = $this->getTaskCellNoForUserTaskCalendar($targetUserId, $targetDayNumber);

            //user_task_calendarsレコード追加
            $insertedId = UserTaskCalendar::insertGetId([
                                                'user_id'    => $targetUserId,
                                                'day_number' => $targetDayNumber,
                                                'task_type'  => $targetTaskType,
                                                'task_id'    => null,
                                                'task_period_order' => $targetCellNumber,
                                                'comment'    => Carbon::now() . ' 出題ロジックにより自動設定',
                                                'is_deleted' => IsDeletedType::ACTIVE,
                                                'created_at' => Carbon::now()
                                            ]);

            $this->logwrite('info', 'Create user_task_calendarsレコード: '
                                    . ' targetTaskType: '     . $targetTaskType
                                    . ' targetUserId: '       . $targetUserId
                                    . ' targetDayNumber: '    . $targetDayNumber
                                    . ' targetCellNumber: '   . $targetCellNumber
                                    . ' UserTaskCalendarId: ' . $insertedId
                            );
        }//foreach

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * 対象ユーザについて、出題カレンダーに従って、翌週分の出題問題を作成する
     * @param int $targetUserId
     * @param array $userTaskCalendars
     * @param array $userAssesmentInfo
     */
    private function createUserTaskPeriods($targetUserId, $userTaskCalendars, $userAssesmentInfo, $assessmentItemNameByTaskTypes){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        //出題カレンダーに従って、翌週分の出題問題を作成する
        //$userTaskCalendarsは、該当ユーザについての「各曜日＋枠No＋タスク種別」の1週間分のレコードである
        foreach($userTaskCalendars as $userTaskCalendar){
            //$userTaskCalendarは、該当ユーザについての「各曜日＋枠No＋タスク種別」の1日分のレコードである
            $targetDayNumber       = $userTaskCalendar->dayNumber;       //曜日
            $targetTaskPeriodOrder = $userTaskCalendar->taskPeriodOrder; //枠No
            $targetTaskType        = $userTaskCalendar->taskType;        //タスク種別

            $targetAssessmentItemName = $assessmentItemNameByTaskTypes[$targetTaskType];

            $this->logwrite('info', '出題作成: Create user_task_periodsレコード: '
                                    . ' targetUserId: '       . $targetUserId
                                    . ' targetDayNumber: '    . $targetDayNumber
                                    . ' targetCellNumber: '   . $targetTaskPeriodOrder
                                    . ' targetTaskType: '     . $targetTaskType
                                    . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                            );

            if( $targetTaskType == TaskType::TASK_GRAMMAR ||
                $targetTaskType == TaskType::TASK_VOCABULARY ){
                $this->createUserTaskPeriodRecordForGV(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber
                                                );

            }elseif($targetTaskType == TaskType::TASK_SPEAKING_RALLY){
                $this->createUserTaskPeriodRecordForSR(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber,
                                                    $userAssesmentInfo,
                                                    $targetAssessmentItemName
                                                );

            }elseif($targetTaskType == TaskType::TASK_TYPE_WORD_CARD){
                $this->createUserTaskPeriodRecordForWordCard(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber,
                                                    $userAssesmentInfo,
                                                    $targetAssessmentItemName
                                                );

            }elseif( $targetTaskType == TaskType::TASK_TYPE_LISTENING_B ||
                     $targetTaskType == TaskType::TASK_TYPE_LISTENING_C ||
                     $targetTaskType == TaskType::TASK_TYPE_LISTENING_D ){
                $this->createUserTaskPeriodRecordForListening(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber,
                                                    $userAssesmentInfo,
                                                    $targetAssessmentItemName
                                                );
            }//end if

        }//foreach

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * Grammar/Vocabularyについて、
     * タスク周期レコード(user_task_periods)を生成する
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param int $targetDayNumber
     */
    private function createUserTaskPeriodRecordForGV($targetTaskType, $targetUserId, $targetDayNumber){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        $this->logwrite('info', 'Create Grammar/Vocabulary UserTaskPeriod: '
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetDayNumber: ' . $targetDayNumber
                        );
        try{
            /*
             * tasksはすでに生成済みであるため、該当ユーザについてのレコードを取得する
             */
            $tasks = Task::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('is_selected', TaskSelectedType::RESERVED)
                            ->where('task_type', $targetTaskType)
                            ->where('user_id', $targetUserId)
                            ->select(['id'])
                            ->orderby('task_order')
                            ->get();

            if(empty($tasks) || $tasks->count() < 1){
                $this->logwrite('info', 'Grammar/Vocabulary Task is Empty: '
                                        . ' targetTaskType: ' . $targetTaskType
                                        . ' targetUserId: '   . $targetUserId
                                        . ' Available Task Num: 0'
                                );
                return true;//「出題用の問題」が無い場合は即時return
            }

            $this->logwrite('info', 'Grammar/Vocabulary Task: '
                                    . ' targetTaskType: ' . $targetTaskType
                                    . ' targetUserId: '   . $targetUserId
                                    . ' Available Task Num: ' . $tasks->count()
                            );

            //出題枠Noを決定する(user_task_periodsについて、再度、調べて取得する)
            $targetCellNumber = $this->getTaskCellNoForUserTaskPeriod($targetUserId, $targetDayNumber);
            $this->logwrite('info', 'Grammar/Vocabulary Task: '
                                    . ' targetTaskType: '   . $targetTaskType
                                    . ' targetUserId: '     . $targetUserId
                                    . ' targetDayNumber: '  . $targetDayNumber
                                    . ' targetCellNumber: ' . $targetCellNumber
                            );

            //tasksの集合の先頭から順に、規定の件数分を取得する
            $newTaskNumber = env('TASK_CALENDAR_CREATE_NEW_TASK_NUMBER');
            //追加対象となるtaskIdの集合
            $taskIds = [];
            for( $idxTask=0; $idxTask < $newTaskNumber; $idxTask++ ){
                if($tasks->has($idxTask)){
                    $task = $tasks->get($idxTask);
                    $taskId = $task->id;
                    $taskIds[] = $taskId;
                }else{
                    //tasksの件数が少なく、先に終了した場合は、
                    //その時点までのtaskを使用して出題する
                    break;
                }
            }//for
            $this->logwrite('info', 'target Tasks: '
                                    . ' 件数: ' . count($taskIds).'件'
                                    . ' targetTaskIds: ' . var_export($taskIds, true)
                            );

            //該当のtaskを、user_task_periodsに追加する
            foreach($taskIds as $taskId){
                $insertedId = UserTaskPeriod::insertGetId([
                                            'user_id'    => $targetUserId,
                                            'day_number' => $targetDayNumber,
                                            'task_type'  => $targetTaskType,
                                            'task_id'    => $taskId,
                                            'task_period_order' => $targetCellNumber,
                                            'comment'    => Carbon::now() . ' 出題ロジックにより自動出題',
                                            'is_deleted' => IsDeletedType::ACTIVE,
                                            'created_at' => Carbon::now(),
                                            'updated_at' => Carbon::now()
                                        ]);

                //当該のtasksレコードを「選択済み」に更新する
                Task::where('id', $taskId)
                        ->update([
                            'is_selected' => TaskSelectedType::SELECTED,
                            'updated_at'  => Carbon::now()
                        ]);

                $this->logwrite('info', 'Create user_task_periodsレコード: '
                                        . ' taskType: '          . $targetTaskType
                                        . ' targetUserId: '      . $targetUserId
                                        . ' targetDayNumber: '   . $targetDayNumber
                                        . ' targetCellNumber: '  . $targetCellNumber
                                        . ' UserTaskPeriodId: '  . $insertedId
                                        . ' AssignedTaskId: '    . $taskId
                                );
            }//foreach

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }

    /**
     * SpeakingRallyについて、指定された曜日に
     * タスク周期レコード(user_task_periods)を生成する
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param int $targetDayNumber
     * @param array $userAssesmentInfo
     * @param string $targetAssessmentItemName
     * @throws Exception
     */
    private function createUserTaskPeriodRecordForSR(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber,
                                                    $userAssesmentInfo,
                                                    $targetAssessmentItemName )
    {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'Create SR UserTaskPeriod: '
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetDayNumber: ' . $targetDayNumber
                                . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                        );
        try{
            //対象ユーザの該当するassessmentItem(Speaking)についてのgradeを取得
            $targetUserGrade = '';
            $assesments = $userAssesmentInfo['assesments'];
            $targetUserAssesment = $assesments->where('category', $targetAssessmentItemName)->first();
            if(array_key_exists('grade', $targetUserAssesment)){
                $targetUserGrade = trim($targetUserAssesment['grade']);
            }
            if(empty($targetUserGrade) || strlen($targetUserGrade) < 1){
                //grade情報が無ければ処理をskipする(即時にreturn)
                $this->logwrite('info', 'targetUser has no grade data: '
                                        . ' targetTaskType: '  . $targetTaskType
                                        . ' targetUserId: '    . $targetUserId
                                        . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                                );
                return;
            }
            $this->logwrite('info', 'targetUserGrade: '
                                    . ' targetTaskType: ' . $targetTaskType
                                    . ' targetUserId: '   . $targetUserId
                                    . ' Category: '       . $targetAssessmentItemName
                                    . ' Grade: '          . $targetUserGrade
                            );

            //宿題タスクの生成（tasksレコード、user_task_periodsレコードの出力）
            $results = [];
            $ret = $this->createSRTaskRecord(
                                                $targetTaskType,
                                                $targetUserId,
                                                $targetUserGrade,
                                                $targetDayNumber,
                                                $results
                                            );
            $this->logwrite('info', 'SRTask生成結果: '
                                . ' ret: ' . ( ($ret) ? 'true' : 'false' )
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetUserGrade: ' . $targetUserGrade
                                . ' targetDayNumber: ' . $targetDayNumber
                                . ' results: '         . var_export($results, true)
                            );

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }
    /**
     * SpeakingRallyタスクの生成（tasksレコード、user_task_periodsレコードの出力）
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param string $targetUserGrade
     * @param int $targetDayNumber
     * @param array &$results
     * @return bool
     * @throws Exception
     */
    private function createSRTaskRecord(
                                        $targetTaskType,
                                        $targetUserId,
                                        $targetUserGrade,
                                        $targetDayNumber,
                                        &$results )
    {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        try{
            // 1 出題カテゴリを取得
            $targetCategoryId = env('CREATE_TASK_SR_CATEGORY_ID');   //とりあえず 2:business 固定
            $targetCategory   = env('CREATE_TASK_SR_CATEGORY_NAME'); //とりあえず 2:business 固定

            $this->logwrite('info', 'SR TaskRecord生成: '
                                    . ' targetUserId: '     . $targetUserId
                                    . ' targetUserGrade: '  . $targetUserGrade
                                    . ' targetCategoryId: ' . $targetCategoryId
                                    . ' targetCategory: '   . $targetCategory
                                    . ' targetDayNumber: '  . $targetDayNumber
                            );

            // 2 直前の出題idを取得
            $assignedTaskId = 0;
            $userSrAssign = UserSrAssign::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('user_id', $targetUserId)
                                        ->where('category', $targetCategoryId)
                                        ->orderby('assigned_date', 'desc')
                                        ->first();
            if(!empty($userSrAssign) && $userSrAssign->count() > 0){
                $assignedTaskId = $userSrAssign->assigned_id;
            }
            $this->logwrite('info', 'SR 直前出題タスクid: '
                                    . ' targetTaskType: '  . $targetTaskType
                                    . ' targetUserId: '    . $targetUserId
                                    . ' targetCategory: '  . $targetCategory
                                    . ' assignedTaskId: '  . $assignedTaskId
                            );

            // 3 出題問題を全件取得する
            //（注）「最後尾での折り返し」に対応するため、前回idまでをskipすることはしないことに注意する！
            $speakingRallyTasks = SpeakingRally::where('is_deleted', IsDeletedType::ACTIVE)
                                            ->where('sr_category_id', $targetCategoryId)//出題対象となるカテゴリ
                                            ->orderby('id') //ここは必ずid順
                                            ->get();
            if(empty($speakingRallyTasks) || $speakingRallyTasks->count() < 1){
                //該当問題無し
                $this->logwrite('info', 'SR 出題問題無し');
                return false;
            }

            $targetSRTaskId = '';
            foreach($speakingRallyTasks as $speakingRallyTask){
                if( $speakingRallyTask->id <= $assignedTaskId ){
                    continue;//前回のtaskIdまではskipする
                }else{
                    $targetSRTaskId = $speakingRallyTask->id;
                    break;
                }
            }//foreach
            /*
             * 最後尾まで出題した場合は折り返して、再び先頭から出題する。
             * ただし重複はしない。
             */
            if( empty($targetSRTaskId) ){
                //再び先頭から出題する
                foreach($speakingRallyTasks as $speakingRallyTask){
                    //重複を避けるためのチェック
                    if( $speakingRallyTask->id != $targetSRTaskId ){
                        $targetSRTaskId = $speakingRallyTask->id;
                        break;
                    }
                }//foreach
            }
            if( empty($targetSRTaskId) ){
                $this->logwrite('info', 'SR 出題問題無し');
                return false;
            }

            /*
             * 4 対象の曜日にタスク生成
             */
            // 4-1 tasks生成
            $task = new Task();
            $task->user_id      = $targetUserId;
            $task->task_type    = $targetTaskType;
            $task->cand_task_id = $targetSRTaskId;
            $task->task_order   = 1; //SRでは1件のみである
            $task->is_selected  = TaskSelectedType::SELECTED;
            $task->is_deleted   = IsDeletedType::ACTIVE;
            $task->created_at   = Carbon::now();
            $task->save();
            $targetTaskId = $task->id;
            $this->logwrite('info', 'tasks出力 : targetDayNumber: ' . $targetDayNumber
                                    . ' 件数: 1件'
                                    . ' targetTaskId: ' . $targetTaskId
                            );

            // 4-2 user_task_periods生成
            // 4-2-1 出題枠Noを決定する(間隔があいている場合も考慮する)
            $targetCellNumber = $this->getTaskCellNoForUserTaskPeriod($targetUserId, $targetDayNumber);
            $this->logwrite('info', 'userTaskPeriod出力準備: '
                                        . ' targetUserId: '        . $targetUserId
                                        . ' targetDayNumber: '     . $targetDayNumber
                                        . ' targetCellNumber: '    . $targetCellNumber );

            // 4-2-2 user_task_periods出力
            $userTaskPeriod = new UserTaskPeriod();
            $userTaskPeriod->user_id           = $targetUserId;
            $userTaskPeriod->day_number        = $targetDayNumber;
            $userTaskPeriod->task_type         = $targetTaskType;
            $userTaskPeriod->task_id           = $targetTaskId;
            $userTaskPeriod->task_period_order = $targetCellNumber; //出題枠Noを設定
            $userTaskPeriod->comment           = Carbon::now() . ' 出題ロジックにより自動出題';
            $userTaskPeriod->is_deleted        = IsDeletedType::ACTIVE;
            $userTaskPeriod->created_at        = Carbon::now();
            $userTaskPeriod->save();
            $targetUserTaskPeriodId = $userTaskPeriod->id;

            $this->logwrite('info', 'userTaskPeriod出力結果: '
                                        . ' targetUserId: '        . $targetUserId
                                        . ' targetDayNumber: '     . $targetDayNumber
                                        . ' targetCellNumber: '    . $targetCellNumber
                                        . ' UserTaskPeriod件数: '  . '1件'
                                        . ' UserTaskPeriodIds: '   . $targetUserTaskPeriodId
                            );
            $results = [
                'targetUserId'         => $targetUserId,
                'targetDayNumber'      => $targetDayNumber,
                'targetCellNumber'     => $targetCellNumber,
                '該当曜日の出力taskId'  => $targetTaskId,
                '該当曜日の出力userTaskPeriodId' => $targetUserTaskPeriodId,
            ];

            /*
             * 5 後処理
             * 出題後は UserSrAssign の
             * assigned_id, assigned_dateを最新の状態に更新しておく
             */
            if(empty($userSrAssign) || $userSrAssign->count() < 1){
                //新規作成
                $userSrAssign = new UserSrAssign();
                $userSrAssign->user_id          = $targetUserId;
                $userSrAssign->category         = $targetCategoryId;
                $userSrAssign->assigned_id      = $targetSRTaskId;
                $userSrAssign->assigned_date    = Carbon::now();
                $userSrAssign->is_deleted       = IsDeletedType::ACTIVE;
                $userSrAssign->created_at       = Carbon::now();
                $userSrAssign->save();

            }else{
                //更新
                $userSrAssign->assigned_id      = $targetSRTaskId;
                $userSrAssign->assigned_date    = Carbon::now();
                $userSrAssign->updated_at       = Carbon::now();
                $userSrAssign->save();
            }

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }


    /**
     * WordCardについて、指定された曜日に
     * タスク周期レコード(user_task_periods)を生成する
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param int $targetDayNumber
     * @param array $userAssesmentInfo
     * @param string $targetAssessmentItemName
     * @throws Exception
     */
    private function createUserTaskPeriodRecordForWordCard(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber,
                                                    $userAssesmentInfo,
                                                    $targetAssessmentItemName )
    {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'Create WordCard UserTaskPeriod: '
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetDayNumber: ' . $targetDayNumber
                                . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                        );
        try{
            //対象ユーザの該当するassessmentItem(Grammar)についてのgradeを取得
            $targetUserGrade = '';
            $assesments = $userAssesmentInfo['assesments'];
            $targetUserAssesment = $assesments->where('category', $targetAssessmentItemName)->first();
            if(array_key_exists('grade', $targetUserAssesment)){
                $targetUserGrade = trim($targetUserAssesment['grade']);
            }
            if(empty($targetUserGrade) || strlen($targetUserGrade) < 1){
                //grade情報が無ければ処理をskipする(即時にreturn)
                $this->logwrite('info', 'targetUser has no grade data: '
                                        . ' targetTaskType: '  . $targetTaskType
                                        . ' targetUserId: '    . $targetUserId
                                        . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                                );
                return;
            }
            $this->logwrite('info', 'targetUserGrade: '
                                    . ' targetTaskType: ' . $targetTaskType
                                    . ' targetUserId: '   . $targetUserId
                                    . ' Category: '       . $targetAssessmentItemName
                                    . ' Grade: '          . $targetUserGrade
                            );

            //宿題タスクの生成（tasksレコード、user_task_periodsレコードの出力）
            $results = [];
            $ret = $this->createWordCardTaskRecord(
                                                $targetTaskType,
                                                $targetUserId,
                                                $targetUserGrade,
                                                $targetDayNumber,
                                                $results
                                            );
            $this->logwrite('info', 'WordCardTask生成結果: '
                                . ' ret: ' . ( ($ret) ? 'true' : 'false' )
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetUserGrade: ' . $targetUserGrade
                                . ' targetDayNumber: ' . $targetDayNumber
                                . ' results: '         . var_export($results, true)
                            );

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }
    /**
     * 単語カードタスクの生成（tasksレコード、user_task_periodsレコードの出力）
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param string $targetUserGrade
     * @param int $targetDayNumber
     * @param array &$results
     * @return bool
     * @throws Exception
     */
    private function createWordCardTaskRecord(
                                                $targetTaskType,
                                                $targetUserId,
                                                $targetUserGrade,
                                                $targetDayNumber,
                                                &$results )
    {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        try{
            /*
             * 1 単語カードタスクの取得
             */
            // 1-1 出題カテゴリを特定する（直前の出題カテゴリは除外する）
            // 1-1-1 直前の出題カテゴリを取得
            $assignedCategoryId = '';
            $userWordCardAssign = UserWordCardAssign::where('is_deleted', IsDeletedType::ACTIVE)
                                                    ->where('user_id', $targetUserId)
                                                    ->orderby('assigned_date', 'desc')
                                                    ->first();
            if(!empty($userWordCardAssign) && $userWordCardAssign->count() > 0){
                $assignedCategoryId = $userWordCardAssign->word_card_category_id;
            }
            $this->logwrite('info', '直前出題カテゴリ: '.$assignedCategoryId);

            // 1-1-2 出題カテゴリの一覧を全件取得
            $wordCardCategories = WordCardCategory::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('grades', 'like', '%'.$targetUserGrade.'%')
                                        ->orderby('priority', 'asc')//昇順：優先順位の高い順に・・・
                                        ->orderby('id', 'asc')//優先順位が同じだった場合の担保
                                        ->get();
            if(empty($wordCardCategories) || $wordCardCategories->count() < 1){
                $this->logwrite('info', '今回出題カテゴリ無し');
                return false;
            }

            // 1-1-3 前回出題カテゴリから出題カテゴリを判定して特定する
            if(empty($assignedCategoryId)){
                //前回出題の カテゴリid 無し：先頭のカテゴリidを採用
                $targetWordCardCategoryId = $wordCardCategories->first()->id;

            }else{
                //前回出題の カテゴリid あり：前回出題のカテゴリ(以前)は除外する
                $targetWordCardCategory = $wordCardCategories->first(function ($row, $key) use($assignedCategoryId){
                    return ($row->id > $assignedCategoryId);
                });
                //もし最後尾まで達した場合は、先頭に戻す
                if(empty($targetWordCardCategory)){
                    $targetWordCardCategory = $wordCardCategories->first();
                }
                $targetWordCardCategoryId = $targetWordCardCategory->id;
            }

            $this->logwrite('info', '今回出題カテゴリ: '.$targetWordCardCategoryId);


            // 1-2 出題問題を取得する
            $wordCards = WordCard::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('word_card_category_id', $targetWordCardCategoryId)//出題対象となるカテゴリ・・・
                                ->orderby('id')
                                ->get();
            if(empty($wordCards) || $wordCards->count() < 1){
                //該当問題無し
                $this->logwrite('info', '該当する出題問題無し');
                return false;
            }

            /*
             * 2 対象の曜日にタスク生成
             */
            // 2-1 tasks生成
            $targetTaskIds = []; //該当曜日に出題されるtaskIds
            foreach($wordCards as $taskOrder => $wordCard){
                $task = new Task();
                $task->user_id      = $targetUserId;
                $task->task_type    = $targetTaskType;
                $task->cand_task_id = $wordCard->id;
                $task->task_order   = $taskOrder + 1; //連番を設定
                $task->is_selected  = TaskSelectedType::SELECTED;
                $task->is_deleted   = IsDeletedType::ACTIVE;
                $task->created_at   = Carbon::now();
                $task->save();
                $targetTaskIds[] = $task->id;
            }//foreach
            $this->logwrite('info', 'tasks出力 : targetDayNumber: ' . $targetDayNumber
                                    . ' 件数: ' . count($targetTaskIds).'件'
                                    . ' targetTaskIds: ' . var_export($targetTaskIds, true)
                            );

            // 2-2 user_task_periods生成
            // 2-2-1 出題枠Noを決定する(間隔があいている場合も考慮する)
            $targetCellNumber = $this->getTaskCellNoForUserTaskPeriod($targetUserId, $targetDayNumber);
            $this->logwrite('info', 'userTaskPeriod出力準備: '
                                        . ' targetUserId: '        . $targetUserId
                                        . ' targetDayNumber: '     . $targetDayNumber
                                        . ' targetCellNumber: '    . $targetCellNumber );

            $targetUserTaskPeriodIds = [];
            foreach($targetTaskIds as $targetTaskId){
                $userTaskPeriod = new UserTaskPeriod();
                $userTaskPeriod->user_id           = $targetUserId;
                $userTaskPeriod->day_number        = $targetDayNumber;
                $userTaskPeriod->task_type         = $targetTaskType;
                $userTaskPeriod->task_id           = $targetTaskId;
                $userTaskPeriod->task_period_order = $targetCellNumber; //出題枠Noを設定
                $userTaskPeriod->comment           = Carbon::now() . ' 出題ロジックにより自動出題';
                $userTaskPeriod->is_deleted        = IsDeletedType::ACTIVE;
                $userTaskPeriod->created_at        = Carbon::now();
                $userTaskPeriod->save();
                $targetUserTaskPeriodIds[] = $userTaskPeriod->id;
            }//foreach
            $this->logwrite('info', 'userTaskPeriod出力結果: '
                                        . ' targetUserId: '        . $targetUserId
                                        . ' targetDayNumber: '     . $targetDayNumber
                                        . ' targetCellNumber: '    . $targetCellNumber
                                        . ' UserTaskPeriod件数: '  . count($targetUserTaskPeriodIds).'件'
                                        . ' UserTaskPeriodIds: '   . var_export($targetUserTaskPeriodIds, true)
                            );
            $results = [
                'targetUserId'         => $targetUserId,
                'targetDayNumber'      => $targetDayNumber,
                'targetCellNumber'     => $targetCellNumber,
                '該当曜日の出力taskIds' => $targetTaskIds,
                '該当曜日の出力userTaskPeriodIds' => $targetUserTaskPeriodIds,
            ];

            /*
             * 3 後処理
             * 出題後は UserWordCardAssign の
             * word_card_category_id, assigned_dateを最新の状態に更新しておく
             */
            if(empty($userWordCardAssign) || $userWordCardAssign->count() < 1){
                //新規作成
                $userWordCardAssign = new UserWordCardAssign();
                $userWordCardAssign->user_id                = $targetUserId;
                $userWordCardAssign->word_card_category_id  = $targetWordCardCategoryId;
                $userWordCardAssign->assigned_date          = Carbon::now();
                $userWordCardAssign->is_deleted             = IsDeletedType::ACTIVE;
                $userWordCardAssign->created_at             = Carbon::now();
                $userWordCardAssign->save();

            }else{
                //更新
                $userWordCardAssign->word_card_category_id  = $targetWordCardCategoryId;
                $userWordCardAssign->assigned_date          = Carbon::now();
                $userWordCardAssign->updated_at             = Carbon::now();
                $userWordCardAssign->save();
            }

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }

    /**
     * Listeningについて、指定された曜日に
     * タスク周期レコード(user_task_periods)を生成する
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param int $targetDayNumber
     * @param array $userAssesmentInfo
     * @param string $targetAssessmentItemName
     * @throws Exception
     */
    private function createUserTaskPeriodRecordForListening(
                                                    $targetTaskType,
                                                    $targetUserId,
                                                    $targetDayNumber,
                                                    $userAssesmentInfo,
                                                    $targetAssessmentItemName )
    {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');
        $this->logwrite('info', 'Create Listening UserTaskPeriod: '
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetDayNumber: ' . $targetDayNumber
                                . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                        );
        try{
            //対象ユーザの該当するassessmentItem(Listening)についてのgradeを取得
            $targetUserGrade = '';
            $assesments = $userAssesmentInfo['assesments'];
            $targetUserAssesment = $assesments->where('category', $targetAssessmentItemName)->first();
            if(array_key_exists('grade', $targetUserAssesment)){
                $targetUserGrade = trim($targetUserAssesment['grade']);
            }
            if(empty($targetUserGrade) || strlen($targetUserGrade) < 1){
                //grade情報が無ければ処理をskipする(即時にreturn)
                $this->logwrite('info', 'targetUser has no grade data: '
                                        . ' targetTaskType: '  . $targetTaskType
                                        . ' targetUserId: '    . $targetUserId
                                        . ' targetAssessmentItemName: ' . $targetAssessmentItemName
                                );
                return;
            }
            $this->logwrite('info', 'targetUserGrade: '
                                    . ' targetTaskType: ' . $targetTaskType
                                    . ' targetUserId: '   . $targetUserId
                                    . ' Category: '       . $targetAssessmentItemName
                                    . ' Grade: '          . $targetUserGrade
                            );

            //宿題タスクの生成（tasksレコード、user_task_periodsレコードの出力）
            $results = [];
            $ret = $this->createListeningTaskRecord(
                                                $targetTaskType,
                                                $targetUserId,
                                                $targetUserGrade,
                                                $targetDayNumber,
                                                $results
                                            );
            $this->logwrite('info', 'ListeningTask生成結果: '
                                . ' ret: ' . ( ($ret) ? 'true' : 'false' )
                                . ' targetTaskType: '  . $targetTaskType
                                . ' targetUserId: '    . $targetUserId
                                . ' targetUserGrade: ' . $targetUserGrade
                                . ' targetDayNumber: ' . $targetDayNumber
                                . ' results: '         . var_export($results, true)
                            );

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }
    /**
     * リスニングタスクの生成（tasksレコード、user_task_periodsレコードの出力）
     *
     * @param int $targetTaskType
     * @param int $targetUserId
     * @param string $targetUserGrade
     * @param int $targetDayNumber
     * @param array &$results
     * @return bool
     * @throws Exception
     */
    private function createListeningTaskRecord(
                                                $targetTaskType,
                                                $targetUserId,
                                                $targetUserGrade,
                                                $targetDayNumber,
                                                &$results )
    {
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        try{
            /*
             * Listeningタスクの取得
             */
            //出題問題数を取得
            if($targetTaskType == TaskType::TASK_TYPE_LISTENING_B){
                $createTaskNum = env('CREATE_TASK_NUMBER_LISTENING_B');
                $listeningType = 'B';
                $listeningTypeName = 'LISTENING_B';

            }elseif($targetTaskType == TaskType::TASK_TYPE_LISTENING_C){
                $createTaskNum = env('CREATE_TASK_NUMBER_LISTENING_C');
                $listeningType = 'C';
                $listeningTypeName = 'LISTENING_C';

            }elseif($targetTaskType == TaskType::TASK_TYPE_LISTENING_D){
                $createTaskNum = env('CREATE_TASK_NUMBER_LISTENING_D');
                $listeningType = 'D';
                $listeningTypeName = 'LISTENING_D';
            }

            $this->logwrite('info', 'ListeningTaskRecord生成: '
                                    . ' listeningTypeName: ' . $listeningTypeName
                                    . ' targetUserId: '      . $targetUserId
                                    . ' targetUserGrade: '   . $targetUserGrade
                                    . ' targetDayNumber: '   . $targetDayNumber
                            );

            // 直前の出題idを取得
            $assignedTaskId = 0;
            $userListeningAssign = UserListeningAssign::where('is_deleted', IsDeletedType::ACTIVE)
                                                    ->where('user_id', $targetUserId)
                                                    ->where('listening_type', $listeningType)
                                                    ->orderby('assigned_date', 'desc')
                                                    ->first();
            if(!empty($userListeningAssign) && $userListeningAssign->count() > 0){
                $assignedTaskId = $userListeningAssign->assigned_id;
            }
            $this->logwrite('info', 'Listening 直前出題タスクid: '
                                    . ' targetUserId: '       . $targetUserId
                                    . ' targetTaskType: '     . $targetTaskType
                                    . ' targetTaskTypeName: ' . $listeningTypeName
                                    . ' assignedTaskId: '     . $assignedTaskId
                            );

            //Listening該当区分の問題を全件取得する
            //（注）「最後尾での折り返し」に対応するため、前回idまでをskipすることはしないことに注意する！
            $listeningTasks = ListeningTask::where('is_deleted', IsDeletedType::ACTIVE)
                                            ->where('listening_type', $listeningType) //出題対象となる区分([B|C|D])
                                            ->orderby('id') //ここは必ずid順
                                            ->get();
            if(empty($listeningTasks) || $listeningTasks->count() < 1){
                $this->logwrite('info', 'Listening 出題問題無し');
                return false;
            }

            $dataCnt = 0;
            $listeningTaskIds = [];
            foreach($listeningTasks as $listeningTask){
                if( $listeningTask->id <= $assignedTaskId ){
                    continue;//前回のtaskIdまではskipする
                }
                if( $dataCnt < $createTaskNum ){
                    $listeningTaskIds[] = $listeningTask->id;
                    $dataCnt++;
                }
                if( $dataCnt >= $createTaskNum ){
                    break;
                }
            }//foreach
            /*
             * 最後尾まで出題した場合は折り返して、再び先頭から出題する。
             * ただし重複はしない。
             */
            if($dataCnt < $createTaskNum){
                //再び先頭から出題する
                foreach($listeningTasks as $listeningTask){
                    //重複を避けるためのチェック
                    if( ! in_array($listeningTask->id, $listeningTaskIds) ){
                        $listeningTaskIds[] = $listeningTask->id;
                        $dataCnt++;
                    }
                    if( $dataCnt >= $createTaskNum ){
                        break;
                    }
                }//foreach
            }//end of if

            if(empty($listeningTaskIds) || count($listeningTaskIds) < 1){
                $this->logwrite('info', 'Listening 出題問題無し');
                return false;
            }

            $this->logwrite('info', 'Listening 出題タスク: '
                                    . ' 出題件数: ' . count($listeningTaskIds) .'件'
                                    . ' listening_tasks.id: ' . var_export($listeningTaskIds, true)
                            );

            // 出題問題を取得する
            $sortRawSql = 'FIELD(id,' . implode(',', $listeningTaskIds) . ')';
            $targetListeningTasks = ListeningTask::where('is_deleted', IsDeletedType::ACTIVE)
                                                ->whereIn('id', $listeningTaskIds)
                                                ->orderby(DB::Raw($sortRawSql)) //ここは指定された順序
                                                ->get();

            if(empty($targetListeningTasks) || $targetListeningTasks->count() < 1){
                //該当問題無し
                $this->logwrite('info', 'Listening 出題問題無し');
                return false;
            }

            /*
             * 対象の曜日にタスク生成
             */
            // tasks生成
            $targetTaskIds = []; //該当曜日に出題されるtaskIds
            foreach($targetListeningTasks as $taskOrder => $targetListeningTask){
                $task = new Task();
                $task->user_id      = $targetUserId;
                $task->task_type    = $targetTaskType;
                $task->cand_task_id = $targetListeningTask->id;
                $task->task_order   = $taskOrder + 1; //連番を設定
                $task->is_selected  = TaskSelectedType::SELECTED;
                $task->is_deleted   = IsDeletedType::ACTIVE;
                $task->created_at   = Carbon::now();
                $task->save();
                $targetTaskIds[] = $task->id;
            }//foreach
            $this->logwrite('info', 'tasks出力 : targetDayNumber: ' . $targetDayNumber
                                    . ' 件数: ' . count($targetTaskIds).'件'
                                    . ' targetTaskIds: ' . var_export($targetTaskIds, true)
                            );

            // user_task_periods生成
            // 出題枠Noを決定する(間隔があいている場合も考慮する)
            $targetCellNumber = $this->getTaskCellNoForUserTaskPeriod($targetUserId, $targetDayNumber);
            $this->logwrite('info', 'userTaskPeriod出力準備: '
                                        . ' targetUserId: '        . $targetUserId
                                        . ' targetDayNumber: '     . $targetDayNumber
                                        . ' targetCellNumber: '    . $targetCellNumber );

            $targetUserTaskPeriodIds = [];
            foreach($targetTaskIds as $targetTaskId){
                $userTaskPeriod = new UserTaskPeriod();
                $userTaskPeriod->user_id           = $targetUserId;
                $userTaskPeriod->day_number        = $targetDayNumber;
                $userTaskPeriod->task_type         = $targetTaskType;
                $userTaskPeriod->task_id           = $targetTaskId;
                $userTaskPeriod->task_period_order = $targetCellNumber; //出題枠Noを設定
                $userTaskPeriod->comment           = Carbon::now() . ' 出題ロジックにより自動出題';
                $userTaskPeriod->is_deleted        = IsDeletedType::ACTIVE;
                $userTaskPeriod->created_at        = Carbon::now();
                $userTaskPeriod->save();
                $targetUserTaskPeriodIds[] = $userTaskPeriod->id;
            }//foreach
            $this->logwrite('info', 'userTaskPeriod出力結果: '
                                        . ' targetUserId: '        . $targetUserId
                                        . ' targetDayNumber: '     . $targetDayNumber
                                        . ' targetCellNumber: '    . $targetCellNumber
                                        . ' UserTaskPeriod件数: '  . count($targetUserTaskPeriodIds).'件'
                                        . ' UserTaskPeriodIds: '   . var_export($targetUserTaskPeriodIds, true)
                            );
            $results = [
                'targetUserId'         => $targetUserId,
                'targetDayNumber'      => $targetDayNumber,
                'targetCellNumber'     => $targetCellNumber,
                '該当曜日の出力taskIds' => $targetTaskIds,
                '該当曜日の出力userTaskPeriodIds' => $targetUserTaskPeriodIds,
            ];

            /*
             * 3 後処理
             * 出題後は UserListeningAssign の
             * assigned_id, assigned_dateを最新の状態に更新しておく
             */
            if(empty($userListeningAssign) || $userListeningAssign->count() < 1){
                //新規作成
                $userListeningAssign = new UserListeningAssign();
                $userListeningAssign->user_id                = $targetUserId;
                $userListeningAssign->listening_type         = $listeningType;
                $userListeningAssign->assigned_id            = end($listeningTaskIds);
                $userListeningAssign->assigned_date          = Carbon::now();
                $userListeningAssign->is_deleted             = IsDeletedType::ACTIVE;
                $userListeningAssign->created_at             = Carbon::now();
                $userListeningAssign->save();

            }else{
                //更新
                $userListeningAssign->assigned_id            = end($listeningTaskIds);
                $userListeningAssign->assigned_date          = Carbon::now();
                $userListeningAssign->updated_at             = Carbon::now();
                $userListeningAssign->save();
            }

        } catch (\Exception $ex) {
            throw $ex;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }


    /**
     * user_task_periodsレコード作成時に、
     * 指定されたユーザについて、指定された曜日番号の出題枠Noを決定する
     * (注) 間隔があいている場合も考慮する
     *
     * @param type $userId
     * @param type $dayNumber
     * @return int
     */
    public function getTaskCellNoForUserTaskPeriod($userId, $dayNumber){

        $userTaskPeriods = UserTaskPeriod::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->where('day_number', $dayNumber)
                            ->select(['task_period_order'])
                            ->orderby('task_period_order', 'asc')
                            ->distinct()
                            ->get();

        if(empty($userTaskPeriods) || $userTaskPeriods->count() < 1){
            $taskCellNumber = 1;
            return $taskCellNumber;
        }

        $taskPeriodOrders = [];//現在のtaskPeriodOrderNoの集合
        foreach($userTaskPeriods as $userTaskPeriod){
            $taskPeriodOrders[] = intval($userTaskPeriod->task_period_order);
        }

        //本来は $taskCellNumber は 1 から 5 までの範囲であるが、50までとしておく
        for($taskCellNumber = 1; $taskCellNumber <= 50; $taskCellNumber++){
            $idx = $taskCellNumber - 1;//配列のキー
            if( array_key_exists($idx, $taskPeriodOrders) ){
                if($taskCellNumber < $taskPeriodOrders[$idx] ){
                    //枠Noが飛んでいる場合はそこに埋める
                    return $taskCellNumber;
                }else{
                    continue;
                }
            }else{
                return $taskCellNumber;
            }
        }

    }

    /**
     * 2018-12-30
     * 出題カレンダー作成時に、
     * 指定されたユーザについて、指定された曜日番号の出題枠Noを決定する
     * (注) 間隔があいている場合も考慮する
     *
     * @param type $userId
     * @param type $dayNumber
     * @return int $taskCellNumber
     * @see $this->getTaskCellNo
     */
    private function getTaskCellNoForUserTaskCalendar($userId, $dayNumber){

        /*
         * 間が空いている場合があるため、まず全件を取得してから、
         * 上から順に調べて、空いている枠Noを取得する。
         */
        $userTaskCalendars = UserTaskCalendar::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->where('day_number', $dayNumber)
                            ->select(['task_period_order'])
                            ->orderby('task_period_order', 'asc')
                            ->distinct()
                            ->get();

        if(empty($userTaskCalendars) || $userTaskCalendars->count() < 1){
            $taskCellNumber = 1;
            return $taskCellNumber;
        }

        //現在のtaskPeriodOrderNoの集合を取得する
        $taskPeriodOrders = [];
        foreach($userTaskCalendars as $userTaskCalendar){
            $taskPeriodOrders[] = intval($userTaskCalendar->task_period_order);
        }

        //本来は $taskCellNumber は 1 から 5 までの範囲であるが、50までとしておく
        for($taskCellNumber = 1; $taskCellNumber <= 50; $taskCellNumber++){
            $idx = $taskCellNumber - 1;//配列のキーなので -1 する
            //枠Noが飛んでいる場合はそこに埋める
            if( array_key_exists($idx, $taskPeriodOrders) ){
                if($taskCellNumber < $taskPeriodOrders[$idx] ){
                    return $taskCellNumber;
                }else{
                    continue;
                }
            }else{
                return $taskCellNumber;
            }
        }//for

    }

    /**
     * 全ユーザのアセスメント情報を取得する
     *
     * @return Collection $userAssesmentInfos
     */
    private function getUserAssesmentInfo(){
        $userAssesmentInfos = collect();
        $dateStr = Carbon::now()->toDateString(); //本日の日付け取得（年月日のみの文字列）

        $users =  User::where('is_deleted', IsDeletedType::ACTIVE)
                        ->whereNull('resigned_at') //退会していないユーザのみを対象とする
                        ->whereDate('opened_at', '<=', $dateStr) //契約が有効なユーザーのみを対象とする：契約開始日
                        ->whereDate('closed_at', '>=', $dateStr) //契約が有効なユーザーのみを対象とする：契約終了日
                        ->orderby('id')
                        ->get();

        foreach($users as $user){
            //各ユーザ毎に最新のアセスメント情報を取得する（最新の１レコードのみを取得）
            $userId = $user->id;
            $userScore =  UserScore::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('user_id', $userId) //該当のユーザーで...
                                    ->whereNotNull('result')    //アセスメント情報が格納されていて...
                                    ->where('result', '<>', '') //アセスメント情報が格納されていて...
                                    ->whereNotNull('assessment_ended_at')    //アセスメント日付が格納されていて...
                                    ->orderby('assessment_ended_at', 'desc') //最新のアセスメント情報を取得
                                    ->orderby('id', 'desc') //同一日付けの場合はより後のレコードを取得する...
                                    ->select(['result'])
                                    ->first();  //最新の１レコードのみを取得する...

            if(empty($userScore) || $userScore->count() < 1){
                continue;
            }
            $resultJson = $userScore->result; //スコア情報を取得
            $assesments = json_decode($resultJson, true);
            if(count($assesments) > 0){
                $assesmentCollection = collect();
                foreach($assesments as $assesment){
                    $category = '';
                    $grade = '';
                    if(array_key_exists('category', $assesment)){
                        $category = $assesment['category'];
                    }
                    if(array_key_exists('grade', $assesment)){
                        $grade = $assesment['grade'];
                    }

                    $assesmentCollection->push([
                        'category' => $category,
                        'grade' => $grade
                    ]);
                }//foreach

                $userAssesmentInfos->push([
                    'userId' => $userId,
                    'assesments' => $assesmentCollection
                ]);
            }

        }//foreach

        return $userAssesmentInfos;
    }

    /**
     * 指定されたタスク種別のtask_settings情報を取得する
     * @param int $taskTypeId
     * @return type
     */
    private function getTaskSettingInfo($taskTypeId){

        $taskSettings = TaskSetting::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('day_number', '>=', 1)
                                ->where('day_number', '<=', 7)
                                ->where('task_type', $taskTypeId)
                                ->orderby('assessment_item')
                                ->orderby('grade')
                                ->orderby('day_number')
                                ->get();

        $taskTypeBreak = '';
        $assessmentItemBreak = '';
        $gradeBreak = '';

        $dayNumbers = [];
        $dayNumbersPerGrade = [];

        $taskType = '';
        $assessmentItem = '';
        $grade = '';
        $dayNumber = '';

        $taskSettingInfos = collect();
        $taskSettingInfo = collect();

        //該当データが無い場合は空のcollectionを返却する
        if(empty($taskSettings) || $taskSettings->count() < 1){
            $this->logwrite('info', __FUNCTION__.': there is no TaskSettings Data');
            return $taskSettingInfos;
        }

        foreach($taskSettings as $taskSetting){

            //データ取得
            $taskType = $taskSetting->task_type;
            $assessmentItem = $taskSetting->assessment_item;
            if($assessmentItem == 'Attitude 2'){
               $assessmentItem =  'Attitude';
            }
            $grade = $taskSetting->grade;
            $dayNumber = $taskSetting->day_number;


            if(empty($taskTypeBreak)){
                $taskSettingInfo = collect();
                $taskSettingInfo->put('taskType', $taskType);
                //初期化
                $taskTypeBreak = $taskType;
                $assessmentItemBreak = '';
                $gradeBreak = '';
                $dayNumbers = [];
                $dayNumbersPerGrade = [];

            }elseif($taskTypeBreak != $taskType){
                //データ保持
                $taskSettingInfo->put('dayNumbersPerGrade', $dayNumbersPerGrade);
                $taskSettingInfos->push($taskSettingInfo);

                //初期化
                $taskSettingInfo = collect();
                $taskSettingInfo->put('taskType', $taskType);

                $taskTypeBreak = $taskType;
                $assessmentItemBreak = '';
                $gradeBreak = '';
                $dayNumbers = [];
                $dayNumbersPerGrade = [];
            }

            if($assessmentItemBreak != $assessmentItem){
                $taskSettingInfo->put('assessmentItem', $assessmentItem);
                //初期化
                $assessmentItemBreak = $assessmentItem;
                $gradeBreak = '';
                $dayNumbers = [];
                $dayNumbersPerGrade = [];
            }

            if(empty($gradeBreak)){
                $dayNumbers = [];
                $dayNumbers[] = $dayNumber;
                $gradeBreak = $grade;

                if($grade == 'E以上'){
                    $dayNumbersPerGrade = array_merge(
                            $dayNumbersPerGrade,
                            [
                                'S' => $dayNumbers, //2018-08-02 追加
                                'A' => $dayNumbers,
                                'B' => $dayNumbers,
                                'C' => $dayNumbers,
                                'D' => $dayNumbers,
                                'E' => $dayNumbers,
                            ]
                    );

                }else{
                    $dayNumbersPerGrade = array_merge(
                            $dayNumbersPerGrade,
                            [$grade => $dayNumbers]
                    );
                }

            }elseif($gradeBreak != $grade){

                $taskSettingInfo->put('dayNumbersPerGrade', $dayNumbersPerGrade);

                $dayNumbers = [];
                $dayNumbers[] = $dayNumber;
                $gradeBreak = $grade;

                if($grade == 'E以上'){
                    $dayNumbersPerGrade = array_merge(
                            $dayNumbersPerGrade,
                            [
                                'S' => $dayNumbers, //2018-08-02 追加
                                'A' => $dayNumbers,
                                'B' => $dayNumbers,
                                'C' => $dayNumbers,
                                'D' => $dayNumbers,
                                'E' => $dayNumbers,
                            ]
                    );

                }else{
                    $dayNumbersPerGrade = array_merge(
                            $dayNumbersPerGrade,
                            [$grade => $dayNumbers]
                    );
                }

            }else{
                if(!in_array($dayNumber, $dayNumbers, true)){
                    $dayNumbers[] = $dayNumber;
                }

                if($grade == 'E以上'){
                    $dayNumbersPerGrade = array_merge(
                            $dayNumbersPerGrade,
                            [
                                'S' => $dayNumbers, //2018-08-02 追加
                                'A' => $dayNumbers,
                                'B' => $dayNumbers,
                                'C' => $dayNumbers,
                                'D' => $dayNumbers,
                                'E' => $dayNumbers,
                            ]
                    );

                }else{
                    $dayNumbersPerGrade = array_merge(
                            $dayNumbersPerGrade,
                            [$grade => $dayNumbers]
                    );
                }
            }

        }//foreach

        //Loop後処理
        //データ保持
        $taskSettingInfo->put('dayNumbersPerGrade', $dayNumbersPerGrade);
        $taskSettingInfos->push($taskSettingInfo);

        return $taskSettingInfos;
    }

    /**
     * 週一で起動する問題作成バッチに先立ち、
     * 毎週分の出題カレンダー情報を全てクリアする。
     */
    public function resetTaskData(){
        $this->logwrite('info', 'reset Task Data start');

        /*
         * tasksのリセット
         * ①問題作成時には tasksについて、is_selected=0 のものは論理削除する。
         * →Pythonによって生成されたが「選択されずに残っている問題」は論理削除する。
         * ②is_selected=1 のものは論理削除しない。
         *
         */
        Task::where('is_deleted', IsDeletedType::ACTIVE)
                ->where('is_selected', TaskSelectedType::RESERVED)
                ->update([
                    'is_deleted' => IsDeletedType::DELETED,
                    'deleted_at' => Carbon::now()
                ]);

        //user_task_periodsは全件を論理削除
        UserTaskPeriod::where('is_deleted', IsDeletedType::ACTIVE)
                ->update([
                    'is_deleted' => IsDeletedType::DELETED,
                    'deleted_at' => Carbon::now()
                ]);

        $this->logwrite('info', 'reset Task Data finish');
        return true;
    }

    /**
     * タスク種別毎のAssessmentItem名の一覧を取得する
     */
    private function getAssessmentItemNameByTaskType(){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        $assessmentItemNameByTaskTypes = [];
        $taskSettings = TaskSetting::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('task_type', '>=', 1)
                                    ->groupby('task_type')
                                    ->select(['task_type', 'assessment_item'])
                                    ->get();
        if(empty($taskSettings) || $taskSettings->count() < 1){
            return $assessmentItemNameByTaskTypes;
        }
        foreach($taskSettings as $taskSetting){
            $assessmentItemNameByTaskTypes[$taskSetting->task_type] = $taskSetting->assessment_item;
        }
        $this->logwrite('info', 'assessmentItemNameByTaskTypes: '
                                . var_export($assessmentItemNameByTaskTypes, true));
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return $assessmentItemNameByTaskTypes;
    }


}

