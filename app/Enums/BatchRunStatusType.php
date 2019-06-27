<?php
namespace App\Enums;
use App\Enums\BaseEnum;

/**
 * BatchRunStatusType
 * 処理ステータス 1:正常  2:異常
 */
class BatchRunStatusType extends BaseEnum {
    const BATCH_RUN_STATUS_OK    = '1';
    const BATCH_RUN_STATUS_FAIL  = '2';

    const BATCH_RUN_STATUS_OK_TEXT    = '正常';
    const BATCH_RUN_STATUS_FAIL_TEXT  = '異常';
}
