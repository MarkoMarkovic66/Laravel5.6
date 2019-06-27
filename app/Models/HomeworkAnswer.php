<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkAnswer extends Model
{
    public $table = 'homework_answers';
    public $name = '宿題回答管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
