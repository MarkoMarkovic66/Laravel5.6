<?php
namespace App\Traits;

use App\Models\BatchLog;
use Illuminate\Support\Carbon;
use App\Enums\IsDeletedType;

/**
 * AlueCommandBaseTrait
 */
Trait AlueCommandBaseTrait {

    /**
     * バッチ処理の実行情報を登録
     */
    private function registRunningInfo($batchName, $runType, $logType, $runStatus, $resultMessage=null){
        try {
            $batchLog = new BatchLog();
            $batchLog->batch_name = $batchName;
            $batchLog->run_type = $runType;
            $batchLog->log_type = $logType;
            $batchLog->status = $runStatus;
            $batchLog->event_at = Carbon::now();
            $batchLog->result_message = $resultMessage;
            $batchLog->is_deleted = IsDeletedType::ACTIVE;
            $batchLog->created_at = Carbon::now();
            $batchLog->save();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

}
