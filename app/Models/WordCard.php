<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class WordCard extends CommonModel
{
    public $table = 'word_cards';
    public $name = '単語カード';
    public $primary = 'id';

    /*
     * リレーション設定
     */
    public $columns = array(
        'id' => 'ID',
        'word_card_category_id' => '単語カードカテゴリ',
        'word' => '単語設問',
        'answer' => '回答',
        //'is_deleted' => '',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

    public function category() {
        return $this->belongsTo('App\Models\WordCardCategory', 'word_card_category_id');
    }
    /*
     * データ全件取得
     */
    public function getAll($offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {
        $result = CommonModel::getAll($offset, $limit, $orderParam, $orderAsc);
        return $result;
    }

    /*
     * データ１件取得
     */
    public function getOne($id)
    {
        $result = CommonModel::getOne($id);
        return $result;
    }

    /*
     * データ一覧取得
     */
    public function getList($params, $offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc', $operators = null)
    {
        $result = CommonModel::getList($params, $offset, $limit, $orderParam, $orderAsc, $operators);
        return $result;
    }

    /*
     * データ一覧検索
     */
    public function search($search_params, $search_word, $search_date_since, $search_date_until, $offset = null, $limit = null, $orderParam = null, $orderAsc = 'desc')
    {

        $result = DB::table($this->table);

        $result = $result->leftJoin('word_card_categories', 'word_card_categories.id', '=', 'word_cards.word_card_category_id');

        $result = $result->select('word_cards.id as id', 'word_cards.word_card_category_id', 'word_cards.word', 'word_cards.answer', 'word_card_categories.category', 'word_cards.created_at', 'word_cards.updated_at');
        $result = $result->where('word_cards.is_deleted', 0);

        //各検索項目による検索
        if(isset($search_params['word_card_category_id'])) {
            $result = $result->where('word_card_categories.category', 'like', "%{$search_params['word_card_category_id']}%");
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('word_cards.word', 'like', '%'.$search_word.'%')
                    ->orWhere('word_cards.answer', 'like', '%'.$search_word.'%');
            });
        }

        if ($search_date_since !='' && $search_date_since != null){
            $result = $result->wheredate('word_cards.created_at', '>=', $search_date_since);
        }

        if ($search_date_until !='' && $search_date_until != null){
            $result = $result->wheredate('word_cards.created_at', '<=', $search_date_until);
        }

        if($offset != null) {
            $result = $result->offset($offset);
        }
        if($limit != null) {
            $result = $result->limit($limit);
        }
        if($orderParam != null) {
            $result = $result->orderBy($orderParam, $orderAsc);
        } else {
            $result = $result->orderBy('word_cards.id', 'asc');
        }
        $result = $result->get();

        return $result;
    }

    /*
     * データ更新
     */
    public function updateData($params, $data)
    {
        $result = CommonModel::updateData($params, $data);
        return $result;
    }

    /*
     * データ論理削除
     */
    public function deleteData($id)
    {
        $result = CommonModel::deleteData($id);
        return $result;
    }

    /*
     * データ登録
     */
    public function setData($data)
    {
        $insert_data = array();
        foreach($data as $key => $param) {
            $insert_data[] = array(
                'word_card_category_id' => $param['word_card_category_id'],
                'word' => $param['word'],
                'answer' => $param['answer'],                
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}

