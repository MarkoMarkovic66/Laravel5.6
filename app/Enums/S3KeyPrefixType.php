<?php

namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * S3KeyPrefixType enum
 */
class S3KeyPrefixType extends BaseEnum
{
    const S3KEY_PREFIX_MEMBER_REPORT_CHART  = 'member_report/chart';
    const S3KEY_PREFIX_MEMBER_REPORT_PDF    = 'member_report/pdf';
}

