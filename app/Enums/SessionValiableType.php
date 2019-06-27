<?php

namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * SessionValiableType enum
 */
class SessionValiableType extends BaseEnum
{
    // ログインユーザ情報
    const LOGIN_ACCOUNT_INFO  = 'loginAccountInfo';

    // 指定の会員基本情報
    const MEMBER_BASIC_INFO   = 'memberBasicInfo';
    // 指定の会員詳細情報
    const MEMBER_DETAIL_INFO  = 'memberDetailInfo';
    
    // 検索条件情報：会員管理
    const SEARCH_CONDITION_MEMBER  = 'searchConditionMember';
    // 検索条件情報：宿題管理
    const SEARCH_CONDITION_TASK  = 'searchConditionTask';

}
