<?php
namespace App\Dtos;

/**
 * 1日単位の宿題リスト
 * 1日単位の出題を管理するクラス
 *
 * 1日には最大5コマの出題がありうる
 */
class TaskListPerDayDto {

    public $dayNumber; // 曜日番号( 1:Mon 2:Tue 3:Wed ... )

    //1日単位の出題リスト(※1コマ単位の出題リストの配列となる)
    //配列のキーはコマ番号 ( 1, 2, ... 5 )
    public $taskList; //list<TaskListPerCellDto>

    public $cellCount; //当該曜日内にある出題枠数
    public $taskCount; //当該曜日内にある全タスク数

    function __construct(){
        $this->dayNumber = 0;
        $this->cellCount = 0;
        $this->taskCount = 0;
        $this->taskList = [];
    }

    public function setTaskCount(){
        $this->cellCount = count($this->taskList);

        $taskCount = 0;
        foreach($this->taskList as $eachCell){
            $taskCount += $eachCell->taskCount;
        }
        $this->taskCount = $taskCount;
    }

}

