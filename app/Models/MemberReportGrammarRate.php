<?php
namespace App\Models;

use App\Models\CommonModel;

/**
 * MemberReportGrammarRate
 */
class MemberReportGrammarRate extends CommonModel
{
    public $table = 'member_report_grammar_rates';
    public $name = '会員レポートGrammarRateデータ管理';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
