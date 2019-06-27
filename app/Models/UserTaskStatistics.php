<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * UserTaskStatistics
 */
class UserTaskStatistics extends CommonModel
{
    public $table = 'user_task_statistics';
    public $name = '出題タスク統計管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
