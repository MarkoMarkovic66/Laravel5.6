<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * MemberReportChart
 */
class MemberReportChart extends CommonModel
{
    public $table = 'member_report_charts';
    public $name = '会員レポートグラフ画像管理';
    public $primary = 'id';

}
