<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * MemberReportGsampleData
 */
class MemberReportGsampleData extends CommonModel
{
    public $table = 'member_report_gsample_datas';
    public $name = '会員レポートg-sampleデータ管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
