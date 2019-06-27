<?php

namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * MenuNameType enum
 */
class MenuNameType extends BaseEnum
{
    //機能メニュー名
    const MENU_TOP  = 'top';
    const MENU_TASK  = 'task';
    const MENU_MEMBER  = 'member';
    const MENU_CHATWORK  = 'chatwork';
    const MENU_MASTER  = 'master';
    const MENU_ACCOUNT  = 'account';
    const MENU_BATCH  = 'batch';
    const MENU_COACH  = 'coach';
    const MENU_REVIEW_LESSON  = 'review_lessons';
    const MENU_REVIEW_COUNTSELORS  = 'review_counselors';
    const MENU_REVIEW_SERVICES  = 'review_services';
    const MENU_PLAN  = 'plan';

}

