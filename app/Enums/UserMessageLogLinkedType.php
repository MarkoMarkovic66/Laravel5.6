<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * UserMessageLogLinkedType enum
 */
class UserMessageLogLinkedType extends BaseEnum
{
  const NOT_LINKED = 0;
  const LINKED = 1;
  const REJECT = 2;
}
