<?php

namespace App\Console\Commands\AlueCommand;

use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use App\Enums\BatchRunType;

class AlueNotifyManageCommand extends Command
{
    use AlueCommandLogTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AlueCommand:notifyManage {runType=null}';
    protected $commandName = 'AlueCommand:notifyManage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parental Command Of Notify Manage';

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
        $this->initAlueCommandLogTrait('console/notifyManage.log');
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
        $command = "AlueBatchCommand:notifyManage " . $runType;

        $this->logwrite('info', 'Alue Batch Exec：'.$command);
        exec("{$phpPath} {$artisan} {$command} > /dev/null");
        $this->logwrite('info', 'Alue Batch Done：'.$command);

    }
}
