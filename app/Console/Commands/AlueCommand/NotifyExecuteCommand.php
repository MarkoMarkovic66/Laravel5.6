<?php
namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use App\Console\Commands\AlueCommand\Traits\AlueCommandRunTrait;
use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Traits\AlueCommandBaseTrait;

use App\Enums\BatchRunType;
use App\Enums\BatchLogType;
use App\Enums\BatchRunStatusType;

use AlueIntegNotifyExecuteService;

class NotifyExecuteCommand extends Command
{
    use AlueCommandRunTrait;
    use AlueCommandLogTrait;
    use AlueCommandBaseTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueBatchCommand:notifyExecute {runType=null}';
    protected $commandName = 'AlueBatchCommand:notifyExecute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify Execute Batch Command';

    //カスタムロガー
    protected $commandLogger;

    protected $alueIntegNotifyExecuteService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AlueIntegNotifyExecuteService $alueIntegNotifyExecuteService)
    {
        parent::__construct();
        $this->initAlueCommandLogTrait('console/notifyExecute.log');
        $this->alueIntegNotifyExecuteService = $alueIntegNotifyExecuteService;
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

            // （注）排他制御 は不要
            //バッチ処理の実行情報をLog登録
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_START, BatchRunStatusType::BATCH_RUN_STATUS_OK, 'メッセージ通知実行: start');

            //起動ログ出力
            $this->logwrite('info', 'process-start:'.$runTypeText);

            //バッチ処理の実行
            $resultMessage = '';
            $ret = $this->alueIntegNotifyExecuteService->exec($resultMessage);

            //バッチ処理の実行情報を登録
            if($ret){
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_OK;
            }else{
                $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            }
            //終了ログ出力
            $this->logwrite('info', 'process-end:'.$runTypeText . ' finishStatus:' .$finishStatus);

            //バッチ処理の実行情報をLog登録
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, 'メッセージ通知実行: finish '.$resultMessage);

            return intval($finishStatus);

        } catch (\Exception $ex) {
            $finishStatus = BatchRunStatusType::BATCH_RUN_STATUS_FAIL;
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', 'process-end:'.$runTypeText . ' error:'. $resultMessage);
            $this->registRunningInfo($this->commandName, $runType, BatchLogType::BATCH_LOG_FINISH, $finishStatus, $resultMessage);
            return intval($finishStatus);
        }
    }


}
