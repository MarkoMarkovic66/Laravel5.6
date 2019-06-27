<?php

namespace App\Dtos;

/**
 * UserTaskStatisticsPerPeriodDto
 * 集計対象7日間の集計値を管理するクラス
 */
class UserTaskStatisticsPerPeriodDto {

    public $sinceDate;
    public $untilDate;

    public $statResults; //Array

    function __construct(){
        $this->statResults = [];
    }

}
