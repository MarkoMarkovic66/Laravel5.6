<?php

namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * UserTaskStatusType enum
 */
class UserTaskStatusType extends BaseEnum
{
    //出題回答ステータス
    const TASK_STATUS_VAL_PUSH_ERROR        = '-1';// -1: 出題投稿エラー
    const TASK_STATUS_VAL_NONE              = '0'; // 0: 未出題
    const TASK_STATUS_VAL_ASKED             = '1'; // 1: 出題済み
    const TASK_STATUS_VAL_ANSWERED          = '2'; // 2: ユーザ回答済み
    const TASK_STATUS_VAL_ANSWER_REJECT     = '3'; // 3: ユーザ回答差し戻し
    const TASK_STATUS_VAL_NOT_ANSWERED      = '4'; // 4: 未提出（宿題がタスク期日までに終わっていない場合のステータス）
    const TASK_STATUS_VAL_FEEDBACKED        = '5'; // 5: フィードバック済み
    const TASK_STATUS_VAL_FEEDBACK_REJECT   = '6'; // 6: フィードバック差し戻し（運用担当がカウンセラーの添削に対して差し戻し））
    const TASK_STATUS_VAL_RESPONSED         = '7'; // 7: フィードバック完了

    /*
     * 2018-07-07
     * 全てのタスク（口頭G、口頭V、復習、SR、その他タスク）において
     * Chatwork「完了ボタン」押下により「完了」となった状態を示すステータス
     */
    const TASK_STATUS_VAL_COMPLETE          = '8';

    /*
     * 2018-07-07
     * user_tasks.is_completed への設定値 ( 1:完了  0:未完了 )
     */
    const TASK_COMPLETED                    = '1';
    const TASK_INCOMPLETED                  = '0';


    //各ステータスの名称定義
    const TASK_STATUS_NAME_PUSH_ERROR       = '出題投稿エラー';
    const TASK_STATUS_NAME_NONE             = '未出題';
    const TASK_STATUS_NAME_ASKED            = '出題済み';
    const TASK_STATUS_NAME_ANSWERED         = 'ユーザ回答済み';
    const TASK_STATUS_NAME_ANSWER_REJECT    = 'ユーザ回答差し戻し';
    const TASK_STATUS_NAME_NOT_ANSWERED     = '未提出';
    const TASK_STATUS_NAME_FEEDBACKED       = 'フィードバック済み';
    const TASK_STATUS_NAME_FEEDBACK_REJECT  = 'フィードバック差し戻し';
    const TASK_STATUS_NAME_RESPONSED        = 'フィードバック完了';


    //2018-12-15 AlueInteg用に修正
    public static function getStatusList(){
        return [
            //self::TASK_STATUS_VAL_NONE           => self::TASK_STATUS_NAME_NONE, //未出題は除外する
            self::TASK_STATUS_VAL_ASKED            => self::TASK_STATUS_NAME_ASKED,
            self::TASK_STATUS_VAL_ANSWERED         => self::TASK_STATUS_NAME_ANSWERED,
            self::TASK_STATUS_VAL_FEEDBACKED       => self::TASK_STATUS_NAME_FEEDBACKED,
            self::TASK_STATUS_VAL_FEEDBACK_REJECT  => self::TASK_STATUS_NAME_FEEDBACK_REJECT,
            self::TASK_STATUS_VAL_RESPONSED        => self::TASK_STATUS_NAME_RESPONSED,
        ];
    }

}

