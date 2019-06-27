<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * AccountKindType enum
 */
class AccountKindType extends BaseEnum
{
    //アカウント種別
    const ACCOUNT_MANAGER  = 'manager';
    const ACCOUNT_SYSTEM_OPERATOR  = 'system_operator';
    const ACCOUNT_COUNSELOR  = 'counselor';
    const ACCOUNT_OPERATOR  = 'operator';

    const ACCOUNT_MANAGER_ID  = '1';
    const ACCOUNT_SYSTEM_OPERATOR_ID  = '2';
    const ACCOUNT_COUNSELOR_ID  = '3';
    const ACCOUNT_OPERATOR_ID  = '4';

}
