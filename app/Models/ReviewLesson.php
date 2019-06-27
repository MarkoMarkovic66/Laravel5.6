<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class ReviewLesson extends Model
{
    public $table = 'review_lessons';
    public $name = 'レビュー管理：レッスンレビュー';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'user_id' => 'ID',
        'alugo_user_id' => 'alugo会員ID',
        'lesson_id' => '対象レッスンID',
        'eval' => 'レッスン評価値	',
        'eval_factor' => '評価要因',
        'comment' => 'フリーコメント	',
        'is_deleted' => '',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
    );
    /*
     * リレーション設定
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /*
     * データ一覧検索
     */
    public function search($search_params, $search_word, $search_date_since, $search_date_until, $offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {

        $result = DB::table($this->table);
        $result = $result->where('is_deleted', 0);

        if($offset != null) {
            $result = $result->offset($offset);
        }
        if($limit != null) {
            $result = $result->limit($limit);
        }
        if($orderParam != null) {
            $result = $result->orderBy($orderParam, $orderAsc);
        } else {
            $result = $result->orderBy('id', 'asc');
        }

        if ($search_date_since !='' && $search_date_since != null){
            $result = $result->wheredate('created_at', '>=', $search_date_since);
        }

        if ($search_date_until !='' && $search_date_until != null){
            $result = $result->wheredate('created_at', '<=', $search_date_until);
        }

        $result = $result->get();
        return $result;
    }

}
