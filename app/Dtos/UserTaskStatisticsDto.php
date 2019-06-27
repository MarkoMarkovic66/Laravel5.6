<?php

namespace App\Dtos;

/**
 * UserTaskStatisticsDto
 * 会員毎のタスク集計情報を管理するクラス
 */
class UserTaskStatisticsDto {

    public $userId;
    public $alugoUserId;

    public $statSinceDate;
    public $statUntilDate;

    public $statDatePeriods; //集計期間日付け情報 Array<[since, until]>

    public $statTaskResults;     //タスク集計結果 Array
    public $statSessionResults;  //セッシヨン集計結果 Array
    public $statLessonResults;   //レッスン集計結果 Array

    function __construct(){
        $this->statDatePeriods = [];
        $this->statTaskResults = [];
        $this->statSessionResults = [];
        $this->statLessonResults = [];
    }

}
