<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * UserTaskProgress
 */
class UserTaskProgress extends CommonModel
{
    public $table = 'user_task_progresses';
    public $name = '出題タスク消化進捗管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
