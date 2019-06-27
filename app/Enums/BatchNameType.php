<?php
namespace App\Enums;
use App\Enums\BaseEnum;

/**
 * BatchNameType
 */
class BatchNameType extends BaseEnum {
    const BATCH_NAME_DATA_IMPORT         = 'AlueBatchCommand:importData';
    const BATCH_NAME_DATA_IMPORT_TEXT    = 'Alugoデータ取得バッチ';

    const BATCH_NAME_CREATE_GV         = 'AlueBatchCommand:createGVTask';
    const BATCH_NAME_CREATE_GV_TEXT    = 'Grammar/Vocabulary問題作成バッチ';

    const BATCH_NAME_QUESTION_ALGORITHM      = 'AlugoBatchCommand::QuestionAlgorithm';
    const BATCH_NAME_QUESTION_ALGORITHM_TEXT = 'Python:出題アルゴリズム';

    const BATCH_NAME_CREATE_QUESTION         = 'AlueBatchCommand:createQuestion';
    const BATCH_NAME_CREATE_QUESTION_TEXT    = 'SR/復習/その他問題作成バッチ';

    const BATCH_NAME_CREATE_TASK         = 'AlueBatchCommand:createTask';
    const BATCH_NAME_CREATE_TASK_TEXT    = '宿題タスク投稿内容作成バッチ';

    const BATCH_NAME_CW_PULL         = 'AlueBatchCommand:pullCwData';
    const BATCH_NAME_CW_PULL_TEXT    = 'Chatwork情報取得バッチ';

    const BATCH_NAME_CW_PUSH         = 'AlueBatchCommand:pushCwData';
    const BATCH_NAME_CW_PUSH_TEXT    = 'Chatwork一括投稿バッチ';

    const BATCH_NAME_TASK_RESET         = 'AlueBatchCommand:resetTask';
    const BATCH_NAME_TASK_RESET_TEXT    = '宿題タスクリセットバッチ';

    const BATCH_NAME_CREATE_MEMBER_REPORT       = 'AlueBatchCommand:createMemberReport';
    const BATCH_NAME_CREATE_MEMBER_REPORT_TEXT  = '会員レポート作成バッチ';

    const BATCH_NAME_PUSH_MEMBER_REPORT       = 'AlueBatchCommand:pushMemberReport';
    const BATCH_NAME_PUSH_MEMBER_REPORT_TEXT  = '会員レポート投稿バッチ';

    const BATCH_NAME_ACHIEVEMENT_COUNT       = 'AlueBatchCommand:achievementCount';
    const BATCH_NAME_ACHIEVEMENT_COUNT_TEXT  = '宿題実施状況集計バッチ';

}
