<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * LessonType
 */
class LessonType extends BaseEnum {
    const LESSON                = '1';
    const ASSESSMENT            = '2';
    const ASSESSMENT_FEEDBACK   = '3';

    const COUNSELING            = '5';

    const PRE_ASSESSMENT        = '101';
    const DEMO_LESSON           = '102';

}
