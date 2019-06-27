<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * UserMessageLogCwPostType enum
 */
class UserMessageLogCwPostType extends BaseEnum
{
    const MESSAGE = 1;
    const TASK = 2;
    const FILE = 3;

    const MESSAGE_NAME   = 'メッセージ';
    const TASK_NAME      = 'タスク';
    const FILE_NAME      = '音声ファイル';

}
