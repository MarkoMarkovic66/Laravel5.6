<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;

use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserTask;
use App\Models\UserScore;
use App\Models\UserLessonLog;
use App\Models\UserTaskProgress;
use App\Models\UserLessonProgress;
use App\Models\UserTaskStatistics;
use App\Models\OtherTask;

use App\Enums\IsDeletedType;
use App\Enums\UserTaskStatusType;
use App\Enums\TaskType;

use App\Dtos\UserTaskStatisticsDto;
use App\Dtos\UserTaskStatisticsPerPeriodDto;
use App\Dtos\UserSessionStatisticsPerPeriodDto;
use App\Dtos\UserLessonStatisticsPerPeriodDto;

use App\Utils\DateUtils;
use Carbon\Carbon;
use Exception;
use DB;

use \CpChart\Data as pData;
use \CpChart\Image as pImage;
use \CpChart\Draw as pDraw;


/**
 * AlueIntegAchievementCountService
 * レッスン実績集計バッチ関連サービス
 */
class AlueIntegAchievementCountService {

    use AlueCommandLogTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;

    private $calcScale = 4; //計算途中での小数点以下の桁数
    private $lastScale = 1; //最終的に丸める小数点以下の桁数

    public function __construct()
    {
        $this->signature = 'AlueIntegAchievementCountService';
        $this->commandName = 'AlueIntegAchievementCountService';
        $this->initAlueCommandLogTrait('console/achievementCount.log');
    }

    /**
     * Main
     */
    public function exec(&$resultMessage){

        $this->logwrite('info', 'AlueIntegAchievementCountService start');

        DB::beginTransaction();
        try {

            //集計対象ユーザーを取得
            $dateStr = Carbon::now()->toDateString(); //本日の年月日 (年月日のみの文字列)

            /*
             * 2018-11-12 修正
             * 有効なユーザーの判定を「契約期間：users.opened_at/closed_at」から
             * 「レッスン開始/終了：user_plans.lesson_start_at/lesson_end_at」に変更した。
             */
            $targetUsers = User::from('users as usr')
                            ->join('user_plans as pln', 'pln.user_id', 'usr.id')
                            ->where('usr.is_deleted', IsDeletedType::ACTIVE)
                            ->where('pln.is_deleted', IsDeletedType::ACTIVE)
                            ->whereNull('usr.resigned_at') //退会していないユーザのみを対象とする
                            ->whereNotNull('usr.opened_at') //契約が有効なユーザーのみを対象とする：契約開始日
                            ->whereDate('pln.lesson_start_at', '<=', $dateStr) //有効なユーザーのみを対象とする：レッスン開始日
                            ->whereDate('pln.lesson_end_at', '>=', $dateStr)   //有効なユーザーのみを対象とする：レッスン終了日
                            ->select([
                                        'usr.id as user_id',
                                        'usr.arugo_user_id',
                                        'usr.cw_room_id',
                                        'usr.cw_room_name',
                                        'usr.first_name',
                                        'usr.last_name'
                                    ])
                            ->distinct()
                            ->orderby('usr.id', 'ASC')
                            ->get();

            if(empty($targetUsers) || $targetUsers->count() < 1){
                DB::rollback();
                $this->logwrite('info', 'AlueIntegAchievementCountService exec: none targetUsers');
                return true;
            }//end if


            foreach($targetUsers as $targetUser){
                $alugoUserId = $targetUser->arugo_user_id;
                if(empty($alugoUserId)){
                   continue;
                }

                $this->logwrite('info', 'AlueIntegAchievementCountService exec:'
                                . ' userId:'. $targetUser->user_id
                                . ' alugoUserId:'. $targetUser->arugo_user_id
                        );

                //レッスン消化数を集計しDBに登録する
                $this->countupLessonProgress($targetUser);

                //出題タスク消化進捗を集計しDBに登録する(全期間での、タスク種別毎の完了数、完了比率を集計する)
                $this->countupTaskProgress($targetUser);

                //出題タスク統計値を集計しDBに登録する(週毎の、タスク種別毎の完了数、完了比率を集計する)
                if(! $this->countupTaskStatistics($targetUser) ){
                    $this->logwrite('debug', '$this->countupTaskStatistics is FALSE');
                }

            }//foreach

            DB::commit();

            //統計グラフ画像を出力しDBに登録する
            $this->putStatisticsChart();

        } catch (\Exception $ex) {
            DB::rollback();
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', 'AlueIntegAchievementCountService finish');
        return true;
    }

    /**
     * 指定されたユーザのレッスン消化数を集計する
     * @param User $targetUser
     * @throws Exception
     */
    private function countupLessonProgress($targetUser){

        $userId = $targetUser->user_id;
        $alugoUserId = $targetUser->arugo_user_id;

        try {
            $this->logwrite('info', 'countupLessonProgress: User単位処理: start:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId );

            $ticketsUsed      = 0; // A:消化済みチケット枚数
            $elapsedDays      = 0; // B:受講開始からの経過日数
            $ticketsTotal     = 0; // C:全チケット枚数
            $contractTermDays = 0; // D:受講期間契約日数
            $ticketsUsedRate  = 0; // E:チケット消化率

            /*
             * A:消化済みチケット枚数
             * user_lesson_logs　にて、Lesson typeを「1」でフィルタリングした上で、idの数をカウント
             */
            $ticketsUsed = UserLessonLog::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('user_id', $userId)
                                        ->where('lesson_type', '1')
                                        ->count('id');

            /*
             * B:受講開始からの経過日数
             * today - plan.MEC.lesson_start_at
             *
             * C:全チケット枚数
             * plan.MEC.lesson_ticket
             *
             * D:受講期間契約日数
             * plan.MEC.lesson_end_at - plan.MEC.lesson_start_at
             */
            $userPlan = UserPlan::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId)
                                ->select(['lesson_start_at', 'lesson_end_at', 'lesson_ticket'])
                                ->orderby('lesson_start_at', 'DESC')
                                ->first();

            if(!empty($userPlan) && $userPlan->count() > 0){
                $lessonStartAt = $userPlan->lesson_start_at;
                $lessonEndAt   = $userPlan->lesson_end_at;
                $lessonTicket  = $userPlan->lesson_ticket;

                $lessonStartDate = Carbon::parse($lessonStartAt);
                $lessonEndDate   = Carbon::parse($lessonEndAt);

                // 経過日数を取得
                $today = Carbon::now();
                $elapsedDays = $today->diffInDays($lessonStartDate); //受講開始からの経過日数

                // 全チケット枚数を取得
                $ticketsTotal = empty($lessonTicket) ? 0: $lessonTicket; //全チケット枚数

                // 受講期間契約日数を取得
                $contractTermDays = $lessonEndDate->diffInDays($lessonStartDate); //受講期間契約日数

            }

            /*
             * E:チケット消化率
             * 計算ロジック：（A／B）／（C／D）
             */

            if( $ticketsUsed      == 0 ||
                $elapsedDays      == 0 ||
                $ticketsTotal     == 0 ||
                $contractTermDays == 0 )
            {
                $ticketsUsedRate  = 0; // E チケット消化率

            }else{

                // $data1 = (A:消化済みチケット枚数 / B:受講開始からの経過日数)
                if(intval($elapsedDays) > 0){
                    $data1 = bcdiv( strval($ticketsUsed), strVal($elapsedDays), $this->calcScale);
                }else{
                    $data1 = 0;
                }

                // $data2 = (C:全チケット枚数 / D:受講期間契約日数)
                if(intval($contractTermDays) > 0){
                    $data2 = bcdiv( strval($ticketsTotal), strVal($contractTermDays), $this->calcScale);
                }else{
                    $data2 = 0;
                }

                // E:チケット消化率 = ($data1 / $data2)
                if( floatval($data2) > 0){
                    $ticketsUsedRate = bcdiv( $data1, $data2, $this->lastScale);
                }else{
                    $ticketsUsedRate = 0;
                }
            }
            $this->logwrite('info', 'A:消化済みチケット枚数: ticketsUsed: ' . $ticketsUsed);
            $this->logwrite('info', 'B:受講開始からの経過日数: elapsedDays: ' . $elapsedDays);
            $this->logwrite('info', 'C:全チケット枚数: ticketsTotal: ' . $ticketsTotal);
            $this->logwrite('info', 'D:受講期間契約日数: contractTermDays: ' . $contractTermDays);
            $this->logwrite('info', 'E:チケット消化率: ticketsUsedRate: ' . $ticketsUsedRate);

            /*
             * DB登録
             */
            $userLessonProgressId = UserLessonProgress::insertGetId([
                                        'user_id'               => $userId,
                                        'alugo_user_id'         => $alugoUserId,
                                        'counted_at'            => Carbon::now(),
                                        'contract_term_days'    => $contractTermDays,
                                        'elapsed_days'          => $elapsedDays,
                                        'tickets_total'         => $ticketsTotal,
                                        'tickets_used'          => $ticketsUsed,
                                        'tickets_used_rate'     => $ticketsUsedRate,
                                        'remark'                => null,
                                        'is_deleted'            => IsDeletedType::ACTIVE,
                                        'created_at'            => Carbon::now(),
                                        'updated_at'            => Carbon::now(),
                                    ]);

        } catch (\Exception $ex) {
            $this->logwrite('error', 'countupLessonProgress: User単位処理: error:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId
                                . ' ' . $ex->getMessage());
            throw $ex;
        }

        $this->logwrite('info', 'countupLessonProgress: User単位処理: end:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId
                                . ' userLessonProgressId:'.$userLessonProgressId );

    }

    /**
     * 指定されたユーザの出題タスク消化進捗を集計する
     * ※全期間での、タスク種別毎の完了数、完了比率を集計する
     *
     * @param User $targetUser
     * @throws Exception
     */
    private function countupTaskProgress($targetUser){

        $userId = $targetUser->user_id;
        $alugoUserId = $targetUser->arugo_user_id;

        try {
            $this->logwrite('info', 'countupTaskProgress: User単位処理: start:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId );

            /*
             * 各タスク種別毎の集計処理
             */
            //当該ユーザにこれまで配信した全タスク数を、タスク種別毎に集計する
            $allTaskCounts = UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->select(DB::raw('task_type as taskType, COUNT(id) AS taskCount'))
                                    ->groupby('task_type')
                                    ->orderby('task_type')
                                    ->get();
            $taskCounts = [];
            foreach($allTaskCounts as $allTaskCount){
                $taskCounts[$allTaskCount->taskType] = [
                    'taskType'      => $allTaskCount->taskType,
                    'sentTaskCount' => $allTaskCount->taskCount,// A:配信済みタスク数
                    'completedTaskCount' => 0,                  // B:完了タスク数
                    'completedTaskRate'  => 0,                  // C:完了タスク比率
                ];
            }

            //当該ユーザにこれまで配信したタスクのうち、完了したタスク数を、タスク種別毎に集計する
            $completedTaskCounts = UserTask::where('is_deleted', IsDeletedType::ACTIVE)
                                    ->where('is_completed', UserTaskStatusType::TASK_COMPLETED)
                                    ->select(DB::raw('task_type as taskType, COUNT(id) AS taskCount'))
                                    ->groupby('task_type')
                                    ->orderby('task_type')
                                    ->get();

            foreach($completedTaskCounts as $completedTaskCount){
                if(array_key_exists($completedTaskCount->taskType, $taskCounts)){

                    $targetTaskType = $completedTaskCount->taskType;

                    /*
                     * タスク完了比率算出
                     * タスク完了比率 = (B:完了タスク数  / A:配信済みタスク数)
                     */
                    $completedCnt = strval($completedTaskCount->taskCount);
                    $sentCnt = strval($taskCounts[$targetTaskType]['sentTaskCount']);
                    if( intval($sentCnt) > 0){
                        $completedTaskRate = bcdiv( strval($completedCnt), strVal($sentCnt), $this->lastScale);
                    }else{
                        $completedTaskRate = 0;
                    }

                    /*
                     * 算出値を格納する
                     */
                    $taskCounts[$targetTaskType]['completedTaskCount'] = intval($completedCnt); // B:完了タスク数
                    $taskCounts[$targetTaskType]['completedTaskRate']  = $completedTaskRate;    // C:完了タスク比率

                }//end if

            }//foreach

            /*
             * DB登録
             */
            foreach($taskCounts as $taskCount){

                $taskType           = $taskCount['taskType'];
                $sentTaskCount      = array_key_exists('sentTaskCount', $taskCount)
                                        ? (empty($taskCount['sentTaskCount']) ? 0 : $taskCount['sentTaskCount'])
                                        : 0;
                $completedTaskCount = array_key_exists('completedTaskCount', $taskCount)
                                        ? (empty($taskCount['completedTaskCount']) ? 0 : $taskCount['completedTaskCount'])
                                        : 0;
                $completedTaskRate  = array_key_exists('completedTaskRate', $taskCount)
                                        ? (empty($taskCount['completedTaskRate']) ? 0 : $taskCount['completedTaskRate'])
                                        : 0;

                $userTaskProgressId = UserTaskProgress::insertGetId([
                                        'user_id'               => $userId,
                                        'alugo_user_id'         => $alugoUserId,
                                        'counted_at'            => Carbon::now(),
                                        'task_type'             => $taskType,
                                        'sent_task_count'       => $sentTaskCount,
                                        'completed_task_count'  => $completedTaskCount,
                                        'completed_task_rate'   => $completedTaskRate,
                                        'remark'                => null,
                                        'is_deleted'            => IsDeletedType::ACTIVE,
                                        'created_at'            => Carbon::now(),
                                        'updated_at'            => Carbon::now(),
                                    ]);

                $this->logwrite('info', 'countupTaskProgress: 集計値:'
                                        . ' userId:'.$userId
                                        . ' alugoUserId:'.$alugoUserId
                                        . ' taskType:'.$taskType
                                        . ' sentTaskCount:'.$sentTaskCount
                                        . ' completedTaskCount:'.$completedTaskCount
                                        . ' completedTaskRate:'.$completedTaskRate
                                        . ' userTaskProgressId:'.$userTaskProgressId );
            }//foreach

        } catch (\Exception $ex) {
            $this->logwrite('error', 'countupTaskProgress: User単位処理: error:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId
                                . ' ' . $ex->getMessage());
            throw $ex;
        }

        $this->logwrite('info', 'countupTaskProgress: User単位処理: end:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId );
    }

    /**
     * 指定されたユーザの出題タスク統計値を集計する
     * ※週毎の、タスク種別毎の完了数、完了比率を集計する
     *
     * @param User $targetUser
     * @throws Exception
     */
    private function countupTaskStatistics($targetUser){

        $userId = $targetUser->user_id;
        $alugoUserId = $targetUser->arugo_user_id;

        try {
            $this->logwrite('info', 'countupTaskStatistics: User単位処理: start:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId );

            //当該会員の集計期間を取得する
            $userTaskStatisticsDto = $this->getStatisticsPeriodNew2($userId);
            if( ! $userTaskStatisticsDto ){
                $this->logwrite('debug', 'countupTaskStatistics: User単位処理: '
                                . ' userId:'.$userId
                                . ' $this->getStatisticsPeriodNew2 is FALSE');
                return false;
            }
            $this->logwrite('debug', 'countupTaskStatistics: User単位処理: '
                            . ' userId:'.$userId
                            . ' $this->getStatisticsPeriodNew2 is TRUE');

            $userTaskStatisticsDto->alugoUserId = $alugoUserId;


            //2018-08-20 セッション数ではなくレッスン数を集計するためコメントアウト
            //当該会員の集計期間におけるセッション数集計を実施する
            //$this->doSessionStatistics($userTaskStatisticsDto);
            //当該会員のセッション数集計結果をDB出力する
            //$this->putSessionStatistics($userTaskStatisticsDto);
            //
            //2018-08-20 追加 レッスン数集計
            //当該会員の集計期間におけるレッスン数集計を実施する
            $this->doLessonStatistics($userTaskStatisticsDto);
            //当該会員のレッスン数集計結果をDB出力する
            $this->putLessonStatistics($userTaskStatisticsDto);


            //当該会員の集計期間におけるタスク集計を実施する
            $this->doTaskStatistics($userTaskStatisticsDto);
            //当該会員のタスク集計結果をDB出力する
            $this->putTaskStatistics($userTaskStatisticsDto);

        } catch (\Exception $ex) {
            $this->logwrite('error', 'countupTaskStatistics: User単位処理: error:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId
                                . ' ' . $ex->getMessage());
            throw $ex;
        }

        $this->logwrite('info', 'countupTaskStatistics: User単位処理: end:'
                                . ' userId:'.$userId
                                . ' alugoUserId:'.$alugoUserId );

        return true;
    }

    /**
     * 2018-08-22 仕様変更のため再作成
     * 当該会員の集計期間を7日間(日曜 - 土曜)の単位で取得する
     *
     * 2018-10-17 集計期間の週単位を変更
     * 当該会員の集計期間を7日間(月曜 - 日曜)の単位で取得する
     *
     * 2018-11-12 修正
     * 有効なユーザーの判定を「契約期間：users.opened_at/closed_at」から
     *  「レッスン開始/終了：user_plans.lesson_start_at/lesson_end_at」に変更した。
     *
     */
    private function getStatisticsPeriodNew2($userId){
        $this->logwrite('info', 'getStatisticsPeriodNew2: start: userId:'.$userId);

        /*
         * 2018-08-03
         * ・集計期間始点：契約開始日(planMEC.start_at)
         * ・集計期間終点：契約終了日(planMEC.end_at)
         *
        $user = User::where('is_deleted', IsDeletedType::ACTIVE)
                        ->where('id', $userId)
                        ->first();
         */
        /*
         * 2018-11-12 修正
         * 有効なユーザーの判定を「契約期間：users.opened_at/closed_at」から
         * 「レッスン開始/終了：user_plans.lesson_start_at/lesson_end_at」に変更した。
         */
        $userPlan = UserPlan::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId)
                            ->select(['lesson_start_at', 'lesson_end_at'])
                            ->orderby('lesson_start_at', 'DESC')
                            ->first();

        if(empty($userPlan) || $userPlan->count() < 1){
            $this->logwrite('info', 'getStatisticsPeriodNew2: userId:'.$userId
                                . ' UserPlanレコード not found'
                            );
            return false;
        }

        //集計開始日
        $countupSinceDate = new Carbon($userPlan->lesson_start_at);
        //集計終了日
        $countupUntilDate = new Carbon($userPlan->lesson_end_at);

        $this->logwrite('debug', 'getStatisticsPeriodNew2: userId:'.$userId
                            . ' countupSinceDate:'.$countupSinceDate
                            . ' countupUntilDate:'.$countupUntilDate
                        );

        Carbon::setWeekStartsAt(Carbon::MONDAY); // 週の最初を月曜日に設定
        Carbon::setWeekEndsAt(Carbon::SUNDAY);   // 週の最後を日曜日に設定

        //第1週は変則的な日付範囲となる
        $statFirstSinceDate = $countupSinceDate; //集計開始日は開始日
        $statFirstUntilDate = $countupSinceDate->copy()->endOfWeek(); //集計開始日の週の日曜日を取得
        $statFirstMonday = $countupSinceDate->copy()->startOfWeek();  //集計開始日の週の月曜日
        $statNextMonday  = $statFirstMonday->copy()->addDay(7); //集計開始日の週の次週の月曜日

        //最終週も変則的な日付範囲となる
        $statLastSinceDate = $countupUntilDate->copy()->startOfWeek(); //集計終了日の週の月曜日を取得
        $statLastUntilDate = $countupUntilDate; //集計終了日は終了日
        $statLastSunday = $countupUntilDate->copy()->endOfWeek();    //集計終了日の週のの日曜日
        $statBeforeLastSunday = $statLastSunday->copy()->subDay(7); //集計終了日の週の前週の日曜日


        $userTaskStatisticsDto = new UserTaskStatisticsDto();
        $userTaskStatisticsDto->userId = $userId;
        $userTaskStatisticsDto->statSinceDate = $countupSinceDate;//集計期間開始日
        $userTaskStatisticsDto->statUntilDate = $countupUntilDate;//集計期間終了日

        //集計日間隔を生成する
        $statDatePeriods = [];
        //第1週は変則的な日付範囲となる
        $statDatePeriods[] = [
            'since' => $statFirstSinceDate, //集計開始日は開始日
            'until' => $statFirstUntilDate  //集計開始日の週の日曜日
        ];

        $dt = $statNextMonday; //ここから開始
        while($dt->lte($statBeforeLastSunday)){
            $dtSince = $dt->copy()->setTime(0, 0, 0);               //月曜
            $dtUntil = $dt->copy()->addDay(6)->setTime(23, 59, 59); //日曜
            $statDatePeriods[] = [
                'since' => $dtSince, //月曜
                'until' => $dtUntil  //日曜
            ];
            $dt->addDay(7);//次の月曜日
        }

        //最終週は変則的な日付範囲となる
        $statDatePeriods[] = [
            'since' => $statLastSinceDate, //集計終了日の週の月曜日
            'until' => $statLastUntilDate  //集計終了日は終了日
        ];

        $userTaskStatisticsDto->statDatePeriods = $statDatePeriods;

        $this->logwrite('info', 'getStatisticsPeriodNew2: end: userId:'.$userId);
        return $userTaskStatisticsDto;
    }

    /**
     * @deprecated
     * 2018-08-22 さらに仕様変更のためdeprecated
     * 当該会員の集計期間を7日間(日曜 - 土曜)の単位で取得する
     */
    private function getStatisticsPeriodNew($userId){
        $this->logwrite('info', 'getStatisticsPeriodNew: start: userId:'.$userId);

        /*
         * 2018-08-03
         * ・集計期間始点：レポート発行日 - 60日（2カ月前）
         * ・集計期間終点：レポート発行日 + 14日（2週間後）
         */
        //集計期間範囲指定：会員レポート発行日からの前後日数
        $beforeDays = env('BATCH_STATISTICS_BEFORE_DAYS');
        $afterDays = env('BATCH_STATISTICS_AFTER_DAYS');
        //レポート発行日(稼働当日の日付け)
        $reportIssueDate = Carbon::now();
        //集計開始日
        $countupSinceDate = $reportIssueDate->copy()->subDay($beforeDays);
        //集計終了日
        $countupUntilDate = $reportIssueDate->copy()->addDay($afterDays);

        $this->logwrite('debug', 'getStatisticsPeriodNew: userId:'.$userId
                            . ' reportIssueDate:'.$reportIssueDate
                            . ' countupSinceDate:'.$countupSinceDate
                            . ' countupUntilDate:'.$countupUntilDate
                        );

        Carbon::setWeekStartsAt(Carbon::SUNDAY); // 週の最初を日曜日に設定
        Carbon::setWeekEndsAt(Carbon::SATURDAY); // 週の最後を土曜日に設定

        //第1週は変則的な日付範囲となる
        $statFirstSinceDate = $countupSinceDate; //集計開始日は開始日
        $statFirstUntilDate = $countupSinceDate->copy()->endOfWeek(); //集計開始日の週の土曜日を取得
        $statFirstSunday = $countupSinceDate->copy()->startOfWeek();  //集計開始週の日曜日
        $statNextSunday  = $statFirstSunday->addDay(7);//次の日曜日

        //最終週も変則的な日付範囲となる
        $statLastSinceDate = $countupUntilDate->copy()->startOfWeek(); //集計終了日の週の日曜日を取得
        $statLastUntilDate = $countupUntilDate; //集計終了日は終了日
        $statLastSaturday = $countupUntilDate->copy()->endOfWeek();    //集計終了週の土曜日
        $statBeforeLastSaturday = $statLastSaturday->subDay(7);//前の土曜日


        $userTaskStatisticsDto = new UserTaskStatisticsDto();
        $userTaskStatisticsDto->userId = $userId;
        $userTaskStatisticsDto->statSinceDate = $countupSinceDate;//集計期間開始日
        $userTaskStatisticsDto->statUntilDate = $countupUntilDate;//集計期間終了日

        //集計日間隔を生成する
        $statDatePeriods = [];
        //第1週は変則的な日付範囲となる
        $statDatePeriods[] = [
            'since' => $statFirstSinceDate, //日曜
            'until' => $statFirstUntilDate  //土曜
        ];

        $dt = $statNextSunday; //ここから開始
        while($dt->lte($statBeforeLastSaturday)){
            $dtSince = $dt->copy()->setTime(0, 0, 0);               //日曜
            $dtUntil = $dt->copy()->addDay(6)->setTime(23, 59, 59); //土曜
            $statDatePeriods[] = [
                'since' => $dtSince, //日曜
                'until' => $dtUntil  //土曜
            ];
            $dt->addDay(7);//次の日曜日
        }

        //最終週は変則的な日付範囲となる
        $statDatePeriods[] = [
            'since' => $statLastSinceDate, //日曜
            'until' => $statLastUntilDate  //土曜
        ];

        $userTaskStatisticsDto->statDatePeriods = $statDatePeriods;

        $this->logwrite('info', 'getStatisticsPeriodNew: end: userId:'.$userId);
        return $userTaskStatisticsDto;
    }

    /**
     * @deprecated
     * 2018-08-03 集計期間範囲の判定について仕様変更のため
     * 本メソッドはdeprecated
     *
     * 当該会員の集計期間を7日間(日曜 - 土曜)の単位で取得する
     */
    private function getStatisticsPeriod($userId){

        $this->logwrite('info', 'getStatisticsPeriod: start: userId:'.$userId);

        $userScore = UserScore::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('user_id', $userId);

        $planLessonStartAt = '';
        $isUserScoreOneRecord = false;
        $userScoreCount = $userScore->count();
        if($userScoreCount < 2){
            $isUserScoreOneRecord = true;
        }

        $userScores = $userScore
                        ->select([
                                    DB::raw('MIN(assessment_started_at) as minStartedAt, '
                                          . 'MAX(assessment_started_at) as maxStartedAt, '
                                          . 'MIN(assessment_ended_at) as minEndedAt, '
                                          . 'MAX(assessment_ended_at) as maxEndedAt  '
                                            )
                                ])
                        ->where('user_id', $userId)
                        ->first();

        if(!empty($userScores) && $userScores->count() > 0){
            $minStartedAt = $userScores->minStartedAt;
            $maxStartedAt = $userScores->maxStartedAt;
            $minEndedAt   = $userScores->minEndedAt;
            $maxEndedAt   = $userScores->maxEndedAt;
        }

        $this->logwrite('debug', 'getStatisticsPeriod: userId:'.$userId
                                . ' minStartedAt:'.$minStartedAt
                                . ' maxStartedAt:'.$maxStartedAt
                                . ' minEndedAt:'.$minEndedAt
                                . ' maxEndedAt:'.$maxEndedAt
                            );


        if(empty($minStartedAt)){
            $this->logwrite('info', 'countupTaskStatistics: User単位処理: error:'
                                . ' userId:'.$userId
                                . ' ' .  'UserScoresにassessment_started_at情報がありません');
            return false;
        }
        if(empty($maxEndedAt)){
            $this->logwrite('info', 'countupTaskStatistics: User単位処理: error:'
                                . ' userId:'.$userId
                                . ' ' .  'UserScoresにassessment_end_at情報がありません');
            return false;
        }


        if($isUserScoreOneRecord){
            //UserScoresが1レコードのみの場合 → PlanMECのlesson_start_atを採用する

            $userPlan = UserPlan::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('user_id', $userId)
                                ->select(['lesson_start_at', 'lesson_end_at'])
                                ->orderby('lesson_start_at', 'DESC')
                                ->first();

            if(!empty($userPlan) && $userPlan->count() > 0){
                $planLessonStartAt = $userPlan->lesson_start_at;
            }
            if(empty($planLessonStartAt)){
                $this->logwrite('info', 'countupTaskStatistics: User単位処理: error:'
                                    . ' userId:'.$userId
                                    . ' ' .  'UserPlansにlesson_start_at情報がありません');
                return false;
            }

            $cbUserPlanStartedAt = new Carbon($planLessonStartAt);
            $lessonStartDate = $cbUserPlanStartedAt;

        }else{
            //UserScoresが複数レコードの場合 → UserScoreのassessment_started_atを採用する
            $lessonStartDate = new Carbon($minStartedAt);
        }

        //集計終了日
        $lessonEndDate = new Carbon($maxEndedAt);

        $this->logwrite('debug', 'getStatisticsPeriod: userId:'.$userId
                            . ' lessonStartDate:'.$lessonStartDate
                            . ' lessonEndDate:'.$lessonEndDate
                        );

        Carbon::setWeekStartsAt(Carbon::SUNDAY); // 週の最初を日曜日に設定
        Carbon::setWeekEndsAt(Carbon::SATURDAY); // 週の最後を土曜日に設定

        //第1週は変則的な日付範囲となる
        $statFirstSinceDate = $lessonStartDate; //集計開始日は開始日
        $statFirstUntilDate = $lessonStartDate->copy()->endOfWeek(); //集計開始日の週の土曜日を取得
        $statFirstSunday = $lessonStartDate->copy()->startOfWeek();  //集計開始週の日曜日
        $statNextSunday  = $statFirstSunday->addDay(7);//次の日曜日

        //最終週も変則的な日付範囲となる
        $statLastSinceDate = $lessonEndDate->copy()->startOfWeek(); //集計終了日の週の日曜日を取得
        $statLastUntilDate = $lessonEndDate; //集計終了日は終了日
        $statLastSaturday = $lessonEndDate->copy()->endOfWeek();    //集計終了週の土曜日
        $statBeforeLastSaturday = $statLastSaturday->subDay(7);//前の土曜日


        $userTaskStatisticsDto = new UserTaskStatisticsDto();
        $userTaskStatisticsDto->userId = $userId;
        $userTaskStatisticsDto->statSinceDate = $lessonStartDate;//集計期間開始日
        $userTaskStatisticsDto->statUntilDate = $lessonEndDate;//集計期間終了日

        //集計日間隔を生成する
        $statDatePeriods = [];
        //第1週は変則的な日付範囲となる
        $statDatePeriods[] = [
            'since' => $statFirstSinceDate, //日曜
            'until' => $statFirstUntilDate  //土曜
        ];

        $dt = $statNextSunday; //ここから開始
        while($dt->lte($statBeforeLastSaturday)){
            $statDatePeriods[] = [
                'since' => $dt->copy(),             //日曜
                'until' => $dt->copy()->addDay(6)   //土曜
            ];
            $dt->addDay(7);//次の日曜日
        }

        //最終週は変則的な日付範囲となる
        $statDatePeriods[] = [
            'since' => $statLastSinceDate, //日曜
            'until' => $statLastUntilDate  //土曜
        ];

        $userTaskStatisticsDto->statDatePeriods = $statDatePeriods;

        $this->logwrite('info', 'getStatisticsPeriod: end: userId:'.$userId);

        return $userTaskStatisticsDto;
    }

    /**
     * 当該会員の集計期間における集計を実施する
     * @param type $userTaskStatisticsDto
     */
    private function doTaskStatistics(&$userTaskStatisticsDto){

        $userId = $userTaskStatisticsDto->userId;
        $statDatePeriods = $userTaskStatisticsDto->statDatePeriods;

        $this->logwrite('info', 'doTaskStatistics: start: userId:'.$userId);

        /*
         * 集計結果の格納用に、
         * 集計範囲の全期間において出題されたタスク種別を取得する。
         * 注．タスク種別は「まとめられる」ものがあることに注意！
         */
        $chargedTasks = UserTask::from('user_tasks as utsk')
                        ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                        ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                        ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                        ->where('utsk.user_task_status', '>=', UserTaskStatusType::TASK_STATUS_VAL_ASKED)//出題済み以上のもの
                        ->where('utsk.user_id', $userId)
                        ->whereBetween('utsk.question_set_date',
                                    [
                                        $userTaskStatisticsDto->statSinceDate,  //集計期間開始日
                                        $userTaskStatisticsDto->statUntilDate,  //集計期間終了日
                                    ])
                        ->select(['tsk.task_type'])->distinct()
                        ->orderby('tsk.task_type')
                        ->get();

        $allTaskTypes = [];
        foreach($chargedTasks as $chargedTask){

            $taskType = strval($chargedTask->task_type);

            /*
             * 2018-10-16 タスク種別を統合アプリ用に変更した
             */
            if( $taskType == TaskType::TASK_GRAMMAR || $taskType == TaskType::TASK_VOCABULARY ){
                $taskType = '400';  //瞬間口頭G, V はまとめる

            }elseif( $taskType == TaskType::TASK_SPEAKING_RALLY ){
                //SpeakingRally

            }elseif( $taskType == TaskType::TASK_REVIEW ){
                //(旧)復習

            }elseif( $taskType == '5' || $taskType == '6' || $taskType == '7' ){
                $taskType = '500';  //(旧)(その他)Listening B, C, D はまとめる

            }elseif( $taskType == '9' || $taskType == '10' || $taskType == '11' || $taskType == '12' ){
                $taskType = '600';  //(旧)(その他)50 Core Sentence A, B, C, D はまとめる

            }elseif( $taskType == '8' || $taskType == '13' ){
                $taskType = '700';  //(旧)(その他)Speaking Rally投稿 はまとめる

            }elseif( $taskType == TaskType::TASK_TYPE_RV_LESSON ){
                // 30 : (統合アプリ)レッスン復習

            }elseif( $taskType == TaskType::TASK_TYPE_WORD_CARD ){
                // 40 : (統合アプリ)単語カード

            }elseif( $taskType == TaskType::TASK_TYPE_LISTENING_B || // 50 : リスニングトレーニング B
                     $taskType == TaskType::TASK_TYPE_LISTENING_C || // 51 : リスニングトレーニング C
                     $taskType == TaskType::TASK_TYPE_LISTENING_D ){ // 52 : リスニングトレーニング D
                $taskType = '510';  //(統合アプリ)Listening B, C, D はまとめる

            }else{
                //統合アプリではこれら以外のタスク種別は集計対象外となる
                continue;
            }

            if(!in_array($taskType, $allTaskTypes)){
                $allTaskTypes[] = $taskType;
            }

        }//foreach
        $this->logwrite('debug', 'doTaskStatistics: userId:'.$userId
                               . ' 集計期間中タスク種別数:'. count($allTaskTypes)
                               . ' 集計期間中タスク種別:'. var_export($allTaskTypes, true)
                        );

        if(count($allTaskTypes) < 1){
            return false; //集計期間中に該当タスクなし
        }


        foreach((array)$statDatePeriods as $datePeriod){
            $sinceDate = $datePeriod['since'];
            $untilDate = $datePeriod['until'];

            $userTaskStatisticsPerPeriodDto = new UserTaskStatisticsPerPeriodDto();
            $userTaskStatisticsPerPeriodDto->sinceDate = $sinceDate;
            $userTaskStatisticsPerPeriodDto->untilDate = $untilDate;

            $this->logwrite('debug', 'doTaskStatistics: userId: '.$userId
                                    . ' sinceDate: '.$sinceDate
                                    . ' untilDate: '.$untilDate );

            //集計用エリアを準備
            $statResults = $this->prepareStatWorkArea($allTaskTypes);

            /*
             * 集計実行
             */
            //その週に出題されたタスク
            $userChargedTasks = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('utsk.user_task_status', '>=', UserTaskStatusType::TASK_STATUS_VAL_ASKED)//出題済み以上のもの
                            ->where('utsk.user_id', $userId)
                            ->whereBetween('utsk.question_set_date', [$sinceDate, $untilDate])
                            ->orderby('utsk.id')
                            ->get();

            if(!empty($userChargedTasks) && $userChargedTasks->count() > 0){

                foreach($userChargedTasks as $userChargedTask){

                    $taskType = strval($userChargedTask->task_type);

                    /*
                     * 2018-10-16 タスク種別を統合アプリ用に変更した
                     */
                    if( $taskType == TaskType::TASK_GRAMMAR || $taskType == TaskType::TASK_VOCABULARY ){
                        $taskType = '400';  //瞬間口頭G, V はまとめる

                    }elseif( $taskType == TaskType::TASK_SPEAKING_RALLY ){
                        //SpeakingRally

                    }elseif( $taskType == TaskType::TASK_REVIEW ){
                        //(旧)復習

                    }elseif( $taskType == '5' || $taskType == '6' || $taskType == '7' ){
                        $taskType = '500';  //(旧)(その他)Listening B, C, D はまとめる

                    }elseif( $taskType == '9' || $taskType == '10' || $taskType == '11' || $taskType == '12' ){
                        $taskType = '600';  //(旧)(その他)50 Core Sentence A, B, C, D はまとめる

                    }elseif( $taskType == '8' || $taskType == '13' ){
                        $taskType = '700';  //(旧)(その他)Speaking Rally投稿 はまとめる

                    }elseif( $taskType == TaskType::TASK_TYPE_RV_LESSON ){
                        // 30 : (統合アプリ)レッスン復習

                    }elseif( $taskType == TaskType::TASK_TYPE_WORD_CARD ){
                        // 40 : (統合アプリ)単語カード

                    }elseif( $taskType == TaskType::TASK_TYPE_LISTENING_B || // 50 : リスニングトレーニング B
                             $taskType == TaskType::TASK_TYPE_LISTENING_C || // 51 : リスニングトレーニング C
                             $taskType == TaskType::TASK_TYPE_LISTENING_D ){ // 52 : リスニングトレーニング D
                        $taskType = '510';  //(統合アプリ)Listening B, C, D はまとめる

                    }else{
                        //統合アプリではこれら以外のタスク種別は集計対象外となる
                        continue;
                    }

                    /*
                     * タスク種別毎にカウントする
                     */
                    if(array_key_exists($taskType, $statResults)){
                        $data = $statResults[$taskType];
                        $chargedTaskCount = $data['chargedTaskCount'];
                        $data['chargedTaskCount'] = intval($chargedTaskCount) + 1;
                        $statResults[$taskType] = $data;
                        $this->logwrite('debug', 'Charged Task: taskType:'.$taskType . ' chargedTaskCount:'. $data['chargedTaskCount'] );
                    }else{
                        $statResults[$taskType] = [
                            'taskType' => $taskType,
                            'chargedTaskCount' => 1,
                            'completedTaskCount' => 0
                        ];
                        $this->logwrite('debug', 'Charged Task: taskType:'.$taskType . ' chargedTaskCount:1' );
                    }

                }//foreach

            }//end if

            //その週に完了したタスク
            $userCompletedTasks = UserTask::from('user_tasks as utsk')
                            ->join('tasks as tsk', 'utsk.task_id', 'tsk.id')
                            ->where('utsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('tsk.is_deleted', IsDeletedType::ACTIVE)
                            ->where('utsk.user_task_status', '>=', UserTaskStatusType::TASK_STATUS_VAL_ASKED)//出題済み以上のもの
                            ->where('utsk.user_id', $userId)
                            ->whereBetween('utsk.completed_at', [$sinceDate, $untilDate]) //その週に・・・
                            ->where('utsk.is_completed', UserTaskStatusType::TASK_COMPLETED)//完了したタスク・・・
                            ->orderby('utsk.id')
                            ->get();

            if(!empty($userCompletedTasks) && $userCompletedTasks->count() > 0){

                foreach($userCompletedTasks as $userCompletedTask){

                    $taskType = strval($userCompletedTask->task_type);

                    /*
                     * 2018-10-16 タスク種別を統合アプリ用に変更した
                     */
                    if( $taskType == TaskType::TASK_GRAMMAR || $taskType == TaskType::TASK_VOCABULARY ){
                        $taskType = '400';  //瞬間口頭G, V はまとめる

                    }elseif( $taskType == TaskType::TASK_SPEAKING_RALLY ){
                        //SpeakingRally

                    }elseif( $taskType == TaskType::TASK_REVIEW ){
                        //(旧)復習

                    }elseif( $taskType == '5' || $taskType == '6' || $taskType == '7' ){
                        $taskType = '500';  //(旧)(その他)Listening B, C, D はまとめる

                    }elseif( $taskType == '9' || $taskType == '10' || $taskType == '11' || $taskType == '12' ){
                        $taskType = '600';  //(旧)(その他)50 Core Sentence A, B, C, D はまとめる

                    }elseif( $taskType == '8' || $taskType == '13' ){
                        $taskType = '700';  //(旧)(その他)Speaking Rally投稿 はまとめる

                    }elseif( $taskType == TaskType::TASK_TYPE_RV_LESSON ){
                        // 30 : (統合アプリ)レッスン復習

                    }elseif( $taskType == TaskType::TASK_TYPE_WORD_CARD ){
                        // 40 : (統合アプリ)単語カード

                    }elseif( $taskType == TaskType::TASK_TYPE_LISTENING_B || // 50 : リスニングトレーニング B
                             $taskType == TaskType::TASK_TYPE_LISTENING_C || // 51 : リスニングトレーニング C
                             $taskType == TaskType::TASK_TYPE_LISTENING_D ){ // 52 : リスニングトレーニング D
                        $taskType = '510';  //(統合アプリ)Listening B, C, D はまとめる

                    }else{
                        //統合アプリではこれら以外のタスク種別は集計対象外となる
                        continue;
                    }

                    if(array_key_exists($taskType, $statResults)){
                        $data = $statResults[$taskType];
                        $completedTaskCount = $data['completedTaskCount'];
                        $data['completedTaskCount'] = intval($completedTaskCount) + 1;
                        $statResults[$taskType] = $data;
                        $this->logwrite('debug', 'Completed Task: taskType:'.$taskType . ' completedTaskCount:'. $data['completedTaskCount'] );
                    }else{
                        $this->logwrite('error', 'Completed Task: taskType:'.$taskType . ' taskType not exist in statResults');
                        continue;
                    }

                }//foreach

            }//end if

            $userTaskStatisticsPerPeriodDto->statResults = $statResults;
            $userTaskStatisticsDto->statTaskResults[] = $userTaskStatisticsPerPeriodDto;

        }//foreach

        $this->logwrite('info', 'doTaskStatistics: end: userId:'.$userId);
    }

    /**
     * 集計用workエリアを準備
     * @param type $allTaskTypes
     */
    private function prepareStatWorkArea($allTaskTypes){
        $statResults = [];
        foreach($allTaskTypes as $taskType){
            $statResults[$taskType] = [
                'taskType' => $taskType,
                'chargedTaskCount'   => 0,
                'completedTaskCount' => 0
            ];
        }
        return $statResults;
    }

    /**
     * 当該会員の集計結果をDB出力する
     * @param UserTaskStatisticsDto $userTaskStatisticsDto
     */
    private function putTaskStatistics($userTaskStatisticsDto){

        $userId = $userTaskStatisticsDto->userId;
        $alugoUserId = $userTaskStatisticsDto->alugoUserId;
        $userTaskStatisticsPerPeriodDtos = $userTaskStatisticsDto->statTaskResults;

        $this->logwrite('info', 'putTaskStatistics: start: userId:'.$userId);

        foreach($userTaskStatisticsPerPeriodDtos as $userTaskStatisticsPerPeriodDto){

            $this->logwrite('debug', 'putTaskStatistics: userId:'.$userId
                            . ' statResultCount:'.count($userTaskStatisticsPerPeriodDto->statResults));

            if(count($userTaskStatisticsPerPeriodDto->statResults) < 1){
                continue; //集計情報なし
            }
            $sinceDate = $userTaskStatisticsPerPeriodDto->sinceDate;
            $untilDate = $userTaskStatisticsPerPeriodDto->untilDate;
            $statResults = $userTaskStatisticsPerPeriodDto->statResults;

            $this->logwrite('debug', 'putTaskStatistics: userId:'.$userId
                            . ' statResultCount:'.count($userTaskStatisticsPerPeriodDto->statResults)
                            . ' sinceDate:'.$sinceDate
                            . ' untilDate:'.$untilDate
                            );

            //タスク種別単位の処理
            foreach($statResults as $statResult){

                if( ! array_key_exists('taskType', $statResult) ||
                    ! array_key_exists('chargedTaskCount', $statResult) ||
                    ! array_key_exists('completedTaskCount', $statResult) ){
                    continue; //集計情報なし
                }

                //完了比率計算
                $taskType           = $statResult['taskType'];
                $chargedTaskCount   = $statResult['chargedTaskCount'];
                $completedTaskCount = $statResult['completedTaskCount'];

                $this->logwrite('debug', 'putTaskStatistics: userId:'.$userId
                                    . ' taskType:'.$taskType
                                    . ' chargedTaskCount:'.$chargedTaskCount
                                    . ' completedTaskCount:'.$completedTaskCount
                                );

                // 完了タスク率 = (完了タスク数 / 送信タスク数)
                if(intval($chargedTaskCount) > 0){
                    $completedTaskRate = bcdiv( strval($completedTaskCount), strVal($chargedTaskCount), $this->lastScale);
                }else{
                    $completedTaskRate = 0;
                }

                $this->logwrite('info', 'タスク種別: '    . $taskType
                                    . ' 送信タスク数: '   . $chargedTaskCount
                                    . ' 完了タスク数: '   . $completedTaskCount
                                    . ' 完了タスク比率: ' . $completedTaskRate);

                $userTaskStatistics = new UserTaskStatistics();
                $userTaskStatistics->user_id			  = $userId;
                $userTaskStatistics->alugo_user_id        = $alugoUserId;
                $userTaskStatistics->counted_at           = Carbon::now();
                $userTaskStatistics->counted_since        = $sinceDate;
                $userTaskStatistics->counted_until        = $untilDate;
                $userTaskStatistics->count_type           = 2;//'集計値タイプ(1:セッション数 2:タスク数)'
                $userTaskStatistics->session_num          = null;
                $userTaskStatistics->task_type            = $taskType;
                $userTaskStatistics->sent_task_count      = strval($chargedTaskCount);
                $userTaskStatistics->completed_task_count = strval($completedTaskCount);
                $userTaskStatistics->completed_task_rate  = strval($completedTaskRate);
                $userTaskStatistics->remark               = null;
                $userTaskStatistics->is_deleted           = IsDeletedType::ACTIVE;
                $userTaskStatistics->created_at           = Carbon::now();
                $userTaskStatistics->save();

                $this->logwrite('info', 'putTaskStatistics: UserTaskStatistics.id:'.$userTaskStatistics->id);
            }//foreach

        }//foreach

        $this->logwrite('info', 'putTaskStatistics: end: userId:'.$userId);
    }


    /**
     * @deprecated
     * 当該会員の集計期間におけるセッション数集計を実施する
     * @param type $userTaskStatisticsDto
     */
    private function doSessionStatistics(&$userTaskStatisticsDto){

        $userId = $userTaskStatisticsDto->userId;
        $statDatePeriods = $userTaskStatisticsDto->statDatePeriods;

        $this->logwrite('info', 'doSessionStatistics: start: userId:'.$userId);

        foreach((array)$statDatePeriods as $datePeriod){
            $sinceDate = $datePeriod['since'];
            $untilDate = $datePeriod['until'];

            $userSessionStatisticsPerPeriodDto = new UserSessionStatisticsPerPeriodDto();
            $userSessionStatisticsPerPeriodDto->sinceDate = $sinceDate;
            $userSessionStatisticsPerPeriodDto->untilDate = $untilDate;

            //その週のセッション数を集計
            $userSessionCounts = UserLessonLog::from('user_lesson_logs as ulog')
                                    ->join('user_sessions as uss', 'uss.lesson_id', 'ulog.id')
                                    ->where('ulog.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('uss.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('ulog.lesson_type', 1)
                                    ->where('ulog.user_id', $userId)
                                    ->whereBetween('ulog.start_at', [$sinceDate, $untilDate])
                                    ->groupby('ulog.id')
                                    ->select([DB::raw('ulog.id as ulogId, count(uss.id) as sessionCount, ulog.start_at as ulogStartAt')])
                                    ->get();

            if(!empty($userSessionCounts) && $userSessionCounts->count() > 0){
                $sessionNum = 0;
                foreach($userSessionCounts as $userSessionCount){
                    $sessionNum += $userSessionCount->sessionCount;
                }
                $userSessionStatisticsPerPeriodDto->sessionNum = $sessionNum;
            }
            $userTaskStatisticsDto->statSessionResults[] = $userSessionStatisticsPerPeriodDto;
        }//foreach

    }

    /**
     * @deprecated
     * 当該会員のセッション数集計結果をDB出力する
     * @param type $userTaskStatisticsDto
     */
    private function putSessionStatistics($userTaskStatisticsDto){

        $userId = $userTaskStatisticsDto->userId;
        $alugoUserId = $userTaskStatisticsDto->alugoUserId;
        $userSessionStatisticsPerPeriodDtos = $userTaskStatisticsDto->statSessionResults;

        $this->logwrite('info', 'putSessionStatistics: start: userId:'.$userId);

        foreach($userSessionStatisticsPerPeriodDtos as $userSessionStatisticsPerPeriodDto){

            $sinceDate = $userSessionStatisticsPerPeriodDto->sinceDate;
            $untilDate = $userSessionStatisticsPerPeriodDto->untilDate;
            $sessionNum = $userSessionStatisticsPerPeriodDto->sessionNum;

            $this->logwrite('info', ' セッション数: '  . $sessionNum);

            $userTaskStatistics = new UserTaskStatistics();
            $userTaskStatistics->user_id			  = $userId;
            $userTaskStatistics->alugo_user_id        = $alugoUserId;
            $userTaskStatistics->counted_at           = Carbon::now();
            $userTaskStatistics->counted_since        = $sinceDate;
            $userTaskStatistics->counted_until        = $untilDate;
            $userTaskStatistics->count_type           = 1;//'集計値タイプ(1:セッション数 2:タスク数)'
            $userTaskStatistics->session_num          = $sessionNum;
            $userTaskStatistics->task_type            = null;
            $userTaskStatistics->sent_task_count      = null;
            $userTaskStatistics->completed_task_count = null;
            $userTaskStatistics->completed_task_rate  = null;
            $userTaskStatistics->remark               = null;
            $userTaskStatistics->is_deleted           = IsDeletedType::ACTIVE;
            $userTaskStatistics->created_at           = Carbon::now();
            $userTaskStatistics->save();

            $this->logwrite('info', 'putSessionStatistics: UserTaskStatistics.id:'.$userTaskStatistics->id);
        }//foreach

        $this->logwrite('info', 'putSessionStatistics: end: userId:'.$userId);
    }

    /**
     * 2018-08-20 追加
     * 当該会員の集計期間におけるレッスン数集計を実施する
     * @param type $userTaskStatisticsDto
     */
    private function doLessonStatistics(&$userTaskStatisticsDto){

        $userId = $userTaskStatisticsDto->userId;
        $statDatePeriods = $userTaskStatisticsDto->statDatePeriods;

        $this->logwrite('info', 'doLessonStatistics: start: userId:'.$userId);

        foreach((array)$statDatePeriods as $datePeriod){
            $sinceDate = $datePeriod['since'];
            $untilDate = $datePeriod['until'];
            $this->logwrite('debug', 'sinceDate:'.$sinceDate . ' untilDate:'.$untilDate);

            $userLessonStatisticsPerPeriodDto = new UserLessonStatisticsPerPeriodDto();
            $userLessonStatisticsPerPeriodDto->sinceDate = $sinceDate;
            $userLessonStatisticsPerPeriodDto->untilDate = $untilDate;

            //その週のレッスン数を集計
            $userLessonCount = UserLessonLog::from('user_lesson_logs as ulog')
                                    ->where('ulog.is_deleted', IsDeletedType::ACTIVE)
                                    ->where('ulog.lesson_type', 1) //lesson_type = 1 のものを集計
                                    ->where('ulog.user_id', $userId)
                                    ->whereBetween('ulog.start_at', [$sinceDate, $untilDate])
                                    ->whereRaw('TIMESTAMPDIFF(SECOND, ulog.start_at, ulog.end_at) >= 60')//開始終了の差が60秒以上のもの
                                    ->count('ulog.id');

            $userLessonStatisticsPerPeriodDto->lessonNum = is_null($userLessonCount) ? 0 : $userLessonCount;
            $userTaskStatisticsDto->statSessionResults[] = $userLessonStatisticsPerPeriodDto;
        }//foreach
    }

    /**
     * 2018-08-20 追加
     * 当該会員のレッスン数集計結果をDB出力する
     * @param type $userTaskStatisticsDto
     */
    private function putLessonStatistics($userTaskStatisticsDto){

        $userId = $userTaskStatisticsDto->userId;
        $alugoUserId = $userTaskStatisticsDto->alugoUserId;
        $userLessonStatisticsPerPeriodDtos = $userTaskStatisticsDto->statSessionResults;

        $this->logwrite('info', 'putLessonStatistics: start: userId:'.$userId);

        foreach($userLessonStatisticsPerPeriodDtos as $userLessonStatisticsPerPeriodDto){

            $sinceDate = $userLessonStatisticsPerPeriodDto->sinceDate;
            $untilDate = $userLessonStatisticsPerPeriodDto->untilDate;
            $lessonNum = $userLessonStatisticsPerPeriodDto->lessonNum;

            $userTaskStatistics = new UserTaskStatistics();
            $userTaskStatistics->user_id			  = $userId;
            $userTaskStatistics->alugo_user_id        = $alugoUserId;
            $userTaskStatistics->counted_at           = Carbon::now();
            $userTaskStatistics->counted_since        = $sinceDate;
            $userTaskStatistics->counted_until        = $untilDate;
            $userTaskStatistics->count_type           = 3;//'集計値タイプ(1:セッション数 2:タスク数 3:レッスン数)'
            $userTaskStatistics->session_num          = $lessonNum;
            $userTaskStatistics->task_type            = null;
            $userTaskStatistics->sent_task_count      = null;
            $userTaskStatistics->completed_task_count = null;
            $userTaskStatistics->completed_task_rate  = null;
            $userTaskStatistics->remark               = null;
            $userTaskStatistics->is_deleted           = IsDeletedType::ACTIVE;
            $userTaskStatistics->created_at           = Carbon::now();
            $userTaskStatistics->save();

            $this->logwrite('info', 'putLessonStatistics: UserTaskStatistics.id:'.$userTaskStatistics->id
                                    . ' userId: ' .$userId
                                    . ' sinceDate: ' .$sinceDate
                                    . ' untilDate: ' .$untilDate
                                    . ' レッスン数: ' .$lessonNum);
        }//foreach

        $this->logwrite('info', 'putLessonStatistics: end: userId:'.$userId);
    }


    /**
     * 統計グラフ画像を出力しDBに登録する
     */
    private function putStatisticsChart(){

        $targetDate = Carbon::now()->toDateString(); //当日分のデータ

        /**
         * 2018-08-20 コメントアウト
         *
        //セッション数chart
        $targetUsers = UserTaskStatistics::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('count_type', 1)//集計値タイプ(1:セッション数 2:タスク数)
                            ->whereDate('counted_at', $targetDate)
                            ->select(['user_id'])->distinct()
                            ->orderby('user_id')
                            ->get();

        if(count($targetUsers) > 0){
            foreach($targetUsers as $targetUser){
                $userId = $targetUser->user_id;
                //ユーザー単位にグラフ描画&出力
                $userTaskStatistics =
                    UserTaskStatistics::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('count_type', 1)//集計値タイプ(1:セッション数 2:タスク数)
                                        ->whereDate('counted_at', $targetDate)
                                        ->where('user_id', $userId)
                                        ->orderby('counted_since')//集計期間範囲の日付順
                                        ->get();
                if(count($userTaskStatistics) > 0){
                    $this->putStatisticsChartSession($userTaskStatistics);
                }
            }//foreach
        }//end of if
         * 2018-08-20 コメントアウト
         */
        // 2018-08-20 追加
        //レッスン数chart
        $targetUsers = UserTaskStatistics::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('count_type', 3)//集計値タイプ(1:セッション数 2:タスク数 3:レッスン数)
                            ->whereDate('counted_at', $targetDate)
                            ->select(['user_id'])->distinct()
                            ->orderby('user_id')
                            ->get();

        if(!empty($targetUsers) && $targetUsers->count() > 0){
            foreach($targetUsers as $targetUser){
                $userId = $targetUser->user_id;
                //ユーザー単位にグラフ描画&出力
                $userTaskStatistics =
                    UserTaskStatistics::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('count_type', 3)//集計値タイプ(1:セッション数 2:タスク数 3:レッスン数)
                                        ->whereDate('counted_at', $targetDate)
                                        ->where('user_id', $userId)
                                        ->orderby('counted_since')//集計期間範囲の日付順
                                        ->get();
                if(!empty($userTaskStatistics) && $userTaskStatistics->count() > 0){
                    $this->putStatisticsChartLesson($userTaskStatistics);
                }
            }//foreach
        }//end of if


        //タスク集計chart
        $targetUsers = UserTaskStatistics::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('count_type', 2)//集計値タイプ(1:セッション数 2:タスク数 3:レッスン数)
                            ->whereDate('counted_at', $targetDate)
                            ->select(['user_id'])->distinct()
                            ->orderby('user_id')
                            ->get();

        if(!empty($targetUsers) && $targetUsers->count() > 0){
            foreach($targetUsers as $targetUser){
                $userId = $targetUser->user_id;
                //ユーザー単位にグラフ描画&出力
                $userTaskStatistics =
                    UserTaskStatistics::where('is_deleted', IsDeletedType::ACTIVE)
                                        ->where('count_type', 2)//集計値タイプ(1:セッション数 2:タスク数 3:レッスン数)
                                        ->whereDate('counted_at', $targetDate)
                                        ->where('user_id', $userId)
                                        ->orderby('counted_since')//集計期間範囲の日付順
                                        ->orderby('task_type')//タスク種別順
                                        ->get();
                if(!empty($userTaskStatistics) && $userTaskStatistics->count() > 0){
                    $this->putStatisticsChartTask($userTaskStatistics);
                }
            }//foreach
        }//end of if
    }

    /**
     * 2018-08-20 追加
     * 1件のユーザー単位にグラフ描画&出力 (レッスン数)
     * @param array<eloquant> $userTaskStatistics
     */
    private function putStatisticsChartLesson($userTaskStatistics){

        if(count($userTaskStatistics) < 1){
            return;
        }
        $userInfo = $userTaskStatistics[0];
        $userId      = $userInfo->user_id;
        $alugoUserId = $userInfo->alugo_user_id;

        $this->logwrite('info', 'putStatisticsChartLesson: start: userId:'.$userId);

        $chartDatas = [];
        $xAxisLabels = [];
       //$yAxisLabels = [];
       $yValueRange = collect();

        foreach($userTaskStatistics as $userTaskStatistic){
            $sinceDate   = $userTaskStatistic->counted_since;
            $untilDate   = $userTaskStatistic->counted_until;
            $lessonNum   = $userTaskStatistic->session_num; //DBカラム名は旧名のままなので注意すること

            $chartDatas[] = $lessonNum;
            $xAxisLabels[] = DateUtils::shortenToJanDD($sinceDate).' ';//Jan-DD型
            $yValueRange->push($lessonNum);
        }

        //y軸値範囲を決める
        $yMin = 0; //最小値は常に0
        /*
         * 2018-08-02 最大値は常に10固定
        $yMax = $yValueRange->max();
        if($yMax == 0){
            $yMax = 1;
        }
        */
        $yMax = 10;

        /*
         * グラフ画像生成 共通設定
         */
        // 文字描画用フォントを指定
        $fontFileName = env('BATCH_MEMBER_REPORT_CHART_FONT_PATH');
        // グラフ画像ファイルパス用の日時文字列生成
        $dateTimeString = Carbon::now()->format('Ymd_His');
        // グラフ画像パス生成
        $chartFolder = env('BATCH_MEMBER_REPORT_CHART_FOLDER');
        $chartFilePath = $chartFolder .'/'.$userId.'_20_'.$dateTimeString.'.png';

        /*
         * グラフ画像生成
         */
        // データセット用オブジェクトの生成
        $myData = new pData();

        //データセットの準備
        // 系列1データ
        $myData->addPoints($chartDatas, "Serie1");
        // 系列1線色
        $serieBasicColor = [
                [255,0,0], //(赤)
            ];
        $colorSet = $serieBasicColor[0];//常に赤色
        $serieColorR = $colorSet[0];
        $serieColorG = $colorSet[1];
        $serieColorB = $colorSet[2];
        $serieSettings = ["R"=>$serieColorR,"G"=>$serieColorG,"B"=>$serieColorB,"Alpha"=>100];
        $myData->setPalette("Serie1", $serieSettings);

        // 系列1の凡例ラベル
        $labelSerie1 = '受講レッスン数';
        $myData->setSerieDescription("Serie1", $labelSerie1);
        $myData->setSerieOnAxis("Serie1", 0);// 0:系列1

        // X軸目盛り
        $myData->addPoints($xAxisLabels, "Absissa");
        $myData->setAbscissa("Absissa");
        // Y軸ラベル
        $myData->setAxisName(0, "受講レッスン数");

        // グラフのサイズとデータセットを引数に渡してpChartオブジェクトを生成
        $myImage = new pImage(400, 300, $myData);

        // フォントとサイズを指定
        $myImage->setFontProperties(["FontName"=>$fontFileName, "FontSize"=>16]);

        // グラフの出力位置と大きさを指定(X軸、Y軸、幅、高さ)
        $myImage->setGraphArea(50, 30, 380, 230);

        // 背景色と背景色に入れる斜線の色を指定
        $Settings = ["R"=>255, "G"=>255, "B"=>255]; //背景色：white #ffffff
        // 背景を描く
        $myImage->drawFilledRectangle(0, 0, 400, 300, $Settings);

        // X軸、Y軸目盛りの描画
        // Y軸目盛り範囲の設定
        $axisBoundaries = [
                [ "Min" => $yMin,
                  "Max" => $yMax ]
            ];

        // フォントとサイズを指定
        $myImage->setFontProperties(["FontName"=>$fontFileName, "FontSize"=>7]);
        // 目盛りの描画
        $scaleParams = [
                'LabelRotation'=>45,
                'XMargin' => 10,
                "CycleBackground" => false,
                "Mode" => SCALE_MODE_MANUAL,
                "ManualScale" => $axisBoundaries
            ];
        $myImage->drawScale($scaleParams);

        // 凡例のオプションと表示
        $legendConfig = [
            "FontR"  => 0, "FontG"=>0, "FontB"=>0, // 凡例の文字色
            "Margin" => 6,                  // 凡例のマージン
            "Style"  => LEGEND_ROUND,       // 凡例枠の形式(角丸)
            "Mode"   => LEGEND_HORIZONTAL   // 凡例枠内の文字の表示形式(水平)
        ];
        //$myImage->drawLegend(300, 20, $legendConfig); //2018-08-04 凡例は非表示

        // lineグラフ描画
        $lineConfig = [
            "DisplayValues" => false,
            "Gradient"      => false,
            "AroundZero"    => false
        ];
        $myImage->drawLineChart($lineConfig);

        // plot点グラフ描画
        $plotConfig = [
            "DisplayValues" => true,
            "Gradient"      => false,
            "AroundZero"    => false
        ];
        $myImage->drawPlotChart($plotConfig);

        // 描いたグラフの保存
        $myImage->render($chartFilePath);

        $this->logwrite('info', 'putStatisticsChartLesson: chartFilePath:'.$chartFilePath);
        $this->logwrite('info', 'putStatisticsChartLesson: end: userId:'.$userId);

    }//putStatisticsChartLesson

    /**
     * @deprecated
     * 1件のユーザー単位にグラフ描画&出力 (セッション数)
     * @param array<eloquant> $userTaskStatistics
     */
    private function putStatisticsChartSession($userTaskStatistics){

        if(count($userTaskStatistics) < 1){
            return;
        }
        $userInfo = $userTaskStatistics[0];
        $userId      = $userInfo->user_id;
        $alugoUserId = $userInfo->alugo_user_id;

        $this->logwrite('info', 'putStatisticsChartSession: start: userId:'.$userId);

        $chartDatas = [];
        $xAxisLabels = [];
       //$yAxisLabels = [];
       $yValueRange = collect();

        foreach($userTaskStatistics as $userTaskStatistic){
            $sinceDate   = $userTaskStatistic->counted_since;
            $untilDate   = $userTaskStatistic->counted_until;
            $sessionNum  = $userTaskStatistic->session_num;

            $chartDatas[] = $sessionNum;
            $xAxisLabels[] = DateUtils::shortenToJanDD($sinceDate).' ';//Jan-DD型
            $yValueRange->push($sessionNum);
        }

        //y軸値範囲を決める
        $yMin = 0; //最小値は常に0
        /*
         * 2018-08-02 最大値は常に10固定
        $yMax = $yValueRange->max();
        if($yMax == 0){
            $yMax = 1;
        }
        */
        $yMax = 10;

        /*
         * グラフ画像生成 共通設定
         */
        // 文字描画用フォントを指定
        $fontFileName = env('BATCH_MEMBER_REPORT_CHART_FONT_PATH');
        // グラフ画像ファイルパス用の日時文字列生成
        $dateTimeString = Carbon::now()->format('Ymd_His');
        // グラフ画像パス生成
        $chartFolder = env('BATCH_MEMBER_REPORT_CHART_FOLDER');
        $chartFilePath = $chartFolder .'/'.$userId.'_20_'.$dateTimeString.'.png';

        /*
         * グラフ画像生成
         */
        // データセット用オブジェクトの生成
        $myData = new pData();

        //データセットの準備
        // 系列1データ
        $myData->addPoints($chartDatas, "Serie1");
        // 系列1線色
        $serieBasicColor = [
                [255,0,0], //(赤)
            ];
        $colorSet = $serieBasicColor[0];//常に赤色
        $serieColorR = $colorSet[0];
        $serieColorG = $colorSet[1];
        $serieColorB = $colorSet[2];
        $serieSettings = ["R"=>$serieColorR,"G"=>$serieColorG,"B"=>$serieColorB,"Alpha"=>100];
        $myData->setPalette("Serie1", $serieSettings);

        // 系列1の凡例ラベル
        $labelSerie1 = '受講レッスン数';
        $myData->setSerieDescription("Serie1", $labelSerie1);
        $myData->setSerieOnAxis("Serie1", 0);// 0:系列1

        // X軸目盛り
        $myData->addPoints($xAxisLabels, "Absissa");
        $myData->setAbscissa("Absissa");
        // Y軸ラベル
        $myData->setAxisName(0, "受講レッスン数");

        // グラフのサイズとデータセットを引数に渡してpChartオブジェクトを生成
        $myImage = new pImage(400, 300, $myData);

        // フォントとサイズを指定
        $myImage->setFontProperties(["FontName"=>$fontFileName, "FontSize"=>16]);

        // グラフの出力位置と大きさを指定(X軸、Y軸、幅、高さ)
        $myImage->setGraphArea(50, 30, 380, 230);

        // 背景色と背景色に入れる斜線の色を指定
        $Settings = ["R"=>255, "G"=>255, "B"=>255]; //背景色：white #ffffff
        // 背景を描く
        $myImage->drawFilledRectangle(0, 0, 400, 300, $Settings);

        // X軸、Y軸目盛りの描画
        // Y軸目盛り範囲の設定
        $axisBoundaries = [
                [ "Min" => $yMin,
                  "Max" => $yMax ]
            ];

        // フォントとサイズを指定
        $myImage->setFontProperties(["FontName"=>$fontFileName, "FontSize"=>7]);
        // 目盛りの描画
        $scaleParams = [
                'LabelRotation'=>45,
                'XMargin' => 10,
                "CycleBackground" => false,
                "Mode" => SCALE_MODE_MANUAL,
                "ManualScale" => $axisBoundaries
            ];
        $myImage->drawScale($scaleParams);

        // 凡例のオプションと表示
        $legendConfig = [
            "FontR"  => 0, "FontG"=>0, "FontB"=>0, // 凡例の文字色
            "Margin" => 6,                  // 凡例のマージン
            "Style"  => LEGEND_ROUND,       // 凡例枠の形式(角丸)
            "Mode"   => LEGEND_HORIZONTAL   // 凡例枠内の文字の表示形式(水平)
        ];
        //$myImage->drawLegend(300, 20, $legendConfig); //2018-08-04 凡例は非表示

        // lineグラフ描画
        $lineConfig = [
            "DisplayValues" => false,
            "Gradient"      => false,
            "AroundZero"    => false
        ];
        $myImage->drawLineChart($lineConfig);

        // plot点グラフ描画
        $plotConfig = [
            "DisplayValues" => true,
            "Gradient"      => false,
            "AroundZero"    => false
        ];
        $myImage->drawPlotChart($plotConfig);

        // 描いたグラフの保存
        $myImage->render($chartFilePath);

        $this->logwrite('info', 'putStatisticsChartSession: chartFilePath:'.$chartFilePath);
        $this->logwrite('info', 'putStatisticsChartSession: end: userId:'.$userId);

    }//putStatisticsChartSession

    /**
     * 1件のユーザー単位にグラフ描画&出力 (タスク完了比率)
     * @param array<eloquant> $userTaskStatistics
     */
    private function putStatisticsChartTask($userTaskStatistics){

        if(count($userTaskStatistics) < 1){
            return;
        }
        $userInfo = $userTaskStatistics[0];
        $userId      = $userInfo->user_id;
        $alugoUserId = $userInfo->alugo_user_id;

        $this->logwrite('info', 'putStatisticsChartTask: start: userId:'.$userId);

        $chartDatas  = [];
        $yAxisLabels = [];

        /*
         * 集計データセットを準備する
         */
        $userTaskInfo = collect($userTaskStatistics);
        //日付けの全コレクション
        $sinceDates = $userTaskInfo->pluck('counted_since')->unique()->values();

        //タスク種別の全コレクション
        $taskTypes = $userTaskInfo->pluck('task_type')->unique()->values();
        //合計値用のタスク種別を用意する
        $taskTypes->push('sum');

        //[タスク種別][日付け]の形式
        $taskStatDatas = $taskTypes->mapWithKeys(function ($taskType) use ($sinceDates){
            $work = $sinceDates->mapWithKeys(function ($sdate) {
                        $keyData = DateUtils::shortenToYYMMDD($sdate);//YYYY-MM-DD型
                        return [ $keyData => collect(['value'=> ''])];
                    });
            return [ $taskType => $work ];
        });


        //X軸ラベルのセット
        $xAxisLabels = $sinceDates->map(function($item){
            return DateUtils::shortenToJanDD($item).' ';//Jan-DD型
        });
        //Y軸値の全データ
        $yValueRange = $userTaskInfo->pluck('completed_task_rate');

        //各タスク種別毎のデータセット
        $sumValues = []; //合計値計算用
        foreach($userTaskStatistics as $userTaskStatistic){
            $sinceDate   = DateUtils::shortenToYYMMDD($userTaskStatistic->counted_since);//YYYY-MM-DD型
            $untilDate   = $userTaskStatistic->counted_until;
            $taskType    = $userTaskStatistic->task_type;
            $completedTaskRate = $userTaskStatistic->completed_task_rate;

            if($taskStatDatas->has($taskType)){
                if($taskStatDatas->get($taskType)->has($sinceDate)){
                    $taskStatDatas->get($taskType)->get($sinceDate)->put('value', sprintf('%.1f', $completedTaskRate));
                }
            }

            //合計完了比率の算出
            if(array_key_exists($sinceDate, $sumValues)){
                $data = $sumValues[$sinceDate];

                $fSentTaskCount = floatval($data['sent_task_count'])
                                + floatval($userTaskStatistic->sent_task_count);

                $fCompletedTaskCount = floatval($data['completed_task_count'])
                                     + floatval($userTaskStatistic->completed_task_count);

                $this->logwrite('debug', 'sumValues: sinceDate:'.$sinceDate . ' fSentTaskCount:'. $fSentTaskCount . ' fCompletedTaskCount:'.$fCompletedTaskCount);


                if($fSentTaskCount > 0){
                    //百分率とする
                    $cTaskRate = bcmul(
                                    bcdiv( strval($fCompletedTaskCount), strVal($fSentTaskCount), $this->calcScale),
                                    '100',
                                    $this->lastScale
                                );
                }else{
                    $cTaskRate = '0';
                }

                $sumValues[$sinceDate] = [
                    'sent_task_count'      => sprintf('%.1f', $fSentTaskCount),
                    'completed_task_count' => sprintf('%.1f', $fCompletedTaskCount),
                    'completed_task_rate'  => sprintf('%.1f', $cTaskRate),
                ];

            }else{
                $sumValues[$sinceDate] = [
                    'sent_task_count'      => $userTaskStatistic->sent_task_count,
                    'completed_task_count' => $userTaskStatistic->completed_task_count,
                    'completed_task_rate'  => $userTaskStatistic->completed_task_rate,
                ];
            }

        }//foreach

        $this->logwrite('debug', 'sumValues: count:'.count($sumValues) . ' sumValues:'. var_export($sumValues, true) );

        //合計完了比率をセットする
        foreach($sumValues as $sinceDate => $sumValue){
            if($taskStatDatas->get('sum')->has($sinceDate)){
                $taskStatDatas->get('sum')->get($sinceDate)->put('value', $sumValue['completed_task_rate']);
            }
        }

        $this->logwrite('debug', 'taskStatDatas[sum]: count:'.count($taskStatDatas->get('sum')) . ' taskStatDatas[sum]:'. var_export($taskStatDatas->get('sum'), true));


        //y軸値範囲を決める
        $yMin = 0; //最小値は常に0
        /*** 2018-08-04 Y軸は百分率とするので最大値は 100%
        $yMax = $yValueRange->max();
        if($yMax == 0){
            $yMax = 1;
        }
        ***/
        $yMax = 100;

        /*
         * グラフ画像生成 共通設定
         */
        // 文字描画用フォントを指定
        $fontFileName = env('BATCH_MEMBER_REPORT_CHART_FONT_PATH');
        // グラフ画像ファイルパス用の日時文字列生成
        $dateTimeString = Carbon::now()->format('Ymd_His');
        // グラフ画像ファイルパス生成
        $chartFolder = env('BATCH_MEMBER_REPORT_CHART_FOLDER');
        $chartFilePath = $chartFolder .'/'.$userId.'_21_'.$dateTimeString.'.png';

        $serieBasicColor = [
                [0,128,0],      //（緑）
                [255,200,0],    //（オレンジ）
                [0,0,255],      //（青）
                [255,0,255],    //（マゼンタ）
                [255,255,0],    //（黄）
                [0,255,0],      //（黄緑）
                [0,255,255],    //（シアン）
                [255,175,175],  //（ピンク）
                [128,128,0],    //（オリーブ色）
                [0,0,128],      //（紺）
                [128,0,128],    //（紫）
                [0,128,128],    //（青緑）
                [128,0,0],      //（栗色）
                //[255,0,0],		//（赤） 合計値として使用する
            ];
        $serieSumColor = [
                [255,0,0],		//（赤） 合計値として使用する
            ];

        /*
         * グラフ画像生成
         */
        // データセット用オブジェクトの生成
        $myData = new pData();

        //データセットの準備
        $sumDatas = []; //全タスク種別合計値計算用
        $serieNo = 0; //系列番号
        foreach($taskStatDatas as $taskType => $taskStatData){

            /*
             * 2018-08-04 合計値だけの表示でよい
             */
            if($taskType !== 'sum'){
                continue;
            }

            // 系列データ:1つのタスク種別についてのデータ
            $serieName = 'taskType'.$taskType;
            $serieDatas = $taskStatData->map(function($row){
                                $val = $row->get('value');
                                if(strlen($val)< 1){
                                    $val = VOID;//欠測値の場合
                                }
                                return $val;
                            });
            $myData->addPoints($serieDatas->all(), $serieName);

            // 系列線色
            if($taskType == 'sum'){
                //合計値
                $colorSet = $serieSumColor[0];//常に赤色
            }else{
                if($serieNo >= count($serieBasicColor)){
                    $i = $serieNo - count($serieBasicColor);
                    $colorSet = $serieBasicColor[$i];
                }else{
                    $colorSet = $serieBasicColor[$serieNo];
                }
            }
            $serieColorR = $colorSet[0];
            $serieColorG = $colorSet[1];
            $serieColorB = $colorSet[2];
            $serieSettings = ["R"=>$serieColorR,"G"=>$serieColorG,"B"=>$serieColorB,"Alpha"=>100];
            $myData->setPalette($serieName, $serieSettings);

            // 系列の凡例ラベル
            $labelSerie = $this->getTaskTypeName($taskType);
            $myData->setSerieDescription($serieName, $labelSerie);
            $myData->setSerieOnAxis($serieName, 0);

            $serieNo++;
        }//foreach

        // X軸目盛り
        $myData->addPoints($xAxisLabels->all(), "Absissa");
        $myData->setAbscissa("Absissa");
        // Y軸ラベル
        $myData->setAxisName(0, "宿題の完了率 (%)");

        // グラフのサイズとデータセットを引数に渡してpChartオブジェクトを生成
        $myImage = new pImage(400, 300, $myData);

        // フォントとサイズを指定
        $myImage->setFontProperties(["FontName"=>$fontFileName, "FontSize"=>16]);

        // グラフの出力位置と大きさを指定(X軸、Y軸、幅、高さ)
        $myImage->setGraphArea(50, 30, 380, 230);
        // 背景色と背景色に入れる斜線の色を指定
        $Settings = ["R"=>255, "G"=>255, "B"=>255]; //背景色：white #ffffff
        // 背景を描く
        $myImage->drawFilledRectangle(0, 0, 400, 300, $Settings);


        // X軸、Y軸目盛りの描画
        // Y軸目盛り範囲の設定
        $axisBoundaries = [
                [ "Min" => $yMin,
                  "Max" => $yMax ]
            ];

        // フォントとサイズを指定
        $myImage->setFontProperties(["FontName"=>$fontFileName, "FontSize"=>7]);
        // 目盛りの描画
        $scaleParams = [
                'LabelRotation' => 45,
                'XMargin' => 10,
                "CycleBackground" => false,
                "DrawYLines" => ['taskType1'],
                "Mode" => SCALE_MODE_MANUAL,
                "Factors" => [2,4,6,8],
                "ManualScale" => $axisBoundaries
            ];
        $myImage->drawScale($scaleParams);

        // 凡例のオプションと表示
        $legendConfig = [
            "FontR"  => 0, "FontG"=>0, "FontB"=>0, // 凡例の文字色
            "Margin" => 6,                  // 凡例のマージン
            "Style"  => LEGEND_ROUND,       // 凡例枠の形式(角丸)
            "Mode"   => LEGEND_VERTICAL     // 凡例枠内の文字の表示形式
        ];
        //$myImage->drawLegend(250, 10, $legendConfig); //2018-08-04 凡例は非表示

        // lineグラフ描画
        $lineConfig = [
            "DisplayValues" => false,
            "Gradient"      => false,
            "AroundZero"    => false,
            "BreakVoid"     => false,
        ];
        $myImage->drawLineChart($lineConfig);

        // plot点グラフ描画
        $plotConfig = [
            "DisplayValues" => true,
            "Gradient"      => false,
            "AroundZero"    => false
        ];
        $myImage->drawPlotChart($plotConfig);

        // 描いたグラフの保存
        $myImage->render($chartFilePath);

        $this->logwrite('info', 'putStatisticsChartTask: chartFilePath:'.$chartFilePath);
        $this->logwrite('info', 'putStatisticsChartTask: start: userId:'.$userId);

    }//putStatisticsChartTask

    /**
     * タスク種別名称について、統計用の特別な名称とする
     * @param type $taskType
     * @return string
     */
    private function getTaskTypeName($taskType) {
        $taskName = '';
        if($taskType == 'sum'){
            $taskName = '合計値';

        }elseif($taskType == '400'){//瞬間口頭G, V はまとめる
            $taskName = '例文学習(文法or語彙)';

        }elseif($taskType == '500'){//Listening B, C, D はまとめる
            $taskName = 'Listening';

        }elseif($taskType == '600'){//50 Core Sentence A, B, C, D はまとめる
            $taskName = '単語帳学習';

        }elseif($taskType == '700'){//Speaking Rally投稿 はまとめる
            $taskName = 'Speaking Rally';

        }elseif( ($taskType >= '5') && ($taskType <= '13') ){
            $otherTask = OtherTask::where('is_deleted', IsDeletedType::ACTIVE)
                            ->where('task_type_id', $taskType)
                            ->select('other_task_name')
                            ->first();
            if(!empty($otherTask) && $otherTask->count() > 0){
                $taskName = $otherTask->other_task_name;
            }

        }elseif($taskType == '3'){
            $taskName = 'SpeakingRally(SR)';

        }elseif($taskType == '4'){
            $taskName = '復習';

        }elseif($taskType == '30'){
            $taskName = 'レッスン復習';

        }elseif($taskType == '40'){
            $taskName = '単語カード';

        }elseif($taskType == '510'){
            $taskName = 'リスニングトレーニング';
        }

        return $taskName;
    }


}

