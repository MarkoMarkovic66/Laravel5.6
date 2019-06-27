<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * UserLessonProgress
 */
class UserLessonProgress extends CommonModel
{
    public $table = 'user_lesson_progresses';
    public $name = 'レッスン消化数管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
