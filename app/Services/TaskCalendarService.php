<?php
namespace App\Services;

use App\Enums\IsDeletedType;
use App\Enums\AccountKindType;
use App\Enums\QuestionType;
use App\Enums\UserTaskStatusType;
use App\Enums\UserMessageLogMessageType;
use App\Enums\UserMessageLogCwPostType;
use App\Enums\UserMessageLogConnectType;
use App\Enums\UserMessageLogLinkedType;
use App\Enums\UserTaskStaffType;
use App\Enums\TaskSelectedType;
use App\Enums\TaskType;
use App\Enums\MemberReportGrammarRateColorType;
use App\Enums\UserLearningPolicyTagType;

use App\Models\Account;
use App\Models\LpCategory;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserLearningPolicy;
use App\Models\UserGoal;
use App\Models\UserScore;
use App\Models\UserOutput;
use App\Models\UserLessonLog;
use App\Models\UserSession;
use App\Models\UserTask;
use App\Models\UserTaskStaff;
use App\Models\UserFeedback;
use App\Models\UserMessageLog;
use App\Models\UserTaskPeriod;
use App\Models\Task;
use \App\Models\Grammar;
use \App\Models\Vocabulary;
use App\Models\SpeakingRally;
use App\Models\Review;
use App\Models\OtherTask;
use App\Models\SrTask;
use App\Models\UserCounselor;
use App\Models\MemberReport;
use App\Models\MemberReportChart;
use App\Models\MemberReportGsampleData;
use App\Models\MemberReportGrammarRate;
use App\Models\UserTaskProgress;
use App\Models\UserLessonProgress;
use App\Models\UserTaskCalendar;
use App\Models\UserSrAssign;


use App\Dtos\UserDto;
use App\Dtos\MemberDetailDto;
use App\Dtos\UserLearningPolicyDto;
use App\Dtos\UserGoalDto;
use App\Dtos\UserScoreDto;
use App\Dtos\UserOutputDto;
use App\Dtos\UserOutputHistoryDto;
use App\Dtos\UserLessonLogDto;
use App\Dtos\TaskDto;
use App\Dtos\QuestionDto;
use App\Dtos\UserTaskDto;
use App\Dtos\UserMessageLogDto;
use App\Dtos\CounselorDto;
use App\Dtos\LpCategoryDto;
use App\Dtos\UserTaskProgressDto;
use App\Dtos\UserLessonProgressDto;
use App\Dtos\TaskListPerWeekDto;
use App\Dtos\TaskListPerDayDto;
use App\Dtos\TaskListPerCellDto;
use App\Dtos\TaskListPerQuestionDto;
use App\Dtos\TaskAssignDto;
use App\Dtos\TaskMasterDto;
use App\Dtos\MemberReportDto;

use App\Utils\CommonUtils;
use App\Utils\DateUtils;
use Carbon\Carbon;

use S3Service;

use DB;
use Log;

/**
 * MemberTaskService
 *
 * 2018-12-24
 * app\Services\AlueCommandService\AlueIntegCreateQuestionService.php から切り出した出題関連ロジック
 */
class TaskCalendarService {

    public function __construct(){
    }

    /**
     * SpeakingRallyについて出題タスクを生成
     *
     * @param type $targetUserId
     * @param type $targetDayNumber
     * @param type $targetCellNumber
     * @param type $userAssesmentInfos
     * @return boolean
     */
    public function createSpeakingRallyTasks($targetUserId, $targetDayNumber, $targetCellNumber, $userAssesmentInfos){
        Log::info(__CLASS__.':'.__FUNCTION__.' start');

        $taskType = TaskType::TASK_SPEAKING_RALLY;

        //宿題タスクの生成（tasksレコード、user_task_calendarsレコードの出力）
        $results = [];
        $ret = $this->createSpeakingRallyTaskRecord($taskType, $targetUserId, $targetDayNumber, $targetCellNumber, $results);
        if($ret){
            $logMsg = 'SpeakingRallyTask生成:'
                    . ' ret: true'
                    . ' userId: ' . $targetUserId
                    . ' targetDayNumber: ' . $targetDayNumber
                    . ' targetCellNumber: ' . $targetCellNumber
                    . ' results: ' . var_export($results, true);
        }else{
            $logMsg = 'SpeakingRallyTask生成:'
                    . ' ret: false'
                    . ' userId: ' . $targetUserId
                    . ' targetDayNumber: ' . $targetDayNumber
                    . ' targetCellNumber: ' . $targetCellNumber;
        }
        Log::info($logMsg);

        Log::info(__CLASS__.':'.__FUNCTION__.' finish');
    }
    /**
     * SpeakingRallyタスクの生成
     * （tasksレコード、user_task_periodsレコードの出力）
     *
     * @param int $taskType
     * @param int $targetUserId
     * @param array $targetDayNumber
     * @param int $targetCellNumber
     * @param array &$results
     * @return bool
     * @throws Exception
     */
    public function createSpeakingRallyTaskRecord($taskType, $targetUserId, $targetDayNumber, $targetCellNumber, &$results){
        Log::info(__CLASS__.':'.__FUNCTION__.' start');

        try{
            // 1 出題カテゴリを取得
            $targetCategory = 'business'; //とりあえず business 固定
            $targetCategoryId = '2'; //とりあえず business 固定
            Log::info('出題カテゴリ: '.$targetCategoryId . ' 出題カテゴリ名: '.$targetCategory);

            $logMsg = 'SpeakingRallyTaskRecord生成: '
                    . ' userId: ' . $targetUserId
                    . ' assignedCategory: ' . $targetCategory
                    . ' targetDayNumber: ' . $targetDayNumber
                    . ' targetCellNumber: ' . $targetCellNumber;
            Log::info($logMsg);


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
            Log::info('直前出題タスク: taskType: '.$taskType
                                    . ' assignedTaskId: '. $assignedTaskId);


            // 3 出題問題を取得する
            $speakingRally = SpeakingRally::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('sr_category_id', $targetCategoryId)//出題対象となるカテゴリ
                                ->where('id', '>', $assignedTaskId) //直前出題タスクの次から
                                ->orderby('id')
                                ->first();
            if(empty($speakingRally) || $speakingRally->count() < 1){
                //該当問題無し
                Log::info('該当する出題問題無し');
                return false;
            }
            $targetSpeakingRallyId = $speakingRally->id;


            /*
             * 4 対象の曜日にタスク生成
             */
            // 4-1 tasks生成
            //$targetTaskIds = null; //該当曜日に出題されるtaskIds(SRでは1件のみである)
            $task = new Task();
            $task->user_id      = $targetUserId;
            $task->task_type    = $taskType;
            $task->cand_task_id = $targetSpeakingRallyId;
            $task->task_order   = 1; //SRでは1件のみである
            $task->is_selected  = TaskSelectedType::SELECTED;
            $task->is_deleted   = IsDeletedType::ACTIVE;
            $task->created_at   = Carbon::now();
            $task->save();
            $targetTaskId = $task->id;
            Log::info('tasks出力 : targetDayNumber: '.$targetDayNumber
                                    . ' 件数: 1件');

            // 4-2 user_task_calendars生成
            Log::info('userTaskPeriod出力: userId: ' . $targetUserId
                                        . ' targetDayNumber: '  . $targetDayNumber
                                        . ' targetCellNumber: ' . $targetCellNumber );

            // 4-2-2 user_task_calendars出力
            $userTaskCalendar = new UserTaskCalendar();
            $userTaskCalendar->user_id           = $targetUserId;
            $userTaskCalendar->day_number        = $targetDayNumber;
            $userTaskCalendar->task_type         = $taskType;
            $userTaskCalendar->task_id           = $targetTaskId;
            $userTaskCalendar->task_period_order = $targetCellNumber; //出題枠Noを設定
            $userTaskCalendar->is_deleted        = IsDeletedType::ACTIVE;
            $userTaskCalendar->created_at        = Carbon::now();
            $userTaskCalendar->save();
            $targetUserTaskCalendarId = $userTaskCalendar->id;
            Log::info('userTaskCalendar出力: userId: ' . $targetUserId
                                        . ' targetDayNumber: '  . $targetDayNumber
                                        . ' targetCellNumber: ' . $targetCellNumber
                                        . ' targetUserTaskCalendarId: ' . $targetUserTaskCalendarId
                                        . ' UserTaskCalendar件数: 1件');

            $results[] = [
                'targetUserId' => $targetUserId,
                'targetDayNumber' => $targetDayNumber,
                'targetCellNumber' => $targetCellNumber,
                '該当曜日の出力taskIds' => $targetTaskId,
                '該当曜日の出力userTaskCalendarIds' => $targetUserTaskCalendarId,
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
                $userSrAssign->assigned_id      = $targetSpeakingRallyId;
                $userSrAssign->assigned_date    = Carbon::now();
                $userSrAssign->is_deleted       = IsDeletedType::ACTIVE;
                $userSrAssign->created_at       = Carbon::now();
                $userSrAssign->save();

            }else{
                //更新
                $userSrAssign->assigned_id      = $targetSpeakingRallyId;
                $userSrAssign->assigned_date    = Carbon::now();
                $userSrAssign->updated_at       = Carbon::now();
                $userSrAssign->save();
            }

        } catch (\Exception $ex) {
            throw $ex;
        }

        Log::info(__CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }





    /**
     * 指定ユーザのアセスメント情報を取得する
     */
    public function getUserAssesmentInfo($userId){
        $userAssesmentInfos = collect();
        $dateStr = Carbon::now()->toDateString(); //本日の日付け取得（年月日のみの文字列）

        $users =  User::where('is_deleted', IsDeletedType::ACTIVE)
                        ->whereNull('resigned_at') //退会していないユーザのみを対象とする
                        ->whereDate('opened_at', '<=', $dateStr) //契約が有効なユーザーのみを対象とする：契約開始日
                        ->whereDate('closed_at', '>=', $dateStr) //契約が有効なユーザーのみを対象とする：契約終了日
                        ->where('id', $userId)
                        ->orderby('id')
                        ->get();

        if(empty($users) || $users->count() < 1){
            return $userAssesmentInfos;
        }

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
            Log::info(__FUNCTION__.': there is no TaskSettings Data');
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
     * 指定されたユーザについて、指定された曜日番号の出題枠Noを決定する
     * (注) 間隔があいている場合も考慮する
     *
     * @param type $userId
     * @param type $dayNumber
     * @return int
     */
    public function getTaskCellNo($userId, $dayNumber){

        $userTaskCalendars = UserTaskCalendar::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->where('day_number', $dayNumber)
                            ->select('task_period_order')
                            ->orderby('task_period_order', 'asc')
                            ->distinct()
                            ->get();

        if(empty($userTaskCalendars) || $userTaskCalendars->count() < 1){
            $taskCellNumber = 1;
            return $taskCellNumber;
        }

        $taskPeriodOrders = [];//現在のtaskPeriodOrderNoの集合
        foreach($userTaskCalendars as $userTaskCalendar){
            $taskPeriodOrders[] = intval($userTaskCalendar->task_period_order);
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

}

