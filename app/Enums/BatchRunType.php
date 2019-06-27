<?php
namespace App\Enums;
use App\Enums\BaseEnum;

/**
 * BatchRunType
 * 起動区分 1:自動起動 2:手動起動
 */
class BatchRunType extends BaseEnum {
    const BATCH_RUN_AUTO    = '1'; //1:自動起動
    const BATCH_RUN_MANUAL  = '2'; //2:手動起動

    const BATCH_RUN_AUTO_TEXT    = '自動起動'; //1:自動起動
    const BATCH_RUN_MANUAL_TEXT  = '手動起動'; //2:手動起動
}
