<?php

namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use App\Console\Commands\AlueCommand\Traits\AlueCommandRunTrait;
use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Traits\AlueCommandBaseTrait;

use App\Enums\BatchRunType;
use App\Enums\BatchLogType;
use App\Enums\BatchRunStatusType;

use App\Services\AlueCommandService\AlueIntegCreateQuestionService;

class ResetTaskCommand extends Command
{
    use AlueCommandRunTrait;
    use AlueCommandLogTrait;
    use AlueCommandBaseTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueBatchCommand:resetTask {runType=null}';
    protected $commandName = 'AlueBatchCommand:resetTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Task Batch Command';

    //カスタムロガー
    protected $commandLogger;

    protected $alueIntegCreateQuestionService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AlueIntegCreateQuestionService $alueIntegCreateQuestionService)
    {
        parent::__construct();
        //$this->initAlueCommandLogTrait('console/resetTask.log');
        $this->initAlueCommandLogTrait('console/createQuestion.log');//こちらに記載する
        $this->alueIntegCreateQuestionService = $alueIntegCreateQuestionService;
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
            $ret = $this->alueIntegCreateQuestionService->resetTaskData();

            //バッチ処理の実行情報を登録
            if($ret){
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_OK;
            }else{
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            }
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
