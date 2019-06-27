<?php

namespace App\Models;

use App\Models\CommonModel as CommonModel;

/**
 * UserPlan
 */
class UserPlan extends CommonModel
{
    public $table = 'user_plans';
    public $name = '会員プランデータ';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
