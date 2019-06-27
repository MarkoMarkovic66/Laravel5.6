<?php
namespace App\Services\AlueCommandService;

use App\Console\Commands\AlueCommand\Traits\AlueCommandLogTrait;
use DB;

use App\Enums\IsDeletedType;
use App\Enums\LessonType;
use App\Enums\TaskType;
use App\Enums\UserTaskStatusType;
use App\Enums\TaskSelectedType;

use App\Models\Lesson;
use App\Models\Counseling;

use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use App\Models\UserTaskPeriod;
use App\Models\EnqueteTask;
use App\Models\RvTask;
use App\Models\HomeworkAnswer;

use Carbon\Carbon;

use App\Utils\DateUtils;
use AlueIntegCreateQuestionService;
use App\Services\SrService;


/**
 * AlueIntegNotifyManageService
 * メッセージ通知管理サービス
 */
class AlueIntegNotifyManageService {

    use AlueCommandLogTrait;

    protected $commandLogger;
    protected $signature;
    protected $commandName;


    public function __construct()
    {
        $this->signature = 'AlueIntegNotifyManageService';
        $this->commandName = 'AlueIntegNotifyManageService';
        $this->initAlueCommandLogTrait('console/notifyManage.log');
    }

    /**
     * Main
     */
    public function exec(&$resultMessage){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');

        DB::beginTransaction();
        try {

            //ここに処理ロジックを実装
            $this->sample(); //sample
            // ・・・


            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            $resultMessage = $ex->getMessage();
            $this->logwrite('error', $ex->getMessage());
            return false;
        }

        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
        return true;
    }

    /**
     * sample
     */
    private function sample(){
        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' start');


        $this->logwrite('info', __CLASS__.':'.__FUNCTION__.' finish');
    }


}

