<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UserTaskCalendar
 */
class UserTaskCalendar extends Model
{
    public $table = 'user_task_calendars';
    public $name = '出題カレンダー管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
