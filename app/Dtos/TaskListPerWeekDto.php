<?php
namespace App\Dtos;

/**
 * 一週間の宿題リスト
 * 一週間単位の出題を管理するクラス
 *
 * 一週間(7日間)の出題を管理する
 */
class TaskListPerWeekDto {

    public $userId;

    //1週間の出題リスト(※1日単位の出題リストの配列となる)
    //配列のキーは曜日番号 ( 1:Mon 2:Tue 3:Wed ... )
    public $taskList; //list<TaskListPerDayDto>

    function __construct(){
        $this->userId = 0;
        $this->taskList = [];
    }

    /**
     * 指定した曜日、指定したコマ番号の設問情報を取得する
     * @param type $dayNumber
     * @param type $cellNumber
     */
    public function getTargetTaskCell($dayNumber, $cellNumber){

        if($dayNumber < 1 || $dayNumber > 7){
            return false;
        }
        if($cellNumber < 1 || $cellNumber > 5){
            return false;
        }

        foreach($this->taskList as $taskListPerDayDto){
            if($taskListPerDayDto->dayNumber == $dayNumber){
                foreach($taskListPerDayDto->taskList as $taskListPerCellDto){
                    if($taskListPerCellDto->cellNumber == $cellNumber){
                        return $taskListPerCellDto;
                    }
                }
            }
        }
        return false;
    }


}
