<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * UserMessageLogMessageType enum
 */
class UserMessageLogMessageType extends BaseEnum
{
  const FEEDBACK_MAIL = 1;
  const SPEAKING_RALLY = 2;
  const CHATWORK = 3;

  const FEEDBACK_MAIL_NAME = 'Feedback Mail';
  const SPEAKING_RALLY_NAME = 'Speaking Rally';
  const CHATWORK_NAME = 'チャットワーク';

}
