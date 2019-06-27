<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //統合コマンド
        Commands\AlueCommand\AlueImportDataCommand::class,
        Commands\AlueCommand\AlueImportJsonCommand::class,
        Commands\AlueCommand\AlueActionWatchCommand::class,

        Commands\AlueCommand\AlueCreateQuestionCommand::class,
        Commands\AlueCommand\AlueCreateTaskCommand::class,
        Commands\AlueCommand\AlueAchievementCountCommand::class,

        //Commands\AlueCommand\AluePushMemberReportCommand::class,
        //Commands\AlueCommand\AluePullCwAnswerCommand::class,

        //2019-05-25 added
        Commands\AlueCommand\AlueNotifyManageCommand::class,
        Commands\AlueCommand\AlueNotifyExecuteCommand::class,


        //個別コマンド
        Commands\AlueCommand\ImportDataCommand::class,
        Commands\AlueCommand\ImportJsonCommand::class,
        Commands\AlueCommand\ActionWatchCommand::class,

        Commands\AlueCommand\ResetTaskCommand::class,
        Commands\AlueCommand\CreateGVTaskCommand::class,
        Commands\AlueCommand\CreateQuestionCommand::class,
        Commands\AlueCommand\CreateMemberReportCommand::class,

        Commands\AlueCommand\CreateTaskCommand::class,
        Commands\AlueCommand\AchievementCountCommand::class,

        //Commands\AlueCommand\PushMemberReportCommand::class,
        //Commands\AlueCommand\PushCwDataCommand::class,
        //Commands\AlueCommand\PullCwDataCommand::class,

        //2019-05-25 added
        Commands\AlueCommand\NotifyManageCommand::class,
        Commands\AlueCommand\NotifyExecuteCommand::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //各バッチ起動時刻
        $batchDataImport_RunAt             = env('BATCH_DATA_IMPORT_RUN_AT');
        $batchGetLessonAvailableJson_RunAt = env('BATCH_GET_LESSON_AVAILABLE_JSON_AT');
        $batchUserActionWatch_RunAt        = env('BATCH_USER_ACTION_WATCH_AT');
        $batchCreateQuestion_RunAt         = env('BATCH_CREATE_QUESTION_RUN_AT');
        $batchCreateTask_RunAt             = env('BATCH_CREATE_TASK_RUN_AT');
        $batchAchievementCount_RunAt       = env('BATCH_ACHIEVEMENT_COUNT_RUN_AT');

        //$batchPushChatwork_RunAt     = env('BATCH_PUSH_CW_RUN_AT');
        //$batchPushMemberReport_RunAt = env('BATCH_PUSH_MEMBER_REPORT_RUN_AT');
        //$batchPullChatwork_RunAt     = env('BATCH_PULL_CW_RUN_AT');

        //2019-05-25 added
        $batchNotifyManage_RunAt  = env('BATCH_NOTIFY_MANAGE_AT');   //cron形式
        $batchNotifyExecute_RunAt = env('BATCH_NOTIFY_EXECUTE_AT');  //cron形式


        /*
         * Artisanコマンド実行
         */
        //2019-05-25 added
        //メッセージ通知管理：10分毎に実行(注．メッセージ通知管理とメッセージ通知実行はタイミングをずらすこと)
        $schedule->command('AlueCommand:notifyManage')->cron($batchNotifyManage_RunAt);
        //メッセージ通知実行：10分毎に実行(注．メッセージ通知管理とメッセージ通知実行はタイミングをずらすこと)
        $schedule->command('AlueCommand:notifyExecute')->cron($batchNotifyExecute_RunAt);


        //レッスン予約可能日時JSONファイル取得：30分毎に実行
        $schedule->command('AlueCommand:importJson')->cron($batchGetLessonAvailableJson_RunAt);
        //ユーザーアクション監視：毎時5分、35分に実行
        $schedule->command('AlueCommand:actionWatch')->cron($batchUserActionWatch_RunAt);


        //データインポート：毎日00:00に実行
        $schedule->command('AlueCommand:importData')->dailyAt($batchDataImport_RunAt);
        //宿題タスク生成：毎日06:00に実行
        $schedule->command('AlueCommand:createTask')->dailyAt($batchCreateTask_RunAt);


        //レッスン/タスク 実績集計：毎週日曜日03:00に実行
        $schedule->command('AlueCommand:achievementCount')->weekly()->sundays()->at($batchAchievementCount_RunAt);
        //問題作成：毎週日曜日04:00に実行（この中で会員レポートも作成する）
        $schedule->command('AlueCommand:createQuestion')->weekly()->sundays()->at($batchCreateQuestion_RunAt);


        ////会員レポート投稿
        //$schedule->command('AlueCommand:pushMemberReport')->weekly()->sundays()->at($batchPushMemberReport_RunAt);

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
