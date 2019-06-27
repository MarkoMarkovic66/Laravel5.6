<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * ChatworkMessageLogRegistType enum
 * 取得したチャットワークメッセージの 新規/既存 の区分
 */
class UserMessageLogRegistType extends BaseEnum {
    const MESSGAE_LOG_NEW      = 'new';         //未取得(未登録)
    const MESSGAE_LOG_REGISTED = 'registed';    //取得済み(登録済み)
}
