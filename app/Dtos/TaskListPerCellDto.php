<?php
namespace App\Dtos;

use App\Enums\IsDeletedType;
use App\Models\OtherTask;

/**
 * 一コマ単位の宿題情報
 * 一コマ単位の出題を管理するクラス
 *
 * 同一のジャンルについて、複数の設問がありうる
 */
class TaskListPerCellDto {

    public $cellNumber; // コマ番号( 1, 2, ... 5 )

    public $taskType;       //出題ジャンル
    public $taskTypeName;   //出題ジャンル名

    //1コマ単位の出題リスト(※タスク単位の配列となる)
    public $taskList; //list<TaskListQuestionDto>
    public $taskCount;

    private $taskCategories;

    function __construct(){
        $this->cellNumber = 0;
        $this->taskType = 0;
        $this->taskTypeName = '';
        $this->taskCount = 0;
        $this->taskList = [];

        $this->taskCategories = [
            '0' => 'なし',
            '1' => '瞬間口頭G',
            '2' => '瞬間口頭V',
            '3' => 'Speaking Rally',
            //'4' => '復習',
            //'30' => 'レッスン復習',
            '40' => '単語カード',
            '50' => 'LISTENING B',
            '51' => 'LISTENING C',
            '52' => 'LISTENING D',
        ];
        /*** alue-integ では「その他タスク」は使用しない
        $otherTasks = OtherTask::where('is_deleted', IsDeletedType::ACTIVE)
                                ->where('task_type_id', '>=', 5)
                                ->orderby('task_type_id')
                                ->get();
        foreach($otherTasks as $otherTask){
            $this->taskCategories[$otherTask->task_type_id] = $otherTask->other_task_name;
        }
        ***/
    }

    public function getTaskTypeName($taskType){
        return $this->taskCategories[$taskType];
    }
    public function setTaskTypeName(){
        $this->taskTypeName = $this->taskCategories[$this->taskType];
    }
    public function setTaskCount(){
        $this->taskCount = count($this->taskList);
    }

    public function getTaskCategories(){
        return $this->taskCategories;
    }

}
