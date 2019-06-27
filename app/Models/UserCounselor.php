<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class UserCounselor extends CommonModel
{
    public $table = 'user_counselors';
    public $name = '担当カウンセラー管理';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => '会員ID',
        'account_id' => '担当カウンセラーアカウントID',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

    protected $guarded = ['id', 'created_at'];

    /*
     * リレーション設定
     */
    public function users()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

}
