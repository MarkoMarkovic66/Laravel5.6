<?php
namespace App\Enums;
use App\Enums\BaseEnum;

/**
 * BatchLogType
 * ログ区分 1:開始ログ 2:終了ログ
 */
class BatchLogType extends BaseEnum {
    const BATCH_LOG_START    = '1'; //開始ログ
    const BATCH_LOG_FINISH   = '2'; //終了ログ

    const BATCH_LOG_START_TEXT    = '開始'; //開始ログ
    const BATCH_LOG_FINISH_TEXT   = '終了'; //終了ログ

}
