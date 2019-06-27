<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * MemberReport
 */
class MemberReport extends CommonModel
{
    public $table = 'member_reports';
    public $name = '会員レポート管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
