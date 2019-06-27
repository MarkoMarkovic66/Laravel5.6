<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class User extends Model
{

    /*
     * リレーション設定
     */
    public function userTasks()
    {
        return $this->hasMany('App\Models\UserTask');
    }

    public function userMessageLogs()
    {
        return $this->hasMany('App\Models\UserMessageLog');
    }

    public function userCounselors()
    {
        return $this->hasMany('App\Models\UserCounselor');
    }

}
