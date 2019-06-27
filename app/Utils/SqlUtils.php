<?php

namespace App\Utils;

use Log;

class SqlUtils
{
    const HALF_SPACE = ' ';
    const PIPE_LINE = '|';

    /**
     * Like用のエスケープ後の文字列を返却する
     * @param $value
     * @return エスケープ後の文字列
    */
    public static function likeEscape($value)
    {
        $value = str_replace(['%', '_'], ['\%', '\_'], $value);
        return $value;
    }


}
