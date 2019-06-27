<?php

namespace App\Dtos;

/**
 * UserSessionStatisticsPerPeriodDto
 * 集計対象7日間のセッション数集計値を管理するクラス
 */
class UserSessionStatisticsPerPeriodDto {

    public $sinceDate;
    public $untilDate;

    public $sessionNum; //セッション数

    function __construct(){
        $this->sessionNum = 0;
    }

}
