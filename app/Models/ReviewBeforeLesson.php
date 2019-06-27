<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewBeforeLesson extends Model
{
    public $table = 'review_before_lessons';
    public $name = 'レビュー管理：受講前アンケート';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
