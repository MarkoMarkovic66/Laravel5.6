<?php

namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Enums\BatchRunType;
use App\Enums\BatchRunStatusType;
use App\Utils\CommonUtils;

class AlueCreateQuestionCommand extends Command
{
    use AlueCommandLogTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueCommand:createQuestion {runType=null}';
    protected $commandName = 'AlueCommand:createQuestion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parental Command Of Create Question';

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
        $this->initAlueCommandLogTrait('console/createQuestion.log');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $runType = $this->argument('runType');
        if( ! empty($runType) && $runType == BatchRunType::BATCH_RUN_MANUAL){
            $runType = BatchRunType::BATCH_RUN_MANUAL;
            $runTypeText = BatchRunType::BATCH_RUN_MANUAL_TEXT;
        }else{
            $runType = BatchRunType::BATCH_RUN_AUTO;
            $runTypeText = BatchRunType::BATCH_RUN_AUTO_TEXT;
        }
        $this->logwrite('info', $this->commandName .': start: runType:'.$runTypeText);

        $phpPath = (new PhpExecutableFinder)->find();
        $artisan = base_path(). DIRECTORY_SEPARATOR. "artisan";
        $command1 = "AlueBatchCommand:resetTask " . $runType;
        $command2 = "AlueBatchCommand:createGVTask " . $runType;
        $command3 = "AlueBatchCommand:createQuestion " . $runType;
        $command4 = "AlueBatchCommand:createMemberReport " . $runType;

        //各コマンドを逐次実行

        // 1. リセットTask
        $retMsg = [];
        $retVal = 0;
        $this->logwrite('info', 'Alue Batch Exec：'.$command1);
        exec("{$phpPath} {$artisan} {$command1} > /dev/null", $retMsg, $retVal);
        $this->logwrite('info', 'Alue Batch Done：'.$command1. ' retVal:'.$retVal);

        if($retVal != BatchRunStatusType::BATCH_RUN_STATUS_OK){
            $this->logwrite('info', 'Alue Batch Error：'.$command1. ' retVal:'.$retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
            return;
        }

        // 2. 出題問題作成モジュール起動
        $retMsg = [];
        $retVal = 0;
        $this->logwrite('info', 'Alue Batch Exec：'.$command2);
        exec("{$phpPath} {$artisan} {$command2} > /dev/null", $retMsg, $retVal);
        $this->logwrite('info', 'Alue Batch Done：'.$command2. ' retVal:'.$retVal);

        if($retVal != BatchRunStatusType::BATCH_RUN_STATUS_OK){
            $this->logwrite('info', 'Alue Batch Error：'.$command2. ' retVal:'.$retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
            return;
        }

        // 3. 問題タスク作成
        $retMsg = [];
        $retVal = 0;
        $this->logwrite('info', 'Alue Batch Exec：'.$command3);
        exec("{$phpPath} {$artisan} {$command3} > /dev/null", $retMsg, $retVal);
        $this->logwrite('info', 'Alue Batch Done：'.$command3. ' retVal:'.$retVal);

        if($retVal != BatchRunStatusType::BATCH_RUN_STATUS_OK){
            $this->logwrite('info', 'Alue Batch Error：'.$command3. ' retVal:'.$retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
            return;
        }

        // 4. 会員レポート関連処理
        // 4-1. 会員レポート生成処理
        $retMsg = [];
        $retVal = 0;
        $this->logwrite('info', 'Alue Batch Exec：'.$command4);
        exec("{$phpPath} {$artisan} {$command4} > /dev/null", $retMsg, $retVal);
        $this->logwrite('info', 'Alue Batch Done：'.$command4. ' retVal:'.$retVal);

        if($retVal != BatchRunStatusType::BATCH_RUN_STATUS_OK){
            $this->logwrite('info', 'Alue Batch Error：'.$command4. ' retVal:'.$retVal . ' retMsg:'. CommonUtils::arrayFlatten($retMsg));
            return;
        }

        // 4-2. 会員レポート投稿処理
        // 2018-06-22 別コマンド(AluePushMemberReportCommand)で行う
        // 2018-10-16 統合アプリでは「会員レポート投稿」は行わない

    }
}
