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


class CreateGVTaskCommand extends Command
{
    use AlueCommandRunTrait;
    use AlueCommandLogTrait;
    use AlueCommandBaseTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueBatchCommand:createGVTask {runType=null}';
    protected $commandName = 'AlueBatchCommand:createGVTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create GV Task Batch Command';

    //カスタムロガー
    protected $commandLogger;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        //$this->initAlueCommandLogTrait('console/createGVTask.log');
        $this->initAlueCommandLogTrait('console/createQuestion.log');//こちらに記載する
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
            $this->logwrite('info', 'process-start'.$runTypeText);
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_START, BatchRunStatusType::BATCH_RUN_STATUS_OK);

            //バッチ処理の実行
            $resultMessage = '';

            //出題アルゴリズムを起動する
            $algorithmModule = env('BATCH_SHELL_RUN_QUESTION_ALGORITHM_MODULE');
            if($algorithmModule == 'R'){
                $this->logwrite('info', '出題アルゴリズム: R');
                // Rモジュール起動シェル
                $runCommand = 'sh '. env('BATCH_SHELL_RUN_QUESTION_ALGORITHM_R');//shell経由での起動とする

            }elseif($algorithmModule == 'P'){
                $this->logwrite('info', '出題アルゴリズム: Python');
                // Pythonモジュール起動シェル
                $runCommand = 'sh '. env('BATCH_SHELL_RUN_QUESTION_ALGORITHM_P');//shell経由での起動とする

            }else{
                $this->logwrite('error', 'env(BATCH_SHELL_RUN_QUESTION_ALGORITHM_MODULE) is illegal value');
            }

            $this->logwrite('info', '出題アルゴリズム起動 : '.$runCommand);

            $retMsg = [];
            $retVal = 0;
            exec($runCommand, $retMsg, $retVal);

            //出題アルゴリズム実行ステータスの判定
            if($retVal != 0){
                $this->logwrite('error', '出題アルゴリズム実行エラー : retVal:' . $retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            }else{
                $this->logwrite('info', '出題アルゴリズム実行完了 : retVal:' . $retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_OK;
            }
            $resultMessage = CommonUtils::arrayFlatten($retMsg);

            //バッチ処理の実行情報を登録
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, $resultMessage);
            $this->logwrite('info', 'process-end'.$runTypeText);

            //実行完了処理
            if(!$this->finishRunning($this->commandName)){
                //実行完了処理エラー
                $resultMessage = 'バッチコマンド実行完了処理エラー';
                $this->logwrite('error', 'process-end-error'.$runTypeText.' :'.$resultMessage);
                $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, BatchRunStatusType::BATCH_RUN_STATUS_FAIL, $resultMessage);
                return false;
            }

            return intval($finishStatus);

        } catch (\Exception $ex) {
            $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', 'process-end'.$runTypeText . ' error:'. $resultMessage);
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, $resultMessage);
            $this->logwrite('info', 'process-end'.$runTypeText);
            return intval($finishStatus);
        }

    }
}
