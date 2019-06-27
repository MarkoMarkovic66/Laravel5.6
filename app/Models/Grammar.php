<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModel as CommonModel;

class Grammar extends CommonModel
{
    public $table = 'grammars';
    public $name = '瞬間口頭G';
    public $primary = 'id';
    public $columns = array(
        'id' => 'ID',
        'grammar_code' => '文法コード',
        'module' => 'モジュール名',
        'grade' => 'グレード',
        'stage' => 'ステージ',
        'seq_number' => 'シーケンス番号',
        'focus' => '注目単語',
        'tips' => 'TIPS',
        'question' => '質問',
        'answer' => '回答',
        'branch' => '部門',
        'created_at' => '作成日',
        'updated_at' => '最終更新日',
        'deleted_at' => '削除日',
    );

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
        $result = $result->where('is_deleted', 0);

        //各検索項目による検索
        if(isset($search_params['grammar_code'])) {
            $result = $result->where('grammar_code', 'like', '%'.$search_params['grammar_code'].'%');
        }
//        if(isset($search_params['module'])) {
//            $result = $result->where('module', '=', $search_params['module']);
//        }
        if(isset($search_params['grade'])) {
            $result = $result->where('grade', 'like', '%'.$search_params['grade'].'%');
        }
        if(isset($search_params['stage'])) {
            $result = $result->where('stage', '=', $search_params['stage']);
        }
        if(isset($search_params['seq_number'])) {
            $result = $result->where('seq_number', 'like', '%'.$search_params['seq_number'].'%');
        }
        if(isset($search_params['focus'])) {
            $result = $result->where('focus', 'like', '%'.$search_params['focus'].'%');
        }
        if(isset($search_params['tips'])) {
            $result = $result->where('tips', 'like', '%'.$search_params['tips'].'%');
        }
        if(isset($search_params['branch'])) {
            $result = $result->where('branch', 'like', '%'.$search_params['branch'].'%');
        }
        if(isset($search_params['answer'])) {
            $result = $result->where('answer', 'like', '%'.$search_params['answer'].'%');
        }

        //フリーワード検索
        if($search_word != '' && $search_word != null) {

            $result = $result->where(function ($query) use($search_word) {
                $query
                    ->orWhere('grammar_code', 'like', '%'.$search_word.'%')
                    ->orWhere('module', 'like', '%'.$search_word.'%')
                    ->orWhere('grade', 'like', '%'.$search_word.'%')
                    ->orWhere('focus', 'like', '%'.$search_word.'%')
                    ->orWhere('tips', 'like', '%'.$search_word.'%')
                    ->orWhere('question', 'like', '%'.$search_word.'%')
                    ->orWhere('answer', 'like', '%'.$search_word.'%')
                    ->orWhere('branch', 'like', '%'.$search_word.'%');
            });
        }

        
        if ($search_date_since !='' && $search_date_since != null){
            $result = $result->wheredate('created_at', '>=', $search_date_since);
        }

        if ($search_date_until !='' && $search_date_until != null){
            $result = $result->wheredate('created_at', '<=', $search_date_until);
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
            $result = $result->orderBy('id', 'asc');
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
                'grammar_code' => $param['grammar_code'],
                'module' => $param['module'],
                'grade' => $param['grade'],
                'stage' => $param['stage'],
                'seq_number' => $param['seq_number'],
                'focus' => $param['focus'],
                'tips' => $param['tips'],
                'question' => $param['question'],
                'answer' => $param['answer'],
                'branch' => $param['branch'],
                'created_at' => date('Y-m-d H:i:s')
            );
        }

        $result = DB::table($this->table)->insert($insert_data);

        //最後のauto increment IDを取得
        $last_id = DB::getPdo()->lastInsertId();

        return $last_id;
    }
}
