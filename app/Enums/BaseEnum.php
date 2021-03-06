<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

/**
 * ベースとなるEnum
 */
abstract class BaseEnum extends Enum
{
    public static function toCsv()
    {
        $result = '';
        foreach (self::toArray() as $value) {
            $result = $result.$value.',';
        }

        return $result;
    }

    public static function count()
    {
        return count(self::toArray());
    }
}
