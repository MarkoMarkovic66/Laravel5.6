<?php

namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use App\Console\Commands\AlueCommand\Traits\AlueCommandRunTrait;
use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Traits\AlueCommandBaseTrait;

use App\Enums\BatchRunType;
use App\Enums\BatchLogType;
use App\Enums\BatchRunStatusType;

use App\Utils\CommonUtils;

class ImportJsonCommand extends Command
{
    use AlueCommandRunTrait;
    use AlueCommandLogTrait;
    use AlueCommandBaseTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueBatchCommand:importJson {runType=null}';
    protected $commandName = 'AlueBatchCommand:importJson';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Alugo Data Batch Command';

    //カスタムロガー
    protected $commandLogger;

    protected $alueIntegDataImportService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->initAlueCommandLogTrait('console/importJson.log');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $runParam = '';
        $runType = $this->argument('runType');
        $runTypeText = '';
        if( ! empty($runType) && $runType == BatchRunType::BATCH_RUN_MANUAL){
            $runTypeText = ':'.BatchRunType::BATCH_RUN_MANUAL_TEXT;
            $runParam = 'man';
        }else{
            $runType = BatchRunType::BATCH_RUN_AUTO;
            $runTypeText = ':'.BatchRunType::BATCH_RUN_AUTO_TEXT;
            $runParam = 'auto';
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
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_START, BatchRunStatusType::BATCH_RUN_STATUS_OK, 'レッスン予約可能日時JSONファイル取得: start');
         *
        ******************/

            //起動ログ出力
            $this->logwrite('info', 'process-start:'.$runTypeText);

            // レッスン予約可能日時JSONファイル取得シェル実行
            $runCommand = 'sh '. env('BATCH_SHELL_GET_LESSON_AVAILABLE_JSON') . ' ' . $runParam;
            $this->logwrite('info', 'レッスン予約可能日時JSONファイル取得シェル実行:'.$runCommand);

            $retMsg = [];
            $retVal = 0;
            exec($runCommand, $retMsg, $retVal);

            //実行ステータスの判定、終了ログ出力
            if($retVal != 0){
                $this->logwrite('error', 'レッスン予約可能日時JSONファイル取得シェル実行エラー : retVal:' . $retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            }else{
                $this->logwrite('info', 'レッスン予約可能日時JSONファイル取得シェル実行完了 : retVal:' . $retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_OK;
            }
            //終了ログ出力
            $this->logwrite('info', 'process-end:'.$runTypeText . ' finishStatus:' .$finishStatus);

        /***********
         * 排他制御 および batchLogテーブルへの起動記録 は不要
         *
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, 'レッスン予約可能日時JSONファイル取得: finish');

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
            return intval($finishStatus);
        }
    }


}
