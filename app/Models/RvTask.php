<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RvTask extends Model
{
    public $table = 'rv_tasks';
    public $name = '復習タスク管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
