<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * InquiryType enum
 */
class InquiryType extends BaseEnum
{
    const INQUIRY_TYPE_1_MSG  = 'システムに関して';
    const INQUIRY_TYPE_1_ID   = '1';

    const INQUIRY_TYPE_2_MSG  = 'アプリの使い方に関して';
    const INQUIRY_TYPE_2_ID   = '2';
    
    const INQUIRY_TYPE_3_MSG  = '学習サービスに関して';
    const INQUIRY_TYPE_3_ID   = '3';

    const INQUIRY_TYPE_4_MSG  = 'ご契約内容に関して';
    const INQUIRY_TYPE_4_ID   = '4';

    const INQUIRY_TYPE_5_MSG  = 'その他';
    const INQUIRY_TYPE_5_ID   = '5';
}