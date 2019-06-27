<?php

namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Enums\BatchRunType;
use App\Enums\BatchRunStatusType;
use App\Utils\CommonUtils;

class AlueCreateTaskCommand extends Command
{
    use AlueCommandLogTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueCommand:createTask {runType=null}';
    protected $commandName = 'AlueCommand:createTask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parental Command Of Create Task';

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
        $this->initAlueCommandLogTrait('console/createTask.log');
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
        $command1 = "AlueBatchCommand:createTask " . $runType;

        // 2018-10-16 alue統合アプリではタスクを生成するのみで良いのでコメントアウト
        ///////$command2 = "AlueBatchCommand:pushCwData " . $runType;


        //各コマンドを実行
        $this->logwrite('info', 'Alue Batch Exec：'.$command1);
        exec("{$phpPath} {$artisan} {$command1} > /dev/null");
        $this->logwrite('info', 'Alue Batch Done：'.$command1);

        // 2018-10-16 alue統合アプリではタスクを生成するのみで良いのでコメントアウト
        ////$this->logwrite('info', 'Alue Batch Exec：'.$command2);
        ////exec("{$phpPath} {$artisan} {$command2} > /dev/null");
        ////$this->logwrite('info', 'Alue Batch Done：'.$command2);

    }
}
