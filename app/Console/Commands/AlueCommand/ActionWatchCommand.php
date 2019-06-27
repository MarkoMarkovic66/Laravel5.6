<?php

namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use App\Console\Commands\AlueCommand\Traits\AlueCommandRunTrait;
use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Traits\AlueCommandBaseTrait;

use App\Enums\BatchRunType;
use App\Enums\BatchLogType;
use App\Enums\BatchRunStatusType;

use App\Services\AlueCommandService\AlueIntegActionWatchService;


class ActionWatchCommand extends Command
{
    use AlueCommandRunTrait;
    use AlueCommandLogTrait;
    use AlueCommandBaseTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueBatchCommand:actionWatch {runType=null}';
    protected $commandName = 'AlueBatchCommand:actionWatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Action Watch Batch Command';

    //カスタムロガー
    protected $commandLogger;

    protected $alueIntegActionWatchService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AlueIntegActionWatchService $alueIntegActionWatchService)
    {
        parent::__construct();
        $this->initAlueCommandLogTrait('console/actionWatch.log');
        $this->alueIntegActionWatchService = $alueIntegActionWatchService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $runType = $this->argument('runType');
        $runTypeText = '';
        if( ! empty($runType) && $runType == BatchRunType::BATCH_RUN_MANUAL){
            $runTypeText = ':'.BatchRunType::BATCH_RUN_MANUAL_TEXT;
        }else{
            $runType = BatchRunType::BATCH_RUN_AUTO;
            $runTypeText = ':'.BatchRunType::BATCH_RUN_AUTO_TEXT;
        }

        try {

        /******************
         * 排他制御 および batchLogテーブルへの起動記録 は不要
         *
            //起動許可判定
            if( ! $this->isRunnable()){
                //起動不可
                $resultMessage = '他のバッチコマンドが実行中のため本コマンドは実行できません';
                $this->logwrite('error', 'process-start-forbidden'.$runTypeText.' :'.$resultMessage);
                $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_START, BatchRunStatusType::BATCH_RUN_STATUS_FAIL, $resultMessage);
                return false;
            }

            //実行開始処理
            if(!$this->startRunning($this->commandName)){
                //実行開始処理エラー
                $resultMessage = 'バッチコマンド実行開始処理エラー';
                $this->logwrite('error', 'process-start-error'.$runTypeText.' :'.$resultMessage);
                $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_START, BatchRunStatusType::BATCH_RUN_STATUS_FAIL, $resultMessage);
                return false;
            }

            //バッチ処理の実行情報を登録
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_START, BatchRunStatusType::BATCH_RUN_STATUS_OK, 'ユーザーアクション監視: start');
         *
        ******************/

            //起動ログ出力
            $this->logwrite('info', 'process-start:'.$runTypeText);

            //バッチ処理の実行
            $resultMessage = '';
            $ret = $this->alueIntegActionWatchService->exec($resultMessage);

            //バッチ処理の実行情報を登録
            if($ret){
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_OK;
            }else{
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            }
            //終了ログ出力
            $this->logwrite('info', 'process-end:'.$runTypeText . ' finishStatus:' .$finishStatus);


        /***********
         * 排他制御 および batchLogテーブルへの起動記録 は不要
         *
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, 'ユーザーアクション監視: finish '.$resultMessage);

            //実行完了処理
            if(!$this->finishRunning($this->commandName)){
                //実行完了処理エラー
                $resultMessage = 'バッチコマンド実行完了処理エラー';
                $this->logwrite('error', 'process-end-error'.$runTypeText.' :'.$resultMessage);
                $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, BatchRunStatusType::BATCH_RUN_STATUS_FAIL, $resultMessage);
                return false;
            }
        ***********/

            return intval($finishStatus);

        } catch (\Exception $ex) {
            $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', 'process-end:'.$runTypeText . ' error:'. $resultMessage);
            //$this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, $resultMessage);
            return intval($finishStatus);
        }
    }


}
