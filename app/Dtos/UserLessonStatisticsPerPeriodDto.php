<?php

namespace App\Dtos;

/**
 * UserLessonStatisticsPerPeriodDto
 * 集計対象7日間のレッスン数集計値を管理するクラス
 */
class UserLessonStatisticsPerPeriodDto {

    public $sinceDate;
    public $untilDate;

    public $lessonNum; //レッスン数

    function __construct(){
        $this->lessonNum = 0;
    }

}
